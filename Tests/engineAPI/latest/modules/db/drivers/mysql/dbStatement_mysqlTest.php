<?php
class dbStatement_mysqlTest extends PHPUnit_Extensions_Database_TestCase {
    const UNDEFINED_TABLE_KEY = 'SomeStringThatDoesNotExistInTheTestingTableThatWeCanUseToTestOnEmptyResultSets';
    /**
     * @var array
     */
    private static $driverOptions;
    /**
     * @var PDO
     */
    static $pdo;
    /**
     * @var dbDriver
     */
    static $db;

    private function createPDO(){
        return new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
    }

    static function setUpBeforeClass(){
        self::$driverOptions = array(
            'dsn'    => $GLOBALS['DB_DSN'],
            'user'   => $GLOBALS['DB_USER'],
            'pass'   => $GLOBALS['DB_PASSWD'],
            'dbname' => $GLOBALS['DB_DBNAME'],
        );
        self::$db = db::create('mysql', self::$driverOptions);
    }

    public function getConnection(){
        self::$pdo = $this->createPDO();
        return $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
    }

    public function getDataSet(){
        self::$pdo->exec(file_get_contents(__DIR__.'/../../testData/drivers/mysql/dbObjectTesting.sql'));

        $dataSet = new PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
        $dataSet->addTable('dbObjectTesting');

        return $dataSet;
    }

    // =================================================================================================================

    # Tests for execute()
    #########################################

    # Tests for bindParam()
    #########################################

    # Tests for bindValue()
    #########################################

    # Tests for fieldCount()
    #########################################
    function test_fieldCountIsOnlyAvailableOnceTheStatementHasBeenExecuted(){
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT 1", FALSE);
        $this->assertEquals(0, $stmt->fieldCount());
    }
    function test_fieldCountSimple(){
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT 1");
        $this->assertEquals(1, $stmt->fieldCount(), "Failed for {$stmt->getSQL()}");
        $stmt = $db->query("SELECT 1,2");
        $this->assertEquals(2, $stmt->fieldCount(), "Failed for {$stmt->getSQL()}");
        $stmt = $db->query("SELECT 1,2,3");
        $this->assertEquals(3, $stmt->fieldCount(), "Failed for {$stmt->getSQL()}");
    }
    function test_fieldCountLiveQuery(){
        $db = db::create('mysql', self::$driverOptions);
        // Test SQL that will return a result
        $stmt = $db->query("SELECT * FROM dbObjectTesting");
        $this->assertEquals(3, $stmt->fieldCount(), "Failed for {$stmt->getSQL()}");
        // Test SQL that will not return a result
        $stmt = $db->query("SELECT * FROM dbObjectTesting WHERE `id`='".self::UNDEFINED_TABLE_KEY."'");
        $this->assertEquals(3, $stmt->fieldCount(), "Failed for {$stmt->getSQL()}");
    }

    # Tests for fieldNames()
    #########################################
    function test_fieldNamesIsOnlyAvailableOnceTheStatementHasBeenExecuted(){
        $db         = db::create('mysql', self::$driverOptions);
        $stmt       = $db->query("SELECT 1", FALSE);
        $fieldNames = $stmt->fieldNames();
        $this->assertTrue(is_array($fieldNames));
        $this->assertEquals(0, sizeof($fieldNames));
    }
    function test_fieldNamesSimple(){
        $db = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT 1 AS a");
        $fieldNames = $stmt->fieldNames();
        $this->assertTrue(is_array($fieldNames));
        $this->assertEquals(1, sizeof($fieldNames));
        $this->assertContains('a', $fieldNames);
    }
    function test_fieldNamesLiveQuery(){
        $db = db::create('mysql', self::$driverOptions);

        // Test SQL that will return a result
        $stmt = $db->query("SELECT * FROM dbObjectTesting");
        $fieldNames = $stmt->fieldNames();
        $this->assertTrue(is_array($fieldNames), 'It returns an array');
        $this->assertEquals(3, sizeof($fieldNames), 'It returns an array with 3 elements');
        $this->assertContains('id', $fieldNames, "It returns the element 'id'");
        $this->assertContains('timestamp', $fieldNames, "It returns the element 'timestamp'");
        $this->assertContains('value', $fieldNames, "It returns the element 'value'");

        // Test SQL that will not return a result
        $stmt = $db->query("SELECT * FROM dbObjectTesting WHERE `id`='".self::UNDEFINED_TABLE_KEY."'");
        $fieldNames = $stmt->fieldNames();
        $this->assertTrue(is_array($fieldNames), 'It returns an array');
        $this->assertEquals(3, sizeof($fieldNames), 'It returns an array with 3 elements');
        $this->assertContains('id', $fieldNames, "It returns the element 'id'");
        $this->assertContains('value', $fieldNames, "It returns the element 'value'");
        $this->assertContains('timestamp', $fieldNames, "It returns the element 'timestamp'");
        $this->assertContains('value', $fieldNames, "It returns the element 'value'");
    }

