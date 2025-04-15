<?php

use PHPUnit\Framework\TestCase;
use Stardothosting\ModSecurityParser\ModSecurityParser;

class ModSecurityParserTest extends TestCase
{
    public function testParseModSecurityFiles()
    {
        $parser = new ModSecurityParser('test-key', 'test-url', 'test-group');

        // Specify the path to the ModSecurity rule files
        $directory = __DIR__ . '/conf/';

        // Call the method to convert ModSecurity rules to JSON
        $jsonData = $parser->parseModSecurityFiles($directory);

        // Decode the JSON data
        $decodedData = json_decode($jsonData, true);

        // Perform assertions
        $this->assertIsArray($decodedData);
        $this->assertNotEmpty($decodedData);
    }
}
