<?php

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testAllIdsPresentInJson()
    {
        $parser = new \StardotHosting\SecRuleParser\Parser();
        $rules = $parser->parseFile(__DIR__ . '/../rules/coreruleset_all.conf'); // returns nested array

        // Custom recursive flatten function
        $allRules = [];
        $flatten = function($arr) use (&$flatten, &$allRules) {
            if (is_array($arr)) {
                if (isset($arr['id'])) {
                    $allRules[] = $arr;
                } else {
                    foreach ($arr as $v) {
                        $flatten($v);
                    }
                }
            }
        };
        $flatten($rules);

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

        // Load and parse the test rules file
        $rules = $parser->parseFile(__DIR__ . '/../rules/group_test.conf'); // returns array

        // Group the rules
        $groups = $parser->groupChainedRules($rules);

        // Assert the structure: each group is an array of rules
        $this->assertIsArray($groups);
        foreach ($groups as $group) {
            $this->assertIsArray($group);
            foreach ($group as $rule) {
                // Debug output
                //if (!is_array($rule) || !array_key_exists('type', $rule)) {
                //    var_dump($rule);
                //}
                $this->assertIsArray($rule);
            }
        }

        $this->assertCount(5, $groups);
    }
} 