    # Tests for rowCount()
    #########################################
    function test_rowCountIsOnlyAvailableOnceTheStatementHasBeenExecuted(){
        $db = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT 1", FALSE);
        $this->assertFalse($stmt->rowCount());
    }
    function test_rowCount(){
        $db = db::create('mysql', self::$driverOptions);

        $stmt = $db->query("SELECT * FROM `dbObjectTesting`");
        $this->assertEquals(0, $stmt->rowCount());

        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");
        $stmt = $db->query("SELECT * FROM `dbObjectTesting`");
        $this->assertEquals(3, $stmt->rowCount());
    }

    # Tests for affectedRows()
    #########################################
    function test_affectedRowsIsOnlyAvailableOnceTheStatementHasBeenExecuted(){
        $db = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT * FROM `dbObjectTesting`", FALSE);
        $this->assertFalse($stmt->affectedRows());
    }
    function test_affectedRows(){
        $db = db::create('mysql', self::$driverOptions);

        // INSERT some rows
        $stmt = $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");
        $this->assertEquals(3, $stmt->affectedRows(), "Failed for {$stmt->getSQL()}");

        // UPDATE 1 of the rows
        $stmt = $db->query("UPDATE `dbObjectTesting` SET `value`='' WHERE `value`='b' LIMIT 1");
        $this->assertEquals(1, $stmt->affectedRows(), "Failed for {$stmt->getSQL()}");

        // DELETE 2 of the rows
        $stmt = $db->query("DELETE FROM  `dbObjectTesting` WHERE `value` IN ('a','c') LIMIT 2");
        $this->assertEquals(2, $stmt->affectedRows(), "Failed for {$stmt->getSQL()}");
    }

    # Tests for insertId()
    #########################################
    function test_insertIdIsOnlyAvailableOnceTheStatementHasBeenExecuted(){
        $db = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT * FROM `dbObjectTesting`", FALSE);
        $this->assertFalse($stmt->insertId());
    }
    function test_insertId(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");

        $stmt = $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUES('test')");
        $insertID = $stmt->insertId();

        $stmt = self::$pdo->query("SELECT `id` FROM dbObjectTesting WHERE `value`='test'LIMIT 1");
        $this->assertEquals($insertID, $stmt->fetchColumn(0));
    }

