<?php

namespace ModSecurity\CLI;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ModSecurity\Parser\RuleSetParser;

class ParseRulesCommand extends Command
{
    protected static $defaultName = 'parse';

    protected function configure(): void
    {
        $this
            ->setDescription('Parse ModSecurity rules from a file or folder')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to a .conf file')
            ->addOption('folder', null, InputOption::VALUE_REQUIRED, 'Path to a folder containing .conf files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getOption('file');
        $folder = $input->getOption('folder');

        if (!$file && !$folder) {
            $output->writeln('<error>You must specify either --file or --folder option.</error>');
            return Command::FAILURE;
        }

        $parser = new RuleSetParser();
        $allRules = [];

        if ($file) {
            if (!file_exists($file)) {
                $output->writeln("<error>File not found: $file</error>");
                return Command::FAILURE;
            }
            $rules = $this->parseFile($parser, $file);
            $allRules = array_merge($allRules, $rules);
        }

        if ($folder) {
            if (!is_dir($folder)) {
                $output->writeln("<error>Folder not found: $folder</error>");
                return Command::FAILURE;
            }
            $rules = $this->parseFolder($parser, $folder);
            $allRules = array_merge($allRules, $rules);
        }

        $output->writeln(json_encode(array_map(fn($r) => $r->toArray(), $allRules), JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }

    private function parseFile(RuleSetParser $parser, string $filePath): array
    {
        $content = file_get_contents($filePath);
        return $parser->parseRules($content);
    }

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
