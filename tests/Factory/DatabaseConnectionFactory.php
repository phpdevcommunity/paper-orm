<?php

namespace Test\PhpDevCommunity\PaperORM\Factory;

use PhpDevCommunity\PaperORM\EntityManager;

final class DatabaseConnectionFactory
{
    public static function createConnection(string $driver): EntityManager
    {
        switch ($driver) {
            case 'sqlite':
                return new EntityManager([
                    'driver' => 'sqlite',
                    'user' => null,
                    'password' => null,
                    'memory' => true,
                ]);

            case 'mariadb':
                return new EntityManager([
                    'driver' => 'pdo_mysql',
                    'host' => 'localhost',
                    'path' => 'test_db',
                    'user' => 'root',
                    'password' => '',
                ]);
            default:
                throw new \InvalidArgumentException("Database driver '$driver' not supported");
        }
    }
}
