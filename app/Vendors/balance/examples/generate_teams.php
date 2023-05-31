<?php

require_once __DIR__ . '/../vendor/autoload.php';

use \Zzepish\Entity\Role;
use \Zzepish\Entity\User;
use \Zzepish\Entity\MmrTier;
use \Zzepish\Service\UsersGenerator;
use Zzepish\Entity\UsersByRole;
use Zzepish\Service\RolesBalancer;
use \Zzepish\Service\TeamsFormer;

$roles_list = [
    'Support'  => new Role('Support'),
    'ADK'      => new Role('ADK'),
    'Top line' => new Role('Top line'),
    'Mid line' => new Role('Mid line'),
    'Jungle'   => new Role('Jungle'),
];

$mmr_ranges = [
    'Iron'         => new MmrTier('Iron', 0, 499),
    'Bronze'       => new MmrTier('Bronze', 500, 899),
    'Silver'       => new MmrTier('Silver', 900, 1299),
    'Gold'         => new MmrTier('Gold', 1300, 1699),
    'Platinum'     => new MmrTier('Platinum', 1700, 2099),
    'Diamond'      => new MmrTier('Diamond', 2100, 2499),
    'Master'       => new MmrTier('Master', 2500, 2599),
    'Grand Master' => new MmrTier('Grand Master', 2600, 2699),
    'Challenger'   => new MmrTier('Challenger', 2700, 2800),
];

$users_amount = 39;

$usersGenerator = new UsersGenerator($users_amount, $roles_list, $mmr_ranges);

$usersByRoles = [];

foreach ($roles_list as $role_name => $role) {
    $usersByRoles[$role_name] = new UsersByRole($role);
}

$users = $usersGenerator->generate();

/*$users = [
    new User(
        2725, $mmr_ranges['Challenger'], [
                $roles_list['ADK'],
                $roles_list['Jungle'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2655, $mmr_ranges['Grand Master'], [
                $roles_list['ADK'],
                $roles_list['Jungle'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2788, $mmr_ranges['Challenger'], [
                $roles_list['ADK'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2701, $mmr_ranges['Challenger'], [
                $roles_list['ADK'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2200, $mmr_ranges['Diamond'], [
                $roles_list['ADK'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2500, $mmr_ranges['Diamond'], [
                $roles_list['ADK'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2322, $mmr_ranges['Diamond'], [
                $roles_list['Top line'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2465, $mmr_ranges['Diamond'], [
                $roles_list['Top line'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        922, $mmr_ranges['Silver'], [
               $roles_list['Support'],
               $roles_list['Mid line'],
               $roles_list['Top line'],
               $roles_list['Top line'],
               $roles_list['Jungle'],
           ]
    ),
    new User(
        1200, $mmr_ranges['Silver'], [
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        525, $mmr_ranges['Bronze'], [
               $roles_list['Support'],
               $roles_list['Mid line'],
               $roles_list['Top line'],
               $roles_list['Mid line'],
               $roles_list['Jungle'],
           ]
    ),
    new User(
        1742, $mmr_ranges['Platinum'], [
                $roles_list['Top line'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1799, $mmr_ranges['Platinum'], [
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1855, $mmr_ranges['Platinum'], [
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2050, $mmr_ranges['Platinum'], [
                $roles_list['Mid line'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1500, $mmr_ranges['Gold'], [
                $roles_list['Mid line'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1355, $mmr_ranges['Gold'], [
                $roles_list['Mid line'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1400, $mmr_ranges['Gold'], [
                $roles_list['Mid line'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1322, $mmr_ranges['Gold'], [
                $roles_list['Mid line'],
                $roles_list['Jungle'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1698, $mmr_ranges['Gold'], [
                $roles_list['Jungle'],
                $roles_list['Top line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
];
$users = array_merge($users, [
    new User(
        2725, $mmr_ranges['Challenger'], [
                $roles_list['ADK'],
                $roles_list['Jungle'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2655, $mmr_ranges['Grand Master'], [
                $roles_list['ADK'],
                $roles_list['Jungle'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2788, $mmr_ranges['Challenger'], [
                $roles_list['ADK'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2701, $mmr_ranges['Challenger'], [
                $roles_list['ADK'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2200, $mmr_ranges['Diamond'], [
                $roles_list['ADK'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2500, $mmr_ranges['Diamond'], [
                $roles_list['ADK'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2322, $mmr_ranges['Diamond'], [
                $roles_list['Top line'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2465, $mmr_ranges['Diamond'], [
                $roles_list['Top line'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        922, $mmr_ranges['Silver'], [
               $roles_list['Support'],
               $roles_list['Mid line'],
               $roles_list['Top line'],
               $roles_list['Top line'],
               $roles_list['Jungle'],
           ]
    ),
    new User(
        1200, $mmr_ranges['Silver'], [
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        525, $mmr_ranges['Bronze'], [
               $roles_list['Support'],
               $roles_list['Mid line'],
               $roles_list['Top line'],
               $roles_list['Mid line'],
               $roles_list['Jungle'],
           ]
    ),
    new User(
        1742, $mmr_ranges['Platinum'], [
                $roles_list['Top line'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1799, $mmr_ranges['Platinum'], [
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1855, $mmr_ranges['Platinum'], [
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        2050, $mmr_ranges['Platinum'], [
                $roles_list['Mid line'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1500, $mmr_ranges['Gold'], [
                $roles_list['Mid line'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1355, $mmr_ranges['Gold'], [
                $roles_list['Mid line'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1400, $mmr_ranges['Gold'], [
                $roles_list['Mid line'],
                $roles_list['Support'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1322, $mmr_ranges['Gold'], [
                $roles_list['Mid line'],
                $roles_list['Jungle'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
    new User(
        1698, $mmr_ranges['Gold'], [
                $roles_list['Jungle'],
                $roles_list['Top line'],
                $roles_list['Mid line'],
                $roles_list['Top line'],
                $roles_list['Jungle'],
            ]
    ),
]);*/

foreach ($users as $user) {
    $usersByRoles[$user->getRoles()[0]->getName()]->addUser($user);
}

$average_mmr = 0;

foreach ($users as $user) {
    $average_mmr += $user->getMmr();
}

$average_mmr /= count($users);


$rolesBalancer = new RolesBalancer(count($users), $roles_list);
$usersByRoles  = $rolesBalancer->getBalancedUsersByRoles($usersByRoles);

$teamsFormer = new TeamsFormer($usersByRoles);

$formedTeams = $teamsFormer->formTeams(false);

if (php_sapi_name() == 'cli') {
    foreach ($usersByRoles as $key => $balancedTeam) {
        echo $key . ' - ' . count($balancedTeam->getUsers()) . PHP_EOL;
    }
    echo $teamsFormer->getAverageMmr() . PHP_EOL;
    foreach ($formedTeams[0] as $formedTeam) {
        echo 'MMR: ' . $formedTeam->getAverageMmr() . PHP_EOL;
    }
    echo 'Not in team: ' . PHP_EOL;
    foreach ($formedTeams[1] as $notInTeamUser) {
        echo 'MMR: ' . $notInTeamUser->getAverageMmr() . PHP_EOL;
    }
} else {
    \Symfony\Component\VarDumper\VarDumper::dump($usersByRoles);
    echo 'Average MMR: ' . $teamsFormer->getAverageMmr() . '<br>';
    \Symfony\Component\VarDumper\VarDumper::dump($formedTeams[0]);
    \Symfony\Component\VarDumper\VarDumper::dump($formedTeams[1]);
}
