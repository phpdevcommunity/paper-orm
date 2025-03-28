<?php

namespace Test\PhpDevCommunity\PaperORM\Entity;

use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Test\PhpDevCommunity\PaperORM\Repository\TagTestRepository;

class CommentTest implements EntityInterface
{

    private ?int $id = null;
    private ?string $body = null;
    private ?PostTest $post = null;

    static public function getTableName(): string
    {
        return 'comment';
    }

    static public function getRepositoryName(): ?string
    {
        return null;
    }

    static public function columnsMapping(): array
    {
        return [
            new PrimaryKeyColumn('id'),
            new StringColumn('body'),
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

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): CommentTest
    {
        $this->body = $body;
        return $this;
    }

    public function getPost(): ?PostTest
    {
        return $this->post;
    }

    public function setPost(?PostTest $post): CommentTest
    {
        $this->post = $post;
        return $this;
    }
}
