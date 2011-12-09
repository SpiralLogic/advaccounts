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