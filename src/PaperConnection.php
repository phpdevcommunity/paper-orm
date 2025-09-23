<?php

namespace PhpDevCommunity\PaperORM;

use PDO;
use PDOStatement;
use PhpDevCommunity\PaperORM\Driver\DriverInterface;
use PhpDevCommunity\PaperORM\Pdo\PaperPDO;

final class PaperConnection
{
    private ?PaperPDO $pdo = null;

    private array $params;

    private DriverInterface $driver;

    private bool $debug;

    public function __construct(DriverInterface $driver, array $params)
    {
        $this->params = $params;
        $this->driver = $driver;
        $this->debug = $params['debug'] ?? false;
    }


    public function executeStatement(string $query, array $params = []): int
    {
        $db = $this->executeQuery($query, $params);
        return $db->rowCount();
    }

    public function executeQuery(string $query, array $params = []): PDOStatement
    {
        $db = $this->getPdo()->prepare($query);
        if ($db === false) {
            throw new \Exception($this->getPdo()->errorInfo()[2]);
        }
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

    public function getPdo(): PaperPDO
    {
        $this->connect();
        return $this->pdo;
    }

    public function connect(): bool
    {
        if (!$this->isConnected()) {
            $this->pdo = $this->driver->connect($this->params);
            if ($this->debug) {
                $this->pdo->enableSqlDebugger();
            }
            return true;
        }

        return false;
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    public function close(): void
    {
        $this->pdo = null;
    }


    public function cloneConnectionWithoutDbname(): self
    {
        $params = $this->params;
        unset($params['dbname']);
        return new self($this->driver, $params);
    }

}
