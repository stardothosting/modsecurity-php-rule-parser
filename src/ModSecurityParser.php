<?php

/**
 * ModSecurity Rule Parser
 * 
 * Parses ModSecurity rules and generates JSON output with AI-powered interpretation
 * 
 * @package Stardothosting\ModSecurityParser
 * @author Star Dot Hosting Inc. <info@stardothosting.com>
 * @license MIT
 */

namespace Stardothosting\ModSecurityParser;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ModSecurityParser
{
    /** @var string OpenAI API key */
    private $openaiApiKey;

    /** @var string OpenAI API endpoint URL */
    private $openaiApiUrl;

    /** @var string|null Optional group identifier for rules */
    private $group;

    /** @var string OpenAI model to use */
    private $model;

    /**
     * Constructor
     *
     * @param string $apiKey OpenAI API key
     * @param string $apiUrl OpenAI API endpoint URL
     * @param string|null $group Optional group identifier
     * @param string $model OpenAI model to use (defaults to gpt-3.5-turbo-instruct)
     */
    public function __construct($apiKey, $apiUrl, $group = null, $model = 'gpt-3.5-turbo-instruct')
    {
        $this->openaiApiKey = $apiKey;
        $this->openaiApiUrl = $apiUrl;
        $this->group = $group;
        $this->model = $model;
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

    // Function to extract rule content
    private function extractRuleId($rule) {
        $value = null;
        if (strpos($rule, 'id:') !== false) {
            // Check for the presence of 'id' in the SecRule field
            if (preg_match('/id:(\d+)/', $rule, $matches)) {
                $value = $matches[1];
            }
        }
        return $value;
    }

    // Function to extract rule content
    private function extractRuleContent($rule, $tag) {
        $value = null;
        $strip_characters = array( '\'', '"', ',' , ';', '<', '>' );
        if ($tag && strpos($rule, $tag . ':') !== false) {
            // Check for the presence of 'id' in the SecRule field
            if (preg_match('/' . $tag . ':(.*)/', $rule, $matches)) {
                $value = str_replace($strip_characters, '', $matches[1]);
            }
        }
        return $value;
    }

    private function callOpenAIChatGPT($ruleString)
    {
        $client = new Client();
        $data = [
            'model' => $this->model, // Use the configured model
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
            return null;
        }
    }

    private function convertModSecurityToJSON($filePath)
    {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $rules = [];
        $currentRule = [];
        $path_parts = pathinfo($filePath);

        foreach ($lines as $line) {

            $line = trim($line);

            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, 'SecRule') === 0) {
                if (!empty($currentRule)) {
                    // Ensure these keys are always present
                    if (!array_key_exists('id', $currentRule)) $currentRule['id'] = null;
                    if (!array_key_exists('msg', $currentRule)) $currentRule['msg'] = null;
                    $currentRule['Description'] = $this->interpretRule($currentRule);
                    $rules[] = $currentRule;
                }
                $currentRule = ['SecRule' => []];
            }


            // Add a new field "ID"
            if (strpos($line, 'id:') !== false) {
                $currentRule['id'] = $this->extractRuleId($line);
            }

            // Check if 'msg:' is present in the SecRule field
            if (strpos($line, 'msg:') !== false) {
                $currentRule['msg'] = $this->extractRuleContent($line, 'msg');
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
            $currentRule['File'] = $path_parts;
            $currentRule['Group'] = $this->group;
        }

        return $rules;
    }

}

?>