<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Maidenii
	 * Date: 7/12/11
	 * Time: 12:31 PM
	 * To change this template use File | Settings | File Templates.
	 */
	require $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'bootstrap.php';
	function getCustomers() {
		$customersXML = getCustomersXML();
		if (!$customersXML) {
			return false;
		}
		$customers = XMLParser::XMLtoArray($customersXML);
		return $customers;
	}

	function getCustomersXML() {
		$apiuser = 'admin@advancedroadsigns.com.au';
		$apikey = '48DA75C51705EF02B195A2AE63DCA10C92E2A24AF93ABFC8731F00E586BC3F94';
		$url = "http://www.advancedroadsigns.com.au/net/WebService.aspx?";
		$url .= "Login=" . $apiuser;
		$url .= '&EncryptedPassword=' . $apikey;
		$url .= '&EDI_Name=Generic\Customers';
		$url .= '&SELECT_Columns=*';
		return file_get_contents($url);
	}

	function getProducts() {
		$productsXML = getProductsXML();
		if (!$productsXML) {
			return false;
		}
		$products = XMLParser::XMLtoArray($productsXML);
		return $products;
	}

	function getProductsXML() {
		$apiuser = 'admin@advancedroadsigns.com.au';
		$apikey = '48DA75C51705EF02B195A2AE63DCA10C92E2A24AF93ABFC8731F00E586BC3F94';
		$url = "http://www.advancedroadsigns.com.au/net/WebService.aspx?";
		$url .= "Login=" . $apiuser;
		$url .= '&EncryptedPassword=' . $apikey;
		$url .= '&EDI_Name=Generic\Products';
		$url .= '&SELECT_Columns=*&LIMIT=1';
		return file_get_contents($url);
	}

	function insertCustomers() {
		$result = DB::select()->from('WebCustomers')->where('extid=', 0)->fetch()->assoc()->all();
		if (!$result) {
			exit();
		}
		foreach ($result as $row) {
			if (empty($row["CompanyName"])) {
				continue;
			}
			$c = new Debtor();
			$c->name = $row["CompanyName"];
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
				$result2 = DB::select('debtor_no')->from('debtors')->where('name LIKE', $c->name)->fetch()->assoc()->one();
				$c->id = $result2['debtor_no'];
				$d = new Debtor((array)$c);
				$d->save();
			}
			else {
				if ($c->id > 0) {
					DB::update('WebCustomers')->value('extid', $c->id)->where('CustomerID=', $row['CustomerID'])->exec();
				}
			}
		}
	}

	if (AJAX_REFERRER) {
		$products = getProducts();
		if ($products) {
			foreach ($products as $product) {
				$name = $product['ProductCode'];
				try {
					DB::insert('WebProducts')->values($product)->exec();
					echo "<div>" . $product['ProductCode'] . "</div>";
				} catch (DBDuplicateException $e) {
					echo "<div>Product already exists: " . $name . "</div>";
				}
			}
		}
		$customers = getCustomers();
		if ($customers) {
			foreach ($customers as $customer) {
				If (!isset($customer['CompanyName'])) {
					continue;
				}
				$name = $customer['CompanyName'];
				try {
					DB::insert('WebCustomers')->values($customer)->exec();
					echo "<div>" . $customer['CompanyName'] . "</div>";
				} catch (DBDuplicateException $e) {
					echo "<div>Customer already exists: " . $name . "</div>";
				}
			}
		}
		else {
			insertCustomers();
		}
		exit();
	}
	Page::start('Get From Web', SA_OPEN, true);
	JS::beforeload(<<<JS
			Adv.getFromWeb = function () {
				$.get('#', function (data) {
					$(data).prependTo('#wrapper');
					if (data) return Adv.getFromWeb();
					$('<div>Finished</div>').prependTo('#wrapper');
				});
			}
			Adv.getFromWeb();
JS);
	Page::end(true);
