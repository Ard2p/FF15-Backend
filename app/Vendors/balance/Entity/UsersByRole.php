<?php


namespace App\Vendors\balance\Entity;


class UsersByRole
{
    public Role $role;

    /**
     * @var User[] $users
     */
    public array $users;

    /**
     * UsersByRoles constructor.
     *
     * @param Role   $role
     * @param User[] $users
     */
    public function __construct(Role $role, $users = [])
    {
        $this->role  = $role;
        $this->users = $users;
    }

    /**
     * @param User[] $users
     */
    public function setUsers(array $users)
    {
        $this->users = $users;
    }

    public function addUser(User $user)
    {
        $this->users[] = $user;
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function removeUser(User $user): bool
    {
        foreach($this->users as $key => $user_in_role) {
            if($user_in_role === $user) {
                unset($this->users[$key]);
                $this->users = array_values($this->users);
                return true;
            }
        }
        return false;
    }

}