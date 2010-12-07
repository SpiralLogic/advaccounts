<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 6/12/10
 * Time: 5:36 PM
 * To change this template use File | Settings | File Templates.
 */

class Messages {


	protected $messages = '';
	protected $count = 0;

	function __construct() {
	}

	function getNewUserMessages($userid = false) {
		if (!$userid) {
			return false;
		}
		$sql = "SELECT * FROM " . TB_PREF . "user_messages WHERE user =$userid AND unread=1";
		$result = db_query($sql, "could not retrieve user messages");
		$this->count = db_num_rows($result);
		while ($row = db_fetch_assoc($result)) {
			if (!empty($row['subject']))
			$this->messages .= '<span style="padding-top:20px; margin-bottom:5px; font-weight:bold; text-decoration:underline;">'.$row['subject'].'</span>:<hr style="margin-top:0px;"> '
					.$row['message'].'<br>';
		    else
			    $this->messages .= '<hr style="margin-top:0px;"> '
					    . $row['message'] . '<br>';
		}
		$sql = "UPDATE ".TB_PREF."user_messages SET unread=0 WHERE unread=1 AND user=".$userid;
		$result = db_query($sql,'Could not mark messages as unread');
		return $this->count;
	}

	function setNewMessage($message) {
		$this->messages .= $message . '<hr>';
	}

	function makeDialog() {

		$dialog = new Dialog($this->count .' New Messages', 'messagesbox', $this->messages);
		$dialog->addButtons(array('Close' => '$(this).dialog("close");'));
		$dialog->setOptions(array('autoopen' => true, 'modal' => true, 'width' => '500', 'resizeable' => false));
		$dialog->show();
	}
}
