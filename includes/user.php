<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 22/10/11
	 * Time: 5:49 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class User
	{
		public static function	add($user_id, $real_name, $password, $phone, $email, $role_id,
			$language, $profile, $rep_popup, $pos) {
			$sql = "INSERT INTO users (user_id, real_name, password,phone, email, role_id, language, pos, print_profile, rep_popup)
				VALUES (" . DBOld::escape($user_id) . ",
				" . DBOld::escape($real_name) . ", " . DBOld::escape($password) . "," . DBOld::escape($phone)
			 . "," . DBOld::escape($email) . ", " . DBOld::escape($role_id) . ", " . DBOld::escape($language)
			 . ", " . DBOld::escape($pos) . "," . DBOld::escape($profile) . "," . DBOld::escape($rep_popup)
			 . " )";

			DBOld::query($sql, "could not add user for $user_id");
		}

		//-----------------------------------------------------------------------------------------------
		public static function	update_password($id, $user_id, $password)
		{
			$sql = "UPDATE users SET password=" . DBOld::escape($password) . ",
				user_id = " . DBOld::escape($user_id) . " WHERE id=" . DBOld::escape($id);
			DBOld::query($sql, "could not update user password for $user_id");
		}

		//-----------------------------------------------------------------------------------------------
		public static function	update(
			$id, $user_id, $real_name, $phone, $email, $role_id,
			$language, $profile, $rep_popup, $pos
		)
		{
			$sql = "UPDATE users SET real_name=" . DBOld::escape($real_name) .
			 ", phone=" . DBOld::escape($phone) . ",
				email=" . DBOld::escape($email) . ",
				role_id=" . DBOld::escape($role_id) . ",
				language=" . DBOld::escape($language) . ",
				print_profile=" . DBOld::escape($profile) . ",
				rep_popup=" . DBOld::escape($rep_popup) . ",
				pos=" . DBOld::escape($pos) . ",
				user_id = " . DBOld::escape($user_id)
			 . " WHERE id=" . DBOld::escape($id);
			DBOld::query($sql, "could not update user for $user_id");
		}

		//-----------------------------------------------------------------------------------------------
		public static function	update_display_prefs(
			$id, $price_dec, $qty_dec, $exrate_dec,
			$percent_dec, $showgl, $showcodes, $date_format, $date_sep, $tho_sep,
			$dec_sep, $theme, $pagesize, $show_hints, $profile, $rep_popup, $query_size,
			$graphic_links, $lang, $stickydate, $startup_tab
		)
		{
			$sql
			 = "UPDATE users SET
				prices_dec=" . DBOld::escape($price_dec) . ",
				qty_dec=" . DBOld::escape($qty_dec) . ",
				rates_dec=" . DBOld::escape($exrate_dec) . ",
				percent_dec=" . DBOld::escape($percent_dec) . ",
				show_gl=" . DBOld::escape($showgl) . ",
				show_codes=" . DBOld::escape($showcodes) . ",
				date_format=" . DBOld::escape($date_format) . ",
				date_sep=" . DBOld::escape($date_sep) . ",
				tho_sep=" . DBOld::escape($tho_sep) . ",
				dec_sep=" . DBOld::escape($dec_sep) . ",
				theme=" . DBOld::escape($theme) . ",
				page_size=" . DBOld::escape($pagesize) . ",
				show_hints=" . DBOld::escape($show_hints) . ",
				print_profile=" . DBOld::escape($profile) . ",
				rep_popup=" . DBOld::escape($rep_popup) . ",
				query_size=" . DBOld::escape($query_size) . ",
				graphic_links=" . DBOld::escape($graphic_links) . ",
				language=" . DBOld::escape($lang) . ",
				sticky_doc_date=" . DBOld::escape($stickydate) . ",
				startup_tab=" . DBOld::escape($startup_tab) . "
				WHERE id = " . DBOld::escape($id);
			DBOld::query($sql, "could not update user display prefs for $id");
		}

		//-----------------------------------------------------------------------------------------------
		public static function	get_all($all = false)
		{
			$sql
			 = "SELECT u.*, r.role FROM users u, security_roles r
				WHERE u.role_id=r.id";
			if (!$all) {
				$sql .= " AND !u.inactive";
			}
			return DBOld::query($sql, "could not get users");
		}

		//-----------------------------------------------------------------------------------------------
		public static function	get($id)
		{
			$sql    = "SELECT * FROM users WHERE id=" . DBOld::escape($id);
			$result = DBOld::query($sql, "could not get user $id");
			return DBOld::fetch($result);
		}

		//-----------------------------------------------------------------------------------------------
		//	This public static function  is necessary for admin prefs update after upgrade from 2.1
		//
		public static function	get_by_login($user_id)
		{
			$sql    = "SELECT * FROM users WHERE user_id=" . DBOld::escape($user_id);
			$result = DBOld::query($sql, "could not get user $user_id");
			return DBOld::fetch($result);
		}

		//-----------------------------------------------------------------------------------------------
		public static function	delete($id)
		{
			$sql = "DELETE FROM users WHERE id=" . DBOld::escape($id);
			DBOld::query($sql, "could not delete user $id");
		}

		//-----------------------------------------------------------------------------------------------
		public static function	get_for_login($user_id, $password)
		{
			DBOld::getInstance();
			// do not exclude inactive records or you lost access after source upgrade
			// on sites using pre 2.2 database
			$user        = new Auth($user_id);
			$md5password = md5($password);
			$result      = DB::select('user_id')->from('users')->where('password=', $md5password)->fetch();
			if (count($result) > 0) {
				$_SESSION['change_password'] = true;
			}
			$password = $user->hash_password($password);
			$sql      = "SELECT * FROM users WHERE user_id = " . DBOld::escape($user_id) . " AND"
			 . " (password=" . DBOld::escape($password) . " OR password=" . DBOld::escape($md5password) . ")";
			return DBOld::query($sql, "could not get validate user login for $user_id");
		}

		//-----------------------------------------------------------------------------------------------
		public static function	update_visitdate($user_id)
		{
			$sql = "UPDATE users SET last_visit_date='" . date("Y-m-d H:i:s") . "'
				WHERE user_id=" . DBOld::escape($user_id);
			DBOld::query($sql, "could not update last visit date for user $user_id");
		}

		//-----------------------------------------------------------------------------------------------
		public static function	check_activity($id)
		{
			$sql    = "SELECT COUNT(*) FROM audit_trail WHERE audit_trail.user="
			 . DBOld::escape($id);
			$result = DBOld::query($sql, "Cant check user activity");
			$ret    = DBOld::fetch($result);
			return $ret[0];
		}

		//-----------------------------------------------------------------------------------------------
		public static function	show_online()
		{
			if (!Config::get('ui_users_showonline') || !isset($_SESSION['get_text'])) {
				return "";
			}
			$result = DBOld::query("SHOW TABLES LIKE 'useronline'");
			if (DBOld::num_rows($result) == 1) {
				$timeoutseconds = 120;
				$timestamp      = time();
				$timeout        = $timestamp - $timeoutseconds;
				/*
														 This will find out if user is from behind proxy server.
														 In that case, the script would count them all as 1 user.
														 This public static function  tryes to get real IP address.
														 */
				if (isset($_SERVER['HTTP_CLIENT_IP'])) {
					$ip = $_SERVER['HTTP_CLIENT_IP'];
				}
				elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
					$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				}
				elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
					$ip = $_SERVER['HTTP_X_FORWARDED'];
				}
				elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
					$ip = $_SERVER['HTTP_FORWARDED_FOR'];
				}
				elseif (isset($_SERVER['HTTP_FORWARDED'])) {
					$ip = $_SERVER['HTTP_FORWARDED'];
				}
				else {
					$ip = $_SERVER['REMOTE_ADDR'];
				}
				// Add user to database
				DBOld::query(
					"INSERT INTO useronline (timestamp, ip, file) VALUES ('" . $timestamp . "','" . $ip . "','" .
					 $_SERVER['PHP_SELF'] . "')"
				);
				//Remove users that were not online within $timeoutseconds.
				DBOld::query("DELETE FROM useronline WHERE timestamp<" . $timeout);
				// Select online users
				$result = DBOld::query("SELECT DISTINCT ip FROM useronline");
				$users  = DBOld::num_rows($result);
			}
			else
			{
				$users = 1;
			}
			return "$users " . ($users == 1 ? _("user online") : _("users online"));
		}
	}