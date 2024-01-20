<?php

namespace Stardothosting\ModSecurityParser;

class ModSecurityRuleParser
{
    /**
     * Function to interpret individual patterns
     *
     * @param string $pattern
     * @return string
     */
    public function interpretPattern($pattern)
    {
        // Placeholder logic for interpreting patterns
        return "Pattern: $pattern";
    }

    /**
     * Function to interpret individual ModSecurity rule and provide a human-readable description
     *
     * @param array $rule
     * @return array
     */
    public function interpretRule($rule)
    {
        $description = [];

        if (isset($rule['SecRule']) && !is_string($rule['SecRule'])) {
            foreach ($rule['SecRule'] as $directive => $value) {
                $description[] = ["Directive" => $directive, "Value" => $value];
                $description[] = ["Pattern" => $this->interpretPattern($value)];
            }
        }

        return $description;
    }

    /**
     * Function to generate a concise human-readable summary
     *
     * @param array $rule
     * @return string
     */
    public function generateSummary($rule)
    {
        // Check if 'msg' directive is present in 'SecRule'
        if (isset($rule['SecRule']["msg:'WordPress:"])) {
            // Extract 'msg' value and sanitize it
            $msg = str_replace(['\'', '\\', '"'], '', $rule['SecRule']["msg:'WordPress:"]);
            return $msg;
        } else {
            return 'No specific information available for this rule.';
        }
    }

    /**
     * Function to extract rule ID from the SecRule field
     *
     * @param array $rule
     * @return string
     */
    public function extractRuleID($rule)
    {
        $id = '';

        if (isset($rule['SecRule'])) {
            // Check for the presence of 'id' in the SecRule field
            if (preg_match('/id:(\d+)/', $rule['SecRule'], $matches)) {
                $id = $matches[1];
            }
        }

        return $id;
    }

    /**
     * Function to parse ModSecurity rules from .conf file and convert to JSON
     *
     * @param string $directory
     * @return string
     */
    public function convertModSecurityToJSON($directory)
    {
        $files = glob($directory . '*.conf');
        $allRules = [];

        foreach ($files as $file) {
            $fileContent = file_get_contents($file);

            // Split rules based on the 'SecRule' directive
            $ruleChunks = preg_split('/(?<=\bSecRule\b)/', $fileContent, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($ruleChunks as $ruleChunk) {
                if (empty($ruleChunk) || strpos($ruleChunk, '#') === 0) {
                    continue;
                }

                $currentRule = ['SecRule' => trim($ruleChunk)];

                // Check if 'id:' is present in the SecRule field
                if (strpos($currentRule['SecRule'], 'id:') !== false) {
                    // Ensure 'Analysis' is a string by encoding the array as JSON
                    $currentRule['Analysis'] = json_encode($this->interpretRule($currentRule), JSON_PRETTY_PRINT);

                    // Add a new field "Summary"
                    $currentRule['Summary'] = $this->generateSummary($currentRule);

                    // Add a new field "ID"
                    $currentRule['ID'] = $this->extractRuleID($currentRule);

                    $allRules[] = $currentRule;
                }
            }
        }

        // Convert all rules to JSON format
        $jsonData = json_encode($allRules, JSON_PRETTY_PRINT);

        return $jsonData;
    }
}

// Example usage:
//$parser = new ModSecurityRuleParser();
//echo $parser->convertModSecurityToJSON('/etc/caddy/coreruleset/rules/test/');
