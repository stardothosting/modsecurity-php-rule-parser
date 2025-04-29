<?php

namespace ModSecurity\Tests;

use PHPUnit\Framework\TestCase;
use ModSecurity\Parser\RuleSetParser;

/**
 * Integration test for parsing all files in the CoreRuleSet.
 */
class CoreRuleSetParsingTest extends TestCase
{
    /**
     * @var RuleSetParser
     */
    private RuleSetParser $parser;

    /**
     * Set up the parser before each test.
     */
    protected function setUp(): void
    {
        $this->parser = new RuleSetParser();
    }

    /**
     * Test parsing all .conf files in the CoreRuleSet rules folder.
     * (Requires manual copy of rules to coreruleset-rules/)
     */
    public function testParseAllCoreRuleSetFiles()
    {
        $rulesFolder = __DIR__ . '/../coreruleset-rules/'; // Copy CoreRuleSet rules folder here manually

        $this->assertDirectoryExists($rulesFolder, 'CoreRuleSet rules folder missing.');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rulesFolder)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'conf') {
                $this->parseFile($file->getPathname());
            }
        }
    }

    /**
     * Parse a single file and assert the rules are valid.
     *
     * @param string $filePath
     */
    private function parseFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $rules = $this->parser->parseRules($content);

        $this->assertIsArray($rules, "Parsing failed for file: $filePath");

        foreach ($rules as $rule) {
            $this->assertNotEmpty($rule->variables, "Parsed rule missing variables in file: $filePath");
            $this->assertNotNull($rule->operator, "Parsed rule missing operator in file: $filePath");
            $this->assertIsArray($rule->actions, "Parsed rule missing actions in file: $filePath");
        }
    }
}
