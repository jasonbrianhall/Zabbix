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
	 * Parsed URL to redirect to.
	 *
	 * @var array
	 */
	private $url;

	/**
	 * @param string      $caption
	 * @param string|CUrl $url           URL to redirect to
	 * @param string      $confirmation  confirmation message text
	 * @param string      $class
	 */
	public function __construct($caption, $url, $confirmation = null) {
		parent::__construct($caption);

		$this->setUrl($url, $confirmation);
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

		$this->url = ($url instanceof CUrl) ? parse_url($url->getUrl()) : parse_url($url);

		return $this;
	}

	/*
	 * @param bool $destroy
	 *
	 * @return string
	 */
	public function toString($destroy = true) {
		// Register the "parent" controller by extracting its name from the back URL.
		if (array_key_exists('query', $this->url)
				&& preg_match('/action=(.*|problem)\.(list|view)/', $this->url['query'], $matches)) {
			zbx_add_post_js('chkbxRange.prefix = '.CJs::encodeJson($matches[1]).';');
		}

		return parent::toString($destroy);
	}
}
