<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 4:07 PM
 * To change this template use File | Settings | File Templates.
 */
abstract class Company extends DB_abstract
{


	// Current status of class, status as current state, process status error occurred, message for current state and if related variable if it applies
	public $discount = '0';
	public $id = 0;
	public $name = '';
	public $address = '';
	public $post_address = '';
	public $tax_id = '';
	public $contact_name;
	public $credit_limit = 0;
	public $dimension_id = 0;
	public $dimension2_id = 0;
	public $payment_terms = 1;
	public $curr_code = '';
	public $email = '';
	public $inactive = 0;
	public $notes = '';
	public $phone = '';
	public $phone2 = '';
	public $fax = '';
	public $website = '';

}
