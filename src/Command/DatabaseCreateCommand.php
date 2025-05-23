<?php

namespace PhpDevCommunity\PaperORM\Command;

use PhpDevCommunity\Console\Command\CommandInterface;
use PhpDevCommunity\Console\InputInterface;
use PhpDevCommunity\Console\Option\CommandOption;
use PhpDevCommunity\Console\Output\ConsoleOutput;
use PhpDevCommunity\Console\OutputInterface;
use PhpDevCommunity\PaperORM\EntityManager;

class DatabaseCreateCommand implements CommandInterface
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getName(): string
    {
        return 'paper:database:create';
    }

    public function getDescription(): string
    {
        return 'Create a new SQL database';
    }

    public function getOptions(): array
    {
        return [
            new CommandOption('if-not-exists', null, 'Create the SQL database only if it does not already exist', true)
        ];
    }

    public function getArguments(): array
    {
        return [];
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = ConsoleOutput::create($output);
        $platform = $this->entityManager->createDatabasePlatform();
        if ($input->hasOption('if-not-exists') && $input->getOptionValue('if-not-exists') === true) {
            $platform->createDatabaseIfNotExists();
            $io->info(sprintf('The SQL database "%s" has been successfully created (if it did not already exist).', $platform->getDatabaseName()));
        } else {
            $platform->createDatabase();
            $io->success(sprintf('The SQL database "%s" has been successfully created.', $platform->getDatabaseName()));
        }
    }
}
