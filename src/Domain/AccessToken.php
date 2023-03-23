<?php

namespace Domain;

class AccessToken
{
    public function __construct(
        private int $idOnInstance,
        private string $instance,
        private string $accessToken,
        private \DateTimeInterface $createdAt,
    )
    {
    }

    public function getIdOnInstance(): int
    {
        return $this->idOnInstance;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}