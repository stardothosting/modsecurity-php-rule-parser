<?php

require __DIR__ . '/../vendor/autoload.php';

use ModSecurity\Parser\RuleSetParser;

// Path to the example ModSecurity rules file
$file = __DIR__ . '/example_rules.conf'; // Point to a real .conf file

if (!file_exists($file)) {
    die("Rules file not found\n");
}

// Read the file contents
$content = file_get_contents($file);

// Instantiate the parser and parse the rules
$parser = new RuleSetParser();
$rules = $parser->parseRules($content);

// Output the rules as pretty-printed JSON
echo json_encode(array_map(fn($r) => $r->toArray(), $rules), JSON_PRETTY_PRINT);
