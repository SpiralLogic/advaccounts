<?php
include_once($path_to_root . "/includes/session.inc");
$_SESSION['App']->selected_application = 'Items';
include_once($path_to_root . "/items/includes/classes/item.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . '/includes/ui2/ui.inc');
get_jqueryui();
get_jquery_live();