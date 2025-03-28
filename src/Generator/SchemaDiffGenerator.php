<?php

namespace PhpDevCommunity\PaperORM\Generator;

use LogicException;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Index;
use PhpDevCommunity\PaperORM\Platform\PlatformInterface;

final class SchemaDiffGenerator
{
    private PlatformInterface $platform;

    public function __construct(PlatformInterface $platform)
    {
        $this->platform = $platform;
    }

    public function generateDiffStatements(array $tables): array
    {
        $tablesExist = $this->platform->listTables();
        $schema = $this->platform->getSchema();
        $sqlUp = [];
        $sqlDown = [];
        foreach ($tables as $tableName => $tableData) {

            if (!isset($tableData['columns'])) {
                throw new LogicException(sprintf(
                    "Missing column definitions for table '%s'. Each table must have a 'columns' key with its column structure.",
                    $tableName
                ));
            }

            if (!isset($tableData['indexes'])) {
                throw new LogicException(sprintf(
                    "Missing index definitions for table '%s'. Ensure the 'indexes' key is set, even if empty, to maintain consistency.",
                    $tableName
                ));
            }

            /**
             * @var array<Column> $columns
             * @var array<Index> $indexes
             */
            $columns = $tableData['columns'];
            $indexes = $tableData['indexes'];

            if ($schema->supportsIndexes()) {
                foreach ($columns as $column) {
                    if (!$column->getIndex()) {
                        continue;
                    }
                    $indexes[] = $column->getIndex();
                }
            } else {
                $indexes = [];
            }

            $diff = $this->platform->diff($tableName, $columns, $indexes);
            $columnsToAdd = $diff->getColumnsToAdd();
            $columnsToUpdate = $diff->getColumnsToUpdate();
            $columnsToDelete = $diff->getColumnsToDelete();

            $indexesToAdd = $diff->getIndexesToAdd();
            $indexesToUpdate = $diff->getIndexesToUpdate();
            $indexesToDelete = $diff->getIndexesToDelete();

            if (!in_array($tableName, $tablesExist)) {
                $sqlUp[] = $schema->createTable($tableName, $columnsToAdd);
                foreach ($indexesToAdd as $index) {
                    $sqlUp[] = $schema->createIndex($index);
                    $sqlDown[] = $schema->dropIndex($index);
                }
                $sqlDown[] = $schema->dropTable($tableName);
                continue;
            }

            foreach ($columnsToAdd as $column) {
                $sqlUp[] = $schema->addColumn($tableName, $column);
                if ($schema->supportsDropColumn()) {
                    $sqlDown[] = $schema->dropColumn($tableName, $column);
                } else {
                    $sqlDown[] = sprintf(
                        '-- Drop column %s is not supported with %s. You might need to manually drop the column.',
                        $column->getName(),
                        get_class($schema)
                    );
                }
            }
            foreach ($indexesToAdd as $index) {
                $sqlUp[] = $schema->createIndex($index);
                $sqlDown[] = $schema->dropIndex($index);
            }

            foreach ($columnsToUpdate as $column) {
                if ($schema->supportsModifyColumn()) {
                    $sqlUp[] = $schema->modifyColumn($tableName, $column);
                    $sqlDown[] = $schema->modifyColumn($tableName, $diff->getOriginalColumn($column->getName()));
                } else {
                    $sqlUp[] = sprintf(
                        '-- Modify column %s is not supported with %s. Consider creating a new column and migrating the data.',
                        $column->getName(),
                        get_class($schema)
                    );
                }
            }

            foreach ($indexesToDelete as $index) {
                $sqlUp[] = $schema->dropIndex($index);
                $sqlDown[] = $schema->createIndex($diff->getOriginalIndex($index->getName()));
            }

            foreach ($columnsToDelete as $column) {
                if ($schema->supportsDropColumn()) {
                    $sqlUp[] = $schema->dropColumn($tableName, $column);
                    $sqlDown[] = $schema->addColumn($tableName, $diff->getOriginalColumn($column->getName()));
                } else {
                    $sqlUp[] = sprintf(
                        '-- Drop column %s is not supported with %s. Consider manually handling this operation.',
                        $column->getName(),
                        get_class($schema)
                    );
                }
            }

            foreach ($indexesToUpdate as $index) {
                $sqlUp[] = $schema->dropIndex($diff->getOriginalIndex($index->getName()));
                $sqlUp[] = $schema->createIndex($index);

                $sqlDown[] = $schema->dropIndex($index);
                $sqlDown[] = $schema->createIndex($diff->getOriginalIndex($index->getName()));
            }
        }


        return [
            'up' => $sqlUp,
            'down' => $sqlDown
        ];
    }

}
