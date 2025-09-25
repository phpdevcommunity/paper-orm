<?php

namespace PhpDevCommunity\PaperORM\Platform;

use InvalidArgumentException;
use LogicException;
use PhpDevCommunity\PaperORM\Mapping\Column\BoolColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\DateColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\DateTimeColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\DecimalColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\FloatColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\IntColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JsonColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\TextColumn;
use PhpDevCommunity\PaperORM\Metadata\ColumnMetadata;
use PhpDevCommunity\PaperORM\Metadata\ForeignKeyMetadata;
use PhpDevCommunity\PaperORM\Metadata\IndexMetadata;
use PhpDevCommunity\PaperORM\PaperConnection;
use PhpDevCommunity\PaperORM\Parser\SQLTypeParser;
use PhpDevCommunity\PaperORM\Schema\SchemaInterface;
use PhpDevCommunity\PaperORM\Schema\SqliteSchema;
use RuntimeException;

class SqlitePlatform extends AbstractPlatform
{
    private PaperConnection $connection;
    private SqliteSchema $schema;

    public function __construct(PaperConnection $connection, SqliteSchema $schema)
    {
        $this->connection = $connection;
        $this->schema = $schema;
    }

    public function getDatabaseName(): string
    {
        $memory = $this->connection->getParams()['memory'] ?? false;
        if ($memory) {
            return ':memory:';
        }
        return $this->connection->getParams()['path'] ?? '';
    }

    public function listTables(): array
    {
        $rows = $this->connection->fetchAll($this->schema->showTables());
        $tables = [];
        foreach ($rows as $row) {
            $tables[] = $row['name'];
        }
        return $tables;
    }

    /**
     * @param string $tableName
     * @return array<ColumnMetadata>
     */
    public function listTableColumns(string $tableName): array
    {
        $rows = $this->connection->fetchAll($this->schema->showTableColumns($tableName));
        $foreignKeys = $this->connection->fetchAll($this->schema->showForeignKeys($tableName));
        $columns = [];
        foreach ($rows as $row) {
            $foreignKeyMetadata = null;
            foreach ($foreignKeys as $foreignKey) {
                if ($row['name'] == $foreignKey['from']) {
                    $foreignKeyMetadata = ForeignKeyMetadata::fromArray([
                        'name' => $this->generateForeignKeyName($tableName, [$row['name']]),
                        'columns' => [$row['name']],
                        'referenceTable' => $foreignKey['table'],
                        'referenceColumns' => [$foreignKey['to']],
                        'onDelete' => $this->convertForeignKeyRuleStringToCode($foreignKey['on_delete']),
                        'onUpdate' => $this->convertForeignKeyRuleStringToCode($foreignKey['on_update']),
                    ]);
                    break;
                }
            }
            $columnMetadata = ColumnMetadata::fromArray([
                'name' => $row['name'],
                'type' => SQLTypeParser::getBaseType($row['type']),
                'primary' => boolval($row['pk']) == true,
                'foreignKeyMetadata' => $foreignKeyMetadata,
                'null' => boolval($row['notnull']) == false,
                'default' => $row['dflt_value'] ?? null,
                'comment' => $row['comment'] ?? null,
                'attributes' => SQLTypeParser::extractTypedParameters($row['type']),
            ]);
            $columns[] = $columnMetadata;
        }
        return $columns;
    }

    /**
     * @param string $tableName
     * @return array<IndexMetadata>
     */
    public function listTableIndexes(string $tableName): array
    {
        $indexes = $this->connection->fetchAll($this->schema->showTableIndexes($tableName));
        $indexesFormatted = [];
        foreach ($indexes as $index) {
            $info = $this->connection->fetchAll(sprintf("PRAGMA index_info('%s')", $index['name']));
            $indexesFormatted[] = IndexMetadata::fromArray([
                'tableName' => $tableName,
                'name' => $index['name'],
                'columns' => array_column($info, 'name'),
                'unique' => $index['unique'],
            ]);
        }
        return $indexesFormatted;
    }

