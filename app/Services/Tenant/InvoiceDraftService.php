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
        $calculation = $this->calculator->calculate(
            $data['lines'],
            $data['discount_type'] ?? null,
            $data['discount_value'] ?? null,
        );

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
                'currency' => 'AFN',
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'customer_snapshot' => $this->snapshots->customer($customer),
                'company_snapshot' => $this->snapshots->company($profile),
                'subtotal' => $calculation['subtotal'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? 0,
                'discount_amount' => $calculation['discount_amount'],
                'total' => $calculation['total'],
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'version_no' => 1,
            ]);

            $invoice->lines()->createMany($calculation['lines']);
            $invoice->load('lines');

            $this->history->version($invoice, $user, 'created');
            $this->history->activity($invoice, $user, 'created');

            return $invoice->fresh(['customer', 'template', 'lines', 'versions', 'activity']);
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

        $discountType = array_key_exists('discount_type', $data) ? $data['discount_type'] : $invoice->discount_type;
        $discountValue = array_key_exists('discount_value', $data) ? $data['discount_value'] : $invoice->discount_value;

        $calculation = $this->calculator->calculate($lines, $discountType, $discountValue);

        return DB::transaction(function () use (
            $invoice,
            $data,
            $user,
            $customer,
            $profile,
            $calculation,
            $discountType,
            $discountValue,
        ): InvoiceDraft {
            $invoice->update([
                'customer_id' => $customer->id,
                'invoice_template_id' => $data['invoice_template_id'] ?? $invoice->invoice_template_id,
                'updated_by_user_id' => $user->id,
                'locale' => $data['locale'] ?? $invoice->locale,
                'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $invoice->due_date,
                'customer_snapshot' => $this->snapshots->customer($customer),
                'company_snapshot' => $this->snapshots->company($profile),
                'subtotal' => $calculation['subtotal'],
                'discount_type' => $discountType,
                'discount_value' => $discountValue ?? 0,
                'discount_amount' => $calculation['discount_amount'],
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

            return $invoice->fresh(['customer', 'template', 'lines', 'versions', 'activity']);
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

            return $copy->fresh(['customer', 'template', 'lines', 'versions', 'activity']);
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
}
