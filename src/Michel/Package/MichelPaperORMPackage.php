<?php

namespace PhpDevCommunity\PaperORM\Michel\Package;

use LogicException;
use PhpDevCommunity\Michel\Package\PackageInterface;
use PhpDevCommunity\PaperORM\Command\DatabaseCreateCommand;
use PhpDevCommunity\PaperORM\Command\DatabaseDropCommand;
use PhpDevCommunity\PaperORM\Command\MigrationDiffCommand;
use PhpDevCommunity\PaperORM\Command\MigrationMigrateCommand;
use PhpDevCommunity\PaperORM\Command\QueryExecuteCommand;
use PhpDevCommunity\PaperORM\Command\ShowTablesCommand;
use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\EntityManagerInterface;
use PhpDevCommunity\PaperORM\Migration\PaperMigration;
use PhpDevCommunity\PaperORM\Parser\DSNParser;
use Psr\Container\ContainerInterface;

class MichelPaperORMPackage implements PackageInterface
{
    public function getDefinitions(): array
    {
        return [
            EntityManagerInterface::class => static function (ContainerInterface $container) {
                return $container->get(EntityManager::class);
            },
            EntityManager::class => static function (ContainerInterface $container) {
                $dsn = $container->get('database.dsn');
                if (!is_string($dsn) || empty($dsn)) {
                    throw new LogicException('Database DSN not found, please set DATABASE_DSN in .env file or database.dsn in config');
                }
                $params = DSNParser::parse($container->get('database.dsn'));
                return new EntityManager($params);
            },
            PaperMigration::class => static function (ContainerInterface $container) {
                return PaperMigration::create(
                    $container->get(EntityManagerInterface::class),
                    $container->get('paper.migration.table'),
                    $container->get('paper.migration.dir')
                );
            },
            MigrationDiffCommand::class => static function (ContainerInterface $container) {
                return new MigrationDiffCommand($container->get(PaperMigration::class), $container->get('paper.entity.dir'));
            },
            DatabaseDropCommand::class => static function (ContainerInterface $container) {
                return new DatabaseDropCommand($container->get(EntityManagerInterface::class), $container->get('michel.environment'));
            }
        ];
    }

    public function getParameters(): array
    {
        return [
            'database.dsn' => getenv('DATABASE_DSN') ?? '',
            'paper.migration.dir' => getenv('PAPER_MIGRATION_DIR') ?: function (ContainerInterface $container) {
                $folder = $container->get('michel.project_dir') . DIRECTORY_SEPARATOR . 'migrations';
                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }
                return $folder;
            },
            'paper.migration.table' => getenv('PAPER_MIGRATION_TABLE') ?: 'mig_versions',
            'paper.entity.dir' => getenv('PAPER_ENTITY_DIR') ?: function (ContainerInterface $container) {
                $folder = $container->get('michel.project_dir') . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Entity';
                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }
                return $folder;
            },
        ];
    }

    public function getRoutes(): array
    {
        return [];
    }

    public function getListeners(): array
    {
        return [];
    }

    public function getCommands(): array
    {
        return [
            DatabaseCreateCommand::class,
            DatabaseDropCommand::class,
            MigrationDiffCommand::class,
            MigrationMigrateCommand::class,
            QueryExecuteCommand::class,
            ShowTablesCommand::class,
        ];
    }
}
