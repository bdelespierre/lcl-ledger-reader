<?php

// try it on https://regex101.com/
define('LCL_LINE_REGEX', "/(?<day>\d{2})\r?\n(?<month>[\wÉÛ]+\.?)\r?\n(?<label>[^\r\n]+)\r?\n(En cours de traitement\*\r?\n)?(?<amount>-?[\d ,]+) €(\r?\nCliquer pourPlus de détails\r?\n)?/m");

define("LCL_MONTHS", [
    'JANV.' =>  1, 'FÉVR.' =>  2, 'MARS'  =>  3,
    'AVR.'  =>  4, 'MAI'   =>  5, 'JUIN'  =>  6,
    'JUIL.' =>  7, 'AOÛT'  =>  8, 'SEPT.' =>  9,
    'OCT.'  => 10, 'NOV.'  => 11, 'DÉC.'  => 12,
]);

/**
 * @return array<array{date:string,label:string,credit:?float,debit:?float}>
 */
function parse_lcl_table_content(string $content): array
{
    $results = preg_match_all(LCL_LINE_REGEX, $content, $matches);

    if (!$results) {
        return [];
    }

    $ops = [];

    for ($i = 0; $i < $results; $i++) {
        $label = $matches['label'][$i];

        list($day, $month, $year) = (get_date_from_label($label)
            ?? [$matches['day'][$i], LCL_MONTHS[$matches['month'][$i]], null]
        );

        list($credit, $debit) = get_credit_debit_from_amount(floatval(
            str_replace([',', ' '], ['.', ''], $matches['amount'][$i])
        ));

        $ops[] = compact('day', 'month', 'year', 'label', 'credit', 'debit');
    }

    return sort_by_dates(format_dates(fix_dates($ops)));
}

function get_date_from_label(string $label): ?array
{
    if (preg_match('/(\d{2})\/(\d{2})\/(\d{2})$/', $label, $matches)) {
        list(, $day, $month, $year) = $matches;
        return [$day, $month, "20{$year}"];
    }

    if (preg_match('/ (\d{6})$/', $label, $matches)) {
        list($day, $month, $year) = str_split($matches[1], 2);
        return [$day, $month, "20{$year}"];
    }

    if (preg_match('/du\s+(\d{2})\/(\d{2})$/i', $label, $matches)) {
        list(, $day, $month) = $matches;
        return [$day, $month, null];
    }

    return null;
}

function get_credit_debit_from_amount(float $amount): array
{
    $credit = $debit = null;
    $amount > 0 ?
        $credit = $amount :
        $debit = abs($amount);

    return [$credit, $debit];
}

function fix_dates(array $ops): array
{
    // fix missing years by proximity lookup, starting from the last operation
    // in $ops (which is at the end) so the current year doesn't "bubble" from
    // the newest operations.
    for ($i = count($ops) - 1; $i >= 0; $i--) {
        if (!isset($ops[$i]['year'])) {
            for ($j = $i + 1, $k = $i - 1; isset($ops[$j]) || isset($ops[$k]); $j++, $k--) {
                foreach ([$j, $k] as $o) {
                    if (isset($ops[$o]['year']) && $ops[$i]['month'] == $ops[$o]['month']) {
                        $ops[$i]['year'] = $ops[$o]['year'];
                        break 2;
                    }
                }
            }
        }
    }

    return $ops;
}

function format_dates(array $ops): array
{
    foreach ($ops as &$op) {
        $op['date'] = sprintf(
            '%04d-%02d-%02d',
            intval($op['year'] ?? '2000'),
            intval($op['month']),
            intval($op['day']),
        );
    }

    return $ops;
}

function sort_by_dates(array $ops): array
{
    usort($ops, fn ($a, $b) => $a['date'] <=> $b['date']);
    return $ops;
}
