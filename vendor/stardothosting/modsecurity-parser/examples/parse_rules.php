<?php

require __DIR__ . '/../vendor/autoload.php';

use ModSecurity\Parser\RuleSetParser;

// Instantiate the parser
$parser = new RuleSetParser();

// Example raw rules as a heredoc string
$rawRules = <<<EOD
SecRule REQUEST_URI "@rx admin" "id:1001,phase:2,deny,chain" \\
SecRule ARGS:username "@streq admin"
EOD;

// Parse the rules from the string
$rules = $parser->parseRules($rawRules);

// Output each rule as an array
foreach ($rules as $rule) {
    print_r($rule->toArray());
}
