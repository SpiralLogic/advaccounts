<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 4:07 PM
 * To change this template use File | Settings | File Templates.
 */
	abstract class Company extends DB_abstract {


		// Current status of class, status as current state, process status error occurred, message for current state and if related variable if it applies
		public $discount = '0';
		public $id = 0;
		public $name = '';
		public $address = '';
		public $post_address = '';
		public $tax_id = '';
		public $contact_name;
		public $credit_limit = 0;
		public $dimension_id = 0;
		public $dimension2_id = 0;
		public $payment_terms = 1;
		public $curr_code = '';

		public $emailAddresses = array();

		protected function addEmailGroup($name, $emails, $trans, $type) {

		}

		public static function getEmailDialogue($emailid) {
			$emailid = explode('-', $emailid);
			$id = $emailid[0];
			$type = $emailid[1];
			$trans = $emailid[2];
			$company = get_called_class();
			$company = new $company($id);
			$emails = $company->getEmailAddresses();
			if (count($emails) == 0) {
				return false;
			}
			$types = Config::get('systypes_array');
			$text = $types[$type];

			return submenu_email(_("Email This ") . $text, $type, $trans, null, $emails, 0, true);
		}

	}
