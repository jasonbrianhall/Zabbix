<?php

namespace Modules\Example\Actions;

use CController;
use CControllerResponseData;

class ModuleFormAction extends CController {
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
		$data = [];
		$response = new CControllerResponseData($data);
		$response->setTitle(_('Modules'));
		$this->setResponse($response);
	}
}
