<?php
class DB {
	private $connection;
	public function __construct() {
		$this->connection = new PDO('mysql:host=localhost;dbname=aventures;charset=utf8mb4','client','PASSWORD_BUT_I_DON\'T_KNOW_HOW_I_COULD_DO_OTHERWISE :)',[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
	}
	public function reqDirect($query) {
		$this->connection->prepare($query)->execute();
	}
	public function req($query,$params) {
		$this->connection->prepare($query)->execute($params);
	}
	public function getDirect($query) {
		$stmt = $this->connection->prepare($query);
		$stmt->execute();
		return $stmt->fetchAll();
	}
	public function get($query,$params) {
		$stmt = $this->connection->prepare($query);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}
	public function getRow($query,$params) {
		$stmt = $this->connection->prepare($query);
		$stmt->execute($params);
		return $stmt->fetch();
	}
	public function getValueDirect($query) {
		$stmt = $this->connection->prepare($query);
		$stmt->execute();
		return $stmt->fetchColumn();
	}
	public function getValueDirectNoNull($query) {
		$stmt = $this->connection->prepare($query);
		$stmt->execute();
		$val = $stmt->fetchColumn();
		if (is_null($val)) return 0;
		return $val;
	}
	public function getValue($query,$params) {
		$stmt = $this->connection->prepare($query);
		$stmt->execute($params);
		return $stmt->fetchColumn();
	}
	public function isEmpty($query,$params) {
		$stmt = $this->connection->prepare($query);
		$stmt->execute($params);
		return ($stmt->rowCount() == 0);
	}
	public function getLastInstertID() {
		return $this->connection->lastInsertId();
	}
	public function close() {
		$this->connection = null;
	}
}
?>