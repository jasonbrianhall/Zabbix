<?php

class CMenuItem {
	protected $label;
	protected $items = [];
	protected $alias = [];
	protected $visible = null;
	protected $action;

	public function __construct($label, array $item) {
		$this->label = $label;
		$this->action = array_key_exists('action', $item) ? $item['action'] : '';
		$this->items = [];

		if (array_key_exists('items', $item)) {
			foreach ($item['items'] as $child_label => $child_item) {
				$this->add($child_label, $child_item);
			}
		}

		if (array_key_exists('visible', $item) && is_callable($item['visible'])) {
			$this->visible = $item['visible'];
		}
	}

	public function getItems() {
		return $this->items;
	}

	public function add($label, $item) {
		$this->items[$label] = new CMenuItem($label, $item);

		return $this;
	}

	public function remove($label) {
		unset($this->items[$label]);

		return $this;
	}

	public function find($label) {
		return array_key_exists($label, $this->items) ? $this->items[$label] : null;
	}

	public function insertBefore($before_label, $label, $data) {
		if (array_key_exists($before_label, $this->items)) {
			$index = array_search($before_label, array_keys($this->items));
			$before = $index > 0 ? array_slice($this->items, 0, $index) : [];
			$after = $index < count($this->items) ? array_slice($this->items, $index) : [];
			$this->items = $before + [$label => new CMenuItem($label, $data)] + $after;
		}

		return $this;
	}

	public function insertAfter($after_label, $label, $data) {
		if (array_key_exists($after_label, $this->items)) {
			$index = array_search($after_label, array_keys($this->items));
			$before = $index >= 0 ? array_slice($this->items, 0, $index + 1) : [];
			$after = $index < count($this->items) ? array_slice($this->items, $index + 1) : [];
			$this->items = $before + [$label => new CMenuItem($label, $data)] + $after;
		}

		return $this;
	}
}
