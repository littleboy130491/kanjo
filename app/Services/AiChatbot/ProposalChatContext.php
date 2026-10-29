<?php

namespace App\Services\AiChatbot;

use App\Models\Proposal;
use Illuminate\Support\Carbon;

/**
 * Builds the plain-text context the chatbot may use for one proposal.
 * Internal notes, access credentials, client email/phone, and author data are never included.
 */
class ProposalChatContext
{
    /**
     * @var array<string, string>
     */
    public const CONTENT_SECTIONS = [
        'brief' => 'Brief',
        'extra_content_brief' => 'Brief (extra)',
        'core_services' => 'Core services',
        'features' => 'Features',
        'assets' => 'Assets',
        'server' => 'Server',
        'security' => 'Security',
        'support' => 'Support',
        'additional_benefit' => 'Additional benefits (Offer 2)',
        'our_process' => 'Our process',
        'payment' => 'Payment terms',
        'additional_info' => 'Additional info',
        'terms_condition' => 'Terms & conditions',
        'faq' => 'FAQ',
        'about_us' => 'About us',
    ];

    public function build(Proposal $proposal, string $locale): string
    {
        $proposal->loadMissing(['company', 'portfolios']);

        $blocks = [
            $this->documentBlock($proposal),
            $this->offersBlock($proposal),
            $this->addOnsBlock($proposal, $locale),
            $this->timelineBlock($proposal, $locale, 'offer_1_project_timeline', $proposal->offer_name_1 ?: 'Offer 1'),
            $this->timelineBlock($proposal, $locale, 'offer_2_project_timeline', $proposal->offer_name_2 ?: 'Offer 2'),
        ];

        foreach (self::CONTENT_SECTIONS as $field => $label) {
            $text = self::htmlToText($this->translated($proposal, $field, $locale));

            if ($text !== '') {
                $blocks[] = "## {$label}\n{$text}";
            }
        }

        $blocks[] = $this->teamBlock($proposal);
        $blocks[] = $this->portfoliosBlock($proposal);

        return implode("\n\n", array_filter($blocks, fn (?string $block): bool => filled($block)));
    }

