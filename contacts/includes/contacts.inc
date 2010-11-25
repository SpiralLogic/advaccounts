<?php

include_once($path_to_root . "/includes/session.inc");
$_SESSION['App']->selected_application = 'contacts';
include_once($path_to_root . "/contacts/includes/classes/contact.inc");
include_once($path_to_root . "/contacts/includes/classes/customer.inc");
include_once($path_to_root . "/contacts/includes/classes/branch.inc");
include_once($path_to_root."/contacts/includes/classes/accounts.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . '/includes/ui2/ui.inc');
get_jqueryui();
get_jquery_live();