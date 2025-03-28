<?php

namespace Test\PhpDevCommunity\PaperORM\Entity;

use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\DateTimeColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Test\PhpDevCommunity\PaperORM\Repository\PostTestRepository;
use Test\PhpDevCommunity\PaperORM\Repository\TagTestRepository;

class TagTest implements EntityInterface
{

    private ?int $id = null;
    private ?string $name = null;
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
        return [
            new PrimaryKeyColumn('id'),
            new StringColumn('name'),
            new JoinColumn('post', 'post_id', 'id', PostTest::class),
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
