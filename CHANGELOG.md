# Changelog

## [4.0.0] - 2024-06-07

### Changed
- **Parser output structure:** The parser now returns a **nested array of rule groups**. Each group is an array of one or more rules, preserving ModSecurity's chaining and grouping. This is a breaking change from the previous flat array output.
- **Chained rule grouping:** Chained rules are now grouped together in the output, with each group representing a main rule and its chained children.
- **Test suite updated:** Tests now recursively flatten the nested output structure to check for rule IDs and other assertions.
- **Documentation updated:** README and usage examples updated to reflect the new output structure and how to work with grouped rules.

### Added
- **Debugging utilities:** Improved debug output for rule IDs and line numbers to assist with troubleshooting and validation.
- **Explicit notes in documentation:** Added clear instructions and examples for flattening the nested rule array if a flat list is needed.

### Removed
- **Flat rule output:** The parser no longer returns a flat array of rules by default.

---

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
