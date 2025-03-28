<?php

namespace PhpDevCommunity\PaperORM\Mapping;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Entity
{
    private string $table;
    private string $repositoryClass;

    public function __construct( string $table, string $repositoryClass)
    {
        $this->table = $table;
        $this->repositoryClass = $repositoryClass;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRepositoryClass(): string
    {
        return $this->repositoryClass;
    }

}
