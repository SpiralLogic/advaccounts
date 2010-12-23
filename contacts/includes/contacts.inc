<?php

include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
$_SESSION['App']->selected_application = 'contacts';
include_once($_SERVER['DOCUMENT_ROOT'] . "/contacts/includes/classes/contact.inc");
include_once($_SERVER['DOCUMENT_ROOT'] . "/contacts/includes/classes/customer.inc");
include_once($_SERVER['DOCUMENT_ROOT'] . "/contacts/includes/classes/branch.inc");
include_once($_SERVER['DOCUMENT_ROOT']."/contacts/includes/classes/accounts.inc");
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/date_functions.inc");
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/banking.inc");
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/ui.inc");
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/ui2/HTML.inc');
include_once($_SERVER['DOCUMENT_ROOT'].'/contacts/includes/classes/contact_log.inc');

get_jqueryui();
get_jquery_live();