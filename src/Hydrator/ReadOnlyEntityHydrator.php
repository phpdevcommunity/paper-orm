<?php

namespace PhpDevCommunity\PaperORM\Hydrator;

final class ReadOnlyEntityHydrator extends AbstractEntityHydrator
{
    protected function instantiate(string $class, array $data): object
    {
        return new $class();
    }
}
