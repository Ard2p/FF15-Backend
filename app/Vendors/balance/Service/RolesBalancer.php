<?php

namespace App\Vendors\balance\Service;

use App\Vendors\balance\Entity\Role;
use App\Vendors\balance\Entity\User;
use App\Vendors\balance\Entity\UsersByRole;

class RolesBalancer
{
    /**
     * @var Role[] $roles
     */
    protected array $roles;

    protected int $users_amount = 0;

    /**
     * RolesBalancer constructor.
     *
     * @param UsersByRole[] $usersByRoles
     */
    protected array $usersByRoles;

    public function __construct(int $users_amount, array $roles)
    {
        $this->users_amount = $users_amount;
        $this->roles        = $roles;
    }

    /**
     * @return UsersByRole[]
     */
    public function getUsersByRoles(): array
    {
        return $this->usersByRoles;
    }

    /**
     * @param UsersByRole[] $usersByRole
     */
    public function setUsersByRole(array $usersByRole): void
    {
        $this->usersByRoles = $usersByRole;
    }

    public function getBalancedUsersByRoles(array $usersByRoles): array
    {
        if ($this->users_amount === 0) {
            return [];
        }
        $users_amount_per_role = (int)floor($this->users_amount / 5);

        $role_index = 1;

        $isBalanced = false;

        while (!$isBalanced) {
            $isBalanced = true;
            foreach ($usersByRoles as $role1 => &$usersByRole1) {
                if (count($usersByRole1->getUsers()) >= $users_amount_per_role) {
                    continue;
                }

                while ($role_index < count($usersByRoles)) {
                    foreach ($usersByRoles as $role2 => &$usersByRole2) {
                        if ($role1 === $role2) {
                            continue;
                        }

                        if (count($usersByRole2->getUsers()) <= $users_amount_per_role) {
                            continue;
                        }

                        $isBalanced = false;

                        [$usersByRole1Balanced, $usersByRole2Balanced] = $this->moveUsersToOtherRole(
                            $this->roles[$role1],
                            $usersByRole1->getUsers(),
                            $usersByRole2->getUsers(),
                            $users_amount_per_role,
                            $role_index
                        );

                        $usersByRoles[$role1]->setUsers($usersByRole1Balanced);
                        $usersByRoles[$role2]->setUsers($usersByRole2Balanced);
                    }
                    $role_index++;
                }

                $role_index = 1;
            }
        }

        return $usersByRoles;
    }

    /**
     * @param User[] $usersByRole1
     * @param User[] $usersByRole2
     * @param int    $users_amount_per_role
     *
     * @return array
     */
    protected function moveUsersToOtherRole(
        Role $destinationRole,
        array $usersByRole1,
        array $usersByRole2,
        int $users_amount_per_role,
        int $role_index
    ) {
        while (count($usersByRole1) < $users_amount_per_role && count($usersByRole2) > $users_amount_per_role) {
            foreach ($usersByRole2 as $user_key => $userByRole2) {
                if ($userByRole2->getRoles()[$role_index]->getName() === $destinationRole->getName()) {
                    $usersByRole1[] = $userByRole2;
                    unset($usersByRole2[$user_key]);
                    continue 2;
                }
            }
            break;
        }

        return [array_values($usersByRole1), array_values($usersByRole2)];
    }
}
