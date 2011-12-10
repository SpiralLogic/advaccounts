<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Maidenii
	 * Date: 7/12/11
	 * Time: 12:31 PM
	 * To change this template use File | Settings | File Templates.
	 */
	require $_SERVER['DOCUMENT_ROOT']. DIRECTORY_SEPARATOR.'bootstrap.php';

	function getCustomers() {
		$productsXML = getProductsXML();
		$products = XMLParser::XMLtoArray($productsXML);
		return $products;
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
		$url .= '&SELECT_Columns=*';
		return file_get_contents($url);
	}
	function insertCustomers() {
				$result = DB::select()->from('WebCustomers')->where('extid=',0)->limit(500,100)->fetch()->assoc()->all();
			foreach ($result as $row) {
				if (empty($row["CompanyName"])) comtinue;
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
				if ($c->id > 0) DB::update('WebCustomers')->value('extid', $c->id)->where('CustomerID=', $row['CustomerID'])->exec();

				echo $row['CompanyName'] . ' done!<br>';
			}

	}

	if (AJAX_REFERRER) {
		$products = getProducts();
		foreach ($products as $product) {
			DB::insert('WebProducts')->values($product)->exec();
			echo "<div>" . $product['ProductCode'] . "</div>";
		}
		$customers = getCustomers();
		foreach ($customers as $customer) {
					DB::insert('WebCustomers')->values($customer)->exec();
					echo "<div>" . $customer['CompanyName'] . "</div>";
				}
		exit();
	}
	Page::start('Get From Web',true);
	JS::beforeload(<<<JS
			Adv.getFromWeb = function () {
				$.get('#', function (data) {
					$(data).prependTo('body');
					if (data) return Adv.getFromWeb();
					$('<div>Finished</div>').prependTo('body');
				});
			}
			Adv.getFromWeb();
JS
);
	Renderer::end_page(true,true,true);