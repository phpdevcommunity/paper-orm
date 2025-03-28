<?php

namespace Test\PhpDevCommunity\PaperORM\Entity;

use PhpDevCommunity\PaperORM\Collection\ObjectStorage;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;
use PhpDevCommunity\PaperORM\Mapping\Column\BoolColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\DateTimeColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\OneToMany;

class UserTest implements EntityInterface
{
    private ?int $id = null;

    private ?string $firstname = null;

    private ?string $lastname    = null;

    private ?string $email = null;

    private ?string $password = null;

    private bool $active = false;

    private ?\DateTime $createdAt = null;

    private ObjectStorage $posts;
    private ?PostTest $lastPost = null;
    public function __construct()
    {
        $this->posts = new ObjectStorage();
        $this->createdAt = new \DateTime();
    }

    static public function getTableName(): string
    {
        return 'user';
    }

    static public function getRepositoryName(): ?string
    {
        return null;
    }

    static public function columnsMapping(): array
    {
        return [
            new PrimaryKeyColumn('id'),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('active', 'is_active'),
            new DateTimeColumn('createdAt', 'created_at'),
            new OneToMany('posts', PostTest::class,  'user'),
            new JoinColumn('lastPost', 'last_post_id', 'id', PostTest::class),
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

    public function setId(?int $id): UserTest
    {
        $this->id = $id;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): UserTest
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): UserTest
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): UserTest
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): UserTest
    {
        $this->password = $password;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): UserTest
    {
        $this->active = $active;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): UserTest
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getPosts(): ObjectStorage
    {
        return $this->posts;
    }

    public function addPost(PostTest $post): UserTest
    {
        $this->posts->add($post);
        return $this;
    }

    public function getLastPost(): ?PostTest
    {
        return $this->lastPost;
    }

    public function setLastPost(?PostTest $lastPost): UserTest
    {
        $this->lastPost = $lastPost;
        return $this;
    }
}
