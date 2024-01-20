<?php

use PHPUnit\Framework\TestCase;
use ModSecurityParser\ModSecurityRuleParser;

class ModSecurityRuleParserTest extends TestCase
{
    public function testConvertModSecurityToJSON()
    {
        $parser = new ModSecurityRuleParser();

        // Specify the path to the ModSecurity rule files
        $directory = __DIR__ . '/conf/';

        // Call the method to convert ModSecurity rules to JSON
        $jsonData = $parser->convertModSecurityToJSON($directory);

        // Decode the JSON data
        $decodedData = json_decode($jsonData, true);

        // Perform your assertions based on the expected structure or content
        $this->assertIsArray($decodedData);
        $this->assertNotEmpty($decodedData);
    }
}
