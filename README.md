# ModSecurity PHP Rule Parser

A fully-featured PHP parser for [ModSecurity](https://modsecurity.org/) rules, including full compatibility with [OWASP CoreRuleSet (CRS)](https://coreruleset.org/).

This project provides a clean way to tokenize, parse, inspect, and manipulate ModSecurity rule files (`.conf`) in structured PHP objects or JSON — usable from PHP code or the command line.

---

## ✨ Features

- ✅ Parses `SecRule` directives from `.conf` files
- ✅ Handles:
  - Chained rules (`chain`)
  - Multiline rules (`\` continuation)
  - Quoted actions and operators
  - Negated operators (e.g. `!@rx`)
  - Escaped characters inside quoted strings
- ✅ Converts rules to structured PHP objects or JSON
- ✅ CLI for parsing files or folders
- ✅ Tested against real OWASP CoreRuleSet rules
- ✅ Composer + PSR-4 ready

---

## 📦 Installation

```bash
composer require stardothosting/modsecurity-php-rule-parser

If working from source:

```bash
git clone https://github.com/stardothosting/modsecurity-php-rule-parser.git
cd modsecurity-php-rule-parser
composer install
```

## 🧰 CLI Usage
The CLI tool allows you to parse ModSecurity .conf rules directly from the command line, either from a single file or an entire folder of rules.

### 🔄 Parse a single .conf file
```bash
php bin/modsec-parser parse --file=/path/to/file.conf
```

Outputs the parsed rule(s) in JSON format to STDOUT.

### 📂 Parse an entire folder of .conf files
```bash
php bin/modsec-parser parse --folder=/path/to/conf/rules/
```

Parses all .conf files in the folder (non-recursive for now).

Example output format:
```json
{
  "/path/to/file1.conf": [ { rule1... }, { rule2... } ],
  "/path/to/file2.conf": [ { rule1... } ]
}
```

## 📝 Notes
Ensure you run composer install first.

Make the CLI executable (optional):

```bash
chmod +x bin/modsec-parser
```

For pretty output:

```bash
php bin/modsec-parser parse --folder=rules/ | jq
```

## 🧪 Testing
We include full PHPUnit tests, including coverage for real-world CoreRuleSet .conf files.

### Run all tests:
```bash
vendor/bin/phpunit
```

### Run only the CoreRuleSet test:
```bash
vendor/bin/phpunit --filter CoreRuleSetParsingTest
```

Make sure to place the CRS rules/ folder inside tests/coreruleset-rules/

## 📚 Output Example
Given this rule:

```apache
SecRule REQUEST_URI "@rx admin" "id:1001,phase:2,deny,chain" \
SecRule ARGS:username "@streq admin"
```

You’ll get:

```json
{
  "variables": ["REQUEST_URI"],
  "operator": {
    "type": "@rx",
    "value": "admin"
  },
  "actions": [
    {"name": "id", "param": "1001"},
    {"name": "phase", "param": "2"},
    {"name": "deny", "param": null},
    {"name": "chain", "param": null}
  ],
  "chained_rules": [
    {
      "variables": ["ARGS:username"],
      "operator": {
        "type": "@streq",
        "value": "admin"
      },
      "actions": []
    }
  ]
}
```

## 🧠 Why This Exists
There was no reliable PHP-native parser for real-world ModSecurity rules that could:

- Parse chained, multiline, and negated rules
- Handle escaped characters
- Return useful structured objects

This solves that — fully compatible with CRS, usable in PHP, CLI, or testing environments.

## 🛠 Roadmap
- Recursive directory parsing in CLI
- Support SecMarker, SecAction
- YAML/JSON output mode
- Integration-ready output for Laravel/Filament

## 🧑‍💻 Contributing
1. Fork this repo

2. Create a feature branch (feature/my-feature)

3. Add tests

4. Submit a PR

## 📝 License
MIT — free for commercial or open source use.