# Changelog

## [3.0.0] - 2024-06-06

### Changed
- **Namespace updated:** All classes now use `StardotHosting\SecRuleParser\` (was `Stardothosting\ModSecurity\`).
- **Composer package renamed:** Now published as `stardothosting/secrule-parser` (was `stardothosting/modsecurity-php-rule-parser`).
- **Dropped backward compatibility** with previous namespaces and package names.
- **Minimum PHP version:** Now requires PHP 7.4 or higher.
- **PSR-4 autoloading:** Updated to match new namespace and directory structure.
- **Logging:** Now uses `psr/log` for optional logging.
- **CLI binary:** Now available as `bin/secrule-parser`.

### Added
- Improved documentation and usage examples in README.
- Explicit support for JSON output and 1:1 compatibility with `msc_pyparser`.

### Removed
- Support for legacy namespaces and package names.

---

## [2.0.2] - 2025-04-29

### Added
- Expanded PHPUnit test coverage:
  - Multi-line chained rules parsing
  - Multi-depth chained rules parsing
  - Handling of rules with excessive tabs and spaces
  - Handling of negated operators (e.g., `!@streq`)
  - Parsing escaped quotes in operator values (e.g., `\"Mozilla\"`)
  - Detection of missing chained rules
- Full CoreRuleSet (CRS) compatibility testing against real CRS `rules/` folder.

### Fixed
- Correct parsing and retention of full variable names (e.g., `ARGS:username`).
- Correct parsing of negated operator syntax (e.g., `@!streq`).
- Correct handling of quoted strings and unescaping internally.
- Full separation of Operator type and value.
- Proper chaining of multi-line rules ending with `chain`.

### Changed
- Updated RuleParser to correctly tokenize and parse real-world ModSecurity rule formats.
- Improved RuleSetParser logic for line continuations and chained rules.

---

## [2.0.1] - 2025-04-28

- Major overhaul of parser structure.
- Introduced Tokenizer for clean rule tokenization.
- Rebuilt RuleSetParser for multi-line chaining and CRS compatibility.
- Initial CLI interface added (`bin/modsec-parser`).
- Full PSR-4 Composer package layout established.

---

## [1.0.x] - Historical

- Initial basic proof-of-concept parser.
- Minimal single-line rule parsing.
