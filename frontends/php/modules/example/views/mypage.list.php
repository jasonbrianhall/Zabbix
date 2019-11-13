<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
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


$widget = (new CWidget())
	->setTitle(_('Modules'))
	->addItem((new CFilter((new CUrl('zabbix.php'))->setArgument('action', 'example.list')))
		->setProfile('module.example.list')
		->setActiveTab(1)
		->addFilterTab(_('Filter'), [
			(new CFormList())->addRow(_('Name'),
				(new CTextBox('filter_name', ''))
					->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH)
					->setAttribute('autofocus', 'autofocus')
			),
			(new CFormList())->addRow(_('Status'),
				(new CRadioButtonList('filter_status', -1))
					->addValue(_('all'), -1)
					->addValue(_('Enabled'), MEDIA_TYPE_STATUS_ACTIVE)
					->addValue(_('Disabled'), MEDIA_TYPE_STATUS_DISABLED)
					->setModern(true)
			)
		])
		->addVar('action', 'example.list')
	);

// create form
$mediaTypeForm = (new CForm())->setName('mediaTypesForm');

// create table
$mediaTypeTable = (new CTableInfo())
	->setHeader([
		(new CColHeader(
			(new CCheckBox('all_media_types'))
				->onClick("checkAll('".$mediaTypeForm->getName()."', 'all_media_types', 'mediatypeids');")
		))->addClass(ZBX_STYLE_CELL_WIDTH),
		make_sorting_header(_('Id'), 'name', 'asc', 'Id'),
		_('Version'),
		_('Author'),
		(new CColHeader(_('Description')))->addStyle('width: 60%'),
		_('Status'),
	]);

foreach ($data['modules'] as $module) {
	$mediaTypeTable->addRow([
		new CCheckBox('mediatypeids[]', 1),
		(new CCol(new CLink($module['id'])))->addClass(ZBX_STYLE_NOWRAP),
		$module['version'],
		$module['author'],
		$module['description'],
		$module['enabled']
			? (new CLink(_('Enabled'), '#'))->addClass(ZBX_STYLE_LINK_ACTION)->addClass(ZBX_STYLE_ORANGE)
			: (new CLink(_('Disabled'), '#'))->addClass(ZBX_STYLE_LINK_ACTION)->addClass(ZBX_STYLE_GREEN),
	]);
}

// append table to form
$mediaTypeForm->addItem([
	$mediaTypeTable,
	'',
	new CActionButtonList('action', 'mediatypeids', [
		'mediatype.enable' => ['name' => _('Enable'), 'confirm' => _('Enable selected media types?')],
		'mediatype.disable' => ['name' => _('Disable'), 'confirm' => _('Disable selected media types?')],
	], 'mediatype')
]);

// append form to widget
$widget->addItem($mediaTypeForm)->show();
