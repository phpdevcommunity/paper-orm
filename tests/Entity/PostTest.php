<?php

namespace Test\PhpDevCommunity\PaperORM\Entity;

use DateTime;
use PhpDevCommunity\PaperORM\Collection\ObjectStorage;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\DateTimeColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\OneToMany;
use Test\PhpDevCommunity\PaperORM\Repository\PostTestRepository;

class PostTest implements EntityInterface
{

    private ?int $id = null;

    private ?string $title = null;

    private ?string $content = null;

    private ?DateTime $createdAt = null;

    private ?UserTest $user = null;

    private ObjectStorage $tags;
    private ObjectStorage $comments;

    public function __construct()
    {
        $this->tags = new ObjectStorage();
        $this->comments = new ObjectStorage();
    }

    static public function getTableName(): string
    {
        return 'post';
    }

    static public function getRepositoryName(): string
    {
        return PostTestRepository::class;
    }

    static public function columnsMapping(): array
    {
        return [
            new PrimaryKeyColumn('id'),
            new StringColumn('title'),
            new StringColumn('content'),
            new DateTimeColumn('createdAt', 'created_at'),
            new JoinColumn('user', 'user_id', 'id', UserTest::class),
            new OneToMany('tags', TagTest::class, 'post'),
            new OneToMany('comments', CommentTest::class, 'post'),
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

    public function setId(?int $id): PostTest
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): PostTest
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): PostTest
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): PostTest
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUser(): ?UserTest
    {
        return $this->user;
    }

    public function setUser(?UserTest $user): PostTest
    {
        $this->user = $user;
        return $this;
    }

    public function getTags(): ObjectStorage
    {
        return $this->tags;
    }

    public function addTag(TagTest $tag): PostTest
    {
        $this->tags->add($tag);
        return $this;
    }

    public function getComments(): ObjectStorage
    {
        return $this->comments;
    }

    public function addComment(CommentTest $comment): PostTest
    {
        $this->comments->add($comment);
        return $this;
    }
}
