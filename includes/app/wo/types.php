<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 8/12/11
	 * Time: 1:32 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class WO_Types
	{
		//------------------------------------------------------------------------------------------------
		public static function select($name, $selected_id = null) {
			global $wo_types_array;
			return array_selector(
				$name, $selected_id, $wo_types_array, array(
																									 'select_submit' => true,
																									 'async' => true
																							)
			);
		}

		public static function row($label, $name, $selected_id = null) {
			echo "<tr><td class='label'>$label</td><td>\n";
			echo static::select($name, $selected_id);
			echo "</td></tr>\n";
		}
	}