    public static function htmlToText(mixed $html): string
    {
        if (! is_string($html) || trim($html) === '') {
            return '';
        }

        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\s*li[^>]*>/i', "\n- ", $text) ?? $text;
        $text = preg_replace('/<\/\s*(p|div|h[1-6]|li|tr|blockquote|ul|ol)\s*>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/\s*(td|th)\s*>/i', ' | ', $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{FEFF}]/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/ *\n */', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function documentBlock(Proposal $proposal): string
    {
        $company = $proposal->company;

        $lines = [
            '## Document',
            'Proposal number: '.$proposal->document_number,
            'Issued by: '.($company?->brand_name ?: $company?->company_name ?: '-'),
        ];

        if (filled($company?->company_name) && $company->company_name !== $company->brand_name) {
            $lines[] = 'Legal entity: '.$company->company_name;
        }

        if (filled($company?->website)) {
            $lines[] = 'Website: '.$company->website;
        }

        if (filled($company?->email_1)) {
            $lines[] = 'Contact email: '.$company->email_1;
        }

        if (filled($company?->phone_1)) {
            $lines[] = 'Contact phone: '.$company->phone_1;
        }

        $lines[] = 'Prepared for: '.collect([$proposal->client_name, $proposal->client_company])->filter()->implode(', ');
        $lines[] = 'Issue date: '.$this->date($proposal->issue_date);
        $lines[] = 'Valid until: '.($proposal->valid_until ? $this->date($proposal->valid_until) : 'No expiry');
        $lines[] = 'Currency: '.($proposal->currency ?: 'IDR');

        if ((float) $proposal->tax_rate > 0) {
            $lines[] = 'Tax (PPN): '.$this->number($proposal->tax_rate).'%';
        }

        return implode("\n", $lines);
    }

    private function offersBlock(Proposal $proposal): string
    {
        $lines = ['## Offers and prices'];

        foreach ([1, 2] as $index) {
            $name = $proposal->{"offer_name_{$index}"};
            $price = $proposal->{"offer_{$index}_price"};

            if (blank($name) && blank($price)) {
                continue;
            }

            $lines[] = "Offer {$index}: ".($name ?: '-');
            $lines[] = '- Price: '.$this->money($proposal, $price);

            if (filled($proposal->{"offer_{$index}_original_price"})) {
                $lines[] = '- Original price before discount: '.$this->money($proposal, $proposal->{"offer_{$index}_original_price"});
            }

            if (filled($proposal->{"offer_{$index}_renewal_price"})) {
                $lines[] = '- Renewal price: '.$this->money($proposal, $proposal->{"offer_{$index}_renewal_price"});
            }

            if (filled($proposal->{"offer_{$index}_original_renewal_price"})) {
                $lines[] = '- Original renewal price before discount: '.$this->money($proposal, $proposal->{"offer_{$index}_original_renewal_price"});
            }
        }

        return count($lines) > 1 ? implode("\n", $lines) : '';
    }

    private function addOnsBlock(Proposal $proposal, string $locale): string
    {
        $rows = collect($this->translatedRows($proposal, 'add_on', $locale))
            ->filter(fn (array $row): bool => filled($row['name'] ?? null))
            ->map(function (array $row): string {
                $line = '- '.$row['name'];

                if (filled($row['price'] ?? null)) {
                    $line .= ': '.$row['price'];
                }

                if (filled($row['description'] ?? null)) {
                    $line .= ' ('.trim((string) $row['description']).')';
                }

                return $line;
            });

        return $rows->isEmpty() ? '' : "## Add-ons\n".$rows->implode("\n");
    }

    private function timelineBlock(Proposal $proposal, string $locale, string $field, string $offerName): string
    {
        $rows = collect($this->translatedRows($proposal, $field, $locale))
            ->filter(fn (array $row): bool => filled($row['activity_name'] ?? null));

        if ($rows->isEmpty()) {
            return '';
        }

        $totalDays = $rows->sum(fn (array $row): int => (int) ($row['activity_days'] ?? 0));

        $lines = $rows->map(fn (array $row): string => sprintf(
            '- %s (PIC: %s, %s days)',
            $row['activity_name'],
            $row['activity_pic'] ?? '-',
            $row['activity_days'] ?? '-',
        ));

        return "## Project timeline for {$offerName}\n".$lines->implode("\n")."\nTotal: {$totalDays} days";
    }

    private function teamBlock(Proposal $proposal): string
    {
        if (! $proposal->show_team_member) {
            return '';
        }

        $rows = collect($proposal->team_members ?? [])
            ->filter(fn ($row): bool => is_array($row) && filled($row['name'] ?? null))
            ->map(fn (array $row): string => '- '.$row['name'].(filled($row['role'] ?? null) ? ' ('.$row['role'].')' : ''));

        return $rows->isEmpty() ? '' : "## Team\n".$rows->implode("\n");
    }

    private function portfoliosBlock(Proposal $proposal): string
    {
        $rows = $proposal->portfolios
            ->map(fn ($portfolio): string => '- '.$portfolio->name.(filled($portfolio->url_link) ? ' ('.$portfolio->url_link.')' : ''));

        return $rows->isEmpty() ? '' : "## Portfolio\n".$rows->implode("\n");
    }

    private function translated(Proposal $proposal, string $field, string $locale): mixed
    {
        $value = $proposal->getTranslation($field, $locale, false);

        if (blank($value) || (is_string($value) && self::htmlToText($value) === '')) {
            $value = $proposal->getTranslation($field, $locale, true);
        }

        return $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function translatedRows(Proposal $proposal, string $field, string $locale): array
    {
        $rows = $this->translated($proposal, $field, $locale);

        if (is_string($rows)) {
            $rows = json_decode($rows, true);
        }

        return is_array($rows)
            ? array_values(array_filter($rows, 'is_array'))
            : [];
    }

    private function money(Proposal $proposal, mixed $value): string
    {
        return ($proposal->currency ?: 'IDR').' '.number_format((float) ($value ?? 0), 0, ',', '.');
    }

    private function number(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    private function date(mixed $value): string
    {
        return $value ? Carbon::parse($value)->format('d M Y') : '-';
    }
}
