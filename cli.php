#!/usr/bin/env php
<?php

define("TABLE_FORMAT", "| %-10.10s | %2.2s | %-45.45s |  %8.8s |  %8.8s |\n");
define("TABLE_DIVIDER", str_replace(['|', ' '], ['+', '-'], vsprintf(TABLE_FORMAT, array_fill(0, 5, ' '))));

require "lcl.php";

$operations = parse_lcl_file(__DIR__ . '/data.txt');

if (in_array('--json', $argv)) {
    echo json_encode($operations);
    exit;
}

echo TABLE_DIVIDER;
printf(TABLE_FORMAT, "Date", "OP", "Label", "Credit", "Debit");
echo TABLE_DIVIDER;

foreach ($operations as $op) {
    printf(
        TABLE_FORMAT,
        $op['date'],
        $op['type'] ?? '--',
        $op['label'],
        $op['credit'] ? sprintf("%4.2f", $op['credit']) : "",
        $op['debit'] ? sprintf("%4.2f", $op['debit']) : ""
    );
}

echo TABLE_DIVIDER;