    # Tests for fetch()
    #########################################
    function test_fetchSupportsFETCH_ASSOC(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('timestamp', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertEquals('a1', $row['value']);
    }
    function test_fetchSupportsFETCH_NUM(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey(0, $row);
        $this->assertArrayHasKey(1, $row);
        $this->assertArrayHasKey(2, $row);
        $this->assertEquals('a1', $row[2]);
    }
    function test_fetchSupportsFETCH_OBJ(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        $this->assertTrue(is_object($row), "fetch() returns an object");
        $this->assertAttributeNotEmpty('id', $row);
        $this->assertAttributeNotEmpty('timestamp', $row);
        $this->assertAttributeNotEmpty('value', $row);
        $this->assertAttributeEquals('a1', 'value', $row);
    }
    function test_fetchUsesFetchAssocByDefault(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch();
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('timestamp', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertEquals('a1', $row['value']);
    }
    function test_fetchReturnsRowByRow(){
        $testData = array('a1','b1','c1');
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1'),('b1'),('c1')");
        $stmt = $db->query('SELECT * FROM dbObjectTesting');

        for($n=0; $row = $stmt->fetch(PDO::FETCH_ASSOC); $n++){
            $this->assertTrue(is_array($row));
            $this->assertEquals(3, sizeof($row));
            $this->assertArrayHasKey('value', $row);
            $this->assertEquals($testData[$n], $row['value']);
        }
    }

    # Tests for fetchAll()
    #########################################
    function test_fetchAllSupportsFETCH_ASSOC(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('timestamp', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertEquals('a1', $row['value']);
    }
    function test_fetchAllSupportsFETCH_NUM(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey(0, $row);
        $this->assertArrayHasKey(1, $row);
        $this->assertArrayHasKey(2, $row);
        $this->assertEquals('a1', $row[2]);
    }
    function test_fetchAllSupportsFETCH_OBJ(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        $this->assertTrue(is_object($row), "fetch() returns an object");
        $this->assertAttributeNotEmpty('id', $row);
        $this->assertAttributeNotEmpty('timestamp', $row);
        $this->assertAttributeNotEmpty('value', $row);
        $this->assertAttributeEquals('a1', 'value', $row);
    }
    function test_fetchAllUsesFetchAssocByDefault(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch();
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('timestamp', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertEquals('a1', $row['value']);
    }
    function test_fetchAllReturnsRowByRow(){
        $testData = array('a1','b1','c1');
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1'),('b1'),('c1')");
        $stmt = $db->query('SELECT * FROM dbObjectTesting');

        for($n=0; $row = $stmt->fetch(PDO::FETCH_ASSOC); $n++){
            $this->assertTrue(is_array($row));
            $this->assertEquals(3, sizeof($row));
            $this->assertArrayHasKey('value', $row);
            $this->assertEquals($testData[$n], $row['value']);
        }
    }

    # Tests for fetchField()
    #########################################
    function test_fetchFieldSupportsFETCH_ASSOC(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('timestamp', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertEquals('a1', $row['value']);
    }
    function test_fetchFieldSupportsFETCH_NUM(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey(0, $row);
        $this->assertArrayHasKey(1, $row);
        $this->assertArrayHasKey(2, $row);
        $this->assertEquals('a1', $row[2]);
    }
    function test_fetchFieldSupportsFETCH_OBJ(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        $this->assertTrue(is_object($row), "fetch() returns an object");
        $this->assertAttributeNotEmpty('id', $row);
        $this->assertAttributeNotEmpty('timestamp', $row);
        $this->assertAttributeNotEmpty('value', $row);
        $this->assertAttributeEquals('a1', 'value', $row);
    }
    function test_fetchFieldUsesFetchAssocByDefault(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch();
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('timestamp', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertEquals('a1', $row['value']);
    }
    function test_fetchFieldReturnsRowByRow(){
        $testData = array('a1','b1','c1');
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1'),('b1'),('c1')");
        $stmt = $db->query('SELECT * FROM dbObjectTesting');

        for($n=0; $row = $stmt->fetch(PDO::FETCH_ASSOC); $n++){
            $this->assertTrue(is_array($row));
            $this->assertEquals(3, sizeof($row));
            $this->assertArrayHasKey('value', $row);
            $this->assertEquals($testData[$n], $row['value']);
        }
    }

    # Tests for fetchFieldAll()
    #########################################
    function test_fetchFieldAllSupportsFETCH_ASSOC(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('timestamp', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertEquals('a1', $row['value']);
    }
    function test_fetchFieldAllSupportsFETCH_NUM(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey(0, $row);
        $this->assertArrayHasKey(1, $row);
        $this->assertArrayHasKey(2, $row);
        $this->assertEquals('a1', $row[2]);
    }
    function test_fetchFieldAllSupportsFETCH_OBJ(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        $this->assertTrue(is_object($row), "fetch() returns an object");
        $this->assertAttributeNotEmpty('id', $row);
        $this->assertAttributeNotEmpty('timestamp', $row);
        $this->assertAttributeNotEmpty('value', $row);
        $this->assertAttributeEquals('a1', 'value', $row);
    }
    function test_fetchFieldAllUsesFetchAssocByDefault(){
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1')");

        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row = $stmt->fetch();
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(3, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('timestamp', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertEquals('a1', $row['value']);
    }
    function test_fetchFieldAllReturnsRowByRow(){
        $testData = array('a1','b1','c1');
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a1'),('b1'),('c1')");
        $stmt = $db->query('SELECT * FROM dbObjectTesting');

        for($n=0; $row = $stmt->fetch(PDO::FETCH_ASSOC); $n++){
            $this->assertTrue(is_array($row));
            $this->assertEquals(3, sizeof($row));
            $this->assertArrayHasKey('value', $row);
            $this->assertEquals($testData[$n], $row['value']);
        }
    }

    # Tests for sqlState()
    #########################################
    function test_sqlState(){
        $db = db::create('mysql', self::$driverOptions);

        // No error
        $stmt = $db->query('SELECT 1');
        $this->assertEquals('00000', $stmt->sqlState());

        // Syntax error
        $stmt = $db->query('SELECT * FROM');
        $this->assertEquals('42000', $stmt->sqlState());

        // TODO: Check more error codes?
    }

    # Tests for errorCode()
    #########################################
    function test_errorCode(){
        $db = db::create('mysql', self::$driverOptions);

        // No error
        $stmt = $db->query('SELECT 1');
        $this->assertNull($stmt->errorCode());

        // Syntax error
        $stmt = $db->query('SELECT * FROM');
        $this->assertEquals('1064', $stmt->errorCode());

        // TODO: Check more errors?
    }

    # Tests for errorMsg()
    #########################################
    function test_errorMsg(){
        $db = db::create('mysql', self::$driverOptions);

        // No error
        $stmt = $db->query('SELECT 1');
        $this->assertNull($stmt->errorMsg());

        // Syntax error
        $stmt = $db->query('SELECT * FROM');
        $this->assertRegExp('/You have an error in your SQL syntax.*/', $stmt->errorMsg());

        // TODO: Check more errors?
    }
}
 