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

    protected function setUp(): void
    {

    }

    protected function tearDown(): void
    {
    }

    protected function execute(): void
    {
        foreach (DataBaseHelperTest::drivers() as  $params) {
            $em = new EntityManager($params);
            DataBaseHelperTest::init($em);
            $this->testInsert($em);
            $this->testUpdate($em);
            $this->testUpdateJoinColumn($em);
            $this->testDelete($em);
            $em->getConnection()->close();
        }
    }



    private function testInsert(EntityManager $em): void
    {
        $user = new UserTest();
        $this->assertNull($user->getId());
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setPassword('secret');
        $user->setEmail('Xq5qI@example.com');
        $user->setActive(true);
        $em->persist($user);
        $em->flush();
        $this->assertNotNull($user->getId());
        $em->clear();
    }

    private function testUpdate(EntityManager $em): void
    {
        $userRepository = $em->getRepository(UserTest::class);
        $user = $userRepository->findBy()->first()->orderBy('id')->toObject();
        $this->assertInstanceOf(ProxyInterface::class, $user);
        $this->assertInstanceOf(UserTest::class, $user);
        /**
         * @var ProxyInterface|UserTest $user
         */
        $user->setActive(false);
        $user->setLastname('TOTO');
        $this->assertStrictEquals(2, count($user->__getPropertiesModified()));

        $em->persist($user);
        $em->flush();
        $user = null;
        $em->clear();

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


    private function testUpdateJoinColumn(EntityManager $em)
    {
        $userRepository = $em->getRepository(UserTest::class);
        $postRepository = $em->getRepository(PostTest::class);
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
        $em->persist($post);
        $em->flush();
        $user2 = $userRepository->find(2)
            ->with(PostTest::class)
            ->toObject();
        $this->assertStrictEquals(3, count($user2->getPosts()->toArray()));

        $user1 = $userRepository->find(1)->with(PostTest::class)->toObject();
        $this->assertStrictEquals(1, count($user1->getPosts()->toArray()));
    }

    private function testDelete(EntityManager $em)
    {
        $user = $em->getRepository(UserTest::class)->find(1)->toObject();
        $this->assertInstanceOf(ProxyInterface::class, $user);
        $this->assertInstanceOf(UserTest::class, $user);

        $em->remove($user);
        $em->flush();
        $this->assertFalse($user->__isInitialized());

        $posts = $user->getPosts();
        $ids = [];
        foreach ($posts as $post) {
            $ids[] = $post->getId();
            $em->remove($post);
            $em->flush();
            $this->assertFalse($post->__isInitialized());
        }
        $user = $em->getRepository(UserTest::class)->find(1)->toObject();
        $this->assertNull($user);

        foreach ($ids as $idPost) {
            $postToDelete = $em->getRepository(PostTest::class)->find($idPost)->toObject();
            $this->assertNull($postToDelete);
        }
    }
}
