<?php

require __DIR__ . '/../vendor/autoload.php';

use ModSecurity\Parser\RuleSetParser;

$file = __DIR__ . '/example_rules.conf'; // Point to a real .conf file

if (!file_exists($file)) {
    die("Rules file not found\n");
}

$content = file_get_contents($file);

$parser = new RuleSetParser();
$rules = $parser->parseRules($content);

echo json_encode(array_map(fn($r) => $r->toArray(), $rules), JSON_PRETTY_PRINT);
