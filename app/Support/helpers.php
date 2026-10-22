<?php

use App\Support\Countries;

if (! function_exists('format_money')) {
    /**
     * Format an amount for display in the given currency.
     * INR uses Indian digit grouping (1,23,456.00) and other
     * currencies use standard grouping ($1,234.56).
     */
    function format_money(float|int|string|null $amount, string $currency = 'INR'): string
    {
        $value = (float) $amount;

        if ($currency === 'INR') {
            return '₹' . format_money_indian($value);
        }

        return match ($currency) {
            'USD' => '$' . number_format($value, 2),
            'EUR' => '€' . number_format($value, 2),
            'GBP' => '£' . number_format($value, 2),
            default => $currency . ' ' . number_format($value, 2),
        };
    }
}

if (! function_exists('format_money_pdf')) {
    /**
     * PDF-safe currency formatter.
     * DomPDF cannot render Unicode symbols (₹ €  £) unless a matching
     * font is embedded.  We use short ASCII prefixes so no glyph goes
     * missing (shows as "?").
     *
     * INR  → Rs. 1,23,456.00  (Indian grouping)
     * USD  → USD 1,234.56
     * EUR  → EUR 1,234.56
     * GBP  → GBP 1,234.56
     */
    function format_money_pdf(float|int|string|null $amount, string $currency = 'INR'): string
    {
        $value = (float) $amount;

        if ($currency === 'INR') {
            return 'Rs. ' . format_money_indian($value);
        }

        return match ($currency) {
            'USD' => 'USD ' . number_format($value, 2),
            'EUR' => 'EUR ' . number_format($value, 2),
            'GBP' => 'GBP ' . number_format($value, 2),
            'AED' => 'AED ' . number_format($value, 2),
            'SGD' => 'SGD ' . number_format($value, 2),
            'AUD' => 'AUD ' . number_format($value, 2),
            default => $currency . ' ' . number_format($value, 2),
        };
    }
}

if (! function_exists('format_money_indian')) {
    /**
     * 1234567.5 => "12,34,567.50" (Indian lakh/crore grouping).
     */
    function format_money_indian(float $value): string
    {
        $negative = $value < 0;
        $value = abs($value);

        $whole = (int) floor($value);
        $decimal = round(($value - $whole) * 100);

        if ($decimal >= 100) {
            $whole += 1;
            $decimal = 0;
        }

        $wholeString = (string) $whole;
        $lastThree = substr($wholeString, -3);
        $rest = substr($wholeString, 0, -3);

        if ($rest !== '') {
            $lastThree = str_pad($lastThree, 3, '0', STR_PAD_LEFT);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        }

        $formatted = $rest === ''
            ? $lastThree
            : $rest . ',' . $lastThree;

        $formatted .= '.' . str_pad((string) (int) $decimal, 2, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '') . $formatted;
    }
}

if (! function_exists('to_date')) {
    /**
     * Accept Carbon/DateTime instances or date strings.
     */
    function to_date(mixed $date): ?DateTimeInterface
    {
        if ($date instanceof DateTimeInterface) {
            return $date;
        }

        if (is_string($date) && $date !== '') {
            $parsed = strtotime($date);

            return $parsed ? new DateTimeImmutable('@' . $parsed) : null;
        }

        return null;
    }
}

if (! function_exists('format_date_short')) {
    /**
     * "Mar 19, 2025" style date for the UI.
     */
    function format_date_short(mixed $date): string
    {
        $date = to_date($date);

        return $date ? $date->format('M j, Y') : '—';
    }
}

if (! function_exists('country_name')) {
    function country_name(?string $code): string
    {
        return Countries::list()[$code] ?? ($code ?? '—');
    }
}
