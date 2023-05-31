<?php


namespace App\Vendors\balance\Entity;


class Team
{
    /**
     * @var User[] $users
     */
    public array $users = [];

    public int $average_mmr = 0;

    public int $total_mmr = 0;

    public function addUser(User $user, Role $role): void
    {
        $this->users[$role->getName()] = $user;
        $this->average_mmr             = $this->getAverageMmr();
        $this->total_mmr               += $user->getMmr();
    }

    public function removeUser(User $user): bool
    {
        $key = array_search($user, $this->users, true);
        if ($key !== false) {
            $this->total_mmr -= $this->users[$key]->getMmr();
            unset($this->users[$key]);
            $this->users = array_values($this->users);

            return true;
        }

        return false;
    }

    public function getAverageMmr(): int
    {
        $mmr = 0;

        foreach ($this->users as $user) {
            $mmr += $user->getMmr();
        }

        return $mmr / count($this->users);
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }
}
