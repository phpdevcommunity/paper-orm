<?php

namespace Test\PhpDevCommunity\PaperORM;

use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\Expression\Expr;
use PhpDevCommunity\UniTester\TestCase;
use Test\PhpDevCommunity\PaperORM\Entity\PostTest;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;

class QueryBuilderTest extends TestCase
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
    }

    protected function execute(): void
    {
        $this->testSelectWithoutJoin();
    }

    public function testSelectWithoutJoin(): void
    {
        $userRepository = $this->em->getRepository(UserTest::class);

//        $user = $userRepository->qq()
//            ->innerJoin(UserTest::class, PostTest::class)
//            ->where(Expr::equal('us.id', 1))
//            ->getOneOrNullResult([], false);
//        print_r($user);
//        exit();
        $user = $userRepository->findBy()
            ->first()
            ->has('lastPost')
            ->orderBy('active', 'DESC')
            ->limit(1)
            ->toArray();

        var_dump($user);
        exit();

//        $qb = $userRepository->qb();
//        $qb
//            ->leftJoin(UserTest::class, PostTest::class)
////            ->leftJoin(PostTest::class, TagTest::class)
////            ->leftJoin(TagTest::class, PostTest::class)
//            ->where(Expr::equal('po1.id', 3))
//            ->orderBy('us.createdAt', 'DESC');

        $users = $qb->getOneOrNullResult([], false);
        print_r($users);
//        foreach ($qb->getResultIterator([], false) as $user) {
//            var_dump($user);
//        }
//        foreach ($users as $user) {
//            var_dump($user->getLastPost());
//            var_dump($user->getPosts()->toArray());
//        }
    }

//
    protected function setUpDatabaseSchema(): void
    {
        $this->em->getConnection()->executeStatement('CREATE TABLE user (
                id INTEGER PRIMARY KEY,
                last_post_id INTEGER,
                firstname VARCHAR(255),
                lastname VARCHAR(255),
                email VARCHAR(255),
                password VARCHAR(255),
                is_active BOOLEAN,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (last_post_id) REFERENCES post (id)
            );');

        $this->em->getConnection()->executeStatement('CREATE TABLE post (
                id INTEGER PRIMARY KEY,
                user_id INTEGER,
                title VARCHAR(255),
                content VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES user (id)
            );');

        $this->em->getConnection()->executeStatement('CREATE TABLE tag (
                id INTEGER PRIMARY KEY,
                post_id INTEGER,
                name VARCHAR(255)
        )');
        $this->em->getConnection()->executeStatement('CREATE TABLE comment (
                id INTEGER PRIMARY KEY,
                post_id INTEGER,
                body VARCHAR(255)
        )');


        for ($i = 0; $i < 5; $i++) {
            $user = [
                'firstname' => 'John' . $i,
                'lastname' => 'Doe' . $i,
                'email' => $i . 'bqQpB@example.com',
                'password' => 'password123',
                'is_active' => true,
            ];

            $this->em->getConnection()->executeStatement("INSERT INTO user (firstname, lastname, email, password, is_active) VALUES (
                '{$user['firstname']}',
                '{$user['lastname']}',
                '{$user['email']}',
                '{$user['password']}',
                '{$user['is_active']}'
            )");
        }
//
        for ($i = 0; $i < 5; $i++) {
            $id = uniqid('post_', true);
            $post = [
                'user_id' => $i + 1,
                'title' => 'Post ' . $id,
                'content' => 'Content ' . $id,
            ];
            $this->em->getConnection()->executeStatement("INSERT INTO post (user_id, title, content) VALUES (
                '{$post['user_id']}',
                '{$post['title']}',
                '{$post['content']}'
            )");

            $id = uniqid('post_', true);
            $post = [
                'user_id' => $i + 1,
                'title' => 'Post ' . $id,
                'content' => 'Content ' . $id,
            ];
            $this->em->getConnection()->executeStatement("INSERT INTO post (user_id, title, content) VALUES (
                '{$post['user_id']}',
                '{$post['title']}',
                '{$post['content']}'
            )");

            $lastId = $this->em->getConnection()->getPdo()->lastInsertId();
            $this->em->getConnection()->executeStatement('UPDATE user SET last_post_id = ' . $lastId . ' WHERE id = ' . $post['user_id']);
        }

        for ($i = 0; $i < 10; $i++) {
            $id = uniqid('tag_', true);
            $tag = [
                'post_id' => $i + 1,
                'name' => 'Tag ' . $id,
            ];
            $this->em->getConnection()->executeStatement("INSERT INTO tag (post_id, name) VALUES (
                '{$tag['post_id']}',
                '{$tag['name']}'
            )");

            $id = uniqid('tag_', true);
            $tag = [
                'post_id' => $i + 1,
                'name' => 'Tag ' . $id,
            ];
            $this->em->getConnection()->executeStatement("INSERT INTO tag (post_id, name) VALUES (
                '{$tag['post_id']}',
                '{$tag['name']}'
            )");
        }
//
//        for ($i = 0; $i < 10; $i++) {
//            $id = uniqid('comment_', true);
//            $comment = [
//                'post_id' => $i + 1,
//                'body' => 'Comment ' . $id,
//            ];
//            $this->em->getConnection()->executeStatement("INSERT INTO comment (post_id, body) VALUES (
//                '{$comment['post_id']}',
//                '{$comment['body']}'
//            )");
//        }

    }
}
