<?php

namespace Test\PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Expression\Expr;
use PhpDevCommunity\PaperORM\Proxy\ProxyInterface;
use PhpDevCommunity\UniTester\TestCase;
use Test\PhpDevCommunity\PaperORM\Entity\PostTest;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;
use Test\PhpDevCommunity\PaperORM\Helper\DataBaseHelperTest;

class PersistAndFlushTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $this->em = new EntityManager([
            'driver' => 'sqlite',
            'user' => null,
            'password' => null,
            'memory' => true,
            'debug' => true
        ]);
        $this->setUpDatabaseSchema();
    }

    protected function setUpDatabaseSchema(): void
    {
        DataBaseHelperTest::init($this->em);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->close();
    }

    protected function execute(): void
    {
        $this->testUpdate();
        $this->testUpdateJoinColumn();
        $this->testDelete();
    }

    private function testUpdate(): void
    {
        $userRepository = $this->em->getRepository(UserTest::class);
        $user = $userRepository->findBy()->first()->orderBy('id')->toObject();
        $this->assertInstanceOf(ProxyInterface::class, $user);
        $this->assertInstanceOf(UserTest::class, $user);
        /**
         * @var ProxyInterface|UserTest $user
         */
        $user->setActive(false);
        $user->setLastname('TOTO');
        $this->assertStrictEquals(2, count($user->__getPropertiesModified()));

        $this->em->persist($user);
        $this->em->flush();
        $user = null;
        $this->em->clear();

        $user = $userRepository->findBy()->first()->orderBy('id')->toObject();
        $this->assertInstanceOf(ProxyInterface::class, $user);
        $this->assertInstanceOf(UserTest::class, $user);
        /**
         * @var ProxyInterface|UserTest $user
         */
        $this->assertStrictEquals(0, count($user->__getPropertiesModified()));
        $this->assertFalse($user->isActive());
        $this->assertStrictEquals('TOTO', $user->getLastname());
    }


    private function testUpdateJoinColumn()
    {
        $userRepository = $this->em->getRepository(UserTest::class);
        $postRepository = $this->em->getRepository(PostTest::class);
        $post = $postRepository->findBy()->first()
            ->where(Expr::isNotNull('user'))
            ->with(UserTest::class)
            ->toObject();
        $this->assertInstanceOf(ProxyInterface::class, $post);
        $this->assertInstanceOf(PostTest::class, $post);
        $this->assertInstanceOf(UserTest::class, $post->getUser());
        $this->assertStrictEquals(1, $post->getUser()->getId());

        $user2 = $userRepository->find(2)
            ->with(PostTest::class)
            ->toObject();
        $this->assertStrictEquals(2, count($user2->getPosts()->toArray()));
        foreach ($user2->getPosts()->toArray() as $postItem) {
            $this->assertInstanceOf(ProxyInterface::class, $postItem);
            $this->assertInstanceOf(PostTest::class, $postItem);
        }
        $post->setUser($user2);
        $this->em->persist($post);
        $this->em->flush();
        $user2 = $userRepository->find(2)
            ->with(PostTest::class)
            ->toObject();
        $this->assertStrictEquals(3, count($user2->getPosts()->toArray()));

        $user1 = $userRepository->find(1)->with(PostTest::class)->toObject();
        $this->assertStrictEquals(1, count($user1->getPosts()->toArray()));
    }

    private function testDelete()
    {
        $user = $this->em->getRepository(UserTest::class)->find(1)->toObject();
        $this->assertInstanceOf(ProxyInterface::class, $user);
        $this->assertInstanceOf(UserTest::class, $user);

        $posts = $user->getPosts();
        $this->em->remove($user);
        $this->em->flush();
        $this->assertFalse($user->__isInitialized());
        /**
         * @var PostTest|ProxyInterface $post
         */
        $post = $this->em->getRepository(PostTest::class)
            ->findBy()
            ->first()
            ->where(Expr::equal('user', $user->getId()))
            ->with(UserTest::class)
            ->toObject();
        $this->assertNull($post->getUser());

        $user = $this->em->getRepository(UserTest::class)->find(1)->toObject();
        $this->assertNull($user);

        $ids = [];
        foreach ($posts as $postToDelete) {
            $ids[] = $postToDelete->getId();
            $this->em->remove($postToDelete);
            $this->em->flush();
            $this->assertFalse($postToDelete->__isInitialized());
        }
        $this->assertStrictEquals($posts->count(), count($ids));
        foreach ($ids as $idPost) {
            $postToDelete = $this->em->getRepository(PostTest::class)->find($idPost)->toObject();
            $this->assertNull($postToDelete);
        }

        $this->assertFalse($post->__isInitialized());
    }
}
