<?php
	// session_start();
	/* Print a report
	* repgen_print.php for PHP Report Generator
		Bauer, 5.2.2002
		Version 0.2
 */
	// this has to be an own page, because otherwise we could not get Content-type application/pdf

	$page_security = 'SA_REPORT_GENERATOR';
	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
	add_access_extensions();

	include_once(APP_PATH . "admin/db/company_db.inc");

	require_once("repgen.inc");
	$id = $_GET['id'];
	$file = COMPANY_PATH . '/pdf_files/' . $id . '.pdf';
	create_report($id, $file);

?>



