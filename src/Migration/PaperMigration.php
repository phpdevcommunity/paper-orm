<?php

namespace PhpDevCommunity\PaperORM\Migration;

use DateTime;
use PDOException;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Generator\SchemaDiffGenerator;
use PhpDevCommunity\PaperORM\Mapper\ColumnMapper;
use PhpDevCommunity\PaperORM\Mapper\EntityMapper;
use PhpDevCommunity\PaperORM\Mapping\Column\DateTimeColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\PaperConnection;
use PhpDevCommunity\PaperORM\Platform\PlatformInterface;
use RuntimeException;
use function date;
use function file_get_contents;
use function file_put_contents;

final class PaperMigration
{

    /** @var EntityManager The EntityManager to use for migrations. */
    private EntityManager $em;
    private PlatformInterface $platform;
    private string $tableName;

    /** @var array<string> List of successfully migrated versions. */
    private array $successList = [];
    /**
     * @var MigrationDirectory
     */
    private MigrationDirectory $directory;

    public static function create(EntityManager $em, string $tableName, string $directory): self
    {
        return new self($em, $tableName, $directory);
    }

    /**
     * MigrateService constructor.
     * @param EntityManager $em
     * @param string $tableName
     * @param string $directory
     */
    private function __construct(EntityManager $em, string $tableName, string $directory)
    {
        $this->em = $em;
        $this->platform = $em->createDatabasePlatform();
        $this->tableName = $tableName;
        $this->directory = new MigrationDirectory($directory);
    }

    public function generateMigration(array $sqlUp = [], array $sqlDown = []): string
    {
        $i = 1;
        $file = date('YmdHis').$i . '.sql';
        $filename = $this->directory->getDir() . DIRECTORY_SEPARATOR . $file;
        while (file_exists($filename)) {
            $i++;
            $filename = rtrim($filename, ($i - 1).'.sql') . $i . '.sql';
        }

        $migrationContent = <<<'SQL'
-- UP MIGRATION --
%s
-- DOWN MIGRATION --
%s
SQL;
        foreach ($sqlUp as $key => $value) {
            $sqlUp[$key] = rtrim($value, ';') . ';';
        }

        foreach ($sqlDown as $key => $value) {
            $sqlDown[$key] = rtrim($value, ';') . ';';
        }

        if (empty($sqlUp)) {
            $sqlUp[] = '-- Write the SQL code corresponding to the up migration here';
            $sqlUp[] = '-- You can add the necessary SQL statements for updating the database';
        }
        if (empty($sqlDown)) {
            $sqlDown[] = '-- Write the SQL code corresponding to the down migration here';
            $sqlDown[] = '-- You can add the necessary SQL statements for reverting the up migration';
        }

        $migrationContent = sprintf($migrationContent, implode(PHP_EOL, $sqlUp), implode(PHP_EOL, $sqlDown));

        file_put_contents($filename, $migrationContent);
        return $filename;
    }

    public function migrate(): void
    {
        $this->createVersion();

        $this->successList = [];
        $versions = $this->getConnection()->fetchAll('SELECT version FROM ' . $this->tableName);
        foreach ($this->directory->getMigrations() as $version => $migration) {

            if (in_array($version, array_column($versions, 'version'))) {
                continue;
            }

            $this->up($version);
            $this->successList[] = $version;
        }
    }

    public function diffEntities(array $entities): ?string
    {
        $tables = [];
        foreach ($entities as $entity) {
            if (is_subclass_of($entity, EntityInterface::class)) {
                $tableName = EntityMapper::getTable($entity);
                $tables[$tableName] = [
                    'columns' => ColumnMapper::getColumns($entity),
                    'indexes' => [] // TODO IndexMapper::getIndexes($entity)
                ];
            }
        }
        return $this->diff($tables);
    }

    public function diff(array $tables): ?string
    {
        $statements = (new SchemaDiffGenerator($this->platform))->generateDiffStatements($tables);
        $sqlUp = $statements['up'];
        $sqlDown = $statements['down'];

        if (empty($sqlUp)) {
            return null;
        }

        return $this->generateMigration($sqlUp, $sqlDown);
    }

    public function up(string $version): void
    {
        $migration = $this->directory->getMigration($version);
        $pdo = $this->getConnection()->getPdo();
        try {
            $pdo->beginTransaction();
            $executedQueries = [];
            foreach (explode(';' . PHP_EOL, self::contentUp($migration)) as $query) {
                if (str_starts_with($query = trim($query), '--')) {
                    continue;
                }
                $query = rtrim($query, ';') . ';';
                $this->getConnection()->executeStatement($query);
                $executedQueries[] = $query;
            }
            if ($executedQueries === []) {
                throw new RuntimeException("Failed to execute any query for version : " . $version);
            }

            $createdAt = (new DateTime())->format($this->platform->getSchema()->getDateTimeFormatString());
            $rows = $this->getConnection()->executeStatement('INSERT INTO ' . $this->tableName . ' (version, created_at) VALUES (:version, :created_at)', ['version' => $version, 'created_at' => $createdAt]);
            if ($rows == 0) {
                throw new RuntimeException("Failed to execute insert for version : " . $version);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw new RuntimeException("Failed to migrate version $version : " . $e->getMessage());
        }
    }

    public function down(string $version): void
    {
        $migration = $this->directory->getMigration($version);
        $pdo = $this->getConnection()->getPdo();
        $currentQuery = '';
        try {
            $pdo->beginTransaction();
            foreach (explode(';' . PHP_EOL, self::contentDown($migration)) as $query) {
                $currentQuery = $query;
                $this->getConnection()->executeStatement(rtrim($query, ';') . ';');
            }
            $this->getConnection()->executeStatement('DELETE FROM ' . $this->tableName . ' WHERE version = :version', ['version' => $version]);

            $pdo->commit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            throw new RuntimeException(sprintf('Failed to migrate version %s : %s -> %s', $version, $e->getMessage(), $currentQuery));
        }

    }

    private function createVersion(): void
    {
        $this->platform->createTableIfNotExists($this->tableName, [
            (new StringColumn( null, 50))->bindProperty('version'),
            (new DateTimeColumn(null, 'created_at'))->bindProperty('created_at'),
        ]);
    }

    private function getConnection(): PaperConnection
    {
        return $this->em->getConnection();

    }

    private static function contentUp(string $migration): string
    {
        return trim(str_replace('-- UP MIGRATION --', '', self::content($migration)[0]));
    }

    private static function contentDown(string $migration): string
    {
        $downContent = self::content($migration)[1];
        return trim($downContent);
    }

    private static function content(string $migration): array
    {
        $migrationContent = file_get_contents($migration);
        $parts = explode('-- DOWN MIGRATION --', $migrationContent, 2);
        return [trim($parts[0]), (isset($parts[1]) ? trim($parts[1]) : '')];
    }

    public function getSuccessList(): array
    {
        return $this->successList;
    }

    public function getEntityManager(): EntityManager
    {
        return $this->em;
    }
}
