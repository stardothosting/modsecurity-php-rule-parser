# StardotHosting SecRule Parser

[![Packagist Version](https://img.shields.io/packagist/v/stardothosting/secrule-parser.svg)](https://packagist.org/packages/stardothosting/secrule-parser)
[![License](https://img.shields.io/packagist/l/stardothosting/secrule-parser.svg)](LICENSE)

A 1:1 PHP port of msc_pyparser (ModSecurity SecRule parser) with JSON output.

---

## Table of Contents

- [Features](#features)  
- [Requirements](#requirements)  
- [Installation](#installation)  
- [Quick Start](#quick-start)  
  - [As a Library](#as-a-library)  
  - [From the CLI](#from-the-cli)  
- [Usage](#usage)  
  - [Parsing a Single File](#parsing-a-single-file)  
  - [Parsing a Directory](#parsing-a-directory)  
- [API Reference](#api-reference)  
- [Contributing](#contributing)  
- [License](#license)  

---

## Features

- **Full fidelity** to the original Python implementation: all variables, operators, actions, quoting rules, chaining logic, and error reporting are preserved exactly.  
- **JSON output** instead of YAML—no extra dependencies.  
- **PSR-4** compliant, installable via Composer.  
- **CLI tool** for rapid testing and integration in scripts or CI.  

---

## Requirements

- PHP 7.4 or higher
- [psr/log](https://packagist.org/packages/psr/log) (optional, for logging)

---

## Installation

```bash
composer require stardothosting/secrule-parser
```

---

## Quick Start

### As a Library

```php
<?php

require 'vendor/autoload.php';

use StardotHosting\SecRuleParser\Parser;

// Parse a single rules file into a PHP array
$parser = new Parser();
$rules  = $parser->parseFile(__DIR__ . '/REQUEST-920-PROTOCOL-ENFORCEMENT.conf');

// Output pretty JSON
echo json_encode($rules, JSON_PRETTY_PRINT), "
";
```

> **Note:**  
> The parser now returns a **nested array structure**:  
> - Each top-level element is a group of rules (e.g., a main rule and its chained children).  
> - Each group is an array of one or more rules, preserving the original chaining and grouping from the ModSecurity config.  
> - To get a flat list of all rules (e.g., for searching by ID), you may need to recursively flatten the array.

### From the CLI

```bash
# Parse one file and print JSON to stdout
vendor/bin/secrule-parser /path/to/my.conf --stdout

# Parse all .conf files in a directory, writing .json files to ./out/
vendor/bin/secrule-parser /path/to/rules/ ./out/

# Show help
vendor/bin/secrule-parser -h
```

---

## Usage

### Parsing a Single File

```php
use StardotHosting\SecRuleParser\Parser;

$parser = new Parser();
$rules = $parser->parseFile('/path/to/rules.conf');
print_r($rules); // $rules is a nested array of rule groups
```

### Parsing a Directory

```php
$allRules = [];
foreach (glob('/etc/modsecurity/rules/*.conf') as $file) {
    $allRules[$file] = (new \StardotHosting\SecRuleParser\Parser())
        ->parseFile($file); // Each file returns a nested array of rule groups
}
```

---

## API Reference

#### `Parser::parseFile(string $path): array`

- Reads and parses the given `.conf` file.  
- **Returns a nested array of rule groups**. Each group is an array of one or more rules (for chained rules, the group contains the main rule and its chained children).  
- Throws `\RuntimeException` on read error or parse exception.

#### `Parser::parse(string $input, string $filename = '<string>'): array`

- Parses a raw ruleset string.  
- **Returns a nested array of rule groups** (see above).

---

## Contributing

1. Fork the repository  
2. Create your feature branch (`git checkout -b feature/YourFeature`)  
3. Commit your changes (`git commit -am 'Add some feature'`)  
4. Push to the branch (`git push origin feature/YourFeature`)  
5. Open a Pull Request  

Please follow PSR-12 coding standards and provide unit tests for any new parsing rules.

---

## License

This project is licensed under the **GPL-3.0-or-later**. See the [LICENSE](LICENSE) file for details.  
Ported from the original [`msc_pyparser`](https://github.com/digitalwave/msc_pyparser) by Ervin Hegedüs (GPL-3.0).

## Breaking Changes in v3.0.1 (2024-06-07)

- **Parser output is now a nested array of rule groups**.  
  - Each group is an array of one or more rules, preserving ModSecurity's chaining/grouping.
  - If you need a flat list of rules, you must flatten the array yourself.
- Test suite updated to recursively flatten the output for ID checks.
- (Also see v3.0.0 changes below.)

## Breaking Changes in v3.0.0 (2024-06-06)

- Namespace changed to `StardotHosting\SecRuleParser\`
- Composer package renamed to `stardothosting/secrule-parser`
- Minimum PHP version is now 7.4
- Old namespaces and package names are no longer supported
