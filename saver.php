<?php

// 此類別代表子類別會使用PDO來永久儲存資料
abstract class PDO_Saver {
	// 取得與資料庫的連線
	abstract protected static function getConnection();
	
	// 永久儲存資料
	abstract public function save();
	
	// 取得儲存的資料
	abstract public static function get($pk);
}

?>