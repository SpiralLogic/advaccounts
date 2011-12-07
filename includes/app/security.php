<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/10/11
	 * Time: 7:22 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Security
	{
		static function check_page($page_security) {
			if (!User::get()->can_access_page($page_security)) {
				echo "<div class='center'><br><br><br><span class='bold'>";
				echo _("The security settings on your account do not permit you to access this function");
				echo "</span>";
				echo "<br><br><br><br></div>";
				end_page();
				exit;
			}
		}

		/*
			 Helper function for setting page security level depeding on
			 GET start variable and/or some value stored in session variable.
			 Before the call $page_security should be set to default page_security value.
		 */
		static function set_page($value = null, $trans = array(), $gtrans = array()) {
			global $page_security;
			// first check is this is not start page call
			foreach ($gtrans as $key => $area) {
				if (isset($_GET[$key])) {
					$page_security = $area;
					return;
				}
			}
			// then check session value
			if (isset($trans[$value])) {
				$page_security = $trans[$value];
				return;
			}
		}

		public static function get_role($id) {
			$sql = "SELECT * FROM security_roles WHERE id='$id'";
			$ret = DB::query($sql, "could not retrieve security roles");
			$row = DB::fetch($ret);
			if ($row != false) {
				$row['areas'] = explode(';', $row['areas']);
				$row['sections'] = explode(';', $row['sections']);
			}
			return $row;
		}

		public static function add_role($name, $description, $sections, $areas) {
			$sql = "INSERT INTO security_roles (role, description, sections, areas)
			VALUES (" . DB::escape($name) . "," . DB::escape($description) . "," . DB::escape(implode(';', $sections)) . "," . DB::escape(implode(';', $areas)) . ")";
			DB::query($sql, "could not add new security role");
		}

		public static function update_role($id, $name, $description, $sections, $areas) {
			$sql = "UPDATE security_roles SET role=" . DB::escape($name) . ",description=" . DB::escape($description) . ",sections=" . DB::escape(implode(';', $sections)) . ",areas=" . DB::escape(implode(';', $areas)) . " WHERE id=$id";
			DB::query($sql, "could not update role");
		}

		public static function get_profile($id) {
			$sql = "DELETE FROM security_roles WHERE id=$id";
			DB::query($sql, "could not delete role");
		}

		public static function check_role_used($id) {
			$sql = "SELECT count(*) FROM users WHERE role_id=$id";
			$ret = DB::query($sql, 'cannot check role usage');
			$row = DB::fetch($ret);
			return $row[0];
		}

		public static function	roles($name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
			$sql = "SELECT id, role, inactive FROM security_roles";
			return select_box($name, $selected_id, $sql, 'id', 'description', array(
																																							'spec_option' => $new_item ? _("New role") :
																																							 false, 'spec_id' => '', 'select_submit' => $submit_on_change, 'show_inactive' => $show_inactive));
		}

		public static function	roles_cells($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Security::roles($name, $selected_id, $new_item, $submit_on_change, $show_inactive);
			echo "</td>\n";
		}

		public static function	roles_row($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
			echo "<tr><td class='label'>$label</td>";
			Security::roles_cells(null, $name, $selected_id, $new_item, $submit_on_change, $show_inactive);
			echo "</tr>\n";
		}
	}