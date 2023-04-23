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
function parse_csv_file($file): array
{
    if ($file === false) {
        return [];
    }

    $padding = array_fill(0, 5, null);
    while (($data = fgetcsv($file, 1024, ";")) !== false) {
        list($date_fr, $amount,,, $label) = $data + $padding;

        if (!$label) {
            continue;
        }

        list($day, $month, $year) = (get_date_from_label($label)
            ?? explode('/', $date_fr)
        );

        list($credit, $debit) = get_credit_debit_from_amount(floatval(
            str_replace([',', ' '], ['.', ''], $amount)
        ));

        $ops[] = compact('day', 'month', 'year', 'label', 'credit', 'debit');
    }

    return sort_by_dates(format_dates(fix_dates($ops)));
}

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
    if (preg_match('/(\d\d)\/(\d\d)\/(\d\d)$/', $label, $matches)) {
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
    // fix missing years
    for ($i = 0; $i < count($ops); $i++) {
        if (is_null($ops[$i]['year'])) {
            // backward lookup <-<-<-[j]
            for ($j = $i - 1; $j >= 0; $j--) {
                if (
                    isset($ops[$j]['year'])
                    && $ops[$i]['month'] == $ops[$j]['month']
                ) {
                    $ops[$i]['year'] = $ops[$j]['year'];
                    break;
                }
            }
        }

        if (is_null($ops[$i]['year'])) {
            // forward lookup [j]->->->
            for ($j = $i + 1; $j < count($ops); $j++) {
                if (
                    isset($ops[$j]['year'])
                    && $ops[$i]['month'] == $ops[$j]['month']
                ) {
                    $ops[$i]['year'] = $ops[$j]['year'];
                    break;
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
