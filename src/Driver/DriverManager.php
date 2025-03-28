<?php

namespace PhpDevCommunity\PaperORM\Driver;

use Exception;
use PhpDevCommunity\PaperORM\PaperConnection;

class DriverManager
{
    private static array $driverSchemeAliases = [
        'sqlite' => SqliteDriver::class,
        'sqlite3' => SqliteDriver::class,
    ];

    public static function getConnection(string $driver, array $params): PaperConnection
    {
        $driver = strtolower($driver);

        $drivers = self::$driverSchemeAliases;
        if (isset($params['driver_class'])) {
            $drivers[$driver] = $params['driver_class'];
        }
        if (!isset($drivers[$driver])) {
            throw new Exception('Driver not found, please check your config : ' . $driver);
        }

        $driver = $drivers[$driver];
        $driver = new $driver();
        return new PaperConnection($driver, $params);
    }

}
