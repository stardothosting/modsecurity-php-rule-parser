# ModSecurity PHP Rule Parser

A fully-featured PHP parser for [ModSecurity](https://modsecurity.org/) rules, including full compatibility with [OWASP CoreRuleSet (CRS)](https://coreruleset.org/).

This project provides a clean way to tokenize, parse, inspect, and manipulate ModSecurity rule files (`.conf`) in structured PHP objects or JSON — usable from PHP code or a CLI.

---

## ✨ Features

- ✅ Fully parses `SecRule` directives from `.conf` files
- ✅ Handles:
  - Chained rules (`chain`)
  - Multiline rules (`\` continuation)
  - Quoted actions and operators
  - Escaped quotes inside rules
  - Negated operators (e.g. `!@rx`)
- ✅ Converts rules to structured PHP objects or JSON
- ✅ CLI to parse files or folders
- ✅ Includes PHPUnit tests against full OWASP CRS rule set
- ✅ 100% Composer + PSR-4 compatible

---

## 📦 Installation

```bash
composer require stardothosting/modsecurity-php-rule-parser
