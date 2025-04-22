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
        $query = $input->hasArgument("query") ? $input->getArgumentValue("query") : null;
        if ($query === null) {
            throw new \LogicException("SQL query is required");
        }
        $io->title('Starting query on ' . $this->entityManager->createDatabasePlatform()->getDatabaseName());

        $data = $this->entityManager->getConnection()->fetchAll($query);
        $io->listKeyValues([
            'query' => $query,
            'rows' => count($data),
        ]);
        if ($data === []) {
            $io->info('The query yielded an empty result set.');
            return;
        }
        $io->table(array_keys($data[0]), $data);
    }
}
