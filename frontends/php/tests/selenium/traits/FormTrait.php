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
trait FormTrait {

	/**
	 * Check ordinary form with left and right parts.
	 *
	 * @param CFormElement $form       Form element.
	 * @param array        $labels     Form elements configuration array (label->id->type->(options|attributes)).
	 * @param array        $allValues  Data provider array.
	 */
	protected function assertOrdinaryForm(CFormElement $form, array $labels, array $allValues) {
		$fields = $form->getFields();

		foreach ($labels as $label => $field) {
			$this->assertArrayHasKey($label, $fields->asText(), '"'.$label.'" must exist.');
			$form_field = $form->getField($label);

			foreach ($field as $id => $input) {
				$this->assertEquals($id, $form_field->getAttribute('id'), 'Element was not found');
				$this->assertFormInput($form_field, $id, $input, $allValues);
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
	protected function assertRrightlabeledForm(CFormElement $form, array $right_labeled, array $allValues) {
		foreach ($right_labeled as $id => $field) {
			$form_field = $form->getField('id:'.$id);

			foreach ($field as $label => $input) {
				if (is_string($label)) {
					$field_label = $form_field->query('xpath:./following-sibling::label[1]')->one();
					$this->assertEquals($label, $field_label->getText(), '"'.$label.'" must exist.');
				}
				$this->assertFormInput($form_field, $id, $input, $allValues);
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
	protected function assertFormButtons(CFormElement $form, array $form_buttons, array $allValues) {
		foreach ($form_buttons as $id => $buttons) {
			$form_field = $form->getField('id:'.$id);

			foreach ($buttons as $label => $button) {
				$this->assertEquals($label, $form_field->getText(), '"'.$label.'" must exist.');
				$this->assertFormInput($form_field, $id, $button, $allValues);
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
	protected function assertFormInput(CElement $form_field, $id, array $input, array $allValues) {
		foreach ($input as $type => $elements) {
			switch ($type) {
				case TEST_DROPDOWN:
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
						$this->assertArrayHasKey($value, $options,
							sprintf('option value "%1$s" was not found', $value)
						);
						$this->assertEquals($text, $options[$value],
							sprintf('option text "%1$s" was not found', $text)
						);
					}
					break;

				case TEST_CHECKBOX:
					if ($allValues[$id]) {
						$this->assertTrue($form_field->isSelected());
					}
					if ($allValues[$id] == 0) {
						$this->assertFalse($form_field->isSelected());
					}
					break;

				case TEST_INPUT:
					$this->assertEquals($allValues[$id], $form_field->getValue(),
						sprintf('wrong value "%1$s" for input "%2$s"', $form_field->getValue(), $id)
					);
					$this->assertAttributes($form_field, $id, $type, $elements);
					break;

				case TEST_BUTTON:
					$this->assertAttributes($form_field, $id, $type, $elements);
					break;
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
	protected function assertAttributes($form_field, $id, $type, $attributes) {
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

	/**
	 * Check the field values after update.
	 *
	 * @param CFormElement $form          Form element.
	 * @param array        $check_fields  Array with fields list.
	 * @param array        $data          Data Array.
	 */
	protected function assertFormFields(CFormElement $form, array $check_fields, array $data) {
		foreach ($check_fields as $field_name) {
			if (array_key_exists($field_name, $data['fields'])) {
				$this->assertEquals($data['fields'][$field_name], $form->getField($field_name)->getValue(),
					sprintf('Incorrect value in the DB field "%s" after update.', $field_name)
				);
			}
		}
	}

	/**
	 * Check form messages after update.
	 *
	 * @param array  $data        Data Array.
	 * @param string $good_title  Expected good message.
	 * @param string $bad_title   Expected error message..
	 */
	protected function assertFormMessage(array $data, $good_title, $bad_title) {
		$message = CMessageElement::find()->one();
		switch ($data['expected']) {
			case TEST_GOOD:
				$this->assertTrue($message->isGood(), $message->getTitle());
				$this->assertEquals($good_title, $message->getTitle());
				break;

			case TEST_BAD:
				$this->assertTrue($message->isBad(), 'No expected error message.');
				$this->assertEquals(CTestArrayHelper::get($data, 'error_title', $data['error_title'] = $bad_title),
					$message->getTitle()
				);
				if (array_key_exists('error_details', $data)) {
					$this->assertTrue($message->hasLine($data['error_details']), 'No expected error message details.');
				}
				break;
		}
	}
}
