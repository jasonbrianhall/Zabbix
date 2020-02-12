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

require_once dirname(__FILE__).'/../../include/CWebTest.php';

/**
 * Trait for check form layout.
 */
trait FormLayoutTrait {

	/**
	 * Check ordinary form with left and right parts.
	 *
	 * @param CFormElement $form       Form element.
	 * @param array        $labels     Form elements configuration array (label->id->type->(options|attributes)).
	 * @param array        $allValues  Data provider array.
	 */
	protected function checkOrdinaryForm(CFormElement $form, array $labels, array $allValues) {
		$fields = $form->getFields();

		foreach ($labels as $label => $field) {
			$this->assertArrayHasKey($label, $fields->asText(), '"'.$label.'" must exist.');
			$form_field = $form->getField($label);

			foreach ($field as $id => $input) {
				$this->assertEquals($id, $form_field->getAttribute('id'), 'Element was not found');
				$this->checkFormInput($form_field, $id, $input, $allValues);
			}
		}
	}

	/**
	 * Check form elements which have label on right side.
	 *
	 * @param CFormElement $form           Form element.
	 * @param array        $right_labeled  Form elements configuration array (id->label->type->attributes).
	 * @param array        $allValues      Data provider array.
	 */
	protected function checkRrightlabeledForm(CFormElement $form, array $right_labeled, array $allValues) {
		foreach ($right_labeled as $id => $field) {
			$form_field = $form->getField('id:'.$id);

			foreach ($field as $label => $input) {
				if (is_string($label)) {
					$field_label = $form_field->query('xpath:./following-sibling::label[1]')->one();
					$this->assertEquals($label, $field_label->getText(), '"'.$label.'" must exist.');
				}
				$this->checkFormInput($form_field, $id, $input, $allValues);
			}
		}
	}

	/**
	 * Check form buttons.
	 *
	 * @param CFormElement $form          Form element.
	 * @param array        $form_buttons  Form elements configuration array (id->label->type->attributes).
	 * @param array        $allValues     Data provider array.
	 */
	protected function checkFormButtons(CFormElement $form, array $form_buttons, array $allValues) {
		foreach ($form_buttons as $id => $buttons) {
			$form_field = $form->getField('id:'.$id);

			foreach ($buttons as $label => $button) {
				$this->assertEquals($label, $form_field->getText(), '"'.$label.'" must exist.');
				$this->checkFormInput($form_field, $id, $button, $allValues);
			}
		}
	}

	/**
	 * Check form element (input).
	 *
	 * @param CElement $form_field  Input element.
	 * @param string   $id          Input id attribute.
	 * @param array    $input       Element configuration array (type->(options|attributes)).
	 * @param array    $allValues   Data provider array.
	 */
	protected function checkFormInput(CElement $form_field, $id, array $input, array $allValues) {
		foreach ($input as $type => $elements) {
			if ($type == 'dropdown') {
				$options = [];

				foreach ($form_field->getOptions() as $option) {
					if ($option->isSelected()) {
						$this->assertEquals($allValues[$id], $option->getValue(),
							sprintf('option %1$s not selected', $allValues[$id])
						);
					}
					$options[$option->getValue()] = $option->getText();
				}

				$this->assertEquals($options[$allValues[$id]], $form_field->getValue());

				foreach ($elements as $value => $text) {
					$this->assertArrayHasKey($value, $options, sprintf('option value "%1$s" was not found', $value));
					$this->assertEquals($text, $options[$value], sprintf('option text "%1$s" was not found', $text));
				}
			}
			elseif ($type == 'checkbox') {
				if ($allValues[$id]) {
					$this->assertTrue($form_field->isSelected());
				}
				if ($allValues[$id] == 0) {
					$this->assertFalse($form_field->isSelected());
				}
			}
			elseif ($type == 'input') {
				$this->assertEquals($allValues[$id], $form_field->getValue(),
					sprintf('wrong value "%1$s" for input "%2$s"', $form_field->getValue(), $id)
				);
				$this->checkAttributes($form_field, $id, $type, $elements);
			}
			elseif ($type == 'button') {
				$this->checkAttributes($form_field, $id, $type, $elements);
			}
		}
	}

	/**
	 * Check input attributes.
	 *
	 * @param CElement $form_field  Input element.
	 * @param string   $id          Input id attribute.
	 * @param string   $type        Type of input element (dropdown|checkbox|input|button).
	 * @param array    $attributes  Array of attributes.
	 */
	protected function checkAttributes($form_field, $id, $type, $attributes) {
		foreach ($attributes as $attribute => $value) {
			$this->assertEquals($value, $form_field->getAttribute($attribute),
				sprintf(
					'wrong attribute "%1$s" with value "%2$s" for %3$s "%4$s"',
					$attribute,
					$form_field->getAttribute($attribute),
					$type,
					$id
				)
			);
		}
	}
}
