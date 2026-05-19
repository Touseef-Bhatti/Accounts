<?php

declare(strict_types=1);

/** Convert amount to words for invoice PDFs (USD-focused). */
function amount_in_words(float $amount, string $currency = 'USD'): string
{
    if (!is_finite($amount)) {
        $amount = 0.0;
    }

    $currency = strtoupper(trim($currency));
    $prefix = match ($currency) {
        'USD' => 'US DOLLARS',
        'EUR' => 'EUROS',
        'GBP' => 'POUNDS STERLING',
        'PKR' => 'PAKISTANI RUPEES',
        'AED' => 'UAE DIRHAMS',
        'SAR' => 'SAUDI RIYALS',
        default => $currency,
    };

    $whole = (int) floor(abs($amount));
    $cents = (int) round((abs($amount) - $whole) * 100);

    $words = strtoupper(number_to_words_en($whole));
    if ($cents > 0) {
        $words .= ' AND ' . strtoupper(number_to_words_en($cents)) . ' CENTS';
    }

    return "SAY {$prefix} {$words} ONLY";
}

function number_to_words_en(int $n): string
{
    if ($n === 0) {
        return 'zero';
    }

    if ($n < 0) {
        return 'negative ' . number_to_words_en(abs($n));
    }

    $parts = [];
    $scales = [
        1_000_000_000 => 'billion',
        1_000_000 => 'million',
        1_000 => 'thousand',
    ];

    foreach ($scales as $scale => $name) {
        if ($n >= $scale) {
            $q = (int) floor($n / $scale);
            $n %= $scale;
            $parts[] = trim(number_words_under_thousand($q) . ' ' . $name);
        }
    }
    if ($n > 0) {
        $parts[] = number_words_under_thousand($n);
    }

    return trim(implode(' ', $parts));
}

/** Words for 0–999 (used recursively by number_to_words_en). */
function number_words_under_thousand(int $num): string
{
    if ($num === 0) {
        return '';
    }

    $ones = [
        '', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
        'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen',
    ];
    $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

    if ($num < 20) {
        return $ones[$num];
    }
    if ($num < 100) {
        $t = $tens[(int) floor($num / 10)];
        $r = $num % 10;
        return $r ? "{$t} {$ones[$r]}" : $t;
    }

    $h = (int) floor($num / 100);
    $r = $num % 100;
    $rest = number_words_under_thousand($r);

    return $rest ? "{$ones[$h]} hundred {$rest}" : "{$ones[$h]} hundred";
}
