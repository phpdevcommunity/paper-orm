<?php

namespace PhpDevCommunity\PaperORM\Assigner;

use PhpDevCommunity\PaperORM\Mapping\Column\Column;

interface CommitAssignerInterface
{
    public function commit(Column $column): void;
}
