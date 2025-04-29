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

    public function testMultiLineMultiChainRule()
    {
        $raw = <<<CONF
    SecRule REQUEST_URI "@rx admin" "id:1004,phase:2,deny,chain" \\
    SecRule ARGS:username "@streq admin" "chain" \\
    SecRule ARGS:password "@streq password123"
    CONF;

        $parser = new RuleSetParser();
        $rules = $parser->parseRules($raw);

        $this->assertCount(1, $rules);
        $this->assertCount(2, $rules[0]->chainedRules);
        $this->assertEquals('ARGS:username', $rules[0]->chainedRules[0]->variables[0]->name);
        $this->assertEquals('ARGS:password', $rules[0]->chainedRules[1]->variables[0]->name);
    }

    public function testTabsAndSpacesHandling()
    {
        $raw = <<<CONF
    SecRule    REQUEST_URI    "@rx    admin"    "id:1005,    phase:2,    deny,    chain" \\
    \tSecRule\tARGS:username\t"@streq\tadmin"
    CONF;

        $parser = new RuleSetParser();
        $rules = $parser->parseRules($raw);

        $this->assertCount(1, $rules);
        $this->assertCount(1, $rules[0]->chainedRules);
    }

    public function testEscapedQuotesInOperator()
    {
        $raw = <<<CONF
    SecRule REQUEST_HEADERS:User-Agent "@contains \\"Mozilla\\"" "id:1006,phase:2,deny"
    CONF;

        $parser = new RuleSetParser();
        $rules = $parser->parseRules($raw);

        $this->assertCount(1, $rules);
        $this->assertEquals('REQUEST_HEADERS:User-Agent', $rules[0]->variables[0]->name);
        $this->assertEquals('@contains', $rules[0]->operator->name);
        $this->assertEquals('\\"Mozilla\\"', $rules[0]->operator->argument);
    }

    public function testNegatedOperator()
    {
        $raw = <<<CONF
    SecRule REQUEST_METHOD "!@streq GET" "id:1007,phase:2,deny"
    CONF;

        $parser = new RuleSetParser();
        $rules = $parser->parseRules($raw);

        $this->assertCount(1, $rules);
        $this->assertEquals('!', $rules[0]->operator->negation);
        $this->assertEquals('@streq', $rules[0]->operator->name);
        $this->assertEquals('GET', $rules[0]->operator->argument);
    }

    public function testRuleWithMissingChain()
    {
        $raw = <<<CONF
    SecRule REQUEST_URI "@rx admin" "id:1008,phase:2,deny,chain"
    CONF;

        $parser = new RuleSetParser();
        $rules = $parser->parseRules($raw);

        $this->assertCount(1, $rules);
        $this->assertEmpty($rules[0]->chainedRules);
    }

}
