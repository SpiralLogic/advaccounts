<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  ADVAccounting::i()->selected_application = 'contacts';
  if (REQUEST_AJAX) {
    if (isset($_POST['branch_id'])) {
      if ($_POST['branch_id'] > 0) {
        $data['branch'] = new Debtor_Branch(array('branch_id' => $_POST['branch_id']));
      } elseif ($_POST['id'] > 0) {
        $data['branch'] = new Debtor_Branch(array('debtor_id' => $_POST['id']));
      }
    }
    JS::_renderJSON($data);
  }
  Page::start(_($help_context = "Items"), SA_CUSTOMER, Input::_request('frame'));
  Debtor::addSearchBox('debtor_id', array('cell' => false, 'description' => ''));
  Page::end();
