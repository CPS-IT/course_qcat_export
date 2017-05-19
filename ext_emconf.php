<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "course_qcat_export".
 *
 * Auto generated 19-05-2017 12:34
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
  'version' => '0.2.2',
  'constraints' => 
  array (
    'depends' => 
    array (
      'typo3' => '6.2.0-7.99.99',
      'php' => '5.4.0-0.0.0',
      't3import_export' => '0.7.0-0.0.0',
      't3events' => '0.29.0-0.0.0',
    ),
    'conflicts' => 
    array (
    ),
    'suggests' => 
    array (
    ),
  ),
  '_md5_values_when_last_written' => 'a:5:{s:9:"ChangeLog";s:4:"6af6";s:9:"README.md";s:4:"09d9";s:13:"composer.json";s:4:"697a";s:57:"Classes/Component/PreProcessor/PerformanceToQcatArray.php";s:4:"5f82";s:64:"Tests/Unit/Component/PreProcessor/PerformanceToQcatArrayTest.php";s:4:"de48";}',
);

