<?php

namespace Modules\Example;

use Z;
use CModule;
use Exception;
use CMainMenu;

class Module extends CModule {
	public function init() {
		// throw new Exception("Error Processing Request", 1);
		$main_menu = Z::$registry->get(CMainMenu::class);

		// $main_menu->find('Administration')->insertAfter('Scripts', 'Modules', [
		// 	'action' => 'module.form',
		// 	'alias' => ['module.list']
		// ]);
		$main_menu->find('Administration')->add('General', [
			'action' => 'module.form',
			'alias' => ['module.list']
		]);
	}
}
