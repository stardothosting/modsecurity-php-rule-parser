<?php

namespace ModSecurity\Tests;

use PHPUnit\Framework\TestCase;
use ModSecurity\Parser\RuleSetParser;

class CoreRuleSetParsingTest extends TestCase
{
    private RuleSetParser $parser;

    protected function setUp(): void
    {
        $this->parser = new RuleSetParser();
    }

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
