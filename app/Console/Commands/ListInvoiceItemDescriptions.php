<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class ListInvoiceItemDescriptions extends Command
{
    protected $signature = 'invoices:item-descriptions';

    protected $description = 'Output invoice item lines that have descriptions.';

    public function handle(): int
    {
        $lineCount = 0;

        Invoice::query()
            ->orderBy('document_number')
            ->lazy()
            ->each(function (Invoice $invoice) use (&$lineCount): void {
                foreach ($this->linesForInvoice($invoice) as $line) {
                    $this->line($line);
                    $lineCount++;
                }
            });

        if ($lineCount === 0) {
            $this->comment('No invoice item descriptions found.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function linesForInvoice(Invoice $invoice): array
    {
        $documentNumber = (string) ($invoice->document_number ?: $invoice->slug ?: 'Invoice #'.$invoice->getKey());
        $lines = [];

        foreach ($this->extractItemRows($invoice->items) as $item) {
            $description = $this->normalizeDescription($item['description'] ?? null);

            if ($description === '') {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $itemText = trim($title === '' ? $description : "{$title}{$description}");

            $lines[] = "{$documentNumber}: {$itemText}";
        }

        return $lines;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractItemRows(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        if (isset($items['en']) && is_array($items['en']) && $this->isItemRowCollection($items['en'])) {
            return array_values($items['en']);
        }

        if (isset($items['id']) && is_array($items['id']) && $this->isItemRowCollection($items['id'])) {
            return array_values($items['id']);
        }

        if ($this->isItemRowCollection($items)) {
            return array_values($items);
        }

        foreach ($items as $value) {
            if (! is_array($value)) {
                continue;
            }

            if ($this->isItemRowCollection($value)) {
                return array_values($value);
            }
        }

        return [];
    }

    /**
     * @param  array<int|string, mixed>  $rows
     */
    private function isItemRowCollection(array $rows): bool
    {
        if ($rows === []) {
            return true;
        }

        foreach (array_values($rows) as $row) {
            if (! is_array($row) || ! $this->isItemRow($row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isItemRow(array $row): bool
    {
        return array_key_exists('price', $row)
            || array_key_exists('title', $row)
            || array_key_exists('description', $row);
    }

    private function normalizeDescription(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
