<?php

namespace PhpDevCommunity\PaperORM\Command;

use PhpDevCommunity\Console\Command\CommandInterface;
use PhpDevCommunity\Console\InputInterface;
use PhpDevCommunity\Console\Option\CommandOption;
use PhpDevCommunity\Console\Output\ConsoleOutput;
use PhpDevCommunity\Console\OutputInterface;
use PhpDevCommunity\PaperORM\Migration\PaperMigration;
use PhpDevCommunity\PaperORM\Tools\EntityExplorer;

class DatabaseSyncCommand implements CommandInterface
{

    private PaperMigration $paperMigration;

    private ?string $env;

    private string $entityDir;

    /**
     * @param PaperMigration $paperMigration
     * @param string $entityDir
     * @param string|null $env
     */
    public function __construct(PaperMigration $paperMigration, string $entityDir, ?string $env = null)
    {
        $this->paperMigration = $paperMigration;
        $this->env = $env;
        $this->entityDir = $entityDir;
    }

    public function getName(): string
    {
        return 'paper:database:sync';
    }

    public function getDescription(): string
    {
        return 'Update the SQL database structure so it matches the current ORM entities.';
    }

    public function getOptions(): array
    {
        return [
            new CommandOption('no-execute', 'n', 'Show the generated SQL statements without executing them.', true)
        ];
    }

    public function getArguments(): array
    {
        return [];
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = ConsoleOutput::create($output);
        if (!$this->isEnabled()) {
            throw new \LogicException('This command is only available in `dev` environment.');
        }

        $noExecute = $input->getOptionValue('no-execute');
        $platform = $this->paperMigration->getEntityManager()->getPlatform();

        $io->title('Starting database sync on ' . $platform->getDatabaseName());
        $io->list([
            'Database : ' . $platform->getDatabaseName(),
            'Entities directory : ' . $this->entityDir
        ]);

        $entities = EntityExplorer::getEntities($this->entityDir);
        $io->title('Number of entities detected: ' . count($entities));
        $io->listKeyValues($entities);

        $updates = $this->paperMigration->getSqlDiffFromEntities($entities);
        if (empty($updates)) {
            $io->info('No differences detected — all entities are already in sync with the database schema.');
            return;
        }

        $count = count($updates);
        $io->writeln("📘 Database synchronization plan");
        $io->writeln("{$count} SQL statements will be executed:");
        $io->writeln("");
        $io->numberedList($updates);
        if ($noExecute) {
            $io->info('Preview mode only — SQL statements were displayed but NOT executed.');
            return;
        }

        $io->writeln("");
        $io->writeln("🚀 Applying changes to database...");
        $conn = $this->paperMigration->getEntityManager()->getConnection();
        foreach ($updates as $sql) {
            $conn->executeStatement($sql);
            $io->writeln("✔ Executed: {$sql}");
        }

        $io->success("Database successfully synchronized.");
    }

    private function isEnabled(): bool
    {
        return 'dev' === $this->env || 'test' === $this->env;
    }
}
