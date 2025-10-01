<?php

namespace Test\PhpDevCommunity\PaperORM\Helper;

use DateTime;
use PhpDevCommunity\PaperORM\EntityManagerInterface;
use PhpDevCommunity\PaperORM\Generator\SchemaDiffGenerator;
use PhpDevCommunity\PaperORM\Mapping\Column\BoolColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\DateTimeColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\JoinColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\PrimaryKeyColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\StringColumn;
use PhpDevCommunity\PaperORM\Mapping\Column\TimestampColumn;
use Test\PhpDevCommunity\PaperORM\Entity\PostTest;
use Test\PhpDevCommunity\PaperORM\Entity\UserTest;

class DataBaseHelperTest
{

    public static function drivers(): array
    {
        return [
            'sqlite' => [
                'driver' => 'sqlite',
                'user' => null,
                'password' => null,
                'memory' => true,
            ],
            'mariadb' => [
                'driver' => 'mariadb',
                'host' => getenv('MARIADB_HOST') ?: '127.0.0.1',
                'port' => (int)(getenv('MARIADB_PORT') ?: 3306),
                'path' => getenv('MARIADB_DB') ?: 'paper_orm_test',
                'user' => getenv('MARIADB_USER') ?: 'root',
                'password' => getenv('MARIADB_PASSWORD') ?: '',
                'charset' => 'utf8mb4',
            ],
        ];
    }
    public static function init(EntityManagerInterface $entityManager, int $nbUsers = 5, bool $withPosts = true)
    {
        $userColumns = [
            new PrimaryKeyColumn('id'),
            (new JoinColumn('last_post_id', PostTest::class, 'id', true, true)),
            new StringColumn('firstname'),
            new StringColumn('lastname'),
            new StringColumn('email'),
            new StringColumn('password'),
            new BoolColumn('is_active'),
            new TimestampColumn('created_at', true),
        ];
        $postColumns = [
            new PrimaryKeyColumn('id'),
            (new JoinColumn('user_id', UserTest::class, 'id', true, false, JoinColumn::SET_NULL)),
            new StringColumn('title'),
            new StringColumn('content'),
            new TimestampColumn('created_at', true),
        ];

        $tagColumns = [
            new PrimaryKeyColumn('id'),
            (new JoinColumn('post_id', PostTest::class, 'id', true, false, JoinColumn::SET_NULL)),
            new StringColumn('name'),
        ];
        $commentColumns = [
            new PrimaryKeyColumn('id'),
            (new JoinColumn('post_id', PostTest::class, 'id', true, false, JoinColumn::SET_NULL)),
            new StringColumn('body'),
        ];
        $platform = $entityManager->getPlatform();
        $platform->createDatabaseIfNotExists();
        $platform->dropDatabase();
        $platform->createDatabaseIfNotExists();
        $statements = (new SchemaDiffGenerator($platform))->generateDiffStatements([
                'user' => [
                    'columns' => $userColumns,
                    'indexes' => [],
                ],
                'post' => [
                    'columns' => $postColumns,
                    'indexes' => [],
                ],
                'tag' => [
                    'columns' => $tagColumns,
                    'indexes' => [],
                ],
                'comment' => [
                    'columns' => $commentColumns,
                    'indexes' => [],
                ],
            ]
        );


        $connection = $entityManager->getConnection();
        $connection->close();
        $connection->connect();
        foreach ($statements['up'] as $statement) {
            $connection->executeStatement($statement);
        }

        for ($i = 0; $i < $nbUsers; $i++) {
            $user = [
                'firstname' => 'John' . $i,
                'lastname' => 'Doe' . $i,
                'email' => $i . 'bqQpB@example.com',
                'password' => 'password123',
                'is_active' => true,
                'created_at' => (new DateTime())->format($platform->getSchema()->getDateTimeFormatString()),
            ];

            $stmt = $connection->getPdo()->prepare("INSERT INTO user (firstname, lastname, email, password, is_active, created_at) VALUES (:firstname, :lastname, :email, :password, :is_active, :created_at)");
            $stmt->execute([
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'email' => $user['email'],
                'password' => $user['password'],
                'is_active' => $user['is_active'],
                'created_at' => $user['created_at']
            ]);
        }
//
        if ($withPosts) {
            $nbPosts = $nbUsers - 1;
            for ($i = 0; $i < $nbPosts; $i++) {
                $id = uniqid('post_', true);
                $post = [
                    'user_id' => $i + 1,
                    'title' => 'Post ' . $id,
                    'content' => 'Content ' . $id,
                    'created_at' => (new DateTime())->format($platform->getSchema()->getDateTimeFormatString()),
                ];

                $stmt = $connection->getPdo()->prepare("INSERT INTO post (user_id, title, content, created_at)  VALUES (:user_id, :title, :content, :created_at)");
                $stmt->execute([
                    'user_id' => $post['user_id'],
                    'title' => $post['title'],
                    'content' => $post['content'],
                    'created_at' => $post['created_at']
                ]);
                $id = uniqid('post_', true);
                $post = [
                    'user_id' => $i + 1,
                    'title' => 'Post ' . $id,
                    'content' => 'Content ' . $id,
                ];
                $connection->executeStatement("INSERT INTO post (user_id, title, content) VALUES (
                '{$post['user_id']}',
                '{$post['title']}',
                '{$post['content']}'
            )");

                $lastId = $connection->getPdo()->lastInsertId();
                $connection->executeStatement('UPDATE user SET last_post_id = ' . $lastId . ' WHERE id = ' . $post['user_id']);
            }
            $nbTags = $nbPosts * 2;
            for ($i = 0; $i < $nbTags; $i++) {
                $id = uniqid('tag_', true);
                $tag = [
                    'post_id' => $i + 1,
                    'name' => 'Tag ' . $id,
                ];
                $connection->executeStatement("INSERT INTO tag (post_id, name) VALUES (
                '{$tag['post_id']}',
                '{$tag['name']}      '
            )");

                $id = uniqid('tag_', true);
                $tag = [
                    'post_id' => $i + 1,
                    'name' => 'Tag ' . $id,
                ];
                $connection->executeStatement("INSERT INTO tag (post_id, name) VALUES (
                '{$tag['post_id']}',
                '{$tag['name']}      '
            )");
            }
            $nbComments = $nbTags - 1;
            for ($i = 0; $i < $nbComments; $i++) {
                $id = uniqid('comment_', true);
                $comment = [
                    'post_id' => $i + 1,
                    'body' => 'Comment ' . $id,
                ];
                $connection->executeStatement("INSERT INTO comment (post_id, body) VALUES (
                '{$comment['post_id']}',
                '{$comment['body']}      '
            )");

                $comment['body'] = 'Comment ' . $id . ' 2';
                $connection->executeStatement("INSERT INTO comment (post_id, body) VALUES (
                '{$comment['post_id']}',
                '{$comment['body']}      '
            )");
            }
        }


    }

}
