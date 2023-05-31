<?php

return [
	'roles' => [

		'dev'       => [
			'dev@view',
			'admin@view',
			'page@create',
			'page@edit',
			'page@delete',
			'banner@create',
			'banner@edit',
			'banner@delete',
			'shop@create',
			'shop@edit',
			'shop@delete',
			'tournament@create',
			'tournament@edit',
			'tournament@edit-self',
			'tournament@delete',
			'tournament@info-role',
			'tournament@kick',
			'tournament@toggle-leave',
			'tournament@toggle-grid',
			'team@create',
			'team@edit',
			'team@edit-self',
			'team@delete',
			'team@delete-self',
			'user@edit',
			'user@edit-self',
			'user@edit-role',
			'user@info-role',
			'user@note',
			'user@ban',
			'profile@edit',
			'profile@info-role',
			'account@edit',
			'account@update',
			'account@update-self',
			'account@deactivate',
			'account@deactivate-self',
			'account@info-role',
		],

		'admin'     => [
			'admin@view',
			'page@create',
			'page@edit',
			'page@delete',
			'banner@create',
			'banner@edit',
			'banner@delete',
			'shop@create',
			'shop@edit',
			'shop@delete',
			'tournament@create',
			'tournament@edit',
			'tournament@edit-self',
			'tournament@delete',
			'tournament@info-role',
			'tournament@kick',
			'tournament@toggle-leave',
			'tournament@toggle-grid',
			'team@create',
			'team@edit',
			'team@edit-self',
			'team@delete',
			'team@delete-self',
			'user@edit',
			'user@edit-self',
			'user@edit-role',
			'user@info-role',
			'user@note',
			'user@ban',
			'profile@edit',
			'profile@info-role',
			'account@edit',
			'account@update',
			'account@update-self',
			'account@deactivate',
			'account@deactivate-self',
			'account@info-role',
		],

		'moder'     => [
			'admin@view',
			'page@create',
			'page@edit',
			'page@delete',
			'banner@create',
			'banner@edit',
			'banner@delete',
			'tournament@kick',
			'tournament@edit',
			'tournament@info-role',
			'team@create',
			'team@edit',
			'team@edit-self',
			'team@delete',
			'team@delete-self',
			'user@edit-role',
			'user@edit-self',
			'user@info-role',
			'user@ban',
			'account@update',
			'account@update-self',
			'account@deactivate',
			'account@deactivate-self',
			'account@info-role',
		],

		'arbiter'   => [
			'team@create',
			'team@edit-self',
			'team@delete-self',
			'tournament@edit-self',
			'tournament@kick',
			'tournament@info-role',
			'tournament@toggle-leave',
			'user@edit-self',
			'account@update-self',
			'account@deactivate-self',
		],

		'streamer'  => [
			'team@create',
			'team@edit-self',
			'team@delete-self',
			'user@edit-self',
			'account@update-self',
			'account@deactivate-self',
		],

		'sword'     => [
			'team@create',
			'team@edit-self',
			'team@delete-self',
			'user@edit-self',
			'account@update-self',
			'account@deactivate-self',
		],

		'vip'       => [
			'team@create',
			'team@edit-self',
			'team@delete-self',
			'user@edit-self',
			'account@update-self',
			'account@deactivate-self',
		],

		'user'      => [
			'team@create',
			'team@edit-self',
			'team@delete-self',
			'user@edit-self',
			'account@update-self',
			'account@deactivate-self',
		]
	],

	'permissions' => [

		// '@create',            Проверка прав на создание
		// '@edit',              Проверка прав на редактирование
		// '@edit-self',         Проверка прав владельца на редактирование
		// '@info-role',         Проверка в зависимости от роли
		// '@delete',            Проверка прав на создание


		#region Админская
		'dev@view',
		'admin@view',

		'page@create',
		'page@edit',
		'page@delete',

		'banner@create',
		'banner@edit',
		'banner@delete',
		#endregion

		#region Турниры
		'tournament@create',
		'tournament@edit',
		'tournament@edit-self',
		'tournament@delete',
		'tournament@info-role',
		'tournament@kick',
		'tournament@toggle-leave',
		'tournament@toggle-grid',
		#endregion

		#region Команды
		'team@create',
		'team@edit',
		'team@edit-self',
		'team@delete',
		'team@delete-self',
		#endregion

		#region Магазин
		'shop@create',
		'shop@edit',
		'shop@delete',
		#endregion


		#region Пользователь
		'user@edit',
		'user@edit-self',
		'user@edit-role',
		'user@info-role',
		'user@note',
		'user@ban',

		'profile@edit',
		'profile@info-role',

		'account@edit',
		'account@update',
		'account@update-self',
		'account@deactivate',
		'account@deactivate-self',
		'account@info-role',
		#endregion
	]
];
