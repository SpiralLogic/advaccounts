<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Maidenii
	 * Date: 7/12/11
	 * Time: 12:31 PM
	 * To change this template use File | Settings | File Templates.
	 */
	require $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'bootstrap.php';



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
				if (!isset($customer['CompanyName'])) {
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
JS
);

	Page::end(true);

