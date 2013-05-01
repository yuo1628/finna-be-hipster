<?php

// 此介面代表子類別會使用PDO來永久儲存資料
interface iPDO_Saver {
	// 取得與資料庫的連線
	public static function getConnection();
	
	// 永久儲存資料
	public function save();
	
	// 刪除儲存資料
	public function delete();
	
	// 取得儲存的資料
	public static function get($pk);
	
	// 取得所有儲存的資料
	public static function all();
}

?>