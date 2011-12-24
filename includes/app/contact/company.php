<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 15/11/10
	 * Time: 4:07 PM
	 * To change this template use File | Settings | File Templates.
	 */
	abstract class Contact_Company extends DB_abstract
	{
		// Current status of class, status as current state, process status error occurred, message for current state and if related variable if it applies
		public $discount = '0';
		public $name = '';
		public $address;
		public $post_address = '';
		public $tax_id;
		public $contact_name;
		public $credit_limit = 0;
		public $dimension_id = 0;
		public $dimension2_id = 0;
		public $payment_terms = 1;
		public $curr_code = '';
		public $emailAddresses = array();

		abstract protected function _countTransactions();

		protected function addEmailGroup($name, $emails, $trans, $type) {
		}

		public static function addInfoDialog($selector, $id = false) {
			if ($id) {
				$company = new static($id);
			}
			$content = '<div><span class="bold">Shipping Address:</span><br>${address}</br></br>
				 		<span class="bold">Mailing Address:</span><br>${post_address}</br></br>
				 		<span class="bold">Phone: </span>${phone}</br></br>
				 		<span class="bold">Phone2: </span>${phone2}</br></br>
				 		<span class="bold">Fax: </span>${fax}</br></br>
				 		<span class="bold">Contact: </span>${contact}</br></br>
				 		<span class="bold">Email: </span><a href="mailto:${email}">${email}</a></br></br>
				 		<span class="bold">Website: </span><a target="_new" href="http://${website}">${website}</a></br></br>
											</div>';
			$type = explode('_', get_called_class());
			$type = array_pop($type);
			$details = new Dialog($type . ' Details:', 'company_details', $content, array('minHeight' => 400));
			$type = strtolower($type);
			$details->setTemplateData(($id) ? $company : '');
			if ($id) {
				$details->addOpenEvent($selector, 'click');
			} else {
				$action = <<<JS

				 $.post('/contacts/{$type}s.php',{id:$(this).data('id')},function(data) {Adv.o.company_details.render(data.$type); \$company_details.dialog('open');},'json');
JS;
				JS::addLiveEvent($selector, 'click', $action, 'wrapper', true);
			}
			$details->addButton('Close', '$(this).dialog("close")');
			$details->show();
		}

		public static function getEmailDialogue($emailid) {
			$emailid = explode('-', $emailid);
			$id = $emailid[0];
			$type = $emailid[1];
			$trans = $emailid[2];
			$company = get_called_class();
			$company = new $company($id);
			$emails = $company->getEmailAddresses();
			if (count($emails) > 0) {
				$types = $GLOBALS['systypes_array'];
				$text = $types[$type];
				$content = Reporting::email_link($trans, _("Email This $text"), true, $type, 'EmailLink', null, $emails, 0, true);
				if ($type == ST_SALESQUOTE || $type == ST_SALESORDER) {
					$type = ($type == ST_SALESORDER) ? ST_PROFORMA : ST_PROFORMAQ;
					$text = $types[$type];
					$content .= Reporting::email_link($trans, _("Email This ") . $text, true, $type, 'EmailLink', null, $emails, 0, true);
				}
				return $content;
			}
			return false;
		}
	}
