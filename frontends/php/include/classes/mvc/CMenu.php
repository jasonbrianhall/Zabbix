<?php

class CMenu extends CMenuItem {
	protected $items = [];

	public function __construct(array $items = []) {
		foreach ($items as $label => $item) {
			$this->items[$label] = new CMenuItem($label, $item);
		}
	}
}
