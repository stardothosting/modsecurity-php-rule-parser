<?php

namespace ModSecurity\Tests;

use PHPUnit\Framework\TestCase;
use ModSecurity\Parser\RuleSetParser;

/**
 * Unit tests for RuleSetParser.
 */
class RuleSetParserTest extends TestCase
{
    /**
     * Test parsing a simple multiline chained rule.
     */
    public function testParseMultilineRule()
    {
        $raw = <<<CONF
SecRule REQUEST_URI "@rx admin" "id:1001,phase:2,deny,chain" \\
SecRule ARGS:username "@streq admin"
CONF;

        $parser = new RuleSetParser();
        $rules = $parser->parseRules($raw);

        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertCount(1, $rules[0]->chainedRules);
    }

    /**
     * Test parsing a rule with multiple chained rules.
     */
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

    /**
     * Test parsing rules with tabs and extra spaces.
     */
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

    /**
     * Test parsing a rule with escaped quotes in the operator value.
     */
    public function testEscapedQuotesInOperator()
    {
        $raw = <<<CONF
    SecRule REQUEST_HEADERS:User-Agent "@contains \\"Mozilla\\"" "id:1006,phase:2,deny"
    CONF;

        $parser = new RuleSetParser();
        $rules = $parser->parseRules($raw);

        $this->assertCount(1, $rules);
        $this->assertEquals('REQUEST_HEADERS:User-Agent', $rules[0]->variables[0]->name);
        $this->assertEquals('@contains', $rules[0]->operator->type);
        $this->assertEquals('"Mozilla"', $rules[0]->operator->value);
    }

    /**
     * Test parsing a rule with a negated operator.
     */
    public function testNegatedOperator()
    {
        $raw = <<<CONF
    SecRule REQUEST_METHOD "!@streq GET" "id:1007,phase:2,deny"
    CONF;

        $parser = new RuleSetParser();
        $rules = $parser->parseRules($raw);

        $this->assertCount(1, $rules);
        $this->assertEquals('@!streq', $rules[0]->operator->type);
        $this->assertEquals('GET', $rules[0]->operator->value);
    }

    /**
     * Test parsing a rule with a missing chain (should not error).
     */
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
