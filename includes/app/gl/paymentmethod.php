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
		static public function add($payment_method, $undeposited, $inactive = 0)
			{
				DB::insert('payment_methods')
				 ->values(array('name' => $payment_method, 'undeposited' => $undeposited, 'inactive' => $inactive))->exec();
			}


		static public function update($id, $payment_method, $undeposited, $inactive = 0)
			{
				DB::update('payment_methods')
				 ->values(array('name' => $payment_method, 'undeposited' => $undeposited, 'inactive' => $inactive))
				 ->where('id=', $id)
				 ->exec();
			}


		static public function delete($id)
			{
				DB::delete('payment_methods')->where('id=', $id)->exec();
			}

		static public function get($id)
			{
				$sql = "SELECT * FROM payment_methods WHERE id=" . DB::escape($id);
				$result = DB::query($sql, "could not retreive bank account for $id");
				return DB::fetch($result);
			}
		static public function select($name, $selected_id = null) {
			$result = DB::select('name')->from('payment_methods')->where('inactive=', 0);
			while ($row = DB::fetch($result)) {
				$payment_methods[] = $row['name'];
			}
			return array_selector($name, $selected_id, $payment_methods);
		}

		static public function cells($label, $name, $selected_id = null) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo GL_PaymentMethod::select($name, $selected_id);
			echo "</td>\n";
		}

		static public 	function row($label, $name, $selected_id = null) {
			echo "<tr><td class='label'>$label</td>";
			Bank_Account::type_cells(null, $name, $selected_id);
			echo "</tr>\n";
		}

	}
