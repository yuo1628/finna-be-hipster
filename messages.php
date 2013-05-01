<?php
require './saver.php';

// 文章類別
class Article extends PDO_Saver {
	private $pk; // 文章資料庫主鍵值
	private $title; // 文章標題
	private $context; // 文章內容
	
	// 設定文章標題
	public function setTitle($title) {
		$this->title = $title;
	}
	
	// 取得文章標題
	public function getTitle() {
		return $this->title;
	}
	
	// 設定文章內容
	public function setContext($context) {
		$this->context = $context;
	}
	
	// 取得文章內容
	public function getContext() {
		return $this->context;
	}
	
	// 設定文章主鍵值
	protected function setPk($pk) {
		$this->pk = $pk;
	}
	
	// 取得文章主鍵值
	public function getPk() {
		return $this->pk;
	}
	
	// 取得與資料庫的連結
	protected static function getConnection() {
		return new PDO(); // missing
	}
	
	// 將此文章永久儲存
	// 回傳True代表儲存成功
	public function save() {
		$connection = $this->getConnection();
		// 新增文章至資料庫
		if ( ! isset($this->pk)) {
			$addArticleSql = <<<SQL
				INSERT INTO article (title, context)
				VALUES (:title, :context)
SQL;
			$statement = $connection->prepare($addArticleSql);
			$statement->bindParam(':title', $this->title, PDO::PARAM_STR);
			$statement->bindParam(':context', $this->context, PDO::PARAM_STR);
			$result = $statement->execute(); // return True on success, return False on failure.
			// 插入失敗
			if ( ! $result) {
				die(implode(', ', $statement->errorInfo()));
			}
			$this->setPk($connection->lastInsertId());
		}
		// 更新文章至資料庫
		else {
			$updateSql = <<<SQL
				UPDATE article
				SET title=:title, context=:context
				WHERE pk=:pk
SQL;
			$statement = $connection->prepare($updateSql);
			$statement->bindParam(':pk', $this->getPk(), PDO::PARAM_INT);
			$statement->bindParam(':title', $this->getTitle(), PDO::PARAM_STR);
			$statement->bindParam(':context', $this->getContext(), PDO::PARAM_STR);
			$result = $statement->execute();
			if ( ! $result) {
				die(implode(', ', $statement->errorInfo()));
			}
		}
		// 能執行到這表示文章更新或新增成功
		return True;
	}
	
	// 從資料庫中刪除文章
	// 刪除失敗回傳False(文章已經刪除或尚未儲存至資料庫)
	// 回傳True則代表刪除成功
	public function delete() {
		$pk = $this->getPk();
		if ( is_null($pk) ) {
			return False;
		}
		$deleteSql = <<<SQL
			DELETE FROM article
			WHERE pk = :pk
SQL;
		$connection = $this->getConnection();
		$statement = $connection->prepare($deleteSql);
		$statement->bindParam(':pk', $pk);
		$result = $statement->execute();
		// 這裡會執行失敗，則代表SQL語法錯誤
		if (!$result) {
			die(implode(', ', $statement->errorInfo()));
		}
		// 清除文章的pk紀錄
		$this->setPk(Null);
		// 執行到此則代表執行成功
		return True;
	}
	
	// 取得儲存的文章
	// 傳入主鍵值來尋找文章
	// 如果找不到指定的文章，則回傳False
	// 如果找到，則回傳一個全新的Article實體
	public static function get($pk) {
		$getSql = <<<SQL
			SELECT pk, title, context
			FROM article
			WHERE pk=:pk
SQL;
		$connection = Article::getConnection();
		$statement = $connection->prepare($getSql);
		$statement->bindParam(':pk', $pk, PDO::PARAM_INT);
		$statement->execute();
		$row = $statement->fetch(PDO::FETCH_ASSOC);
		// 找不到結果
		if ( ! $row) {
			return False;
		}
		// 把找到的文章的值存入物件中
		$newArticle = new Article();
		$newArticle->setPk($row['pk']);
		$newArticle->setTitle($row['title']);
		$newArticle->setContext($row['context']);
		return $newArticle;
	}
	
	// 取得所有儲存的文章
	// 回傳包含Article實體的陣列
	public static function all() {
		$allSql = <<<SQL
			SELECT pk, title, context
			FROM article
SQL;
		$connection = Article::getConnection();
		$allArticlesArray = array();
		$statement = $connection->query($allSql);
		foreach($statement->fetchAll() as $row) {
			$newArticle = new Article();
			$newArticle->setPk($row['pk']);
			$newArticle->setTitle($row['title']);
			$newArticle->setContext($row['context']);
			$allArticlesArray[] = $newArticle;
		}
		return $allArticlesArray;
	}
}

?>