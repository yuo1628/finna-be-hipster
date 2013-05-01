<?php
require './saver.php';

// 文章類別
class Article implements iPDO_Saver {
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
	public static function getConnection() {
		return new PDO(); // missing
	}
	
	// 將此文章永久儲存
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
	}
	
	// 從資料庫中刪除文章
	// 刪除失敗回傳False(文章已經刪除或尚未儲存至資料庫)
	// 回傳True則代表刪除成功
	public function delete() {
		$pk = $this->getPk();
		// 未設定主鍵值則代表此文章尚未新增至資料庫或已刪除
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

// 部落格文章
class BlogArticle extends Article {
	private $date; // 文章發表日期
	
	public function __construct() {
		$this->setDate(new DateTime('now')); // DateTime(string $time, DateTimeZone $timezone)
	}
	
	// 設定文章發表日期
	public function setDate($date) {
		$this->date = $date;
	}
	
	// 取得文章發表日期
	public function getDate() {
		return $this->date;
	}
	
	// 儲存或更新文章至資料庫
	public function save() {
		$connection = $this->getConnection();
		// 新增
		if (is_null($this->getPk())) {
			$insertSql = <<<SQL
				INSERT INTO blog_article (title, context, date)
				VALUES (:title, :context, FROM_UNIXTIME(:date))
SQL;
			$statement = $connection->prepare($insertSql);
			$statement->bindValue(':title', $this->getTitle(), PDO::PARAM_STR);
			$statement->bindValue(':context', $this->getContext(), PDO::PARAM_STR);
			$statement->bindValue(':date', $this->getDate()->getTimestamp(), PDO::PARAM_STR);
			// 如果SQL語法出錯
			if (!$statement->execute()) {
				die(implode(", ", $statement->errorInfo()));
			}
			$lastInsertId = $connection->lastInsertId();
			$this->setPk($lastInsertId);
		}
		// 更新
		else {
			$updateSql = <<<SQL
				UPDATE blog_article
				SET title=:title, context=:context, date=FROM_UNIXTIME(:date)
				WHERE pk=:pk
SQL;
			$statement = $connection->prepare($updateSql);
			$statement->bindValue(':title', $this->getTitle());
			$statement->bindValue(':context', $this->getContext());
			$statement->bindValue(':date', $this->getDate()->getTimestamp());
			$statement->bindValue(':pk', $this->getPk());
			if (!$statement->execute()) {
				die(implode(', ', $statement->errorInfo()));
			}
			$this->setPk(null);
		}
	}
	
	// 取得指定主鍵值的文章
	public static function get($pk) {
		$selectSql = <<<SQL
			SELECT pk, title, context, date
			FROM blog_article
			WHERE pk=:pk
SQL;
		$statement = BlogArticle::getConnection()->prepare($selectSql);
		$statement->bindParam(':pk', $pk);
		// SQL 語法出錯
		if (!$statement->execute()) {
			die(implode(', ', $statement->errorInfo()));
		}
		$getArticle = new BlogArticle();
		$row = $statement->fetch(PDO::FETCH_ASSOC);
		// 找不到資料
		if (!$row) {
			return False;
		}
		// 填入從資料庫讀回的資料
		$getArticle->setTitle($row['title']);
		$getArticle->setContext($row['context']);
		$getArticle->setDate(new DateTime($row['date']));
		$getArticle->setPk($row['pk']);
		return $getArticle;
	}
	
	// 從資料庫中刪除文章
	// 刪除成功則回傳True，資料不須刪除(資料庫中無此文章資料)時則回傳False
	public function delete() {
		$pk = $this->getPk();
		// 無主鍵值則代表此文章已刪除或還未儲存
		if (is_null($pk)) {
			return False;
		}
		$deleteSql = <<<SQL
			DELETE FROM blog_article
			WHERE pk=:pk
SQL;
		$connection = $this->getConnection();
		$statement = $connection->prepare($deleteSql);
		$statement->bindValue(':pk', $pk);
		// SQL語法出錯
		if (!$statement->execute()) {
			die(implode(', ', $statement->errorInfo()));
		}
		return True;
	}
	
	// 取得所有已儲存文章
	public static function all() {
		$allSql = <<<SQL
			SELECT pk, title, context, date
			FROM blog_article
SQL;
		$connection = BlogArticle::getConnection();
		$statement = $connection->query($allSql);
		$all_articles = array();
		$rows = $statement->fetchAll();
		foreach ($rows as $row) {
			$article = new BlogArticle();
			$article->setPk($row['pk']);
			$article->setTitle($row['title']);
			$article->setContext($row['context']);
			$article->setDate($row['date']);
			$all_articles[] = $article;
		}
		return $all_articles;
	}
}

print '<pre>';
print_r(BlogArticle::all());
print '</pre>';
?>