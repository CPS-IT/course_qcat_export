<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "t3import_export".
 *
 * Auto generated 25-05-2016 12:45
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Open Qcat Export',
	'description' => 'Exports records from t3events_course to Open Qcat.',
	'category' => 'module',
	'author' => 'Dirk Wenzel',
	'author_email' => 'dirk.wenzel@cps-it.de',
	'author_company' => 'CPS-IT GmbH Berlin',
	'state' => 'beta',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 1,
	'version' => '0.0.0',
	'constraints' =>
	array (
		'depends' =>
		array (
			'typo3' => '6.2.0-7.99.99',
			'php' => '5.4.0-0.0.0',
			't3import_export' => '0.7.0-0.0.0',
		),
		'conflicts' =>
		array (
		),
		'suggests' =>
		array (
		),
	),
	'_md5_values_when_last_written' => '',
);

