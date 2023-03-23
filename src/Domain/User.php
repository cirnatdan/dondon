<?php

namespace Domain;

class User
{
    private int $id;
    public function __construct(
        private string $username,
        private string $instance,
        private int $idOnInstance,
        private \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
    )
    {
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
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

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}