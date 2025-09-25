<?php

namespace PhpDevCommunity\PaperORM\Debugger;

use Psr\Log\LoggerInterface;

class SqlDebugger
{
    private ?LoggerInterface $logger;

    private array $queries = [];

    public int $currentQuery = 0;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function startQuery(string $query, array $params): void
    {
        $query = preg_replace('/\s*\n\s*/', ' ', $query);
        $this->queries[++$this->currentQuery] = [
            'query' => sprintf('[%s] %s', strtok($query, " "), $query),
            'params' => $params,
            'startTime' => microtime(true),
            'executionTime' => 0
        ];
    }

    public function stopQuery(): void
    {
        if (!isset($this->queries[$this->currentQuery]['startTime'])) {
            throw new \LogicException('stopQuery() called without startQuery()');
        }

        $start = $this->queries[$this->currentQuery]['startTime'];
        $this->queries[$this->currentQuery]['executionTime'] = microtime(true) - $start;
        if ($this->logger !== null) {
            $this->logger->debug($this->queries[$this->currentQuery]);
        }
    }

    public function getQueries(): array
    {
        return array_values($this->queries);
    }

    public function clear(): void
    {
        $this->queries = [];
    }

}
