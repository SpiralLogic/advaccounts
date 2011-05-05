<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 1/9/11
 * Time: 4:00 PM
 * To change this template use File | Settings | File Templates.
 */
class SalesOrder extends Orders
{


	public $trans_type, $type;
	public $customer_id; // $debtor_no in DB
	public $branch_id; //$branch_code in DB
	public $customer_ref, $order_type, $ship_via, $name, $contact_phone, $contact_email, $deliver_to, $from_stk_loc, $delivery_date;

	protected function _canProcess()
	{
		// TODO: Implement _canProcess() method.
	}

	protected function _countTransactions()
	{
		// TODO: Implement _countTransactions() method.
	}

	protected function _defaults()
	{
		// TODO: Implement _defaults() method.
	}

	protected function _new()
	{
		// TODO: Implement _new() method.
	}

	protected function _read()
	{
		// TODO: Implement _read() method.
	}

	protected function _saveNew()
	{
		// TODO: Implement _saveNew() method.
	}

	public function delete()
	{
		// TODO: Implement delete() method.
	}

	public function save($changes = null)
	{
		// TODO: Implement save() method.
	}
}
