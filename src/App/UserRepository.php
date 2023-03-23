<?php

namespace App;

use Domain\User;

class UserRepository
{
    public function __construct(private \PDO $db)
    {
    }

    public function save(string $username, string $instance, int $idOnInstance)
    {
        $this->db->prepare('INSERT INTO users (username, instance, id_on_instance, created_at, updated_at) VALUES (:username, :instance, :id_on_instance, :created_at, :updated_at)')
            ->execute([
                'username' => $username,
                'instance' => $instance,
                'id_on_instance' => $idOnInstance,
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
    }

    public function findById(int $id): ?User
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return new User(
            username: $row['username'],
            instance: $row['instance'],
            idOnInstance: (int) $row['id_on_instance'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
        );
    }

    public function findByUsernameAndInstance(string $username, string $instance): ?User
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE username = :username AND instance = :instance');
        $statement->execute(['username' => $username, 'instance' => $instance]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return new User(
            username: $row['username'],
            instance: $row['instance'],
            idOnInstance: (int) $row['id_on_instance'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
        );
    }

    public function findByIdOnInstance(int $idOnInstance, string $instance): ?User
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE id_on_instance = :id_on_instance AND instance = :instance');
        $statement->execute(['id_on_instance' => $idOnInstance, 'instance' => $instance]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return new User(
            username: $row['username'],
            instance: $row['instance'],
            idOnInstance: (int) $row['id_on_instance'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
        );
    }
}