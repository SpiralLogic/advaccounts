<?php

include_once($_SERVER['DOCUMENT_ROOT']. "/includes/session.inc");
$_SESSION['App']->selected_application = 'contacts';
include_once(APP_PATH . "includes/date_functions.inc");
include_once(APP_PATH . "includes/banking.inc");
include_once(APP_PATH . "includes/ui.inc");
