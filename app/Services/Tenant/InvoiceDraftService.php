<?php

namespace App\Services\Tenant;

use App\Models\Tenant\BusinessProfile;
use App\Models\Tenant\Customer;
use App\Models\Tenant\InvoiceDraft;
use App\Models\Tenant\InvoiceTemplate;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class InvoiceDraftService
{
    public function __construct(
        private readonly InvoiceCalculator $calculator,
        private readonly InvoiceNumberGenerator $numbers,
        private readonly InvoiceSnapshotFactory $snapshots,
        private readonly InvoiceHistoryRecorder $history,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): InvoiceDraft
    {
        $customer = Customer::query()->findOrFail($data['customer_id']);
        $profile = BusinessProfile::query()->where('onboarding_completed', true)->firstOrFail();
        $calculation = $this->calculate($data);

        return DB::transaction(function () use ($data, $user, $customer, $profile, $calculation): InvoiceDraft {
            $templateId = $data['invoice_template_id']
                ?? InvoiceTemplate::query()->where('is_active', true)->orderBy('template_number')->value('id');

            $invoice = InvoiceDraft::query()->create([
                'uuid' => (string) Str::uuid(),
                'number' => $this->numbers->next(),
                'customer_id' => $customer->id,
                'invoice_template_id' => $templateId,
                'created_by_user_id' => $user->id,
                'updated_by_user_id' => $user->id,
                'status' => 'draft',
                'locale' => $data['locale'] ?? $profile->default_locale,
                'currency' => $data['currency'] ?? $profile->default_currency ?? 'AFN',
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'customer_snapshot' => $this->snapshots->customer($customer),
                'company_snapshot' => $this->snapshots->company($profile),
                'subtotal' => $calculation['subtotal'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? 0,
                'discount_amount' => $calculation['discount_amount'],
                'tax_label' => $data['tax_label'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'tax_amount' => $calculation['tax_amount'],
                'additional_charge_label' => $data['additional_charge_label'] ?? null,
                'additional_charge_amount' => $calculation['additional_charge_amount'],
                'total' => $calculation['total'],
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'version_no' => 1,
            ]);

            $invoice->lines()->createMany($calculation['lines']);
            $invoice->load('lines');

            $this->history->version($invoice, $user, 'created');
            $this->history->activity($invoice, $user, 'created');

            return $invoice->fresh(['customer', 'template', 'lines', 'versions', 'activity', 'exports']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(InvoiceDraft $invoice, array $data, User $user): InvoiceDraft
    {
        if ($invoice->status !== 'draft') {
            throw new LogicException('Only draft invoices can be edited.');
        }

        $customer = Customer::query()->findOrFail($data['customer_id'] ?? $invoice->customer_id);
        $profile = BusinessProfile::query()->where('onboarding_completed', true)->firstOrFail();
        $lines = $data['lines'] ?? $invoice->lines()->orderBy('position')->get()->map(fn ($line) => [
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit' => $line->unit,
            'unit_price' => $line->unit_price,
            'discount_percent' => $line->discount_percent,
            'meta' => $line->meta,
        ])->all();

        $effective = [
            ...$invoice->only([
                'discount_type',
                'discount_value',
                'additional_charge_amount',
                'tax_rate',
            ]),
            ...$data,
            'lines' => $lines,
        ];

        $calculation = $this->calculate($effective);

        return DB::transaction(function () use (
            $invoice,
            $data,
            $user,
            $customer,
            $profile,
            $calculation,
            $effective,
        ): InvoiceDraft {
            $invoice->update([
                'customer_id' => $customer->id,
                'invoice_template_id' => $data['invoice_template_id'] ?? $invoice->invoice_template_id,
                'updated_by_user_id' => $user->id,
                'locale' => $data['locale'] ?? $invoice->locale,
                'currency' => $data['currency'] ?? $invoice->currency,
                'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $invoice->due_date,
                'customer_snapshot' => $this->snapshots->customer($customer),
                'company_snapshot' => $this->snapshots->company($profile),
                'subtotal' => $calculation['subtotal'],
                'discount_type' => $effective['discount_type'] ?? null,
                'discount_value' => $effective['discount_value'] ?? 0,
                'discount_amount' => $calculation['discount_amount'],
                'tax_label' => array_key_exists('tax_label', $data) ? $data['tax_label'] : $invoice->tax_label,
                'tax_rate' => $effective['tax_rate'] ?? 0,
                'tax_amount' => $calculation['tax_amount'],
                'additional_charge_label' => array_key_exists('additional_charge_label', $data)
                    ? $data['additional_charge_label']
                    : $invoice->additional_charge_label,
                'additional_charge_amount' => $calculation['additional_charge_amount'],
                'total' => $calculation['total'],
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $invoice->notes,
                'terms' => array_key_exists('terms', $data) ? $data['terms'] : $invoice->terms,
                'version_no' => $invoice->version_no + 1,
            ]);

            $invoice->lines()->delete();
            $invoice->lines()->createMany($calculation['lines']);
            $invoice->load('lines');

            $this->history->version($invoice, $user, 'updated');
            $this->history->activity($invoice, $user, 'updated', ['version' => $invoice->version_no]);

            return $invoice->fresh(['customer', 'template', 'lines', 'versions', 'activity', 'exports']);
        });
    }

    public function duplicate(InvoiceDraft $source, User $user): InvoiceDraft
    {
        $source->load('lines');

        return DB::transaction(function () use ($source, $user): InvoiceDraft {
            $copy = InvoiceDraft::query()->create([
                'uuid' => (string) Str::uuid(),
                'number' => $this->numbers->next(),
                'customer_id' => $source->customer_id,
                'invoice_template_id' => $source->invoice_template_id,
                'created_by_user_id' => $user->id,
                'updated_by_user_id' => $user->id,
                'duplicated_from_id' => $source->id,
                'status' => 'draft',
                'locale' => $source->locale,
                'currency' => $source->currency,
                'issue_date' => now()->toDateString(),
                'due_date' => null,
                'customer_snapshot' => $source->customer_snapshot,
                'company_snapshot' => $source->company_snapshot,
                'subtotal' => $source->subtotal,
                'discount_type' => $source->discount_type,
                'discount_value' => $source->discount_value,
                'discount_amount' => $source->discount_amount,
                'tax_label' => $source->tax_label,
                'tax_rate' => $source->tax_rate,
                'tax_amount' => $source->tax_amount,
                'additional_charge_label' => $source->additional_charge_label,
                'additional_charge_amount' => $source->additional_charge_amount,
                'total' => $source->total,
                'notes' => $source->notes,
                'terms' => $source->terms,
                'version_no' => 1,
            ]);

            $copy->lines()->createMany($source->lines->map(fn ($line) => [
                'position' => $line->position,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit' => $line->unit,
                'unit_price' => $line->unit_price,
                'discount_percent' => $line->discount_percent,
                'line_subtotal' => $line->line_subtotal,
                'line_discount_amount' => $line->line_discount_amount,
                'line_total' => $line->line_total,
                'meta' => $line->meta,
            ])->all());

            $copy->load('lines');
            $this->history->version($copy, $user, 'duplicated');
            $this->history->activity($copy, $user, 'duplicated', ['source_invoice_id' => $source->id]);
            $this->history->activity($source, $user, 'duplicated_to', ['invoice_id' => $copy->id]);

            return $copy->fresh(['customer', 'template', 'lines', 'versions', 'activity', 'exports']);
        });
    }

    public function delete(InvoiceDraft $invoice, User $user): void
    {
        if ($invoice->status !== 'draft') {
            throw new LogicException('Only draft invoices can be deleted.');
        }

        DB::transaction(function () use ($invoice, $user): void {
            $this->history->activity($invoice, $user, 'deleted', [
                'number' => $invoice->number,
                'total' => $invoice->total,
            ]);

            $invoice->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function calculate(array $data): array
    {
        return $this->calculator->calculate(
            $data['lines'],
            $data['discount_type'] ?? null,
            $data['discount_value'] ?? null,
            $data['additional_charge_amount'] ?? null,
            $data['tax_rate'] ?? null,
        );
    }
}
