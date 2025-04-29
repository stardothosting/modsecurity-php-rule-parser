<?php

namespace ModSecurity\Tests;

use PHPUnit\Framework\TestCase;
use ModSecurity\Parser\RuleSetParser;

class RuleSetParserTest extends TestCase
{
    public function testParseMultilineRule()
    {
        $raw = <<<CONF
SecRule REQUEST_URI "@rx admin" "id:1001,phase:2,deny,chain" \\
SecRule ARGS:username "@streq admin"
CONF;

        // Make sure to normalize newline endings
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);

        $parser = new RuleSetParser();
        $rules = $parser->parseRules($raw);

        $this->assertIsArray($rules, "Parser did not return an array");
        $this->assertCount(1, $rules, "Expected one top-level rule");
        $this->assertCount(1, $rules[0]->chainedRules, "Expected one chained rule");
    }
}
