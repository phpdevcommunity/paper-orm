<?php

namespace PhpDevCommunity\PaperORM\Michel\Package;

use PhpDevCommunity\Michel\Package\PackageInterface;
use PhpDevCommunity\PaperORM\Collector\EntityDirCollector;
use PhpDevCommunity\PaperORM\Command\DatabaseCreateCommand;
use PhpDevCommunity\PaperORM\Command\DatabaseDropCommand;
use PhpDevCommunity\PaperORM\Command\DatabaseSyncCommand;
use PhpDevCommunity\PaperORM\Command\Migration\MigrationDiffCommand;
use PhpDevCommunity\PaperORM\Command\Migration\MigrationMigrateCommand;
use PhpDevCommunity\PaperORM\Command\QueryExecuteCommand;
use PhpDevCommunity\PaperORM\Command\ShowTablesCommand;
use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\EntityManagerInterface;
use PhpDevCommunity\PaperORM\Migration\PaperMigration;
use PhpDevCommunity\PaperORM\PaperConfiguration;
use Psr\Container\ContainerInterface;

class MichelPaperORMPackage implements PackageInterface
{
    public function getDefinitions(): array
    {
        return [
            PaperConfiguration::class => static function (ContainerInterface $container) {
                return PaperConfiguration::fromDsn(
                    $container->get('paper.orm.dsn'),
                    $container->get('paper.orm.debug')
                )
                    ->withLogger($container->get('paper.orm.logger'));
            },
            EntityDirCollector::class => static function (ContainerInterface $container) {
                return EntityDirCollector::bootstrap([$container->get('paper.entity_dir')]);
            },
            EntityManagerInterface::class => static function (ContainerInterface $container) {
                return $container->get(EntityManager::class);
            },
            EntityManager::class => static function (ContainerInterface $container) {
                return EntityManager::createFromConfig($container->get(PaperConfiguration::class));
            },
            PaperMigration::class => static function (ContainerInterface $container) {
                return PaperMigration::create(
                    $container->get(EntityManagerInterface::class),
                    $container->get('paper.orm.migrations_table'),
                    $container->get('paper.orm.migrations_dir')
                );
            },
            MigrationDiffCommand::class => static function (ContainerInterface $container) {
                return new MigrationDiffCommand($container->get(PaperMigration::class), $container->get(EntityDirCollector::class));
            },
            DatabaseDropCommand::class => static function (ContainerInterface $container) {
                return new DatabaseDropCommand($container->get(EntityManagerInterface::class), $container->get('michel.environment'));
            },
            DatabaseSyncCommand::class => static function (ContainerInterface $container) {
                return new DatabaseSyncCommand($container->get(PaperMigration::class), $container->get(EntityDirCollector::class), $container->get('michel.environment'));
            }
        ];
    }

    public function getParameters(): array
    {
        return [
            'paper.orm.dsn' => getenv('DATABASE_URL') ?? '',
            'paper.orm.debug' => static function (ContainerInterface $container) {
                return $container->get('michel.debug');
            },
            'paper.orm.logger' => null,
            'paper.orm.entity_dir' => getenv('PAPER_ORM_ENTITY_DIR') ?: static function (ContainerInterface $container) {
                $folder = $container->get('michel.project_dir') . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Entity';
                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }
                return $folder;
            },
            'paper.orm.migrations_dir' => getenv('PAPER_ORM_MIGRATIONS_DIR') ?: static function (ContainerInterface $container) {
                $folder = $container->get('michel.project_dir') . DIRECTORY_SEPARATOR . 'migrations';
                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }
                return $folder;
            },
            'paper.orm.migrations_table' => getenv('PAPER_ORM_MIGRATIONS_TABLE') ?: 'paper_mig_version',
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
            DatabaseSyncCommand::class,
            MigrationDiffCommand::class,
            MigrationMigrateCommand::class,
            QueryExecuteCommand::class,
            ShowTablesCommand::class,
        ];
    }
}
