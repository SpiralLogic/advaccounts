<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 6/12/10
	 * Time: 5:36 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Messages
	{
		protected static $messages = '';
		protected static $count = 0;

		public function __construct() {
		}

		public static function	getNewUserMessages($userid = false) {
			if (!$userid) {
				return false;
			}
			$sql = "SELECT * FROM user_messages WHERE user =$userid AND unread > 0";
			$result = DB::query($sql, "could not retrieve user messages");
			static::$count = DB::num_rows($result);
			while ($row = DB::fetch_assoc($result)) {
				if (!empty($row['subject'])) {
					static::$messages .= '<div style="margin-top:5px; margin-bottom:5px; font-weight:bold; text-decoration:underline;">' .
					 $row['subject'] . ':</div><hr style="margin-top:1px;"/> <div style="padding-top:5px;" >'
					 . trim($row['message']) . '</div>';
				} else {
					static::$messages .= '<hr style="margin-top:1px;"/> <div style="margin-top:5px; padding-bottom:10px;" >'
					 . trim($row['message']) . '</div>';
				}
				$unread = $row['unread'] - 1;
				$id = $row['id'];
				$sql2 = "UPDATE user_messages SET unread={$unread} WHERE  id={$id} AND user=" . $userid;
				DB::query($sql2, 'Could not mark messages as unread');
			}
			return static::$count;
		}

		public static function setNewMessage($userid, $subject, $message) {
			$sql = "INSERT INTO user_messages (user, subject,message,unread) VALUES (" . DB::escape($userid) . ", " . DB::escape($subject) . ", " . DB::escape($message) . ", 1)";
			$result = DB::query($sql, "Couldn't add message for $userid");
			return $result;
		}

		public static function showNewMessages($user = false) {
			if (!$user && isset($_SESSION['current_user'])) {
				$user = $_SESSION['current_user']->user;
			}
			if ($user && static::getNewUserMessages($user) > 0) {
				static::makeDialog();
			}
		}

		public static function makeDialog() {
			$dialog = new Dialog(static::$count . ' New Messages', 'messagesbox', static::$messages);
			$dialog->addButtons(array('Close' => '$(this).dialog("close");'));
			$dialog->setOptions(array(
															 'autoopen' => true,
															 'modal' => true,
															 'width' => '500',
															 'resizeable' => false));
			$dialog->show();
		}
	}
