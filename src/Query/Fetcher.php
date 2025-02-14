<?php

namespace PhpDevCommunity\PaperORM\Query;

final class Fetcher
{
    private QueryBuilder $queryBuilder;
    private array $arguments;
    private bool $collection;

    public function __construct(QueryBuilder $queryBuilder, array $arguments, bool $collection = true)
    {
        $this->queryBuilder = $queryBuilder;
        $this->arguments = $arguments;
        $this->collection = $collection;
    }

    public function join(string...$relationsClasses): Fetcher
    {
        $primaryAlias = $this->queryBuilder->getPrimaryAlias();

        foreach ($relationsClasses as $relationClass) {
            $this->queryBuilder->leftJoin($primaryAlias, $relationClass);
        }

        return $this;
    }

    public function joinFrom(string $fromEntity, string...$relationsClasses): Fetcher
    {
        foreach ($relationsClasses as $relationClass) {
            $this->queryBuilder->leftJoin($fromEntity, $relationClass);
        }

        return $this;

    }


    public function toArray(): ?array
    {
        if ($this->collection) {
            return $this->queryBuilder->getResult($this->arguments, false);
        }

        return $this->queryBuilder->getOneOrNullResult($this->arguments, false);
    }

    public function toObject()
    {
        if ($this->collection) {
            return $this->queryBuilder->getResult($this->arguments);
        }

        return $this->queryBuilder->getOneOrNullResult($this->arguments);
    }
}
