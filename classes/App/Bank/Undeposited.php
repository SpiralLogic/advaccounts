<?php
    use ADV\Core\DB\DB;

    /**
     * Created by JetBrains PhpStorm.
     * User: Complex
     * Date: 4/08/12
     * Time: 5:25 PM
     * To change this template use File | Settings | File Templates.
     */
    class Bank_Undeposited
    {
        /** @var \ADV\Core\DB\DB */
        static $DB;
        /** @var Dates */
        static $Dates;
        /**
         * @param $groupid
         */
        public static function ungroup($group_id)
        {
            $sql = "UPDATE bank_trans SET undeposited=1, reconciled=null WHERE undeposited =" . static::$DB->_escape($group_id);
            static::$DB->_query($sql, "Couldn't ungroup group deposit");
            $sql = "UPDATE bank_trans SET ref=" . static::$DB->_quote('Removed group: ' . $group_id) . ", amount=0, reconciled='" . static::$Dates->_today(
            ) . "', undeposited=" . $group_id . " WHERE id=" . $group_id;
            static::$DB->_query($sql, "Couldn't update removed group deposit data");
        }
        /**
         * @param $deposit
         */
        public static function undeposit($deposit_id)
        {
            $sql = "UPDATE bank_trans SET undeposited=1, reconciled=null WHERE id=" . $deposit_id;
            static::$DB->_query($sql, "Can't change undeposited status");
        }
    }

    Bank_Undeposited::$DB    = DB::i();
    Bank_Undeposited::$Dates = Dates::i();
