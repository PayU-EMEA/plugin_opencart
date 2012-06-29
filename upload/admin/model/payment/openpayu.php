<?php
class ModelPaymentOpenpayu extends Model {
	
	public function createDatabaseTables() {
		$sql  = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."openpayu_so` ( ";
		$sql  = "`bind_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
		$sql .= "`order_id` int(32) NOT NULL, ";
		$sql .= "`session_id` int(32) NOT NULL, ";
		$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$this->db->query($sql);
	}
	
	public function dropDatabaseTables() {
		$sql = "DROP TABLE IF EXISTS `".DB_PREFIX."openpayu_so`;";
		$this->db->query($sql);
	}
}
?>

