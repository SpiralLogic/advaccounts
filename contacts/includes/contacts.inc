<?php

include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
$_SESSION['App']->selected_application = 'contacts';
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/date_functions.inc");
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/banking.inc");
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/ui.inc");
get_jqueryui();
get_jquery_live();