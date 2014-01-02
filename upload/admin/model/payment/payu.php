<?php
/*
* ver. 0.1.8
* PayU Payment Modules
*
* @copyright  Copyright 2012 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
* http://twitter.com/openpayu
*/
class ModelPaymentPayu extends Model
{

    public function createDatabaseTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "payu_so` (
            `bind_id` int(11) NULL AUTO_INCREMENT PRIMARY KEY,
            `order_id` int(32) NOT NULL,
            `session_id` varchar(32) NOT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $this->db->query($sql);
    }

    public function dropDatabaseTables()
    {
        $sql = "DROP TABLE IF EXISTS `" . DB_PREFIX . "payu_so`;";
        $this->db->query($sql);
    }
}