<?php

namespace PhpDevCommunity\PaperORM\Metadata;

use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapping\Column\Column;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;

class ColumnMetadata
{
    private string $name;
    private string $type;
    private bool $isPrimary;
    private array $foreignKeyMetadata;
    private bool $isNullable;
    private $defaultValue;
    private ?string $comment;
    private array $attributes;
    private ?IndexMetadata $indexMetadata;

    public function __construct(
        string  $name,
        string  $type,
        bool    $isPrimary = false,
        array   $foreignKeyMetadata = [],
        bool    $isNullable = true,
                $defaultValue = null,
        ?string $comment = null,
        array   $attributes = []
    )
    {
        $this->name = $name;
        $this->type = strtoupper($type);
        $this->isPrimary = $isPrimary;
        $this->foreignKeyMetadata = $foreignKeyMetadata;
        $this->isNullable = $isNullable;
        $this->defaultValue = $defaultValue;
        $this->comment = $comment;
        $this->attributes = $attributes;
    }

    // Getters
    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeWithAttributes(): string
    {
        if (!empty($this->attributes)) {
            return sprintf('%s(%s)', $this->getType(), implode(',', $this->attributes));
        }
        return $this->getType();
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function getForeignKeyMetadata(): array
    {
        return $this->foreignKeyMetadata;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public static function fromColumn(Column $column, string $sqlType): self
    {
        $foreignKeyMetadata = [];
        if ($column instanceof JoinColumn) {
            $targetEntity = $column->getTargetEntity();
            if (is_subclass_of($targetEntity, EntityInterface::class)) {
                $foreignKeyMetadata = [
                    'referencedTable' => $targetEntity::getTableName(),
                    'referencedColumn' => $column->getReferencedColumnName(),
                ];
            }
        }

        $arguments = [];
        if ($column->getFirstArgument()) {
            $arguments[] = $column->getFirstArgument();
        }
        if ($column->getSecondArgument()) {
            $arguments[] = $column->getSecondArgument();
        }

        $defaultValue = $column->getDefaultValue();
        if (is_array($defaultValue)) {
            $defaultValue = json_encode($defaultValue);
        }
        return new self(
            $column->getName(),
            $sqlType,
            $column instanceof PrimaryKeyColumn,
            $foreignKeyMetadata,
            $column->isNullable(),
            $defaultValue,
            null,
            $arguments
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['type'],
            $data['primary'] ?? false,
            $data['foreignKeyMetadata'] ?? false,
            $data['null'] ?? true,
            $data['default'] ?? null,
            $data['comment'] ?? null,
            $data['attributes'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),

            'type' => $this->getType(),
            'primary' => $this->isPrimary(),
            'foreignKeyMetadata' => $this->getForeignKeyMetadata(),
            'null' => $this->isNullable(),
            'default' => $this->getDefaultValue(),
            'comment' => $this->getComment(),
            'attributes' => $this->getAttributes(),
        ];
    }
}
