<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 9:50 PM
 * To change this template use File | Settings | File Templates.
 */
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once("includes/contacts.inc");
if (isset($_GET['term'])) {
	$sql = "SELECT debtor_no as id, debtor_ref as label, debtor_ref as value FROM " . TB_PREF . "debtors_master " . "where debtor_ref LIKE '%" . $_GET['term'] . "%' LIMIT 20";
	$result = db_query($sql, 'Couldn\'t Get Customers');
	while ($row = db_fetch_assoc($result)) {
		$data[] = $row;
	}
}
 elseif (isset ($_POST['id'])) {
	 $data = new Customer($_POST['id']);

}
if (isset($_POST['branch_code'])) {
  $data = new Branch(array('branch_code'=>$_POST['branch_code']));

}


echo json_encode($data);
