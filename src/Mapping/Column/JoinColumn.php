<?php

namespace PhpDevCommunity\PaperORM\Mapping\Column;

use PhpDevCommunity\PaperORM\Types\IntegerType;
use ReflectionClass;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class JoinColumn extends Column
{
    /**
     * @var string
     */
    private string $referencedColumnName;
    /**
     * @var string
     */
    private string $targetEntity;

    final public function __construct(
        string  $name,
        string  $referencedColumnName,
        string  $targetEntity,
        bool   $nullable = false,
        bool   $unique = false
    )
    {
        parent::__construct('', $name, IntegerType::class, $nullable, null, $unique);
        $this->referencedColumnName = $referencedColumnName;
        $this->targetEntity = $targetEntity;
    }

    public function getReferencedColumnName(): string
    {
        return $this->referencedColumnName;
    }

    /**
     * @return class-string
     */
    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }


    public function getType(): string
    {
        return '\\' . ltrim(parent::getType(), '\\');
    }

}
