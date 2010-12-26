<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 23/12/10
 * Time: 2:39 PM
 * To change this template use File | Settings | File Templates.
 */
class contact_log {

    private static $dbTable = 'contact_log';

    static function add($contact_id, $type, $message) {
        if (!isset($contact_id)) return false;
        if (!isset($type)) return false;
        if (!isset($message)) return false;
        $date = date('Y-m-d H:i:s');
        $sql = "INSERT INTO " . TB_PREF . self::$dbTable . " (contact_id, date, type, message) VALUES (" . db_escape($contact_id) . "," . strtotime($date) . "," . db_escape($type) . "," . db_escape($message) . ")";
        db_query($sql, "Couldn't insert contact log");
        return db_insert_id();
    }

    static function read($contact_id, $type) {
        if (!isset($contact_id)) return false;
        if (!isset($type)) return false;
        $sql = "SELECT * FROM " . TB_PREF . self::$dbTable . " WHERE contact_id=" . db_escape($contact_id) . " AND type=" . db_escape($type) . " ORDER BY date";
        $result = db_query($sql, "Couldn't get contact log entries");
        return db_fetch_assoc($result);
    }
}
