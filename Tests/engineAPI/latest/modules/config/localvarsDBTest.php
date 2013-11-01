<?php

class MyGuestbookTest extends PHPUnit_Extensions_Database_TestCase {
    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection() {

    	if ($this->conn == null) {
    		if (self::$pdo == null) {
    			try {
    				self::$pdo = new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
    			}
    			catch(PDOException $e) {
    				echo $e->getMessage();
    			}
    		}

    		$this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
    	}
        return $this->conn;
    }
 
    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet() {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/_files/guestbook-seed.xml');
    }
}


?>