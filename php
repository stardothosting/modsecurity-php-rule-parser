<?php

namespace Stardothosting\ModSecurity\Parser;

use Stardothosting\ModSecurity\Parser\RuleSetParser;

class RuleSetParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        $parser = new RuleSetParser();
        $this->assertInstanceOf('Stardothosting\ModSecurity\Parser\RuleSetParser', $parser);
    }
} 