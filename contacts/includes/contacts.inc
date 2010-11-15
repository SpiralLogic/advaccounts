<?php

include_once($path_to_root . "/includes/session.inc");
$_SESSION['App']->selected_application = 'contacts';
include_once($path_to_root . "/contacts/includes/classes/contact.inc");
include_once($path_to_root . "/contacts/includes/classes/customer.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/ui.inc");
$js .= get_jqueryui();
$js .= get_jquery_live();