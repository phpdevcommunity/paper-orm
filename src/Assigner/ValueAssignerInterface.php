<?php

namespace PhpDevCommunity\PaperORM\Assigner;

use PhpDevCommunity\PaperORM\Mapping\Column\Column;

interface ValueAssignerInterface
{
    public function assign(object $entity, Column $column): void;
}
