<?php

// Require Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define the namespace of your package's classes
use Stardothosting\ModSecurityParser\ModSecurityParser;

// Example usage
$openaiApiKey = '';
$parser = new ModSecurityParser($openaiApiKey);
$directory = '/etc/caddy/wordpress-modsecurity-ruleset/';
echo $parser->parseModSecurityFiles($directory);

?>