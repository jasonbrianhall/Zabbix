<?php

class CModule {
	public $manifest = [];

	public function __construct(array $manifest) {
		$this->manifest = $manifest;
	}

	public function init() {

	}
}
