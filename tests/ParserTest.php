<?php

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testAllIdsPresentInJson()
    {
        $parser = new \StardotHosting\SecRuleParser\Parser();
        $rules = $parser->parseFile(__DIR__ . '/../rules/coreruleset_all.conf'); // returns array

        // Only include rules that have an 'id' key
        $jsonIds = array_map(
            fn($r) => (string)$r['id'],
            array_filter($rules, fn($r) => isset($r['id']))
        );

        $conf = file_get_contents(__DIR__ . '/../rules/coreruleset_all.conf');
        // Match the parser's id regex: allow comma or space after the id
        preg_match_all('/id\s*:\s*["\']?([0-9]+)["\']?[, ]/i', $conf, $matches);
        $confIds = array_unique($matches[1]);

        sort($jsonIds);
        sort($confIds);

        $missing = array_diff($confIds, $jsonIds);
        $extra = array_diff($jsonIds, $confIds);

        $this->assertEmpty($missing, 'IDs in conf but missing in parser output: ' . implode(', ', $missing));
        $this->assertEmpty($extra, 'IDs in parser output but not in conf: ' . implode(', ', $extra));
    }
} 