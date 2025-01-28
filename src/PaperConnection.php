<?php

namespace PhpDevCommunity\PaperORM;

use PDO;
use PDOStatement;
use PhpDevCommunity\PaperORM\Driver\DriverInterface;

final class PaperConnection
{
    private ?PDO $pdo = null;

    private array $params;

    private DriverInterface $driver;

    public function __construct(DriverInterface $driver, array $params)
    {
        $this->params = $params;
        $this->driver = $driver;
    }

    public function executeStatement(string $query, array $params = []): int
    {
        $db = $this->executeQuery($query, $params);
        return $db->rowCount();
    }

    public function executeQuery(string $query, array $params = []): PDOStatement
    {
        $db = $this->getPdo()->prepare($query);
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $db->bindValue(':' . $key, $value);
            } else {
                $db->bindValue($key + 1, $value);
            }
        }
        $db->execute();
        return $db;
    }

    public function fetch(string $query, array $params = []): ?array
    {
        $db = $this->executeQuery($query, $params);
        $data = $db->fetch(PDO::FETCH_ASSOC);
        return $data === false ? null : $data;
    }

    public function fetchAll(string $query, array $params = []): array
    {
        $db = $this->executeQuery($query, $params);
        return $db->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    public function getPdo(): PDO
    {
        $this->connect();
        return $this->pdo;
    }

    public function connect(): bool
    {
        if ($this->pdo === null) {
            $this->pdo = $this->driver->connect($this->params);
            return true;
        }

        return false;
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    public function close()
    {
        $this->pdo = null;
    }

}
