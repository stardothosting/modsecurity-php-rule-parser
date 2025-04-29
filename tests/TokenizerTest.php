<?php

namespace ModSecurity\Tests;

use PHPUnit\Framework\TestCase;
use ModSecurity\Parser\Tokenizer;

class TokenizerTest extends TestCase
{
    public function testSimpleRuleTokenization()
    {
        $tokenizer = new Tokenizer('SecRule ARGS "@rx test" "id:1,phase:2,deny"');
        $tokens = $tokenizer->tokenize();

        $this->assertCount(5, $tokens);
        $this->assertEquals('WORD', $tokens[0]->type);
        $this->assertEquals('SecRule', $tokens[0]->value);
    }
}
