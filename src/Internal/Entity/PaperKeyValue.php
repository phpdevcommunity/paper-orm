<?php

namespace PhpDevCommunity\PaperORM\Internal\Entity;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Entity\SystemEntityInterface;
use PhpDevCommunity\PaperORM\Mapping\Column\AnyColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\TimestampColumn;
use PhpDevCommunity\PaperORM\Mapping\Entity;

#[Entity(table : 'paper_key_value')]
class PaperKeyValue implements EntityInterface, SystemEntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;

    #[StringColumn(name: 'k', length: 100, nullable: false, unique: true)]
    private ?string $key = null;

    /**
     * @var mixed
     */
    #[AnyColumn(name: 'val')]
    private $value = null;

    #[TimestampColumn(name: 'created_at', onCreated: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[TimestampColumn(name: 'updated_at', onCreated: false, onUpdated: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getPrimaryKeyValue() : ?int
    {
        return $this->id;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): PaperKeyValue
    {
        $this->key = $key;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): PaperKeyValue
    {
        $this->value = $value;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): PaperKeyValue
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): PaperKeyValue
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }


    static public function getTableName(): string
    {
        return 'paper_key_value';
    }

    static public function getRepositoryName(): ?string
    {
        return null;
    }

    static public function columnsMapping(): array
    {
        return [
            (new PrimaryKeyColumn())->bindProperty('id'),
            (new StringColumn('k', 100, false, null, true))->bindProperty('key'),
            (new AnyColumn('val'))->bindProperty('value'),
            (new TimestampColumn('created_at', true))->bindProperty('createdAt'),
            (new TimestampColumn('updated_at', false, true))->bindProperty('updatedAt'),
        ];
    }

}
