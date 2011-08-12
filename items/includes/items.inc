  <?php
  include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
	  add_include_path('items/includes/classes');
	  $_SESSION['App']->selected_application = 'Items';
	  include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/date_functions.inc");
	  include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/banking.inc");
	  include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/faui.inc");

