<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


class CModuleRegistry {

	const MODULE_ENABLED = true;
	const MODULE_DISABLED = false;
	const NAMESPACE_SEPARATOR = '.';

	/**
	 * Modules directory absolute path.
	 */
	protected $modules_dir;

	/**
	 * Available modules.
	 */
	protected $modules = [];

	/**
	 * Create class object instance
	 *
	 * @param string $modules_dir    Absolute path to modules directory.
	 */
	public function __construct($modules_dir) {
		$this->modules_dir = $modules_dir;
	}

	/**
	 * Scan modules directory and register valid modules, parses manifest.json for valid modules.
	 */
	public function scanModulesDirectory() {
		foreach (new DirectoryIterator($this->modules_dir) as $path) {
			if ($path->isDot() || !$path->isDir()) {
				continue;
			}

			$dir = $path->getPathname();
			$id = $path->getFilename();
			$manifest = $dir.'/manifest.json';
			$module = $dir.'/Module.php';

			// TODO: add php -l syntax check for Module.php file?
			if (file_exists($manifest) && file_exists($module)) {
				$manifest_json = json_decode(file_get_contents($manifest), true);

				if (!$manifest_json) {
					continue;
				}

				if (array_key_exists('manifest_version', $manifest_json) && array_key_exists('id', $manifest_json)
						&& $manifest_json['id'] === $id) {
					$this->modules[$id] = [
						'id' => $id,
						'path' => [
							'root' => $dir,
							'manifest' => $manifest,
							'module' => $module
						],
						'manifest' => $manifest_json,
						'status' => static::MODULE_DISABLED
					];
				}
			}
		}
	}

	/**
	 * Set module runtime enabled/disabled status.
	 *
	 * @param string $id        Module id.
	 * @param bool   $status    Module status.
	 */
	public function setModuleStatus($id, $status) {
		if (array_key_exists($id, $this->modules)) {
			$this->modules[$id]['status'] = $status ? static::MODULE_ENABLED : static::MODULE_DISABLED;
		}
	}

	/**
	 * Create instance of enabled module and call init.
	 */
	public function initModules() {
		foreach ($this->modules as &$module_details) {
			if ($module_details['status'] == static::MODULE_ENABLED) {
				$module_class = 'Modules\\'.$module_details['id'].'\\Module';
				$manifest = $module_details['manifest'];
				$instance = new $module_class($manifest);
				$instance->init();
				$module_details['instance'] = $instance;
			}
		}
		unset($module_details);
	}

	/**
	 * Return actions only for modules with enabled status.
	 *
	 * Action keys:
	 *     fqcn        Fully qualified class name for action.
	 *     view        View file name for action, default value null.
	 *     layout      Layout file name for action, default 'layout.htmlpage'.
	 *
	 * @return array
	 */
	public function getModulesRoutes() {
		$routes = [];

		foreach ($this->modules as $module) {
			if ($module['status'] === static::MODULE_ENABLED && $module['manifest']
					&& array_key_exists('actions', $module['manifest'])) {
				$namespace = $module['id'];

				foreach ($module['manifest']['actions'] as $action) {
					$routes[$action['action']] = [
						'fqcn' => 'Modules\\'.ucfirst($namespace).'\\Actions\\'.$action['class'],
						'view' => array_key_exists('view', $action) ? $action['view'] : null,
						'layout' => array_key_exists('layout', $action) ? $action['layout'] : 'layout.htmlpage',
						'module' => $namespace
					];
				}
			}
		}

		return $routes;
	}

	/**
	 * Get absolute path root directory for module.
	 */
	public function getModuleRootDir($module) {
		return array_key_exists($module, $this->modules) ? $this->modules[$module]['path']['dir'] : null;
	}
}
