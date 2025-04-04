<?php

namespace PhpDevCommunity\PaperORM\Michel\Package;

use Exception;
use PhpDevCommunity\Michel\Package\PackageInterface;
use PhpDevCommunity\PaperORM\Command\DatabaseCreateCommand;
use PhpDevCommunity\PaperORM\Command\DatabaseDropCommand;
use PhpDevCommunity\PaperORM\Command\QueryExecuteCommand;
use PhpDevCommunity\PaperORM\Command\ShowTablesCommand;
use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Parser\DSNParser;
use Psr\Container\ContainerInterface;

class MichelPaperORMPackage implements PackageInterface
{
    public function getDefinitions(): array
    {
        return [
            EntityManager::class => static function (ContainerInterface $container) {
                $dsn = $container->get('database.dsn');
                if (!is_string($dsn) || empty($dsn)) {
                    throw new \LogicException('Database DSN not found, please set DATABASE_DSN in .env file or database.dsn in config');
                }
                $params = DSNParser::parse($container->get('database.dsn'));
                return new EntityManager($params);
            }
        ];
    }

    public function getParameters(): array
    {
        return [
            'database.dsn' => getenv('DATABASE_DSN') ?? '',
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
            QueryExecuteCommand::class,
            ShowTablesCommand::class,
        ];
    }
}
