<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 20/11/11
	 * Time: 1:02 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class GL_PaymentMethod
	{
		public static function add($payment_method, $undeposited, $inactive = 0)
			{
				DB::insert('payment_methods')
				 ->values(array('name' => $payment_method, 'undeposited' => $undeposited, 'inactive' => $inactive))->exec();
			}

		//---------------------------------------------------------------------------------------------
		public static function update($id, $payment_method, $undeposited, $inactive = 0)
			{
				DB::update('payment_methods')
				 ->values(array('name' => $payment_method, 'undeposited' => $undeposited, 'inactive' => $inactive))
				 ->where('id=', $id)
				 ->exec();
			}

		//---------------------------------------------------------------------------------------------
		public static function delete($id)
			{
				DB::delete('payment_methods')->where('id=', $id)->exec();
			}

		public static function get($id)
			{
				$sql = "SELECT * FROM payment_methods WHERE id=" . DB::escape($id);
				$result = DB::query($sql, "could not retreive bank account for $id");
				return DB::fetch($result);
			}
	}
