<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 22/10/11
	 * Time: 5:49 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Users
	{
		public static function	add($user_id, $real_name, $password, $phone, $email, $role_id,
			$language, $profile, $rep_popup, $pos)
			{
				$sql
				 = "INSERT INTO users (user_id, real_name, password,phone, email, role_id, language, pos, print_profile, rep_popup)
				VALUES (" . DB::escape($user_id) . ",
				" . DB::escape($real_name) . ", " . DB::escape($password) . "," . DB::escape($phone)
				 . "," . DB::escape($email) . ", " . DB::escape($role_id) . ", " . DB::escape($language)
				 . ", " . DB::escape($pos) . "," . DB::escape($profile) . "," . DB::escape($rep_popup)
				 . " )";
				DB::query($sql, "could not add user for $user_id");
			}

		//-----------------------------------------------------------------------------------------------
		public static function	update_password($id, $user_id, $password)
			{
				$sql = "UPDATE users SET password=" . DB::escape($password) . ",
				user_id = " . DB::escape($user_id) . " WHERE id=" . DB::escape($id);
				DB::query($sql, "could not update user password for $user_id");
				session_regenerate_id();
			}

		//-----------------------------------------------------------------------------------------------
		public static function	update(
			$id, $user_id, $real_name, $phone, $email, $role_id,
			$language, $profile, $rep_popup, $pos
		)
			{
				$sql = "UPDATE users SET real_name=" . DB::escape($real_name) .
				 ", phone=" . DB::escape($phone) . ",
				email=" . DB::escape($email) . ",
				role_id=" . DB::escape($role_id) . ",
				language=" . DB::escape($language) . ",
				print_profile=" . DB::escape($profile) . ",
				rep_popup=" . DB::escape($rep_popup) . ",
				pos=" . DB::escape($pos) . ",
				user_id = " . DB::escape($user_id)
				 . " WHERE id=" . DB::escape($id);
				DB::query($sql, "could not update user for $user_id");
				session_regenerate_id();
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
				prices_dec=" . DB::escape($price_dec) . ",
				qty_dec=" . DB::escape($qty_dec) . ",
				rates_dec=" . DB::escape($exrate_dec) . ",
				percent_dec=" . DB::escape($percent_dec) . ",
				show_gl=" . DB::escape($showgl) . ",
				show_codes=" . DB::escape($showcodes) . ",
				date_format=" . DB::escape($date_format) . ",
				date_sep=" . DB::escape($date_sep) . ",
				tho_sep=" . DB::escape($tho_sep) . ",
				dec_sep=" . DB::escape($dec_sep) . ",
				theme=" . DB::escape($theme) . ",
				page_size=" . DB::escape($pagesize) . ",
				show_hints=" . DB::escape($show_hints) . ",
				print_profile=" . DB::escape($profile) . ",
				rep_popup=" . DB::escape($rep_popup) . ",
				query_size=" . DB::escape($query_size) . ",
				graphic_links=" . DB::escape($graphic_links) . ",
				language=" . DB::escape($lang) . ",
				sticky_doc_date=" . DB::escape($stickydate) . ",
				startup_tab=" . DB::escape($startup_tab) . "
				WHERE id = " . DB::escape($id);
				DB::query($sql, "could not update user display prefs for $id");
				session_regenerate_id();
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
				return DB::query($sql, "could not get users");
			}

		//-----------------------------------------------------------------------------------------------
		public static function	get($id)
			{
				$sql = "SELECT * FROM users WHERE id=" . DB::escape($id);
				$result = DB::query($sql, "could not get user $id");
				return DB::fetch($result);
			}

		//-----------------------------------------------------------------------------------------------
		//	This public static function  is necessary for admin prefs update after upgrade from 2.1
		//
		public static function	get_by_login($user_id)
			{
				$sql = "SELECT * FROM users WHERE user_id=" . DB::escape($user_id);
				$result = DB::query($sql, "could not get user $user_id");
				return DB::fetch($result);
			}

		//-----------------------------------------------------------------------------------------------
		public static function	delete($id)
			{
				$sql = "DELETE FROM users WHERE id=" . DB::escape($id);
				DB::query($sql, "could not delete user $id");
			}

		//-----------------------------------------------------------------------------------------------
		public static function	get_for_login($user_id, $password)
			{
				// do not exclude inactive records or you lost access after source upgrade
				// on sites using pre 2.2 database
				$user = new Auth($user_id);
				$md5password = md5($password);
				$result = DB::select('user_id')->from('users')->where('password=', $md5password)->fetch();
				if (count($result) > 0) {
					$_SESSION['change_password'] = true;
				}
				$password = $user->hash_password($password);
				$sql = "SELECT * FROM users WHERE user_id = " . DB::escape($user_id) . " AND"
				 . " (password=" . DB::escape($password) . " OR password=" . DB::escape($md5password) . ")";
				DB::query($sql, "could not get validate user login for $user_id");
				$result = DB::fetch();
				$success = DB::num_rows();
				DB::insert('user_log')->values(array('user' => $user_id, 'success' => $success))->exec();
				return $result ? : false;
			}

		//-----------------------------------------------------------------------------------------------
		public static function	update_visitdate($user_id)
			{
				$sql = "UPDATE users SET last_visit_date='" . date("Y-m-d H:i:s") . "'
				WHERE user_id=" . DB::escape($user_id);
				DB::query($sql, "could not update last visit date for user $user_id");
			}

		//-----------------------------------------------------------------------------------------------
		public static function	check_activity($id)
			{
				$sql = "SELECT COUNT(*) FROM audit_trail WHERE audit_trail.user="
				 . DB::escape($id);
				$result = DB::query($sql, "Cant check user activity");
				$ret = DB::fetch($result);
				return $ret[0];
			}

		//-----------------------------------------------------------------------------------------------
		public static function	show_online()
			{
				if (!Config::get('ui_users_showonline') || !isset($_SESSION['get_text'])) {
					return "";
				}
				$result = DB::query("SHOW TABLES LIKE 'useronline'");
				if (DB::num_rows($result) == 1) {
					$timeoutseconds = 120;
					$timestamp = time();
					$timeout = $timestamp - $timeoutseconds;
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
					DB::query(
						"INSERT INTO useronline (timestamp, ip, file) VALUES ('" . $timestamp . "','" . $ip . "','" .
						 $_SERVER['PHP_SELF'] . "')"
					);
					//Remove users that were not online within $timeoutseconds.
					DB::query("DELETE FROM useronline WHERE timestamp<" . $timeout);
					// Select online users
					$result = DB::query("SELECT DISTINCT ip FROM useronline");
					$users = DB::num_rows($result);
				} else {
					$users = 1;
				}
				return _("users online") . ": $users";
			}
	}
