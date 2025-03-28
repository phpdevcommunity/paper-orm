<?php

namespace Test\PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\UniTester\TestCase;
use Test\PhpDevCommunity\PaperORM\Entity\PostTest;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;
use Test\PhpDevCommunity\PaperORM\Helper\DataBaseHelperTest;

class RepositoryTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $this->em = new EntityManager([
            'driver' => 'sqlite',
            'user' => null,
            'password' => null,
            'memory' => true,
        ]);
        $this->setUpDatabaseSchema();
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->close();
    }

    protected function execute(): void
    {
        $this->testSelectWithoutJoin();
        $this->testSelectInnerJoin();
        $this->testSelectLeftJoin();
    }

    public function testSelectWithoutJoin(): void
    {
        $userRepository = $this->em->getRepository(UserTest::class);
        $user = $userRepository->findBy()
            ->first()->orderBy('id')->toArray();

        $this->assertStrictEquals( 1, $user['id'] );
        $this->assertStrictEquals( 'John0', $user['firstname'] );
        $this->assertStrictEquals( 'Doe0', $user['lastname'] );
        $this->assertStrictEquals( '0bqQpB@example.com', $user['email'] );
        $this->assertStrictEquals( 'password123', $user['password'] );

        $user = $userRepository->find(1)->toArray();

        $this->assertStrictEquals( 1, $user['id'] );
        $this->assertStrictEquals( 'John0', $user['firstname'] );
        $this->assertStrictEquals( 'Doe0', $user['lastname'] );
        $this->assertStrictEquals( '0bqQpB@example.com', $user['email'] );
        $this->assertStrictEquals( 'password123', $user['password'] );

        /**
         * @var UserTest $user
         */
        $user = $userRepository->find(1)->toObject();

        $this->assertStrictEquals( 1, $user->getId() );
        $this->assertStrictEquals( 'John0', $user->getFirstname() );
        $this->assertStrictEquals( 'Doe0', $user->getLastname() );
        $this->assertStrictEquals( '0bqQpB@example.com', $user->getEmail() );
        $this->assertStrictEquals( 'password123', $user->getPassword() );
        $this->assertInstanceOf( \DateTimeInterface::class, $user->getCreatedAt() );
        $this->assertEmpty($user->getPosts()->toArray());
        $this->assertNull($user->getLastPost());

        $users = $userRepository->findBy()->orderBy('id')->toArray();
        $this->assertStrictEquals( 1, $users[0]['id'] );
        $this->assertStrictEquals(5, count($users));

        $users = $userRepository->findBy()->orderBy('id')->toObject();
        $this->assertStrictEquals(5, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
        }
    }

    public function testSelectInnerJoin(): void
    {
        $userRepository = $this->em->getRepository(UserTest::class);
        $user = $userRepository->findBy()
            ->first()
            ->orderBy('id', 'DESC')
            ->has(PostTest::class)
            ->toArray();

        $this->assertStrictEquals( 4, $user['id'] );
        $this->assertTrue(is_array( $user['posts'] ));
        $this->assertNotEmpty($user['posts']);
        $this->assertTrue(is_array( $user['lastPost'] ));
        $this->assertNotEmpty($user['lastPost']);

        $this->em->clear();
        /**
         * @var UserTest $user
         */
        $user = $userRepository->findBy()
            ->first()
            ->orderBy('id', 'DESC')
            ->has(PostTest::class)
            ->toObject();

        $this->assertStrictEquals( 4, $user->getId() );
        $this->assertNotEmpty($user->getPosts()->toArray());
        $this->assertInstanceOf(PostTest::class, $user->getLastPost());


        $this->em->clear();
        $users = $userRepository->findBy()->orderBy('id', 'DESC')->has(PostTest::class)->toArray();
        $this->assertStrictEquals( 4, $users[0]['id'] );
        $this->assertStrictEquals(4, count($users));

        $this->em->clear();
        $users = $userRepository->findBy()->orderBy('id', 'DESC')->has(PostTest::class)->toObject();
        $this->assertStrictEquals( 4, $users[0]->getId() );
        $this->assertStrictEquals(4, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertInstanceOf(PostTest::class, $user->getLastPost());
            $this->assertEmpty($user->getLastPost()->getTags()->toArray());

            $this->assertNotEmpty($user->getPosts()->toArray());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertEmpty($post->getTags()->toArray());
            }
        }

        $this->em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->toObject();
        foreach ($users as $user) {;
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertNull($user->getLastPost());
            $this->assertNotEmpty($user->getPosts()->toArray());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
            }
        }

        $this->em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->has('lastPost.tags')
            ->toObject();
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertInstanceOf(PostTest::class, $user->getLastPost());
            $this->assertNotEmpty($user->getPosts()->toArray());
            $this->assertNotEmpty($user->getLastPost()->getTags()->toArray());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
            }
        }

        $this->em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->has('posts.user')
            ->has('lastPost.tags')
            ->toObject();
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertInstanceOf(PostTest::class, $user->getLastPost());
            $this->assertNotEmpty($user->getPosts()->toArray());
            $this->assertNotEmpty($user->getLastPost()->getTags()->toArray());

            $this->assertStrictEquals($user, $user->getLastPost()->getUser());
            foreach ($user->getPosts() as $post) {
                $this->assertStrictEquals($user, $post->getUser());
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertInstanceOf(UserTest::class, $post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
            }
        }

        $this->em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->has('posts.comments')
            ->toArray();

        $this->assertStrictEquals(4, count($users));
        foreach ($users as $user) {
            $this->assertTrue(is_array( $user['posts'] ));
            $this->assertNotEmpty($user['posts']);
            foreach ($user['posts'] as $post) {
                $this->assertTrue(!array_key_exists('user', $post));
                $this->assertNotEmpty($post['comments']);
                $this->assertNotEmpty($post['tags']);
                $this->assertStrictEquals(2, count($post['comments']));
            }
        };

        $this->em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('posts.tags')
            ->has('posts.comments')
            ->toObject();

        $this->assertStrictEquals(4, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertNull($user->getLastPost());
            $this->assertNotEmpty($user->getPosts()->toArray());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
                $this->assertNotEmpty($post->getComments()->toArray());
            }
        };
        $this->em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->has('lastPost.comments')
            ->toObject();


        $this->assertStrictEquals(3, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertEmpty($user->getPosts()->toArray());

            $post = $user->getLastPost();
            $this->assertInstanceOf(PostTest::class, $post);
            $this->assertNull($post->getUser());
            $this->assertEmpty($post->getTags()->toArray());
            $this->assertNotEmpty($post->getComments()->toArray());
        }

        $users = $userRepository->findBy()
            ->has('lastPost.user')
            ->limit(2)
            ->toObject();

        $this->assertStrictEquals(2, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            $this->assertInstanceOf(PostTest::class, $user->getLastPost());
            $this->assertStrictEquals($user, $user->getLastPost()->getUser());
            $this->assertEmpty($user->getPosts()->toArray());
        }
    }

    public function testSelectLeftJoin(): void
    {
        $userRepository = $this->em->getRepository(UserTest::class);
        $user = $userRepository->findBy()
            ->first()
            ->orderBy('id', 'DESC')
            ->with(PostTest::class)
            ->toArray();


        $this->assertStrictEquals( 5, $user['id'] );
        $this->assertTrue(is_array( $user['posts'] ));
        $this->assertEmpty($user['posts']);
        $this->assertNull($user['lastPost']);

        $this->em->clear();
        /**
         * @var UserTest $user
         */
        $user = $userRepository->findBy()
            ->first()
            ->orderBy('id', 'DESC')
            ->with(PostTest::class)
            ->toObject();

        $this->assertStrictEquals( 5, $user->getId() );
        $this->assertEmpty($user->getPosts()->toArray());
        $this->assertNull($user->getLastPost());

        $this->em->clear();
        $users = $userRepository->findBy()->orderBy('id', 'DESC')->with(PostTest::class)->toArray();
        $this->assertStrictEquals( 5, $users[0]['id'] );
        $this->assertStrictEquals(5, count($users));

        $this->em->clear();
        $users = $userRepository->findBy()->orderBy('id', 'DESC')->with(PostTest::class)->toObject();

        $this->assertStrictEquals( 5, $users[0]->getId() );
        $this->assertStrictEquals(5, count($users));
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            if ($user->getId() === 5) {
                $this->assertNull($user->getLastPost());
                $this->assertEmpty($user->getPosts()->toArray());
                continue;
            }
            $this->assertInstanceOf(PostTest::class, $user->getLastPost());
            $this->assertEmpty($user->getLastPost()->getTags()->toArray());

            $this->assertNotEmpty($user->getPosts()->toArray());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertEmpty($post->getTags()->toArray());
            }
        }

        $this->em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->with('posts.tags')
            ->toObject();
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            if ($user->getId() === 5) {
                $this->assertEmpty($user->getPosts()->toArray());
            }else {
                $this->assertNotEmpty($user->getPosts()->toArray());
            }
            $this->assertNull($user->getLastPost());
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
            }
        }

        $this->em->clear();
        $users = $userRepository->findBy()
            ->orderBy('id', 'DESC')
            ->with('posts.tags')
            ->with('lastPost.tags')
            ->toObject();
        foreach ($users as $user) {
            $this->assertInstanceOf(UserTest::class, $user);
            if ($user->getId() === 5) {
                $this->assertNull($user->getLastPost());
                $this->assertEmpty($user->getPosts()->toArray());
            }else {
                $this->assertInstanceOf(PostTest::class, $user->getLastPost());
                $this->assertNotEmpty($user->getPosts()->toArray());
                $this->assertNotEmpty($user->getLastPost()->getTags()->toArray());
            }
            foreach ($user->getPosts() as $post) {
                $this->assertInstanceOf(PostTest::class, $post);
                $this->assertNull($post->getUser());
                $this->assertNotEmpty($post->getTags()->toArray());
            }
        }
    }
    protected function setUpDatabaseSchema(): void
    {
        DataBaseHelperTest::init($this->em);
    }
}
