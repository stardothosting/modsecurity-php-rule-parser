<?php

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testAllIdsPresentInJson()
    {
        $parser = new \StardotHosting\SecRuleParser\Parser();
        $rules = $parser->parseFile(__DIR__ . '/../rules/coreruleset_all.conf'); // returns array

        // Flatten all rules and chained children into a single array
        $allRules = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($rules));
        foreach ($iterator as $rule) {
            if (is_array($rule) && isset($rule['id'])) {
                $allRules[] = $rule;
            }
        }

        $jsonIds = array_map(
            fn($r) => (string)$r['id'],
            $allRules
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

    public function testChainedRuleGrouping()
    {
        $parser = new \StardotHosting\SecRuleParser\Parser();
        $rules = $parser->parseFile(__DIR__ . '/../rules/group_test.conf');

        // Group rules by chains
        $grouped = $parser->groupChains($rules);

        print_r($grouped);

        // Assert the number of groups (now 6: 3 chains, 2 standalones, 1 nested chain)
        $this->assertCount(6, $grouped);

        // Group 1: 2 rules (first chain)
        $this->assertCount(2, $grouped[0]);
        $this->assertEquals('220000', $grouped[0][0]['id']);
        $this->assertArrayNotHasKey('id', $grouped[0][1]); // chained rule has no id

        // Group 2: 1 rule (standalone)
        $this->assertCount(1, $grouped[1]);
        $this->assertEquals('218400', $grouped[1][0]['id']);

        // Group 3: 4 rules (second chain)
        $this->assertCount(4, $grouped[2]);
        $this->assertEquals('225140', $grouped[2][0]['id']);

        // Group 4: 1 rule (standalone)
        $this->assertCount(1, $grouped[3]);
        $this->assertEquals('225100', $grouped[3][0]['id']);

        // Group 5: 2 rules (third chain)
        $this->assertCount(2, $grouped[4]);
        $this->assertEquals('225100', $grouped[4][0]['id']);

        // Group 6: 5 rules (nested chain)
        $this->assertCount(5, $grouped[5]);
        $this->assertEquals('300000', $grouped[5][0]['id']);
        $this->assertStringContainsString('chain', $grouped[5][1]['raw']);
        $this->assertStringContainsString('chain', $grouped[5][2]['raw']);
        $this->assertStringContainsString('chain', $grouped[5][3]['raw']);
        $this->assertStringNotContainsString('chain', $grouped[5][4]['raw']);

        // Optionally, check the raw lines or other properties as needed
    }
} 