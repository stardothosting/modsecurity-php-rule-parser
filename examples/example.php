<?php

// Require Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/ModSecurityRuleParser.php';


// Define the namespace of your package's classes
use Stardothosting\ModSecurityParser\ModSecurityParser;

// Example usage
$openaiApiKey = '';
$openaiApiUrl = 'https://api.openai.com/v1/completions';
$parser = new ModSecurityParser($openaiApiKey, $openApiUrl, 'wordpress');
$directory = '/etc/caddy/wordpress-modsecurity-ruleset/';
echo $parser->parseModSecurityFiles($directory);

