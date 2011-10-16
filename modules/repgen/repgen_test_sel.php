<?php
	/* Test the SQL Statement
	* repgen_test_sel.php for PHP Report Generator
		Bauer, 22.5.2001
		Version 0.1
 */

	$page_security = 'SA_REPORT_GENERATOR';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Security::add_access_extensions();

	require_once("repgen_const.php");
	require_once("repgen_def.php");

	/* If this page is called directly, switch to repgen_main.php
 */

	if ($sql == "") {
		$error = SQL_ERROR1;
		ui_msgs::display_error($error);
		exit;
	}

	Renderer::page("Report Generator REPGEN", true);

	ui_msgs::display_heading(SQL_STATEMENT);

	$sql = stripslashes($sql);
	$sqle = urldecode($sql);
	//   print the SQL-Command

	ui_msgs::display_notification($sqle);

	$sql = str_replace("", '', $sql);

	$res = DBOld::query($sql, SQL_ERROR); // test, if SQL-statement is correct
	$th = array();
	$num = DBOld::num_fields($res);
	$i = 0;
	while ($i < $num)
	{
		$meta = mysql_fetch_field($res, $i);
		$th[] = $meta->name;
		$i++;
	}
	/*
	*
	* show 10 records of this resultset
	*
	*
 */
	ui_msgs::display_heading(SQL_ERG);

	start_table(Config::get('tables.style'));
	table_header($th);
	$k = 0;
	for ($j = 0; $j < 10; $j++)
	{
		alt_table_row_color($k);
		$f = DBOld::fetch_row($res);
		for ($i = 0; $i < $num; $i++) // write column names
		{
			label_cell($f[$i]);
		}
		end_row();
	}

	end_table(1);

	Renderer::end_page();
?>