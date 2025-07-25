<?php

namespace PhpDevCommunity\PaperORM\Entity;

interface TableMetadataInterface
{
    static public function getTableName(): string;
    static public function getRepositoryName(): ?string;
    static public function columnsMapping(): array;
}
