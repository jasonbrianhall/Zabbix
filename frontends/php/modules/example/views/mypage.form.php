<?php

$widget = (new CWidget())->setTitle(_('Modules'));

$scriptForm = (new CForm())
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('form', 1);

$scriptFormList = (new CFormList())
	->addRow((new CLabel(_('Id'), 'name')), 'example')
	->addRow(new CLabel('Version'), '1.0')
	->addRow(new CLabel(_('Author'), 'command'),
		'Some Author'
	)
	->addRow(new CLabel(_('Description'), 'command'),
		'This is is demo module short description, will be shown in frontend with module name'
	)
	->addRow(new CLabel(_('Module home page'), 'host_access'), 'http://example.com')
	->addRow((new CLabel(_('Enabled'), 'host_access')),
		(new CCheckBox('', 1))->setChecked(true)
	)
	->addRow('Module log', [
		(new CTable())
			->addClass('list-table')
			->addStyle('width: 60%')
			->setHeader(['Time', 'User', (new CColHeader('Message'))->addStyle('width: 80%; padding: 6px 5px')])
			->addRow([
				(new CCol('2019-10-01 20:00'))->addClass(ZBX_STYLE_NOWRAP), 'guest', 'Module initialization failed: "Uncaught Error: Call to a member function add() on null in Module.php:8
				Stack trace:
				#0 Module.php (13): Module->init()"'
			])
		]
	);

$scriptView = (new CTabView())->addTab('scripts', _('Script'), $scriptFormList);

// footer
$cancelButton = (new CRedirectButton(_('Cancel'), 'zabbix.php?action=script.list'))->setId('cancel');
$clear_log = (new CRedirectButton(_('Clear log'), 'zabbix.php?action=script.list'))->setId('cancel');

$updateButton = (new CSubmitButton(_('Update'), 'action', 'script.update'))->setId('update');

$scriptView->setFooter(makeFormFooter(
	$updateButton,
	[
		$clear_log,
		$cancelButton
	]
));

$scriptForm->addItem($scriptView);

$widget->addItem($scriptForm)->show();
echo <<<CSS
<style>
table.list-table tbody tr td { padding: 6px !important; }

</style>
CSS;
