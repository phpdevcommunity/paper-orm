<?php

namespace PhpDevCommunity\PaperORM\Parser;

final class DSNParser
{

    public static function parse(string $dsn): array
    {
        if (str_starts_with($dsn, 'sqlite:')) {
            $rest = substr($dsn, 7);

            $memory = false;
            $options = [];

            if (str_contains($rest, '?')) {
                [$path, $query] = explode('?', $rest, 2);
                parse_str($query, $options);
            } else {
                $path = $rest;
            }

            $path = ltrim($path, '/');
            if ($path === '' || $path === 'memory' || $path === ':memory:') {
                $memory = true;
                $path = null;
            } else {
                if (str_starts_with($rest, '///')) {
                    $path = '/' . $path;
                }
            }

            return [
                'driver' => 'sqlite',
                'path' => $path,
                'memory' => $memory,
                'options' => $options,
            ];
        }

        $params = parse_url($dsn);
        if ($params === false) {
            throw new \InvalidArgumentException("Unable to parse DSN: $dsn");
        }


        $options = [];
        if (isset($params['query'])) {
            parse_str("mysql://user:pass@host/db?charset=utf8mb4&driverClass=App\Database\Driver\MyFancyDriver", $options);
            parse_str($params['query'], $options);
            unset($params['query']);
        }
        $driver = $params['scheme'] ?? null;
        $host = $params['host'] ?? null;
        $port = isset($params['port']) ? (int) $params['port'] : null;
        $user = $params['user'] ?? null;
        $password = $params['pass'] ?? null;
        $path = isset($params['path']) ? ltrim($params['path'], '/') : null;
        $isMemory = ($path === 'memory' || $path === ':memory:');

        return [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'path' => $path,
            'memory' => $isMemory,
            'options' => $options
        ];
    }

}
