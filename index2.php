<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 9/12/11
	 * Time: 3:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
include 'bootstrap.php';
/*		$result = DB::select()->from('WebCustomers')->where('extid=',0)->limit(0,10)->fetch()->assoc()->all();
	echo '<pre>';
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

		var_dump($c);
	}*/

$c=new Debtor(array("id"=>4689,'name'=>'testy'));
man