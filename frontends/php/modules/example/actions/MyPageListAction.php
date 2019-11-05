<?php

namespace Modules\Example\Actions;

use CController;
use CControllerResponseData;

class MyPageListAction extends CController {
	protected function init() {
		$this->disableSIDValidation();
	}

	public function checkPermissions() {
		return true;
	}

	public function checkInput() {
		return true;
	}

	public function doAction() {
		$data = [
			'modules' => [
				[
					'version' => '1.0',
					'id' => 'example',
					'enabled' => false,
					'description' => 'Example module.',
					'url' => 'http://example.com'
				],
				[
					'version' => '1.0',
					'id' => 'custom_hosts',
					'enabled' => true,
					'description' => 'Custom hosts list page module.',
					'url' => 'http://example.org'
				]
			]
		];
		$response = new CControllerResponseData($data);
		$response->setTitle(_('Modules'));
		$this->setResponse($response);
	}
}
