<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 19/09/11
	 * Time: 2:22 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Auth
	{
		/**
		 * @var
		 */
		protected $username;
		/**
		 * @var
		 */
		protected $id;
		/**
		 * @var
		 */
		private $hasher;
		/**
		 * @param $username
		 */
		public function __construct($username) {
			$this->username = $username;
		}
		/**
		 * @param $id
		 * @param $password
		 */
		public function update_password($id, $password) {

			DB::update('users')->value('password', $this->hash_password($password))->value('user_id', $this->username)
			 ->value('change_password', 0)->where('id=', $id)->exec();

			session_regenerate_id();
		}
		/**
		 * @param $password
		 *
		 * @return string
		 */
		public function hash_password(&$password) {
			$password = crypt($password, '$6$rounds=5000$' . Config::get('auth_salt') . '$');
	return $password;
		}


		public function check_user_password($user_id, $password) {
			$result = DB::select()->from('users')->where('user_id=', $user_id)->fetch()->one();
			$this->hash_password($password);

			if ($result['password'] != $password) {
				$result = false;
			}else {
				unset($result['password']);
			}
			DB::insert('user_login_log')->values(array(
																								'user' => $user_id, 'IP' => Users::get_ip(), 'success' => (bool)$result))->exec();

			return $result;
		}
		/**
		 * @static
		 *
		 * @param			$password
		 * @param bool $username
		 *
		 * @return array
		 */
		static public function checkPasswordStrength($password, $username = false) {
			$returns = array(
				'strength' => 0, 'error' => 0, 'text' => '');
			$length = strlen($password);
			if ($length < 8) {
				$returns['error'] = 1;
				$returns['text'] = 'The password is not long enough';
			}
			else {
				//check for a couple of bad passwords:
				if ($username && strtolower($password) == strtolower($username)) {
					$returns['error'] = 4;
					$returns['text'] = 'Password cannot be the same as your Username';
				}
				elseif (strtolower($password) == 'password') {
					$returns['error'] = 3;
					$returns['text'] = 'Password is too common';
				}
				else {
					preg_match_all("/(.)\1{2}/", $password, $matches);
					$consecutives = count($matches[0]);
					preg_match_all("/\d/i", $password, $matches);
					$numbers = count($matches[0]);
					preg_match_all("/[A-Z]/", $password, $matches);
					$uppers = count($matches[0]);
					preg_match_all("/[^A-z0-9]/", $password, $matches);
					$others = count($matches[0]);
					//see if there are 3 consecutive chars (or more) and fail!
					if ($consecutives > 0) {
						$returns['error'] = 2;
						$returns['text'] = 'Too many consecutive characters';
					}
					elseif ($others > 1 || ($uppers > 1 && $numbers > 1)) {
						//bulletproof
						$returns['strength'] = 5;
						$returns['text'] = 'Virtually Bulletproof';
					}
					elseif (($uppers > 0 && $numbers > 0) || $length > 14) {
						//very strong
						$returns['strength'] = 4;
						$returns['text'] = 'Very Strong';
					}
					else {
						if ($uppers > 0 || $numbers > 2 || $length > 9) {
							//strong
							$returns['strength'] = 3;
							$returns['text'] = 'Strong';
						}
						else {
							if ($numbers > 1) {
								//fair
								$returns['strength'] = 2;
								$returns['text'] = 'Fair';
							}
							else {
								//weak
								$returns['strength'] = 1;
								$returns['text'] = 'Weak';
							}
						}
					}
				}
			}
			return $returns;
		}
	}
