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
$sql = "SELECT debtor_no, debtor_ref, curr_code, inactive FROM " . TB_PREF . "debtors_master " . "where debtor_name LIKE '%".$_GET['q']."%'";
$result = db_query($sql,'Couldn\'t Get Customers');
$data = db_fetch_assoc($result);
echo json_encode($data);
