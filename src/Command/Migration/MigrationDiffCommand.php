<?php

namespace PhpDevCommunity\PaperORM\Command\Migration;

use PhpDevCommunity\Console\Command\CommandInterface;
use PhpDevCommunity\Console\InputInterface;
use PhpDevCommunity\Console\Option\CommandOption;
use PhpDevCommunity\Console\Output\ConsoleOutput;
use PhpDevCommunity\Console\OutputInterface;
use PhpDevCommunity\FileSystem\Tools\FileExplorer;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Migration\PaperMigration;
use PhpDevCommunity\PaperORM\Tools\EntityExplorer;

class MigrationDiffCommand implements CommandInterface
{
    private PaperMigration $paperMigration;
    private ?string $defaultEntitiesDir;

    public function __construct(PaperMigration $paperMigration, ?string $defaultEntitiesDir = null)
    {
        $this->paperMigration = $paperMigration;
        $this->defaultEntitiesDir = $defaultEntitiesDir;
    }

    public function getName(): string
    {
        return 'paper:migration:diff';
    }

    public function getDescription(): string
    {
        return 'Generate a migration diff for the SQL database';
    }

    public function getOptions(): array
    {
        return [
            new CommandOption('entities-dir', null, 'The directory where the entities are', false)
        ];
    }

    public function getArguments(): array
    {
        return [];
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = ConsoleOutput::create($output);

        $entitiesDir = $this->defaultEntitiesDir;
        $printOutput = $input->getOptionValue('verbose');
        if ($input->hasOption('entities-dir')) {
            $entitiesDir = $input->getOptionValue('entities-dir');
        }

        if ($entitiesDir === null) {
            throw new \LogicException('The --entities-dir option is required');
        }

        $platform = $this->paperMigration->getEntityManager()->getPlatform();

        $io->title('Starting migration diff on ' . $platform->getDatabaseName());
        $io->list([
            'Database : ' . $platform->getDatabaseName(),
            'Entities directory : ' . $entitiesDir
        ]);

        $entities = EntityExplorer::getEntities([$entitiesDir]);
        $io->title('Number of entities detected: ' . count($entities));
        $io->listKeyValues($entities);

        $file = $this->paperMigration->generateMigrationFromEntities($entities);
        if ($file === null) {
            $io->info('No migration file was generated — all entities are already in sync with the database schema.');
            return;
        }

        if ($printOutput === true) {
            $splFile = new \SplFileObject($file);
            $lines = [];
            while (!$splFile->eof()) {
                $lines[] = $splFile->fgets();
            }
            unset($splFile);
            $io->listKeyValues($lines);
        }

        $io->success('Migration file successfully generated: ' . $file);
    }

}
