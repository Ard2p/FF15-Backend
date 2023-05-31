<?php

namespace App\Vendors\balance\Service;

use App\Vendors\balance\Entity\Role;
use App\Vendors\balance\Entity\Team;
use App\Vendors\balance\Entity\User;

class TeamsFormer
{
    protected array $users_by_roles;

    protected int $average_mmr = 0;

    public function __construct(array $users_by_roles)
    {
        $this->users_by_roles = $users_by_roles;
    }

    public function formTeams(bool $form_all_possible_teams = true)
    {
        $teams          = [];
        $users_by_roles = [];
        foreach ($this->users_by_roles as $role => $users_by_role) {
            $users_by_roles[$users_by_role->getRole()->getName()] = $this->users_by_roles[$role]->getUsers();
        }

        $roles_keys = array_keys($users_by_roles);

        shuffle($roles_keys);

        $roles_keys = array_flip($roles_keys);

        $users_by_roles    = array_merge($roles_keys, $users_by_roles);
        $this->average_mmr = $this->getAverageUsersMmr($users_by_roles);
        $max_min           = [true, false];

        $team_range_precision              = 50;
        $team_range_precision_add          = 10;
        $team_range_precision_current_rate = $team_range_precision;

        $counter = 0;


        while ($this->getAmountOfUsers($users_by_roles) >= 5) {

            if(
                $this->getAmountOfUsers($users_by_roles) < 10
                && count($teams) % 2 === 0
                && !$form_all_possible_teams
            ) {
                break;
            }

            if ($counter === 10000) {
                //echo 'No team formed! Counter: ' . $counter . '. Total amount of teams:' . count($teams) . PHP_EOL;
                $team_range_precision_current_rate += $team_range_precision_add;
                $counter                           = 0;
            }

            $users_by_roles_temp = $users_by_roles;       
            
            



            // foreach ($users_by_roles_temp as $key => $userByRoles){                
            //     $collection = collect($userByRoles);
            //     $sorted = $collection->sortByDesc(function ($user, $key) {                   
            //         return $user->getPriority();
            //     })->all();  
            //     while (count($sorted) > (int)($this->getAmountOfUsers($users_by_roles) / 5)){
            //         end($sorted);                    
            //         unset($sorted[key($sorted)]);
            //     }               
            //     $users_by_roles_temp[$key] = $sorted;               
            // }    
            
            // dump($users_by_roles_temp['mid']);







            $team = new Team();
            $max  = $max_min[array_rand($max_min)];
            // $tmp_users_by_roles_temp = $users_by_roles_temp;
            foreach ($users_by_roles_temp as $role => $users) {
                $user = $users[array_rand($users)];
                // $user = reset($users); 
                // [array_rand($users)];
                // $tmp_users[$role] =  $user;
                
                $currentUserRole = array_reduce(
                    $user->getRoles(),
                    function (?Role $currentUserRole, Role $role2) use ($role) {
                        if ($role2->getName() === $role) {
                            return $currentUserRole = $role2;
                        }

                        return $currentUserRole;
                    }
                );
               
                $team->addUser($user, $currentUserRole);

                $key = array_search($user, $users_by_roles_temp[$role], true);
                unset($users_by_roles_temp[$role][$key]);
                $max = !$max;
            }

            // dd($tmp_users, $tmp_users_by_roles_temp);

            if (
                $team->getAverageMmr() < $this->getAverageMmr() - $team_range_precision_current_rate
                || $team->getAverageMmr() > $this->getAverageMmr() + $team_range_precision_current_rate
            ) {
                $counter++;
                continue;
            }

            $users_by_roles = $users_by_roles_temp;

            $teams[] = $team;
            $counter = 0;
        }

        return [$teams, $users_by_roles];
    }

    private function getAverageUsersMmr(array $users_by_roles)
    {
        $total_mmr = 0;
        foreach ($users_by_roles as $users_by_role) {
            foreach ($users_by_role as $user) {
                $total_mmr += $user->getMmr();
            }
        }

        return $total_mmr / $this->getAmountOfUsers($users_by_roles);
    }

    private function getAmountOfUsers(array $users_by_roles): int
    {
        $amount = 0;
        foreach ($users_by_roles as $users_by_role) {
            $amount += count($users_by_role);
        }

        return $amount;
    }


    /**
     * @return int
     */
    public function getAverageMmr(): int
    {
        return $this->average_mmr;
    }
}
