<?php

namespace Stardothosting\ModSecurityParser;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ModSecurityParser
{
    private $openaiApiKey;
    private $openaiApiUrl;

    public function __construct($apiKey)
    {
        $this->openaiApiKey = $apiKey;
        $this->openaiApiUrl = 'https://api.openai.com/v1/completions';
    }

    public function parseModSecurityFiles($directory)
    {
        $files = glob($directory . '*.conf');
        $allRules = [];

        foreach ($files as $file) {
            $fileRules = $this->convertModSecurityToJSON($file);
            $allRules = array_merge($allRules, $fileRules);
        }

        return json_encode($allRules, JSON_PRETTY_PRINT);
    }

    private function interpretPattern($pattern)
    {
        // Placeholder logic for interpreting patterns
        return "Pattern: $pattern";
    }

    private function interpretRule($rule)
    {
        $description = '';

        if (isset($rule['SecRule'])) {
            $ruleDetails = $rule['SecRule'];
            $ruleString = '';

            foreach ($ruleDetails as $directive => $value) {
                $ruleString .= "$directive: $value\n";
            }

            // Call OpenAI ChatGPT API to interpret the rule
            $analysis = $this->callOpenAIChatGPT($ruleString);
            $description .= $analysis;
        }

        return $description;
    }

    private function callOpenAIChatGPT($ruleString)
    {
        $client = new Client();
        $data = [
            'model' => 'gpt-3.5-turbo-instruct', // Specify the model parameter
            'prompt' => "Interpret the following ModSecurity rule and produce a summary in 50 words or less:\n\n$ruleString\n\nDescription:",
            'max_tokens' => 100,
            'temperature' => 0.5,
            'stop' => '###'
        ];

        try {
            $response = $client->post($this->openaiApiUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->openaiApiKey,
                ],
                'json' => $data,
            ]);

            $decodedResponse = json_decode($response->getBody(), true);

            if (isset($decodedResponse['choices']) && !empty($decodedResponse['choices'])) {
                return trim($decodedResponse['choices'][0]['text']);
            } else {
                return 'Description not found.';
            }
        } catch (GuzzleException $e) {
            // Handle exception
            return 'Failed to fetch data from OpenAI API.';
        }
    }

    private function convertModSecurityToJSON($filePath)
    {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $rules = [];
        $currentRule = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, 'SecRule') === 0) {
                if (!empty($currentRule)) {
                    $currentRule['Analysis'] = $this->interpretRule($currentRule);
                    $rules[] = $currentRule;
                }
                $currentRule = ['SecRule' => []];
            }

            $parts = explode(' ', $line, 2);

            if (count($parts) >= 2) {
                $directive = rtrim($parts[0], ',\\');
                $value = $parts[1];
            } elseif (count($parts) == 1) {
                $directive = rtrim($parts[0], ',\\');
                $value = null;
            } else {
                $directive = null;
                $value = null;
            }

            $currentRule['SecRule'][$directive] = $value;
        }

        if (!empty($currentRule)) {
            $currentRule['Analysis'] = $this->interpretRule($currentRule);
            $rules[] = $currentRule;
        }

        return $rules;
    }

}

// Example usage:
//$openaiApiKey = '';
//$parser = new ModSecurityParser($openaiApiKey);
//$directory = '/etc/caddy/wordpress-modsecurity-ruleset/';
//echo $parser->parseModSecurityFiles($directory);

?>