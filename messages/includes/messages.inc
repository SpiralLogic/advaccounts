<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 11/07/11
 * Time: 10:59 AM
 * To change this template use File | Settings | File Templates.
 */
 

include_once($_SERVER['DOCUMENT_ROOT']. "/includes/session.inc");
$_SESSION['App']->selected_application = 'messages';
include_once(APP_PATH . "includes/date_functions.inc");
include_once(APP_PATH . "includes/banking.inc");
include_once(APP_PATH . "includes/faui.inc");