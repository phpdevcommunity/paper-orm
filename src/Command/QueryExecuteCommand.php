<?php

namespace PhpDevCommunity\PaperORM\Command;

use PhpDevCommunity\Console\Argument\CommandArgument;
use PhpDevCommunity\Console\Command\CommandInterface;
use PhpDevCommunity\Console\InputInterface;
use PhpDevCommunity\Console\Output\ConsoleOutput;
use PhpDevCommunity\Console\OutputInterface;
use PhpDevCommunity\PaperORM\EntityManager;

class QueryExecuteCommand implements CommandInterface
{
    private EntityManager $entityManager;
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getName(): string
    {
       return 'paper:query:execute';
    }

    public function getDescription(): string
    {
        return 'Execute a SQL query';
    }

    public function getOptions(): array
    {
        return [];
    }

    public function getArguments(): array
    {
        return [
            new CommandArgument('query', true, null, 'The SQL query : select * from users')
        ];
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = ConsoleOutput::create($output);
        $query = $input->getOptionValue("query");
        if ($query === null) {
            throw new \LogicException("SQL query is required");
        }
        $data = $this->entityManager->getConnection()->fetchAll($query);
        if ($data === []) {
            $io->info('The query yielded an empty result set.');
            return;
        }

        $io->title('Database : ' . $this->entityManager->createDatabasePlatform()->getDatabaseName());
        $io->table(array_keys($data[0]), $data);
    }
}
