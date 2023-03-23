<?php

namespace App;

use Domain\AccessToken;

class AccessTokenRepository
{
    public function __construct(private \PDO $db)
    {
    }

    public function saveAccessToken(int $idOnInstance, string $instance, string $accessToken, \DateTimeInterface $createdAt): void
    {
        $statement = $this->db->prepare('INSERT INTO access_tokens (id_on_instance, instance, access_token, created_at) VALUES (:id_on_instance, :instance, :access_token, :created_at)');
        $statement->execute([
            'id_on_instance' => $idOnInstance,
            'instance' => $instance,
            'access_token' => $accessToken,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findAccessToken(string $token): ?AccessToken
    {
        $statement = $this->db->prepare('SELECT * FROM access_tokens WHERE access_token = :access_token');
        $statement->execute(['access_token' => $token]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return new AccessToken(
            idOnInstance: (int) $row['id_on_instance'],
            instance: $row['instance'],
            accessToken: $row['access_token'],
            createdAt: new \DateTimeImmutable($row['created_at']),
        );
    }
}