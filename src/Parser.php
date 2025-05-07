<?php
namespace StardotHosting\SecRuleParser;

use Psr\Log\LoggerInterface;

class Parser
{
    private $logger = null;
    private $logToConsole = false;
    /** @var bool */
    private $debug = false;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setLogToConsole(bool $flag)
    {
        $this->logToConsole = $flag;
    }

    /**
     * Enable or disable debug output.
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
    }

    private function log($level, $message)
    {
        if ($this->logger) {
            $this->logger->log($level, $message);
        }
    }

    private function logToConsole($message)
    {
        if ($this->logToConsole) {
            fwrite(STDERR, $message . "\n");
        }
    }

    /**
     * Parse a .conf file of SecRules/SecAction/SecMarker directives.
     * Only files ending in .conf are processed (skips .conf.example).
     *
     * @param string $path
     * @return array
     * @throws \RuntimeException
     */
    public function parseFile(string $path): array
    {
        if (pathinfo($path, PATHINFO_EXTENSION) !== 'conf') {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Cannot read file: $path");
        }

        return $this->parse($content);
    }

    /**
     * Parse raw rules text.
     *
     * @param string $input
     * @return array
     */
    public function parse(string $input): array
    {
        // Normalize line endings
        $input = str_replace("\r\n", "\n", $input);

        // Handle line continuations (backslash at end of line)
        $lines = explode("\n", $input);
        $logicalLines = [];
        $currentLine = '';
        foreach ($lines as $rawLine) {
            $line = rtrim($rawLine, "\r\n");
            if ($line === '' && $currentLine === '') {
                continue;
            }
            if (substr($line, -1) === '\\') {
                // Remove the trailing backslash and join with next line
                $currentLine .= substr($line, 0, -1);
            } else {
                $currentLine .= $line;
                $logicalLines[] = $currentLine;
                $currentLine = '';
            }
        }

        $result = [];
        $buffer = [];
        $bufferStartLine = 0;
        foreach ($logicalLines as $lineno => $line) {
            if (preg_match('/^\s*SecRule\b/', $line) || preg_match('/^\s*SecAction\b/', $line) || preg_match('/^\s*SecMarker\b/', $line)) {
                if ($buffer) {
                    $this->parseSecRuleBuffer($buffer, $bufferStartLine, $result);
                    $buffer = [];
                }
                $bufferStartLine = $lineno;
                $buffer[] = $line;
            } elseif (preg_match('/^\s+/', $line) && $buffer) {
                // This is a chained child (indented)
                $buffer[] = $line;
            } else {
                // Not a rule or child, flush buffer if needed
                if ($buffer) {
                    $this->parseSecRuleBuffer($buffer, $bufferStartLine, $result);
                    $buffer = [];
                }
            }
        }
        if ($buffer) {
            $this->parseSecRuleBuffer($buffer, $bufferStartLine, $result);
        }

        $flat = [];
        foreach ($result as $group) {
            foreach ($group as $rule) {
                if (isset($rule['id'])) {
                    $flat[] = $rule;
                }
            }
        }
        // Now $flat contains all rules with IDs, including chain children

        return $result;
    }

    /**
     * Parse a buffered SecRule (including chains).
     * Emits each rule (parent and chained) as a separate object.
     */
    private function parseSecRuleBuffer(array $buffer, int $start_lineno, array &$result)
    {
        // Join all buffer lines to search for id robustly
        $joined = implode(' ', $buffer);

        // More robust id search: allow for quotes, whitespace, comma, or end of string after id
        if (preg_match('/id\s*:\s*["\']?([0-9]+)["\']?(?=\s*,|\s|$)/i', $joined, $m)) {
            $id = $m[1];
        } else {
            $this->log('debug', "SKIPPED $start_lineno: {$buffer[0]} [reason: no id]");
            return;
        }

        $group = [];
        foreach ($buffer as $idx => $line) {
            $lineno = $start_lineno + $idx;
            $parsed = $this->parseSecRuleLine($line, $lineno);
            if ($parsed) {
                if ($idx === 0) {
                    $parsed['id'] = $id;
                }
                $group[] = $parsed;
                $this->log('debug', "PARSED $lineno: $line [id:" . ($parsed['id'] ?? '-') . "]");
            }
        }
        if ($group) {
            $result[] = $group;
        }
    }

    /**
     * Parse a single SecRule line (parent or chained).
     * Returns a rule array or null.
     */
    private function parseSecRuleLine(string $line, int $lineno)
    {
        if (preg_match('/^\s*SecRule\b/', $line)) {
            return [
                'type' => 'SecRule',
                'lineno' => $lineno,
                'raw' => $line,
            ];
        }
        if (preg_match('/^\s*SecAction\b/', $line)) {
            return [
                'type' => 'SecAction',
                'lineno' => $lineno,
                'raw' => $line,
            ];
        }
        // Handle chained child: line does not start with SecRule/SecAction, but is not empty/comment
        if (preg_match('/^\s*[@"]/', $line)) {
            return [
                'type' => 'SecRuleChainChild',
                'lineno' => $lineno,
                'raw' => $line,
            ];
        }
        return null;
    }

    /**
     * Strip comments from a line, but ignore '#' inside quotes.
     *
     * @param string $line
     * @return string
     */
    private function stripComments(string $line): string
    {
        $out = '';
        $inq = null;
        $len = strlen($line);

        for ($i = 0; $i < $len; $i++) {
            $c = $line[$i];
            if ($c === '"' || $c === "'") {
                // Toggle in‐quote state
                if ($inq === $c) {
                    $inq = null;
                } elseif ($inq === null) {
                    $inq = $c;
                }
                $out .= $c;
            } elseif ($c === '#' && $inq === null) {
                // Real comment begins here
                break;
            } else {
                $out .= $c;
            }
        }

        return $out;
    }

    /**
     * Group parent rules with their chained children.
     *
     * @param array $rules
     * @return array
     */
    public function groupChainedRules(array $rules): array
    {
        $groups = [];
        $i = 0;
        $count = count($rules);

        while ($i < $count) {
            $rule = $rules[$i];

            // If this rule is a chain parent
            if (isset($rule['raw']) && strpos($rule['raw'], 'chain') !== false) {
                $group = [];
                $group[] = $rule;
                $i++;

                // Add all subsequent rules that are children of this chain
                while ($i < $count) {
                    $child = $rules[$i];

                    // A child rule is typically indented (starts with whitespace)
                    // or does not have 'chain' in its raw (but is part of the chain)
                    if (
                        isset($child['raw']) &&
                        (preg_match('/^\s+/', $child['raw']) || !preg_match('/SecRule|SecAction/', $child['raw']))
                    ) {
                        $group[] = $child;
                        $i++;
                    } else {
                        // Next rule is not a child, break
                        break;
                    }
                }
                $groups[] = $group;
            } else {
                // Not a chain parent, just add as its own group
                $groups[] = [$rule];
                $i++;
            }
        }

        return $groups;
    }

    public function parseString(string $rules)
    {
        // If your parser already has a method that parses file contents,
        // just call it here. For example, if you have parse($string):
        return $this->parse($rules);

        // If not, you may need to refactor your code so that both
        // parseFile() and parseString() use the same underlying logic.
    }
}
