<?php

namespace ModSecurity\CLI;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ModSecurity\Parser\RuleSetParser;

/**
 * Symfony Console Command to parse ModSecurity rules from a file or folder.
 */
class ParseRulesCommand extends Command
{
    /**
     * The default command name ("parse").
     * @var string
     */
    protected static $defaultName = 'parse';

    /**
     * Configures the command options and description.
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Parse ModSecurity rules from a file or folder')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to a .conf file')
            ->addOption('folder', null, InputOption::VALUE_REQUIRED, 'Path to a folder containing .conf files');
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getOption('file');
        $folder = $input->getOption('folder');

        // Ensure at least one option is provided
        if (!$file && !$folder) {
            $output->writeln('<error>You must specify either --file or --folder option.</error>');
            return Command::FAILURE;
        }

        $parser = new RuleSetParser();
        $allRules = [];

        // Parse a single file if specified
        if ($file) {
            if (!file_exists($file)) {
                $output->writeln("<error>File not found: $file</error>");
                return Command::FAILURE;
            }
            $rules = $this->parseFile($parser, $file);
            $allRules = array_merge($allRules, $rules);
        }

        // Parse all .conf files in a folder if specified
        if ($folder) {
            if (!is_dir($folder)) {
                $output->writeln("<error>Folder not found: $folder</error>");
                return Command::FAILURE;
            }
            $rules = $this->parseFolder($parser, $folder);
            $allRules = array_merge($allRules, $rules);
        }

        // Output the parsed rules as pretty-printed JSON
        $output->writeln(json_encode(array_map(fn($r) => $r->toArray(), $allRules), JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }

    /**
     * Parse rules from a single file.
     *
     * @param RuleSetParser $parser
     * @param string $filePath
     * @return array Parsed rules
     */
    private function parseFile(RuleSetParser $parser, string $filePath): array
    {
        $content = file_get_contents($filePath);
        return $parser->parseRules($content);
    }

    /**
     * Parse rules from all .conf files in a folder (recursively).
     *
     * @param RuleSetParser $parser
     * @param string $folderPath
     * @return array Parsed rules
     */
    private function parseFolder(RuleSetParser $parser, string $folderPath): array
    {
        $rules = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'conf') {
                $fileRules = $this->parseFile($parser, $file->getPathname());
                $rules = array_merge($rules, $fileRules);
            }
        }

        return $rules;
    }
}
