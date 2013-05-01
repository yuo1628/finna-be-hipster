<?php

// 此類別代表子類別會使用PDO來永久儲存資料
abstract class PDO_Saver {
	// 取得與資料庫的連線
	protected static function getConnection() {
		throw new Exception("未實作getConnection方法");
	}
	
	// 永久儲存資料
	abstract public function save();
	
	// 刪除儲存資料
	abstract public function delete();
	
	// 取得儲存的資料
	public static function get($pk) {
		throw new Exception("未實作get方法");
	}
}

?>