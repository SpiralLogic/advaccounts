<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	/*
	 Print request redirector. This file is fired via print link or
	 print button in reporting module.
 */
	$page_security = 'SA_OPEN'; // this level is later overriden in rep file
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	function find_report_file($rep)
	{
		global $installed_extensions;
		// customized per company versions
		$path = "/company/reporting";
		$rep_file = $path . "/rep$rep.php";
		if (file_exists($rep_file)) {
			// add local include path for custom reports
			set_include_path($path . PATH_SEPARATOR . get_include_path());
			return $rep_file;
		}
		// reports added by active extension modules
		if (count($installed_extensions) > 0) {
			$extensions = $installed_extensions;
			foreach ($extensions as $ext) {
				if (($ext['active'] && $ext['type'] == 'module')) {
					$path = PATH_TO_ROOT . '/' . $ext['path'] . "/reporting";
					$rep_file = $path . "/rep$rep.php";
					if (file_exists($rep_file)) {
						set_include_path($path . PATH_SEPARATOR . get_include_path());
						return $rep_file;
					}
				}
			}
		}
		// standard reports
		$rep_file = PATH_TO_ROOT . "/reporting/rep$rep.php";
		if (file_exists($rep_file)) {
			return $rep_file;
		}
		return null;
	}

	if (isset($_GET['xls'])) {
		$filename = $_GET['filename'];
		$unique_name = $_GET['unique'];
		$path = COMPANY_PATH . '/pdf_files/';
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=$filename");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		header("Pragma: public");
		echo file_get_contents($path . $unique_name);
		exit();
	} elseif (isset($_GET['xml'])) {
		$filename = $_GET['filename'];
		$unique_name = $_GET['unique'];
		$path = COMPANY_PATH . '/pdf_files/';
		header("content-type: text/xml");
		header("Content-Disposition: attachment; filename=$filename");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		header("Pragma: public");
		echo file_get_contents($path . $unique_name);
		exit();
	}
	if (!isset($_POST['REP_ID']) && isset($_GET['REP_ID'])) { // print link clicked
		$def_pars = array(0, 0, '', '', 0, '', '', 0); //default values
		$rep = $_POST['REP_ID'] = $_GET['REP_ID'];
		for ($i = 0; $i < 8; $i++) {
			$_POST['PARAM_' . $i] = isset($_GET['PARAM_' . $i]) ? $_GET['PARAM_' . $i] : $def_pars[$i];
		}
	}
	if (isset($_POST['REP_ID'])) {
		$rep_file = find_report_file($_POST['REP_ID']);
	}
	if (!empty($rep_file)) {
		require($rep_file);
	}
	exit();