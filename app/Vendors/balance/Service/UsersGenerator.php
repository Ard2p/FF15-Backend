<?php

namespace App\Vendors\balance\Service;

use App\Vendors\balance\Entity\MmrTier;
use App\Vendors\balance\Entity\Role;
use App\Vendors\balance\Entity\User;

class UsersGenerator
{
    /**
     * @var int
     */
    protected int   $amount;
    /**
     * @var Role[] $roles
     */
    protected array $roles;

    /**
     * @var MmrTier[] $mmr_ranges
     */
    protected array $mmr_ranges;

    public function __construct(int $amount, array $roles, array $mmr_ranges)
    {
        $this->amount     = $amount;
        $this->roles      = $roles;
        $this->mmr_ranges = $mmr_ranges;
    }

    public function generate(): array
    {
        $users = [];

        foreach (range(1, $this->amount) as $key) {
            $mmr      = rand(0, 2800);
            $mmrRange = null;
            foreach ($this->mmr_ranges as $mmr_range) {
                if ($mmr >= $mmr_range->getMin() && $mmr <= $mmr_range->getMax()) {
                    $mmrRange = $mmr_range;
                    break;
                }
            }

            shuffle($this->roles);

            $users[] = new User($mmr, array_values($this->roles), uniqid());
        }

        return $users;
    }
}
