<?php
namespace StardotHosting\SecRuleParser;

use Psr\Log\LoggerInterface;

class Parser
{
    private $logger = null;
    private $logToConsole = false;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setLogToConsole(bool $flag)
    {
        $this->logToConsole = $flag;
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

        // echo "LOGICAL LINE $i: $rawLine\n";

        $result = [];
        $buffer = [];
        $start_lineno = 0;
        foreach ($logicalLines as $lineno => $line) {
            // Start of a rule
            if (preg_match('/^\s*SecRule\b/i', $line) || preg_match('/^\s*SecAction\b/i', $line)) {
                if (!empty($buffer)) {
                    $this->parseSecRuleBuffer($buffer, $start_lineno, $result);
                    $buffer = [];
                }
                $start_lineno = $lineno;
            }
            // Add line to buffer
            $buffer[] = $line;

            // If the line ends with a double quote (and not a backslash-escaped one), it's the end of the rule
            if (preg_match('/"\s*$/', rtrim($line))) {
                $this->parseSecRuleBuffer($buffer, $start_lineno, $result);
                $buffer = [];
            }
        }
        // Flush any remaining buffer
        if (!empty($buffer)) {
            $this->parseSecRuleBuffer($buffer, $start_lineno, $result);
        }
        return $result;
    }

    /**
     * Parse a buffered SecRule (including chains).
     * Emits each rule (parent and chained) as a separate object.
     */
    private function parseSecRuleBuffer(array $buffer, int $start_lineno, array &$result)
    {
        // All file logging removed

        // Join all buffer lines to search for id robustly
        $joined = implode(' ', $buffer);

        // More robust id search: allow for quotes, whitespace, comma, or end of string after id
        if (preg_match('/id\s*:\s*["\']?([0-9]+)["\']?(?=\s*,|\s|$)/i', $joined, $m)) {
            $id = $m[1];
        } else {
            $this->log('debug', "SKIPPED $start_lineno: {$buffer[0]} [reason: no id]");
            return;
        }

        foreach ($buffer as $idx => $line) {
            $lineno = $start_lineno + $idx;

            $parsed = $this->parseSecRuleLine($line, $lineno);
            if ($parsed) {
                $parsed['id'] = $id;
                $result[] = $parsed;
                $this->log('debug', "PARSED $lineno: $line [id:$id]");
            }
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
}
