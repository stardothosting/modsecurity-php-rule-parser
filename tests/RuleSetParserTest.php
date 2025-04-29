<?php

namespace ModSecurity\Tests;

use PHPUnit\Framework\TestCase;
use ModSecurity\Parser\RuleSetParser;

class RuleSetParserTest extends TestCase
{
    public function testParseMultilineRule()
    {
        $raw = "SecRule REQUEST_URI \"@rx admin\" \"id:1001,phase:2,deny,chain\" \\\n" .
               "SecRule ARGS:username \"@rx admin\"";

        $parser = new RuleSetParser();
        $rules = $parser->parseRules($raw);

        $this->assertCount(1, $rules);
        $this->assertCount(1, $rules[0]->chainedRules);
    }
}
