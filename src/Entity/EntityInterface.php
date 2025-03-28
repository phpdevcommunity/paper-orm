<?php

namespace PhpDevCommunity\PaperORM\Entity;

interface EntityInterface
{
    static public function getTableName(): string;
    static public function getRepositoryName(): ?string;
    static public function columnsMapping(): array;
    public function getPrimaryKeyValue();
}
