<?php

/*
	Do not edit this file manually. This copy of global file is overwritten
	by extensions editor.
*/

$installed_extensions = array(
	1 => array('tab' => 'system',
			   'name' => 'Report Generator ',
			   'path' => 'repgen',
			   'title' => 'Report Generator',
			   'active' => '1',
			   'type' => 'plugin',
			   'filename' => 'repgen_select.php',
			   'acc_file' => 'acc_levels.php',
			   'access' => 'SA_REPORT_GENERATOR',
	),
	2 => array('tab' => 'GL',
			   'name' => 'Import Multi Journal Entries',
			   'path' => 'import_multijournal',
			   'title' => 'Import Multi Journal Entries',
			   'active' => '1',
			   'type' => 'plugin',
			   'filename' => 'import_multijournalentries.php',
			   'acc_file' => 'acc_levels.php',
			   'access' => 'SA_CSVMULTIJOURNALIMPORT',
	),
);
?>