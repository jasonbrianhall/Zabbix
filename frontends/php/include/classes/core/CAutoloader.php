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


/**
 * The Zabbix autoloader class.
 */
class CAutoloader {
	protected $root_dir;
	/**
	 * An array of directories, where the autoloader will look for the classes.
	 *
	 * @var array
	 */
	protected $includePaths = [];

	/**
	 * Initializes object with array of include paths.
	 *
	 * @param array     $includePaths absolute paths
	 * @param string    frontend root directory
	 */
	public function __construct(array $includePaths, $root_dir) {
		$this->includePaths = $includePaths;
		$this->root_dir = $root_dir;
	}

	/**
	 * Add "loadClass" method as an autoload handler.
	 *
	 * @return bool
	 */
	public function register() {
		return spl_autoload_register([$this, 'loadClass']);
	}

	/**
	 * Attempts to find and load the given class.
	 *
	 * @param $className
	 */
	protected function loadClass($className) {
		$class_path = $this->findClassFile($className);

		if ($class_path === false) {
			$class_path = $this->findNamespaceClassFile($className);
		}

		if ($class_path) {
			require $class_path;
		}
	}

	/**
	 * Attempts to find corresponding file for given class name in the current include directories.
	 *
	 * @param string $className
	 *
	 * @return bool|string
	 */
	protected function findClassFile($className) {
		foreach ($this->includePaths as $includePath) {
			$filePath = $includePath.'/'.$className.'.php';

			if (is_file($filePath)) {
				return $filePath;
			}
		}

		return false;
	}

	/**
	 * Get path to class with namespace. All namespace parts except class name will be lowercased.
	 *
	 * @param string $class    Fully qualified class name.
	 * @return bool|string
	 */
	protected function findNamespaceClassFile($class) {
		$path = explode('\\', $class);

		if (count($path) > 1) {
			$name = array_pop($path);
			$path = array_map('strtolower', $path);
			array_unshift($path, $this->root_dir);
			$path[] = $name.'.php';
			$file_path = implode('/', $path);

			return is_file($file_path) ? $file_path : false;
		}

		return false;
	}
}
