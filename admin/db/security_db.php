<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	//--------------------------------------------------------------------------------------------------

	function get_security_role($id) {
		$sql = "SELECT * FROM security_roles WHERE id='$id'";
		$ret = DBOld::query($sql, "could not retrieve security roles");
		$row = DBOld::fetch($ret);
		if ($row != false) {
			$row['areas'] = explode(';', $row['areas']);
			$row['sections'] = explode(';', $row['sections']);
		}
		return $row;
	}

	//--------------------------------------------------------------------------------------------------

	function add_security_role($name, $description, $sections, $areas) {
		$sql = "INSERT INTO security_roles (role, description, sections, areas)
	VALUES ("
		 . DBOld::escape($name) . ","
		 . DBOld::escape($description) . ","
		 . DBOld::escape(implode(';', $sections)) . ","
		 . DBOld::escape(implode(';', $areas)) . ")";

		DBOld::query($sql, "could not add new security role");
	}

	//--------------------------------------------------------------------------------------------------

	function update_security_role($id, $name, $description, $sections, $areas) {
		$sql = "UPDATE security_roles SET role=" . DBOld::escape($name)
		 . ",description=" . DBOld::escape($description)
		 . ",sections=" . DBOld::escape(implode(';', $sections))
		 . ",areas=" . DBOld::escape(implode(';', $areas))
		 . " WHERE id=$id";
		DBOld::query($sql, "could not update role");
	}

	//--------------------------------------------------------------------------------------------------

	function delete_security_role($id) {
		$sql = "DELETE FROM security_roles WHERE id=$id";

		DBOld::query($sql, "could not delete role");
	}

	//--------------------------------------------------------------------------------------------------

	function check_role_used($id) {
		$sql = "SELECT count(*) FROM users WHERE role_id=$id";
		$ret = DBOld::query($sql, 'cannot check role usage');
		$row = DBOld::fetch($ret);
		return $row[0];
	}

?>