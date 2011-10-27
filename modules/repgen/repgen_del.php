<?php
	// session_start();
	/* Delete a report
	* repgen_del.php for PHP Report Generator
		Bauer, 5.2.2002
		Version 0.2
 */

	/*
	 *  Delete routine for Report generator repgen.
	 *
	 *
	 * 1. A section where utility functions are defined.
	 * 2. A section that is called only after the submit.
	 * 3. And a final section that is called when the script runs first time and
	 *    every time after the submit.
	 *
	 * Scripts organized in this way will allow the user perpetual
	 * editing and they will reflect submitted changes immediately
	 * after a form submission.
	 *
	 * We consider this to be the standard organization of table editor
	 * scripts.
	 *
	 */

	/* If this page is called direct, switch to repgen_main.php
 */
	$page_security = 'SA_REPORT_GENERATOR';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	add_access_extensions();

	require_once("repgen_const.php");
	require_once("repgen_def.php");

	###
	### Submit Handler
	###

	## Check if there was a submission
	if (isset($back)) {
		$url = REPGENDIR . "/repgen_select.php";
		header("Location: http://$HTTP_HOST" . $url);
		exit;
	}
	if (isset($delete)) {
		// deletes all records with id from table reports
		$query = "DELETE FROM xx_reports WHERE id = '" . $id . "'";
		DBOld::query($query);
		$url = REPGENDIR . "/repgen_select.php?id=" . $id;
		$url = "http://$HTTP_HOST" . $url;
		header("Location: " . $url); // switches to repgen_select.php
		exit;
	}

	page("Report Generator REPGEN");
	### Output key administration forms, including all updated
	### information, if we come here after a submission...

	ui_msgs::display_heading(DESCRIPT);

	$note = DEL_REALLY;
	switch (substr($id, 0, 1))
	{
		case 'B':
			$note .= DEL_BLOCK;
			break;
		case 'F':
			$note .= DEL_FUNC;
			break;
		default:
			$note .= DEL_REPORT;
			break;
	}

	$h = explode("|", $attr);
	$note .= "  " . $h[3] . "  "; /* longname of report*/
	$note .= DEL_DELETE;

	ui_msgs::display_notification($note);

	start_form(false, false, "repgen_del.php", "edit");

	start_table(Config::get('tables_style2'));
	start_row();
	submit_cells("delete", DEL_BACK);
	submit_cells("back", BACK);
	hidden("id", $id);
	end_row();
	end_table();
	end_form();

	end_page();
?>