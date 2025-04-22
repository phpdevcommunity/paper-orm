<?php

namespace PhpDevCommunity\PaperORM\Command;

use PhpDevCommunity\Console\Command\CommandInterface;
use PhpDevCommunity\Console\InputInterface;
use PhpDevCommunity\Console\Option\CommandOption;
use PhpDevCommunity\Console\Output\ConsoleOutput;
use PhpDevCommunity\Console\OutputInterface;
use PhpDevCommunity\FileSystem\Tools\FileExplorer;
use PhpDevCommunity\PaperORM\Migration\PaperMigration;

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
            new CommandOption('entities-dir', null, 'The directory where the entities are', false),
            new CommandOption('output', 'o', 'The output file', true)
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
        $output = $input->hasOption('output');
        if ($input->hasOption('entities-dir')) {
            $entitiesDir = $input->getOptionValue('entities-dir');
        }

        if ($entitiesDir === null) {
            throw new \LogicException('The --entities-dir option is required');
        }

        $platform = $this->paperMigration->getEntityManager()->createDatabasePlatform();

        $io->title('Starting migration diff on ' . $platform->getDatabaseName());
        $io->list([
            'Database : ' . $platform->getDatabaseName(),
            'Entities directory : ' . $entitiesDir
        ]);

        $explorer = new FileExplorer($entitiesDir);
        $files = $explorer->searchByExtension('php', true);
        $entities = [];
        foreach ($files as $file) {
            $entityClass = self::getFullClassName($file['path']);
            if ($entityClass !== null) {
                $entities[$file['path']] = $entityClass;
            }
        }

        $io->title('Number of entities detected: ' . count($entities));
        $io->listKeyValues($entities);

        $file = $this->paperMigration->diffEntities($entities);
        if ($file === null) {
            $io->info('No migration file was generated — all entities are already in sync with the database schema.');
            return;
        }

        if ($output === true) {
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

    private static function getFullClassName($file): ?string
    {
        $content = file_get_contents($file);
        $tokens = token_get_all($content);
        $namespace = $className = '';

        foreach ($tokens as $i => $token) {
            if ($token[0] === T_NAMESPACE) {
                for ($j = $i + 1; isset($tokens[$j]); $j++) {
                    if ($tokens[$j] === ';') break;
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NS_SEPARATOR])) {
                        $namespace .= $tokens[$j][1];
                    }
                }
            }

            if ($token[0] === T_CLASS && isset($tokens[$i + 2][1])) {
                $className = $tokens[$i + 2][1];
                break;
            }
        }
        if (empty($className)) {
            return null;
        }

        return trim($namespace . '\\' . $className, '\\');
    }
}
