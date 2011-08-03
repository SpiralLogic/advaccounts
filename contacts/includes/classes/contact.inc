<?php
/**
 * User: Sorijen
 * Date: 15/04/11 - 4:08 PM
 */
	class contact extends DB_abstract {


		public $id = 0;
		public $parent_id;
		public $name = "New Contact";
		public $phone1 = '';
		public $phone2 = '';
		public $email = '';
		public $department = '';
		public $emailAddresses = array();

		function __construct($id = null) {
			parent::__construct($id);

		}

		protected function _canProcess() {
			$temp = new Contact();
			if ($this->id > 0) {
				return true;
			}
			foreach ($this as $key => $value) {
				if ($key != 'parent_id' && $key != 'id' && $key != '_status' && $temp->$key !== $value) {
					return true;
				}
			}
		}

		protected function _countTransactions() {
			// TODO: Implement _countTransactions() method.
		}

		protected function _defaults() {
			$this->id = 0;
		}

		protected function _new() {
			$this->_defaults();
			return $this->_status(true, 'Initialize new Contact', 'Now working with a new Contact');
		}

		protected function _read($params = array()) {
			$where = array();
			if (!is_array($params)) {
				$params = array('id' => $params);
			}
			elseif (empty($params)) {
				$this->_status(false, 'Retrieving contact', 'No parameters provided');
				return false;
			}
			$sql = "SELECT *
		FROM contacts WHERE ";
			foreach ($params as $key => $value) {
				$where[] = "$key =" . db_escape($value);
			}
			$sql .= join(" AND ", $where);
			$result = db_query($sql, 'Could not get contact.');
			if (db_num_rows($result) < 1) {
				$this->_new();
			}
			else {
				$result = db_fetch_assoc($result);
				$this->setFromArray($result);
			}
			return true;

		}

		protected function _saveNew() {
			begin_transaction();
			$sql = "INSERT INTO contacts (parent_id, name, phone1, phone2, email, department)
				VALUES (" . db_escape($this->parent_id) . "," . db_escape($this->name) . ", " . db_escape($this->phone1) . ", " . db_escape($this->phone2) . ", " . db_escape($this->email) . ", " . db_escape($this->department) . ")";
			db_query($sql, "The contact could not be added");
			$this->id = db_insert_id();
			commit_transaction();
			$this->_status(true, 'Saving', "New contact has been added");
		}

		protected function delete() {
			// TODO: Implement delete() method.
		}

		function save($changes = null) {
			if (is_array($changes)) {
				$this->setFromArray($changes);
			}
			if (!$this->_canProcess()) {
				return false;
			}
			if ((int) $this->id == 0) {
				$this->_saveNew();
			}
			begin_transaction();
			$sql = "UPDATE contacts SET
			name=" . db_escape($this->name) . ",
			phone1=" . db_escape($this->phone1) . ",
			phone2=" . db_escape($this->phone2) . ",
			email=" . db_escape($this->email) . ",
			department=" . db_escape($this->department) . " WHERE parent_id =" . db_escape($this->parent_id) . "
    	    AND id=" . db_escape($this->id);
			db_query($sql, "The customer could not be updated");
			commit_transaction();
			return $this->_status(true, 'Processing', "Contact has been updated.");
		}
		protected function addEmailGroup($name,$emails) {


		$debtor = $row['debtor_no'];
		$trans = $row['trans_no'];
		$type = $row['type'];
		$id = $debtor . '-' . $type . '-' . $trans;
		if (empty($row['debtor_no']) || isset($emailBoxEmails [$id])) {
			return;
		}
		$customer = new Customer($debtor);
		$emails = $customer->getEmailAddresses();

		if (!$emails) {
			return $emailBoxEmails [$id] = '';
		}
		switch ($type) {
			case ST_CUSTDELIVERY:
				$text = "Delivery Notice";
				break;
			case ST_SALESINVOICE:
				$text = "Tax Invoice";
				break;
			case ST_CUSTPAYMENT:
				$text = "Payment Receipt";
				break;
			case ST_CUSTREFUND:
				$text = "Refund Receipt";
				break;
			case ST_CUSTCREDIT:
				$text = "Credit Note";
				break;
		}

		$emailBoxEmails[$id] = submenu_email(_("Email This ") . $text, $type, $trans, null, $emails, 0, true);
		if ($first) {
			$emailBox = new Dialog('Select Email Address:', 'emailBox', '');
			$emailBox->addButtons(array('Email' => '$("#EmailButton").trigger("click")', 'Close' => '$(this).dialog("close");'));
			$emailBox->setOptions(array('autoopen' => false, 'modal' => true, 'width' => '"500"', 'height' => '"300"', 'resizeable' => false));
			$emailBox->show();
			$action = <<<JS
	var emailID= $(this).data('emailid');
	\$emailBox.html(emailBoxEmails[emailID]);
	\$emailBox.dialog('open');
	$('#EmailButton').button().click(function() {
		Adv.loader.show();
		var email = $("#EmailSelect").val();
		$.get($(this).data('url') + "&Email="+email,function(responce) {
			$('#msgbox').append(responce);
			\$emailBox.dialog('close');
			Adv.loader.hide();
		});
	});
	return false;
JS;
			JS::addLiveEvent('.email-button', 'click', $action, '#wrapper');

			$first = false;
		}
		ob_start();
		UI::button(false, 'Email', array('class' => 'button email-button', 'data-emailid' => $id));
		return ob_get_clean();
		}
	}
