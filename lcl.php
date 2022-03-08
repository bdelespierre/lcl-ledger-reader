<?php

define('LCL_OP_TYPES', ['VIREMENT', 'PRET', 'PRLV', 'CB', 'VIR', 'CHQ.']);

/**
 * @return array<array{date: string, type: ?string, label: string, credit: ?float, debit: ?float}>
 */
function parse_lcl_table_content(string $content): array
{
    $operations = [];

    foreach (explode("\n", $content) as $line) {
        if ($parsed = parse_lcl_table_line($line)) {
            $operations[] = $parsed;
        }
    }

    usort($operations, fn ($a, $b) => $a['date'] <=> $b['date']);

    return $operations;
}

/**
 * @return array<array{date: string, type: ?string, label: string, credit: ?float, debit: ?float}>
 */
function parse_lcl_file(string $filename): array | false
{
    if (! is_readable($filename) || ! is_file($filename)) {
        return false;
    }

    $operations = [];

    foreach ((array) file($filename) as $line) {
        if ($parsed = parse_lcl_table_line($line)) {
            $operations[] = $parsed;
        }
    }

    usort($operations, fn ($a, $b) => $a['date'] <=> $b['date']);

    return $operations;
}

/**
 * @return array{date: string, type: ?string, label: string, credit: ?float, debit: ?float} | false
 */
function parse_lcl_table_line(string $line): array | false
{
    $line = trim($line);

    if (strlen($line) == 0 || ! preg_match('#^(\d{2})/(\d{2})/(\d{4})#', $line, $matches)) {
        return false;
    }

    list(, $day, $month, $year) = $matches;
    $date = "{$year}-{$month}-{$day}";
    $line = substr($line, strlen($matches[0]));

    $type = null;
    $opTypeRegex = implode('|', array_map('preg_quote', LCL_OP_TYPES));
    if (preg_match("#^(${opTypeRegex})#", $line, $matches)) {
        list(, $type) = $matches;
        $line = substr($line, strlen($matches[0]));
    }

    $debit = null;
    $credit = null;

    if (preg_match('#(\-|\+) ([0-9 ]+,\d+)$#', $line, $matches)) {
        list(, $operator, $amount) = $matches;
        $amount = floatval(str_replace([' ', ','], ['', '.'], $amount));
        $operator == '+' ? $credit = $amount : $debit = $amount;
        $line = substr($line, 0, -strlen($matches[0]));
    }

    if (preg_match('#(\d{2})/(\d{2})/(\d{2})(Horloge)?$#', $line, $matches)) {
        list(, $day, $month, $year) = $matches;
        $date = "20{$year}-{$month}-{$day}";
        $line = substr($line, 0, -strlen($matches[0]));
    }

    if (preg_match('#Horloge$#', $line)) {
        $line = substr($line, 0, -7);
    }

    $label = trim($line);
    $label = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);

    return compact('date', 'type', 'label', 'credit', 'debit');
}



