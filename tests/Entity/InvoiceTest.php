<?php

namespace Test\PhpDevCommunity\PaperORM\Entity;

use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapping\Column\AutoIncrementColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\Entity;

#[Entity(table: 'invoice', repository: null)]
class InvoiceTest implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;
    #[AutoIncrementColumn(pad: 8, prefix: 'INV-{YYYY}-', key: 'invoice.number')]
    private ?string $number = null;

    #[AutoIncrementColumn(pad: 8, key: 'invoice.code')]
    private ?string $code = null;


    static public function getTableName(): string
    {
        return 'invoice';
    }

    static public function getRepositoryName(): ?string
    {
        return null;
    }

    static public function columnsMapping(): array
    {
        return [
            (new PrimaryKeyColumn())->bindProperty('id'),
            (new AutoIncrementColumn(null, 'invoice.number', 6, 'INV-{YYYY}-'))->bindProperty('number'),
            (new AutoIncrementColumn(null, 'invoice.code', 8, null))->bindProperty('code'),
            (new JoinColumn('post_id', PostTest::class))->bindProperty('post'),
        ];
    }

    public function getPrimaryKeyValue(): ?int
    {
        return $this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): InvoiceTest
    {
        $this->number = $number;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): InvoiceTest
    {
        $this->code = $code;
        return $this;
    }


}
