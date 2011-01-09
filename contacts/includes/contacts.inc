<?php

include_once($path_to_root."/includes/session.inc");
$_SESSION['App']->selected_application = 'contacts';
include_once(APP_PATH . "includes/date_functions.inc");
include_once(APP_PATH . "includes/banking.inc");
include_once(APP_PATH . "includes/ui.inc");
get_jqueryui();
