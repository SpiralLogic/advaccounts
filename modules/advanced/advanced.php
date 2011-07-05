<?php
$page_security = 'SA_ADVANCED';
$path_to_root = "../..";

include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
add_access_extensions();

// Turn these next two lines on for debugging
//error_reporting(E_ALL);
//ini_set("display_errors", "on");

//--------------------------------------------------------------------------------------------------

// Begin the UI
include_once($path_to_root . "/includes/ui.inc");

page("Advanced");

end_page();
