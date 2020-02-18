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

require_once dirname(__FILE__).'/../include/CLegacyWebTest.php';
require_once dirname(__FILE__).'/traits/FormTrait.php';

/**
 * @backup config
 */
class testFormAdministrationGeneralGUI extends CWebTest {

	use FormTrait;

	private static $test_dropdown_first_entry = true;
	const MESSAGE_SNIPPET = 'The value of the "%1$s" field in the config does not %2$s.';

	public static function allValues() {
		return CDBHelper::getDataProvider(
			'SELECT default_theme,dropdown_first_entry,dropdown_first_remember,search_limit,max_in_table,'.
				'server_check_interval'.
			' FROM config'.
			' ORDER BY configid'
		);
	}

	/**
	* @dataProvider allValues
	*/
	public function testFormAdministrationGeneralGUI_CheckLayout($allValues) {
		$this->page->login()->open('zabbix.php?action=gui.edit');
		$popup = $this->query('id:page-title-general')->asPopupButton()->one()->select('GUI');

		$form = $this->query('xpath:.//main/form')->asForm()->waitUntilVisible()->one();

		$this->assertPageTitle('Configuration of GUI');
		$this->assertEquals('GUI', $this->query('tag:h1')->one()->getText());

		$labels = [
			'Default theme' => [					// label
				'default_theme' => [				// id
					TEST_DROPDOWN => [				// type
						'blue-theme' => 'Blue',
						'dark-theme' => 'Dark',
						'hc-light' => 'High-contrast light',
						'hc-dark' => 'High-contrast dark'
					]
				]
			],
			'Dropdown first entry' => [
				'dropdown_first_entry' => [TEST_DROPDOWN => ['None', 'All']]
			],
			'Limit for search and filter results' => [
				'search_limit' => [TEST_INPUT => ['maxlength' => '6']]
			],
			'Max count of elements to show inside table cell' => [
				'max_in_table' => [TEST_INPUT => ['maxlength' => '5']]
			],
			'Show warning if Zabbix server is down' => [
				'server_check_interval' => [TEST_CHECKBOX => true]
			]
		];

		$form_buttons = [
			'update' => [					// id
				'Update' => [				// label
					TEST_BUTTON => [		// type
						'name' => 'update',
						'value' => 'Update'
					]
				]
			],
		];

		$right_labeled = [
			'dropdown_first_remember' => [		// id
				'remember selected' => [		// label
					TEST_CHECKBOX => true		// type
				]
			],
		];

		$this->assertOrdinaryForm($form, $labels, $allValues);
		$this->assertFormButtons($form, $form_buttons, $allValues);
		$this->assertRrightlabeledForm($form, $right_labeled, $allValues);

	}

