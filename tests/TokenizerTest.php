<?php

namespace Stardothosting\ModSecurity\Tests;

use PHPUnit\Framework\TestCase;
use Stardothosting\ModSecurity\Parser\Tokenizer;

/**
 * Unit test for the Tokenizer class.
 */
class TokenizerTest extends TestCase
{
    /**
     * Test tokenization of a simple rule.
     */
    public function testSimpleRuleTokenization()
    {
        $tokenizer = new Tokenizer('SecRule ARGS "@rx test" "id:1,phase:2,deny"');
        $tokens = $tokenizer->tokenize();

        $this->assertCount(5, $tokens);
        $this->assertEquals('WORD', $tokens[0]->type);
        $this->assertEquals('SecRule', $tokens[0]->value);
    }
}