    public function listDatabases(): array
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the platform interface.", __METHOD__));
    }

    public function createDatabase(): void
    {
        $database = $this->getDatabaseName();
        if ($database == ':memory:') {
            return;
        }

        if (empty($database)) {
            throw new RuntimeException(sprintf("The database name cannot be empty. %s::createDatabase()", __CLASS__));
        }

        $databaseFile = pathinfo($database);
        if (empty($databaseFile['extension'])) {
            throw new RuntimeException(sprintf("The database name '%s' must have an extension.", $database));
        }

        if (file_exists($database)) {
            throw new LogicException(sprintf("The database '%s' already exists.", $database));
        }

        touch($database);
        chmod($database, 0666);
    }

    public function createDatabaseIfNotExists(): void
    {
        try {
            $this->createDatabase();
        } catch (LogicException $e) {
            return;
        }
    }

    public function dropDatabase(): void
    {
        $database = $this->getDatabaseName();
        if (!file_exists($database)) {
            return;
        }

        unlink($database);
    }

    public function createTable(string $tableName, array $columns): int
    {
        return $this->connection->executeStatement($this->schema->createTable($tableName, $this->mapColumnsToMetadata($tableName, $columns)));
    }

    public function createTableIfNotExists(string $tableName, array $columns, array $options = []): int
    {
        return $this->connection->executeStatement($this->schema->createTableIfNotExists($tableName, $this->mapColumnsToMetadata($tableName, $columns)));
    }

    public function dropTable(string $tableName): int
    {
        return $this->connection->executeStatement($this->schema->dropTable($tableName));
    }

    public function addColumn(string $tableName, Column $column): int
    {
        return $this->connection->executeStatement($this->schema->addColumn($tableName, $this->mapColumnToMetadata($tableName, $column)));
    }

    public function dropColumn(string $tableName, Column $column): int
    {
        return $this->connection->executeStatement($this->schema->dropColumn($tableName, $this->mapColumnToMetadata($tableName, $column)));
    }

    public function renameColumn(string $tableName, string $oldColumnName, string $newColumnName): int
    {
        return $this->connection->executeStatement($this->schema->renameColumn($tableName, $oldColumnName, $newColumnName));
    }

    public function createIndex(IndexMetadata $indexMetadata): int
    {
        return $this->connection->executeStatement($this->schema->createIndex($indexMetadata));
    }

    public function dropIndex(IndexMetadata $indexMetadata): int
    {
        return $this->connection->executeStatement($this->schema->dropIndex($indexMetadata));
    }

    public function createForeignKeyConstraint(string $tableName, ForeignKeyMetadata $foreignKey): int
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the platform interface.", __METHOD__));
    }

    public function dropForeignKeyConstraints(string $tableName, string $foreignKeyName): int
    {
        throw new LogicException(sprintf("The method '%s' is not supported by the platform interface.", __METHOD__));
    }

    public function getMaxLength(): int
    {
        return 30;
    }

    public function getPrefixIndexName(): string
    {
        return 'ix_';
    }

    public function getPrefixForeignKeyName(): string
    {
        return 'fk_';
    }

    public function getColumnTypeMappings(): array
    {
        return [
            PrimaryKeyColumn::class => [
                'type' => 'INTEGER',
                'args' => [],
            ],
            IntColumn::class => [
                'type' => 'INTEGER',
                'args' => [],
            ],
            JoinColumn::class => [
                'type' => 'INTEGER',
                'args' => [],
            ],
            DecimalColumn::class => [
                'type' => 'DECIMAL',
                'args' => [10, 5],
            ],
            FloatColumn::class => [
                'type' => 'FLOAT',
                'args' => [],
            ],
            DateColumn::class => [
                'type' => 'DATE',
                'args' => [],
            ],
            DateTimeColumn::class => [
                'type' => 'DATETIME',
                'args' => [],
            ],
            BoolColumn::class => [
                'type' => 'BOOLEAN',
                'args' => [],
            ],
            TextColumn::class => [
                'type' => 'TEXT',
                'args' => [],
            ],
            JsonColumn::class => [
                'type' => 'JSON',
                'args' => [],
            ],
            StringColumn::class => [
                'type' => 'VARCHAR',
                'args' => [255],
            ],
        ];
    }

    public function convertForeignKeyRuleStringToCode(?string $rule): int
    {
        $rule = strtoupper($rule);
        switch ($rule) {
            case 'CASCADE':
                return ForeignKeyMetadata::CASCADE;
            case 'SET NULL':
                return ForeignKeyMetadata::SET_NULL;
            case 'SET DEFAULT':
                return ForeignKeyMetadata::SET_DEFAULT;
            case 'RESTRICT':
                return ForeignKeyMetadata::RESTRICT;
            case 'NO ACTION':   /* fall-through */
            default:
                return ForeignKeyMetadata::NO_ACTION;
        }
    }

    public function getSchema(): SchemaInterface
    {
        return $this->schema;
    }

    public function supportsTransactionalDDL(): bool
    {
       return true;
    }
}
