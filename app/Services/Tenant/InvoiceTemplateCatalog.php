<?php

namespace App\Services\Tenant;

use App\Models\Tenant\InvoiceTemplate;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class InvoiceTemplateCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(InvoiceTemplate|string|int|null $template): array
    {
        if ($template instanceof InvoiceTemplate) {
            $key = $template->key;
        } elseif (is_int($template) || ctype_digit((string) $template)) {
            $number = (int) $template;
            $key = collect(config('invoice_templates'))
                ->first(fn (array $definition) => $definition['number'] === $number, null);

            if (is_array($key)) {
                return $key;
            }

            throw new InvalidArgumentException("Unknown invoice template number [{$number}].");
        } elseif (is_string($template) && $template !== '') {
            $key = $template;
        } else {
            $key = 'executive-navy';
        }

        $definition = config("invoice_templates.{$key}");

        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unknown invoice template [{$key}].");
        }

        return ['key' => $key, ...$definition];
    }

    public function all(): Collection
    {
        return collect(config('invoice_templates'))
            ->map(fn (array $definition, string $key) => ['key' => $key, ...$definition])
            ->sortBy('number')
            ->values();
    }
}
