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
	$data = Customer::search($_GET['term']);
}
if (isset($_POST['id'])) {
	$data = new Customer($_POST['id']);
}
if (isset($_POST['branch_code'])) {
	if (isset($_POST['submit']) && $_POST['id'] > 0) {
		$customer = new customer($_POST['id']);
		$branch = new Branch();
		$branch->debtor_no = $_POST['id'];
		$_POST['branch_ref'] = $_POST['br_name'];
		$branch->save($_POST);
		$customer->branches[$branch->branch_code] = $branch;
		$data = $branch;
	} elseif ($_POST['branch_code'] > 0) {
		$data = new Branch(array('branch_code' => $_POST['branch_code']));
	} elseif ($_POST['debtor_no'] > 0) {
		$data = new Branch(array('debtor_no' => $_POST['debtor_no']));

	}

}
echo json_encode($data);
