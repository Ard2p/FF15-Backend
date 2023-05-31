<?php

namespace App\Vendors\balance\Entity;

class Role
{
    public string $name;

    public function __construct(string $role_name)
    {
        $this->name = $role_name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

}