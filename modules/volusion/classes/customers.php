<?php
	namespace Modules\Volusion;
	class Customers
	{
		public $customers;
		public function __construct() {
			echo __NAMESPACE__;
		}

		function get() {
			$customersXML = $this->getXML();
			if (!$customersXML) {
				return false;
			}
			$customers = \XMLParser::XMLtoArray($customersXML);
			$this->customers = $customers;
		}

		function getXML() {
			$apiuser = \Config::get('webstore.apiuser');
			$apikey = \Config::get('webstore.apikey');
			$url = \Config::get('webstore.apiurl');
			$url .= "Login=" . $apiuser;
			$url .= '&EncryptedPassword=' . $apikey;
			$url .= '&EDI_Name=Generic\Customers';
			$url .= '&SELECT_Columns=*';
			return file_get_contents($url);
		}
		function insert() {
			$result = \DB::select()->from('WebCustomers')->where('extid=', 0)->fetch()->assoc()->all();
			if (!$result) {
				exit();
			}
			foreach ($result as $row) {
				if (empty($row["CompanyName"])) {
					continue;
				}
				$c = new \Debtor();
				$c->name = $c->debtor_ref = $row["CompanyName"];
				$c->branches[0]->post_address = $row["BillingAddress2"];
				$c->branches[0]->br_address = $row["BillingAddress1"];
				$c->branches[0]->city = $row["City"];
				$c->branches[0]->state = $row["State"];
				$c->branches[0]->postcode = $row["PostalCode"];
				$c->branches[0]->contact_name = $row["FirstName"];
				$c->branches[0]->phone = $row["PhoneNumber"];
				$c->branches[0]->fax = $row["FaxNumber"];
				$c->branches[0]->website = $row["WebsiteAddress"];
				$c->branches[0]->email = $row["EmailAddress"];
				$c->address = $row["BillingAddress1"];
				$c->post_address = $row ["BillingAddress2"];
				$c->tax_id = $row["TaxID"];
				$c->webid = $row["CustomerID"];
				$c->contact_name = $row["FirstName"];
				$c->save();
				$status = $c->getStatus();
				$dup = ((substr($status['message'], 0, 9) == "Duplicate"));
				if ($dup) {
					$result2 = \DB::select('debtor_no')->from('debtors')->where('name LIKE', $c->name)->fetch()->assoc()->one();
					if (!$result2) {
						continue;
					}
					$c->id = $result2['debtor_no'];
					$d = new \Debtor((array)$c);
					$d->save();
				}
				else {
					if ($c->id > 0) {
						\DB::update('WebCustomers')->value('extid', $c->id)->where('CustomerID=', $row['CustomerID'])->exec();
					}
				}
			}
		}
	}
