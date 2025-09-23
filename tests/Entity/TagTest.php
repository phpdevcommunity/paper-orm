<?php

namespace Test\PhpDevCommunity\PaperORM\Entity;

use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\DateTimeColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\Entity;
use Test\PhpDevCommunity\PaperORM\Repository\PostTestRepository;
use Test\PhpDevCommunity\PaperORM\Repository\TagTestRepository;

#[Entity(table : 'tag', repository : TagTestRepository::class)]
class TagTest implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;
    #[StringColumn]
    private ?string $name = null;

    #[JoinColumn(name : 'post_id', targetEntity : PostTest::class)]
    private ?PostTest $post = null;

    static public function getTableName(): string
    {
        return 'tag';
    }

    static public function getRepositoryName(): string
    {
        return TagTestRepository::class;
    }

    static public function columnsMapping(): array
    {
        if (PHP_VERSION_ID > 80000) {
            return [];
        }
        return [
            (new PrimaryKeyColumn())->bindProperty('id'),
            (new StringColumn())->bindProperty('name'),
            (new JoinColumn( 'post_id', PostTest::class))->bindProperty('post'),
        ];
    }

    public function getPrimaryKeyValue() : ?int
    {
        return $this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): TagTest
    {
        $this->name = $name;
        return $this;
    }

    public function getPost(): ?PostTest
    {
        return $this->post;
    }

    public function setPost(?PostTest $post): TagTest
    {
        $this->post = $post;
        return $this;
    }
}
