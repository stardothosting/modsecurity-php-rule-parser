# ModSecurity PHP Rule Parser

## Overview
The ModSecurity Rule Parser is a PHP library that parses ModSecurity rules and generates JSON output of parsed values. It leverages OpenAI's GPT models to analyze rules and produce human-readable summaries included in the output.

## Features
- Parse ModSecurity rule files (.conf)
- Convert rules to structured JSON format
- AI-powered rule interpretation and summarization
- Support for rule grouping
- Extracts key rule components (ID, messages, directives)

## Installation
```
 composer require stardothosting/modsecurity-parser
```

## Usage
```
use Stardothosting\ModSecurityParser\ModSecurityParser;
// Initialize parser with OpenAI credentials
$parser = new ModSecurityParser(
'your-openai-api-key',
'https://api.openai.com/v1/completions',
'wordpress' // Optional group name
);
// Parse rules from a directory
$jsonRules = $parser->parseModSecurityFiles('/path/to/rules/');
```

## Output Format
The parser generates JSON output containing:
- Rule ID
- Rule message
- File information
- Group assignment
- AI-generated description
- Original SecRule directives

## Requirements
- PHP 7.2 or higher
- OpenAI API access
- Composer for installation

## License
MIT License