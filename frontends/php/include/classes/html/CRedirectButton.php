<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
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
 * A class for creating buttons that redirect you to a different page.
 */
class CRedirectButton extends CSimpleButton {

	/**
	 * @param string $caption
	 * @param string $url			URL to redirect to
	 * @param string $confirmation	confirmation message text
	 * @param string $class
	 */
	public function __construct($caption, $url, $confirmation = null) {
		parent::__construct($caption);

		$this->setUrl($url, $confirmation);
		$this->setJsScript($url);
	}

	/**
	 * Set the URL and confirmation message.
	 *
	 * If the confirmation is set, a confirmation pop up will appear before redirecting to the URL.
	 *
	 * @param string $url
	 * @param string|null $confirmation
	 *
	 */
	public function setUrl($url, $confirmation = null) {
		$this->setAttribute('data-url', $url);

		if ($confirmation !== null) {
			$this->setAttribute('data-confirmation', $confirmation);
		}
		return $this;
	}

	/**
	 * Register the "parent" controller by extracting its name from the back url.
	 *
	 * @param string|CUrl $url  Back url.
	 */
	private function setJsScript($url) {
		if ($url !== null) {
			$parsed = is_object($url) ? parse_url($url->getUrl()) : parse_url($url);
			if (array_key_exists('query', $parsed)
					&& preg_match('/action=(.*|problem)\.(list|view)/', $parsed['query'], $matches)) {
				zbx_add_post_js('chkbxRange.prefix = '.CJs::encodeJson($matches[1]).';');

			}
		}
	}
}
