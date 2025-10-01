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
        $printOutput = $input->getOptionValue('output');
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

        $explorer = new FileExplorer($entitiesDir);
        $files = $explorer->searchByExtension('php', true);
        $entities = [];
        foreach ($files as $file) {
            $entityClass = self::extractNamespaceAndClass($file['path']);
            if ($entityClass !== null && class_exists($entityClass) && is_subclass_of($entityClass, EntityInterface::class)) {
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

    private static function extractNamespaceAndClass(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('File not found: ' . $filePath);
        }

        $contents = file_get_contents($filePath);
        $namespace = '';
        $class = '';
        $isExtractingNamespace = false;
        $isExtractingClass = false;

        foreach (token_get_all($contents) as $token) {
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $isExtractingNamespace = true;
            }

            if (is_array($token) && $token[0] == T_CLASS) {
                $isExtractingClass = true;
            }

            if ($isExtractingNamespace) {
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR,  265 /* T_NAME_QUALIFIED For PHP 8*/])) {
                    $namespace .= $token[1];
                } else if ($token === ';') {
                    $isExtractingNamespace = false;
                }
            }

            if ($isExtractingClass) {
                if (is_array($token) && $token[0] == T_STRING) {
                    $class = $token[1];
                    break;
                }
            }
        }

        if (empty($class)) {
            return null;
        }

        $fullClass = $namespace ? $namespace . '\\' . $class : $class;
        if (class_exists($fullClass) && is_subclass_of($fullClass, EntityInterface::class)) {
            return $fullClass;
        }

        return null;
    }
}
