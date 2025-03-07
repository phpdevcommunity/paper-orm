<?php

namespace Test\PhpDevCommunity\PaperORM\Helper;

use PhpDevCommunity\PaperORM\PaperConnection;

class DataBaseHelperTest
{

    public static function init(PaperConnection $connection, int $nbUsers = 5)
    {
        $connection->close();
        $connection->connect();
        $connection->executeStatement('CREATE TABLE user (
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

        $connection->executeStatement('CREATE TABLE post (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL,
                title VARCHAR(255),
                content VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES user (id)
            );');

        $connection->executeStatement('CREATE TABLE tag (
                id INTEGER PRIMARY KEY,
                post_id INTEGER,
                name VARCHAR(255)
        )');
        $connection->executeStatement('CREATE TABLE comment (
                id INTEGER PRIMARY KEY,
                post_id INTEGER,
                body VARCHAR(255)
        )');


        for ($i = 0; $i <$nbUsers; $i++) {
            $user = [
                'firstname' => 'John' . $i,
                'lastname' => 'Doe' . $i,
                'email' => $i . 'bqQpB@example.com',
                'password' => 'password123',
                'is_active' => true,
            ];

            $connection->executeStatement("INSERT INTO user (firstname, lastname, email, password, is_active) VALUES (
                '{$user['firstname']}',
                '{$user['lastname']}',
                '{$user['email']}',
                '{$user['password']}',
                '{$user['is_active']}'
            )");
        }
//
        $nbPosts = $nbUsers - 1;
        for ($i = 0; $i < $nbPosts; $i++) {
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
        for ($i = 0; $i <$nbComments; $i++) {
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