	public function getUpdateData() {
		return [
			// All minimal values.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Default theme' => 'Blue',
						'Dropdown first entry' => 'None',
						'Limit for search and filter results' => '1',
						'Max count of elements to show inside table cell' => '1',
						'Show warning if Zabbix server is down' => false,
						'id:dropdown_first_remember' => false
					],
					'check_form' => true
				]
			],
			// All maximal values.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Default theme' => 'High-contrast dark',
						'Dropdown first entry' => 'All',
						'Limit for search and filter results' => '999999',
						'Max count of elements to show inside table cell' => '99999',
						'Show warning if Zabbix server is down' => true,
						'id:dropdown_first_remember' => true
					],
					'check_form' => true
				]
			],
			// Test "Dark" theme.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Default theme' => 'Dark'
					],
					'check_form' => true
				]
			],
			// Test "High-contrast light" theme.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Default theme' => 'High-contrast light'
					],
					'check_form' => true
				]
			],
			// All defaults values.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Default theme' => 'Blue',
						'Dropdown first entry' => 'All',
						'Limit for search and filter results' => '1000',
						'Max count of elements to show inside table cell' => '50',
						'Show warning if Zabbix server is down' => false,
						'id:dropdown_first_remember' => true
					],
					'check_form' => true
				]
			],
			// Test hosts table row count after update.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Dropdown first entry' => 'All',
						'Limit for search and filter results' => '3',
					],
					'check_form' => true
				]
			],
			// Test hosts table cell limit after update.
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Dropdown first entry' => 'All',
						'Limit for search and filter results' => '1001',
						'Max count of elements to show inside table cell' => '1'
					],
					'check_form' => true
				]
			],
			// Incorrect Limit for search and filter results.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Limit for search and filter results' => '0'
					],
					'error_title' => 'Cannot update configuration',
					'error_details' => 'Incorrect value "0" for "search_limit" field.'
				]
			],
			// Incorrect Limit for search and filter results.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Limit for search and filter results' => '-1'
					],
					'error_title' => 'Cannot update configuration',
					'error_details' => 'Incorrect value "-1" for "search_limit" field.'
				]
			],
			// Incorrect Max count of elements to show inside table cell.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Max count of elements to show inside table cell' => '0'
					],
					'error_title' => 'Cannot update configuration',
					'error_details' => 'Incorrect value "0" for "max_in_table" field.'
				]
			],
			// Incorrect Max count of elements to show inside table cell.
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Max count of elements to show inside table cell' => '-1'
					],
					'error_title' => 'Cannot update configuration',
					'error_details' => 'Incorrect value "-1" for "max_in_table" field.'
				]
			]
		];
	}

	/**
	 * @dataProvider getUpdateData
	 */
	public function testFormAdministrationGeneralGUI_Update($data) {
		$sql = 'SELECT default_theme,dropdown_first_entry,dropdown_first_remember,search_limit,max_in_table,'.
				'server_check_interval'.
			' FROM config'.
			' ORDER BY configid';

		$old_hash = CDBHelper::getHash($sql);

		$this->page->login()->open('zabbix.php?action=gui.edit');

		$form = $this->query('xpath:.//main/form')->asForm()->waitUntilVisible()->one();
		$form->fill($data['fields']);
		$form->submit();
		$this->page->waitUntilReady();

		// Verify if the config was updated.
		$this->assertFormMessage($data, 'Configuration updated', 'Page received incorrect data');


		if ($data['expected'] === TEST_BAD) {
			$this->assertEquals($old_hash, CDBHelper::getHash($sql), 'Error form should not be saved.');
		}

		if (CTestArrayHelper::get($data, 'check_form', false)) {
			$this->page->open('zabbix.php?action=gui.edit');
			$form_update = $this->query('xpath:.//main/form')->asForm()->waitUntilVisible()->one();

			// Verify that fields are updated.
			$check_fields = [
				'Default theme',
				'Dropdown first entry',
				'Limit for search and filter results',
				'Max count of elements to show inside table cell',
				'Show warning if Zabbix server is down',
				'id:dropdown_first_remember'
			];

			$this->assertFormFields($form_update, $check_fields, $data);

			array_shift($check_fields);

			$this->assertResultFields($check_fields, $data);
		}

	}

	private function assertResultFields($check_result_for, $data) {
		foreach ($check_result_for as $field_name) {
			if (array_key_exists($field_name, $data['fields'])) {
				switch ($field_name) {
					case 'Dropdown first entry':
						if (self::$test_dropdown_first_entry) {
							$this->page->open('hosts.php');
							$select = $this->query('css:div.header-title select')->asDropdown()->waitUntilVisible()->one();
							$option = $select->query("xpath:.//option[@value='0']")->one();

							$pattern = ['None' => 'not selected', 'All' => 'all'];

							$this->assertEquals($pattern[$data['fields'][$field_name]], $option->getText(),
								sprintf(self::MESSAGE_SNIPPET, $field_name, 'match the first dropdown option in Group')
							);
							if ($data['fields'][$field_name] == 'All') {
								self::$test_dropdown_first_entry = false;
							}
						}
						break;

					case 'Limit for search and filter results':
						if ($data['fields'][$field_name] == '3') {
							$this->page->open('hosts.php');
							$table = $this->query('xpath:.//main/form/table')->asTable()->waitUntilVisible()->one();

							$this->assertEquals(3, $table->getRows()->count(),
								sprintf(self::MESSAGE_SNIPPET, $field_name, 'limit table size')
							);
						}
						break;

					case 'Max count of elements to show inside table cell':
						if ($data['fields'][$field_name] == '1'
								&& $data['fields']['Limit for search and filter results'] == '1001') {
							$this->page->open('hosts.php');
							$cell = $this->query("xpath://a[text()='Template App Zabbix Server']/..")
								->asTableRow()->waitUntilVisible()->one();

							$this->assertEquals(html_entity_decode('&hellip;'), mb_substr($cell->getText(), -1),
								sprintf(self::MESSAGE_SNIPPET, $field_name, 'limit table row elements')
							);
						}
						break;

					case 'Show warning if Zabbix server is down':
						if ($data['fields'][$field_name] == true) {
							$this->page->open('hosts.php');
							$message = $this->query('xpath://output')->waitUntilVisible()->one();

							$this->assertEquals(
								'Zabbix server is not running: the information displayed may not be current.',
								$message->getText(),
								sprintf(self::MESSAGE_SNIPPET, $field_name, 'allow displaying a warning in the footer')
							);
						}
						break;

					case 'id:dropdown_first_remember':
						if ($data['fields'][$field_name] == true &&  $data['fields']['Default theme'] == 'Blue') {
							$this->page->open('hosts.php');
							$select = $this->query('css:div.header-title select')
								->asDropdown()->waitUntilVisible()->one();

							$option = $select->query("xpath:.//option[@value='4']")->one()->click();
							$this->page->open('templates.php')->waitUntilReady();

							$this->page->open('hosts.php?ddreset=1');
							$select = $this->query('css:div.header-title select')
								->asDropdown()->waitUntilVisible()->one();

							$option = $select->query("xpath:.//option[@value='4']")->one();

							$this->assertTrue(
								$option->isSelected(),
								sprintf(
									self::MESSAGE_SNIPPET,
									$field_name,
									'allow saving the value of the selected option'
								)
							);
						}
						break;
				}
			}
		}
	}
}
