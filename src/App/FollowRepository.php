<?php

namespace App;

use Domain\FollowRelationship;

class FollowRepository
{
    public function __construct(private \PDO $db)
    {
    }

    public function saveRelationship(string $instance, int $idOnInstance, string $targetInstance, string $targetIdOnInstance): void
    {
        $this->db->prepare('INSERT INTO follows (instance, id_on_instance, target_instance, target_id_on_instance, created_at) VALUES (:instance, :id_on_instance, :target_instance, :target_id_on_instance, :created_at)')
            ->execute([
                'instance' => $instance,
                'id_on_instance' => $idOnInstance,
                'target_instance' => $targetInstance,
                'target_id_on_instance' => $targetIdOnInstance,
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
    }

    public function getRelationship(string $instance, int $idOnInstance, string $targetInstance, int $targetIdOnInstance): ?FollowRelationship
    {
        $statement = $this->db->prepare('SELECT * FROM follows WHERE instance = :instance AND id_on_instance = :id_on_instance AND target_instance = :target_instance AND target_id_on_instance = :target_id_on_instance');
        $statement->execute([
            'instance' => $instance,
            'id_on_instance' => $idOnInstance,
            'target_instance' => $targetInstance,
            'target_id_on_instance' => $targetIdOnInstance,
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return new FollowRelationship(
            instance: $row['instance'],
            idOnInstance: (int) $row['id_on_instance'],
            targetInstance: $row['target_instance'],
            targetIdOnInstance: (int) $row['target_id_on_instance'],
            createdAt: new \DateTimeImmutable($row['created_at']),
        );
    }

    public function deleteRelationship(string $instance, int $idOnInstance, string $targetInstance, int $targetIdOnInstance)
    {
        $this->db->prepare('DELETE FROM follows WHERE instance = :instance AND id_on_instance = :id_on_instance AND target_instance = :target_instance AND target_id_on_instance = :target_id_on_instance')
            ->execute([
                'instance' => $instance,
                'id_on_instance' => $idOnInstance,
                'target_instance' => $targetInstance,
                'target_id_on_instance' => $targetIdOnInstance,
            ]);
    }

    public function getFollowRelationShipsForIdOnInstance(string $instance, int $idOnInstance): array
    {
        $statement = $this->db->prepare('SELECT * FROM follows WHERE instance = :instance AND id_on_instance = :id_on_instance');
        $statement->execute([
            'instance' => $instance,
            'id_on_instance' => $idOnInstance,
        ]);
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $followRelationships = [];
        foreach ($rows as $row) {
            $followRelationships[] = new FollowRelationship(
                instance: $row['instance'],
                idOnInstance: (int) $row['id_on_instance'],
                targetInstance: $row['target_instance'],
                targetIdOnInstance: (int) $row['target_id_on_instance'],
                createdAt: new \DateTimeImmutable($row['created_at']),
            );
        }

        return $followRelationships;
    }
}