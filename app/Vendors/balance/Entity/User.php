<?php

namespace App\Vendors\balance\Entity;

class User
{
    public int   $mmr;
    /**
     * @var Role[]
     */
    protected array $roles;

    public $user_identifier = null;

    public int $priority = 0;

    public function __construct(int $mmr, array $roles, $user_identifier, int $priority)
    {
        $this->mmr             = $mmr;
        $this->roles           = $roles;
        $this->user_identifier = $user_identifier;
        $this->priority        = $priority;
    }

    public function getMmr(): int
    {
        return $this->mmr;
    }

    public function setMmr(int $mmr): void
    {
        $this->mmr = $mmr;
    }

    /**
     * @return Role[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return null
     */
    public function getUserIdentifier()
    {
        return $this->user_identifier;
    }
    public function getPriority(): int
    {
        return $this->priority;
    }
}
