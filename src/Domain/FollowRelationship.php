<?php

namespace Domain;

class FollowRelationship
{
    public function __construct(private string $instance, private int $idOnInstance, private string $targetInstance, private int $targetIdOnInstance, private \DateTimeImmutable $createdAt)
    {
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    /**
     * @return int
     */
    public function getIdOnInstance(): int
    {
        return $this->idOnInstance;
    }

    public function getTargetInstance(): string
    {
        return $this->targetInstance;
    }

    public function getTargetIdOnInstance(): string
    {
        return $this->targetIdOnInstance;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}