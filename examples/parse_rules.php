<?php

require __DIR__ . '/../vendor/autoload.php';

use ModSecurity\Parser\RuleSetParser;

$parser = new RuleSetParser();

$rawRules = <<<EOD
SecRule REQUEST_URI "@rx admin" "id:1001,phase:2,deny,chain" \\
SecRule ARGS:username "@streq admin"
EOD;

$rules = $parser->parseRules($rawRules);

foreach ($rules as $rule) {
    print_r($rule->toArray());
}
