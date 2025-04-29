<?php

namespace Stardothosting\ModSecurity\CLI;

use Stardothosting\ModSecurity\Parser\RuleSetParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseRulesCommand extends Command
{
    protected static $defaultName = 'parse';

    protected function configure()
    {
        $this
            ->setDescription('Parse ModSecurity rules from a file or folder')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to .conf file')
            ->addOption('folder', null, InputOption::VALUE_REQUIRED, 'Path to folder of .conf files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $parser = new RuleSetParser();

        if ($file = $input->getOption('file')) {
            if (!file_exists($file)) {
                $output->writeln("<error>File not found: $file</error>");
                return Command::FAILURE;
            }

            $rules = $parser->parseRules(file_get_contents($file));
            $output->writeln(json_encode(array_map(fn($r) => $r->toArray(), $rules), JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        if ($folder = $input->getOption('folder')) {
            if (!is_dir($folder)) {
                $output->writeln("<error>Folder not found: $folder</error>");
                return Command::FAILURE;
            }

            $allRules = [];
            $files = glob($folder . '/*.conf');

            foreach ($files as $file) {
                $content = file_get_contents($file);
                $parsed = $parser->parseRules($content);
                $allRules[$file] = array_map(fn($r) => $r->toArray(), $parsed);
            }

            $output->writeln(json_encode($allRules, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $output->writeln('<error>You must specify --file or --folder</error>');
        return Command::FAILURE;
    }
}
