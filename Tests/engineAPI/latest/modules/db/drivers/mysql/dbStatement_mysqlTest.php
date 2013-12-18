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

    private function createPDO() {
        return new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
    }

    static function setUpBeforeClass() {
        self::$driverOptions = array(
            'dsn'    => $GLOBALS['DB_DSN'],
            'user'   => $GLOBALS['DB_USER'],
            'pass'   => $GLOBALS['DB_PASSWD'],
            'dbname' => $GLOBALS['DB_DBNAME'],
        );
        self::$db            = db::create('mysql', self::$driverOptions);
    }

    public function getConnection() {
        self::$pdo = $this->createPDO();

        return $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
    }

    public function getDataSet() {
        self::$pdo->exec(file_get_contents(__DIR__.'/../../testData/drivers/mysql/dbObjectTesting.sql'));

        $dataSet = new PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
        $dataSet->addTable('dbObjectTesting');

        return $dataSet;
    }

    // =================================================================================================================

    # Tests for execute()
    #########################################
    function test_executeIsOnlyAvailableBeforeExecution() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT 1");
        $this->assertFalse($stmt->execute());
    }

    function test_executeCatchesCasesWhenNoPdoStatementHasBeenSet() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = new dbStatement_mysql($db);
        $this->assertFalse($stmt->execute());
    }

    function text_executeExecutesThePreparedStatement() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT 1", FALSE);
        $this->assertFalse($stmt->isExecuted());
        $this->assertTrue($stmt->execute());
        $this->assertTrue($stmt->isExecuted());
        $this->assertEquals(1, $stmt->fetchField());
    }

    function test_executeTakesParamsForThePreparedStatement() {
        $db = db::create('mysql', self::$driverOptions);

        $stmt1 = $db->query("INSERT INTO `dbObjectTesting` (a) VALUES(?)", FALSE);
        $this->assertTrue($stmt1->execute('a'));
        $verifyRow = $db->query("SELECT * FROM `dbObjectTesting` WHERE id=".$stmt1->insertId()." LIMIT 1")->fetch();
        $this->assertEquals('a', $verifyRow['a']);

        $stmt2 = $db->query("INSERT INTO `dbObjectTesting` (a,b) VALUES(?,?)", FALSE);
        $this->assertTrue($stmt2->execute('a', 'b'));
        $verifyRow = $db->query("SELECT * FROM `dbObjectTesting` WHERE id=".$stmt1->insertId()." LIMIT 1")->fetch();
        $this->assertEquals('a', $verifyRow['a']);
        $this->assertEquals('b', $verifyRow['b']);

        $stmt3 = $db->query("INSERT INTO `dbObjectTesting` (a,b,c) VALUES(?,?,?)", FALSE);
        $this->assertTrue($stmt3->execute('a', 'b', 'c'));
        $verifyRow = $db->query("SELECT * FROM `dbObjectTesting` WHERE id=".$stmt1->insertId()." LIMIT 1")->fetch();
        $this->assertEquals('a', $verifyRow['a']);
        $this->assertEquals('b', $verifyRow['b']);
        $this->assertEquals('c', $verifyRow['c']);
    }

    # Tests for bindParam()
    #########################################
    function test_bindParamIsOnlyAvailableBeforeExecution() {
        $var  = NULL;
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT 1");
        $this->assertFalse($stmt->bindParam(0, $a));
    }

    function test_bindParamCatchesCasesWhenNoPdoStatementHasBeenSet() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = new dbStatement_mysql($db);
        $var  = NULL;
        $this->assertFalse($stmt->bindParam(0, $var));
    }

    function test_bindParamBaseImplementation() {
        $db = db::create('mysql', self::$driverOptions);

        $pdo = $this->getMock('PDOStatement', array());
        $pdo->expects($this->once())
            ->method('bindParam')
            ->with($this->equalTo(1), $this->equalTo('abc'));

        $var  = 'abc';
        $stmt = new dbStatement_mysql($db);
        $stmt->set_pdoStatement($pdo);
        $stmt->bindParam(1, $var);
    }

    function test_bindParamSpecifyingType() {
        $db = db::create('mysql', self::$driverOptions);

        $pdo = $this->getMock('PDOStatement', array());
        $pdo->expects($this->once())
            ->method('bindParam')
            ->with($this->equalTo(1), $this->equalTo('abc'), $this->equalTo(PDO::PARAM_STR));

        $var  = 'abc';
        $stmt = new dbStatement_mysql($db);
        $stmt->set_pdoStatement($pdo);
        $stmt->bindParam(1, $var, PDO::PARAM_STR);
    }

    function test_bindParamSpecifyingTypeAndLength() {
        $db = db::create('mysql', self::$driverOptions);

        $pdo = $this->getMock('PDOStatement', array());
        $pdo->expects($this->once())
            ->method('bindParam')
            ->with($this->equalTo(1), $this->equalTo('abc'), $this->equalTo(PDO::PARAM_STR), $this->equalTo(3));

        $var  = 'abc';
        $stmt = new dbStatement_mysql($db);
        $stmt->set_pdoStatement($pdo);
        $stmt->bindParam(1, $var, PDO::PARAM_STR, 3);
    }


    # Tests for bindValue()
    #########################################
    function test_bindValueIsOnlyAvailableBeforeExecution() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT 1");
        $this->assertFalse($stmt->bindValue(0, NULL));
    }

    function test_bindValueCatchesCasesWhenNoPdoStatementHasBeenSet() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = new dbStatement_mysql($db);
        $this->assertFalse($stmt->bindValue(0, 'abc'));
    }

    function test_bindValueBaseImplementation() {
        $db = db::create('mysql', self::$driverOptions);

        $pdo = $this->getMock('PDOStatement', array());
        $pdo->expects($this->once())
            ->method('bindValue')
            ->with($this->equalTo(1), $this->equalTo('abc'));

        $stmt = new dbStatement_mysql($db);
        $stmt->set_pdoStatement($pdo);
        $stmt->bindValue(1, 'abc');
    }

    function test_bindValueSpecifyingType() {
        $db = db::create('mysql', self::$driverOptions);

        $pdo = $this->getMock('PDOStatement', array());
        $pdo->expects($this->once())
            ->method('bindValue')
            ->with($this->equalTo(1), $this->equalTo('abc'), $this->equalTo(PDO::PARAM_STR));

        $stmt = new dbStatement_mysql($db);
        $stmt->set_pdoStatement($pdo);
        $stmt->bindValue(1, 'abc', PDO::PARAM_STR);
    }

    function test_bindValueSpecifyingTypeAndLength() {
        $db = db::create('mysql', self::$driverOptions);

        $pdo = $this->getMock('PDOStatement', array());
        $pdo->expects($this->once())
            ->method('bindValue')
            ->with($this->equalTo(1), $this->equalTo('abc'), $this->equalTo(PDO::PARAM_STR), $this->equalTo(3));

        $stmt = new dbStatement_mysql($db);
        $stmt->set_pdoStatement($pdo);
        $stmt->bindValue(1, 'abc', PDO::PARAM_STR, 3);
    }


    # Tests for fieldCount()
    #########################################
    function test_fieldCountIsOnlyAvailableOnceTheStatementHasBeenExecuted() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT 1", FALSE);
        $this->assertEquals(0, $stmt->fieldCount());
    }

    function test_fieldCountSimple() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT 1");
        $this->assertEquals(1, $stmt->fieldCount(), "Failed for {$stmt->getSQL()}");
        $stmt = $db->query("SELECT 1,2");
        $this->assertEquals(2, $stmt->fieldCount(), "Failed for {$stmt->getSQL()}");
        $stmt = $db->query("SELECT 1,2,3");
        $this->assertEquals(3, $stmt->fieldCount(), "Failed for {$stmt->getSQL()}");
    }

    function test_fieldCountLiveQuery() {
        $db = db::create('mysql', self::$driverOptions);
        // Test SQL that will return a result
        $stmt = $db->query("SELECT * FROM dbObjectTesting");
        $this->assertEquals(6, $stmt->fieldCount(), "Failed for {$stmt->getSQL()}");
        // Test SQL that will not return a result
        $stmt = $db->query("SELECT * FROM dbObjectTesting WHERE `id`='".self::UNDEFINED_TABLE_KEY."'");
        $this->assertEquals(6, $stmt->fieldCount(), "Failed for {$stmt->getSQL()}");
    }

    # Tests for fieldNames()
    #########################################
    function test_fieldNamesIsOnlyAvailableOnceTheStatementHasBeenExecuted() {
        $db         = db::create('mysql', self::$driverOptions);
        $stmt       = $db->query("SELECT 1", FALSE);
        $fieldNames = $stmt->fieldNames();
        $this->assertTrue(is_array($fieldNames));
        $this->assertEquals(0, sizeof($fieldNames));
    }

    function test_fieldNamesSimple() {
        $db         = db::create('mysql', self::$driverOptions);
        $stmt       = $db->query("SELECT 1 AS a");
        $fieldNames = $stmt->fieldNames();
        $this->assertTrue(is_array($fieldNames));
        $this->assertEquals(1, sizeof($fieldNames));
        $this->assertContains('a', $fieldNames);
    }

    function test_fieldNamesLiveQuery() {
        $db = db::create('mysql', self::$driverOptions);

        // Test SQL that will return a result
        $stmt       = $db->query("SELECT * FROM dbObjectTesting");
        $fieldNames = $stmt->fieldNames();
        $this->assertTrue(is_array($fieldNames), 'It returns an array');
        $this->assertEquals(6, sizeof($fieldNames), 'It returns an array with 3 elements');
        $this->assertContains('id', $fieldNames, "It returns the element 'id'");
        $this->assertContains('timestamp', $fieldNames, "It returns the element 'timestamp'");
        $this->assertContains('value', $fieldNames, "It returns the element 'value'");

        // Test SQL that will not return a result
        $stmt       = $db->query("SELECT * FROM dbObjectTesting WHERE `id`='".self::UNDEFINED_TABLE_KEY."'");
        $fieldNames = $stmt->fieldNames();
        $this->assertTrue(is_array($fieldNames), 'It returns an array');
        $this->assertEquals(6, sizeof($fieldNames), 'It returns an array with 3 elements');
        $this->assertContains('id', $fieldNames, "It returns the element 'id'");
        $this->assertContains('value', $fieldNames, "It returns the element 'value'");
        $this->assertContains('timestamp', $fieldNames, "It returns the element 'timestamp'");
        $this->assertContains('value', $fieldNames, "It returns the element 'value'");
    }

    # Tests for rowCount()
    #########################################
    function test_rowCountIsOnlyAvailableOnceTheStatementHasBeenExecuted() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT 1", FALSE);
        $this->assertFalse($stmt->rowCount());
    }

    function test_rowCount() {
        $db = db::create('mysql', self::$driverOptions);

        $stmt = $db->query("SELECT * FROM `dbObjectTesting`");
        $this->assertEquals(0, $stmt->rowCount());

        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");
        $stmt = $db->query("SELECT * FROM `dbObjectTesting`");
        $this->assertEquals(3, $stmt->rowCount());
    }

    # Tests for affectedRows()
    #########################################
    function test_affectedRowsIsOnlyAvailableOnceTheStatementHasBeenExecuted() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT * FROM `dbObjectTesting`", FALSE);
        $this->assertFalse($stmt->affectedRows());
    }

    function test_affectedRows() {
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
    function test_insertIdIsOnlyAvailableOnceTheStatementHasBeenExecuted() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT * FROM `dbObjectTesting`", FALSE);
        $this->assertFalse($stmt->insertId());
    }

    function test_insertId() {
        $db = db::create('mysql', self::$driverOptions);
        $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");

        $stmt     = $db->query("INSERT INTO `dbObjectTesting` (`value`) VALUES('test')");
        $insertID = $stmt->insertId();

        $stmt = self::$pdo->query("SELECT `id` FROM dbObjectTesting WHERE `value`='test'LIMIT 1");
        $this->assertEquals($insertID, $stmt->fetchColumn(0));
    }

    # Tests for fetch()
    #########################################
    function test_fetchIsOnlyAvailableOnceTheStatementHasBeenExecuted() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT * FROM `dbObjectTesting`", FALSE);
        $this->assertFalse($stmt->fetch());
    }

    function test_fetchAlwaysReturnsNullWhenPastLastRow() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a')");

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $this->assertEquals(1, $stmt->rowCount(), 'There should be 1 row');
        $this->assertTrue(is_array($stmt->fetch()), 'fetch() returns the row');
        $this->assertNull($stmt->fetch(), 'fetch() returns NULL');
        $this->assertNull($stmt->fetch(), 'fetch() returns NULL');
        $this->assertNull($stmt->fetch(), 'fetch() returns NULL');
    }

    function test_fetchSupportsFETCH_ASSOC() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a')");

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, $stmt->rowCount(), 'There should be 1 rows');
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(6, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('timestamp', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertEquals('a', $row['value']);
    }

    function test_fetchSupportsFETCH_NUM() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a')");

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row  = $stmt->fetch(PDO::FETCH_NUM);
        $this->assertEquals(1, $stmt->rowCount(), 'There should be 1 rows');
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(6, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey(0, $row);
        $this->assertArrayHasKey(1, $row);
        $this->assertArrayHasKey(2, $row);
        $this->assertEquals('a', $row[2]);
    }

    function test_fetchSupportsFETCH_OBJ() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a')");

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row  = $stmt->fetch(PDO::FETCH_OBJ);
        $this->assertEquals(1, $stmt->rowCount(), 'There should be 1 rows');
        $this->assertTrue(is_object($row), "fetch() returns an object");
        $this->assertAttributeNotEmpty('id', $row);
        $this->assertAttributeNotEmpty('timestamp', $row);
        $this->assertAttributeNotEmpty('value', $row);
        $this->assertAttributeEquals('a', 'value', $row);
    }

    function test_fetchUsesFetchAssocByDefault() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a')");

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $row  = $stmt->fetch();
        $this->assertEquals(1, $stmt->rowCount(), 'There should be 1 rows');
        $this->assertTrue(is_array($row), "fetch() returns an array");
        $this->assertEquals(6, sizeof($row), "fetch() returns an array with 3 elements");
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('timestamp', $row);
        $this->assertArrayHasKey('value', $row);
        $this->assertEquals('a', $row['value']);
    }

    function test_fetchReturnsRowByRow() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");

        $db       = db::create('mysql', self::$driverOptions);
        $stmt     = $db->query('SELECT * FROM dbObjectTesting');
        $counter  = 0;
        $testData = array('a', 'b', 'c');
        $this->assertEquals(3, $stmt->rowCount(), 'There should be 3 rows');
        while ($row = $stmt->fetch()) {
            $counter++;
            $this->assertTrue(is_array($row));
            $this->assertEquals(6, sizeof($row));
            $this->assertArrayHasKey('value', $row);
            $this->assertEquals($testData[$counter - 1], $row['value']);
        }
        $this->assertEquals(3, $counter, 'We should have looped 3 times');
    }

    # Tests for fetchAll()
    #########################################
    function test_fetchAllIsOnlyAvailableOnceTheStatementHasBeenExecuted() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT * FROM `dbObjectTesting`", FALSE);
        $this->assertFalse($stmt->fetchAll());
    }

    function test_fetchAllSupportsFETCH_ASSOC() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");

        $db       = db::create('mysql', self::$driverOptions);
        $stmt     = $db->query('SELECT * FROM dbObjectTesting');
        $testData = array('a', 'b', 'c');
        $rows     = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertEquals(3, $stmt->rowCount(), 'There should be 3 rows');
        $this->assertTrue(is_array($rows), 'fetchAll() returns an array');
        $this->assertEquals(3, sizeof($rows), 'fetchAdd() returns 3 rows');
        for ($n = 0; $n < 3; $n++) {
            $this->assertTrue(isset($rows[$n]));
            $row = $rows[$n];
            $this->assertTrue(is_array($row), "Each row is an array");
            $this->assertEquals(6, sizeof($row), "Each row has 3 elements");
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('value', $row);
            $this->assertEquals($testData[$n], $row['value']);

        }
    }

    function test_fetchAllSupportsFETCH_NUM() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");

        $db       = db::create('mysql', self::$driverOptions);
        $stmt     = $db->query('SELECT * FROM dbObjectTesting');
        $testData = array('a', 'b', 'c');
        $rows     = $stmt->fetchAll(PDO::FETCH_NUM);
        $this->assertEquals(3, $stmt->rowCount(), 'There should be 3 rows');
        $this->assertTrue(is_array($rows), 'fetchAll() returns an array');
        $this->assertEquals(3, sizeof($rows), 'fetchAdd() returns 3 rows');
        for ($n = 0; $n < 3; $n++) {
            $this->assertTrue(isset($rows[$n]));
            $row = $rows[$n];
            $this->assertTrue(is_array($row), "Each row is an array");
            $this->assertEquals(6, sizeof($row), "Each row has 3 elements");
            $this->assertArrayHasKey(0, $row);
            $this->assertArrayHasKey(1, $row);
            $this->assertArrayHasKey(2, $row);
            $this->assertEquals($testData[$n], $row[2]);
        }
    }

    function test_fetchAllSupportsFETCH_OBJ() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");

        $db       = db::create('mysql', self::$driverOptions);
        $stmt     = $db->query('SELECT * FROM dbObjectTesting');
        $testData = array('a', 'b', 'c');
        $rows     = $stmt->fetchAll(PDO::FETCH_OBJ);
        $this->assertEquals(3, $stmt->rowCount(), 'There should be 3 rows');
        $this->assertTrue(is_array($rows), 'fetchAll() returns an array');
        $this->assertEquals(3, sizeof($rows), 'fetchAdd() returns 3 rows');
        for ($n = 0; $n < 3; $n++) {
            $this->assertTrue(isset($rows[$n]));
            $row = $rows[$n];
            $this->assertTrue(is_object($row), "Each row is an object");
            $this->assertAttributeNotEmpty('id', $row);
            $this->assertAttributeNotEmpty('timestamp', $row);
            $this->assertAttributeNotEmpty('value', $row);
            $this->assertAttributeEquals($testData[$n], 'value', $row);
        }
    }

    function test_fetchAllUsesFetchAssocByDefault() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");

        $db       = db::create('mysql', self::$driverOptions);
        $stmt     = $db->query('SELECT * FROM dbObjectTesting');
        $testData = array('a', 'b', 'c');
        $rows     = $stmt->fetchAll();
        $this->assertEquals(3, $stmt->rowCount(), 'There should be 3 rows');
        $this->assertTrue(is_array($rows), 'fetchAll() returns an array');
        $this->assertEquals(3, sizeof($rows), 'fetchAdd() returns 3 rows');
        for ($n = 0; $n < 3; $n++) {
            $this->assertTrue(isset($rows[$n]));
            $row = $rows[$n];
            $this->assertTrue(is_array($row), "Each row is an array");
            $this->assertEquals(6, sizeof($row), "Each row has 3 elements");
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('timestamp', $row);
            $this->assertArrayHasKey('value', $row);
            $this->assertEquals($testData[$n], $row['value']);

        }
    }

    # Tests for fetchField()
    #########################################
    function test_fetchFieldIsOnlyAvailableOnceTheStatementHasBeenExecuted() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT * FROM `dbObjectTesting`", FALSE);
        $this->assertFalse($stmt->fetchField());
    }

    function test_fetchFieldAlwaysReturnsNullWhenPastLastRow() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a')");

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $this->assertEquals(1, $stmt->rowCount(), 'There should be 1 row');
        $this->assertTrue(is_string($stmt->fetchField()), 'fetchField() returns the field');
        $this->assertNull($stmt->fetchField(), 'fetchField() returns NULL');
        $this->assertNull($stmt->fetchField(), 'fetchField() returns NULL');
        $this->assertNull($stmt->fetchField(), 'fetchField() returns NULL');
    }

    function test_fetchFieldReturnsFalseWhenGivenAnInvalidFieldOffset() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a')");

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $this->assertFalse($stmt->fetchField(-1), 'fetchField() returns NULL');
        $this->assertFalse($stmt->fetchField(20), 'fetchField() returns NULL');
    }

    function test_fetchFieldReturnsFalseWhenGivenAnInvalidFieldName() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a')");

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query('SELECT * FROM dbObjectTesting');
        $this->assertFalse($stmt->fetchField('foo'), 'fetchField() returns NULL');
        $this->assertFalse($stmt->fetchField('bar'), 'fetchField() returns NULL');
    }

    function test_fetchFieldReturnsTheFirstFieldByDefault() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a')");

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query('SELECT value,id,timestamp FROM dbObjectTesting');
        $this->assertEquals('a', $stmt->fetchField());
    }

    function test_fetchFieldAcceptsNumericKeys() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a')");

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query('SELECT id,value,timestamp FROM dbObjectTesting');
        $this->assertEquals('a', $stmt->fetchField(1));
    }

    function test_fetchFieldAcceptsStringKeys() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a')");

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query('SELECT id,value,timestamp FROM dbObjectTesting');
        $this->assertEquals('a', $stmt->fetchField('value'));
    }

    function test_fetchFieldReturnsRowByRow() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");

        $testData = array('a', 'b', 'c');
        $db       = db::create('mysql', self::$driverOptions);
        $stmt     = $db->query('SELECT value,id,timestamp FROM dbObjectTesting');
        $counter  = 0;
        while ($field = $stmt->fetchField()) {
            $counter++;
            $this->assertEquals($testData[$counter - 1], $field);
        }
        $this->assertEquals(3, $counter, 'We should have looped 3 times');
    }

    # Tests for fetchFieldAll()
    #########################################
    function test_fetchFieldAllFieldIsOnlyAvailableOnceTheStatementHasBeenExecuted() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("SELECT * FROM `dbObjectTesting`", FALSE);
        $this->assertFalse($stmt->fetchFieldAll());
    }

    function test_fetchFieldAllReturnsTheFirstFieldByDefault() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");

        $db       = db::create('mysql', self::$driverOptions);
        $stmt     = $db->query('SELECT value,id,timestamp FROM dbObjectTesting');
        $counter  = 0;
        $testData = array('a', 'b', 'c');
        $rows     = $stmt->fetchFieldAll();
        $this->assertTrue(is_array($rows), 'fetchFieldAll() returns an array');
        $this->assertEquals(3, sizeof($rows), 'fetchFieldAll() returns 3 rows');
        foreach ($rows as $row) {
            $counter++;
            $this->assertEquals($testData[$counter - 1], $row);
        }
        $this->assertEquals(3, $counter, 'We should have looped 3 times');
    }

    function test_fetchFieldAllAcceptsNumericKeys() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");

        $db       = db::create('mysql', self::$driverOptions);
        $stmt     = $db->query('SELECT id,value,timestamp FROM dbObjectTesting');
        $counter  = 0;
        $testData = array('a', 'b', 'c');
        $rows     = $stmt->fetchFieldAll(1);
        $this->assertTrue(is_array($rows), 'fetchFieldAll() returns an array');
        $this->assertEquals(3, sizeof($rows), 'fetchFieldAll() returns 3 rows');
        foreach ($rows as $row) {
            $counter++;
            $this->assertEquals($testData[$counter - 1], $row);
        }
        $this->assertEquals(3, $counter, 'We should have looped 3 times');
    }

    function test_fetchFieldAllAcceptsStringKeys() {
        self::$pdo->exec("INSERT INTO `dbObjectTesting` (`value`) VALUE('a'),('b'),('c')");

        $db       = db::create('mysql', self::$driverOptions);
        $stmt     = $db->query('SELECT id,value,timestamp FROM dbObjectTesting');
        $counter  = 0;
        $testData = array('a', 'b', 'c');
        $rows     = $stmt->fetchFieldAll('value');
        $this->assertTrue(is_array($rows), 'fetchFieldAll() returns an array');
        $this->assertEquals(3, sizeof($rows), 'fetchFieldAll() returns 3 rows');
        foreach ($rows as $row) {
            $counter++;
            $this->assertEquals($testData[$counter - 1], $row);
        }
        $this->assertEquals(3, $counter, 'We should have looped 3 times');
    }

    # Tests for sqlState()
    #########################################
    function test_sqlState() {
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
    function test_errorCode() {
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
    function test_errorMsg() {
        $db = db::create('mysql', self::$driverOptions);

        // No error
        $stmt = $db->query('SELECT 1');
        $this->assertNull($stmt->errorMsg());

        // Syntax error
        $stmt = $db->query('SELECT * FROM');
        $this->assertRegExp('/You have an error in your SQL syntax.*/', $stmt->errorMsg());

        // TODO: Check more errors?
    }

    # Tests for encoding and decoding objects and arrays
    #######################################################
    function test_itStoresAndReturnsArraysByDefault() {
        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("INSERT INTO `dbObjectTesting` (value) VALUES(?)", FALSE);
        $this->assertTrue($stmt->execute(array('a', 'b' => 'c')));

        $testRow = $db->query("SELECT * FROM `dbObjectTesting` WHERE id=".$stmt->insertId()." LIMIT 1")->fetch();
        $this->assertInternalType('array', $testRow['value']);
        $this->assertEquals(array('a', 'b' => 'c'), $testRow['value']);
    }

    function test_itStoresAndReturnsObjectsByDefault() {
        $obj    = new stdClass;
        $obj->a = 123;
        $obj->b = array(1, 2, 3);

        $db   = db::create('mysql', self::$driverOptions);
        $stmt = $db->query("INSERT INTO `dbObjectTesting` (value) VALUES(?)", FALSE);
        $this->assertTrue($stmt->execute($obj));

        $testRow = $db->query("SELECT * FROM `dbObjectTesting` WHERE id=".$stmt->insertId()." LIMIT 1")->fetch();
        $this->assertInternalType('object', $testRow['value']);
        $this->assertAttributeEquals(123, 'a', $testRow['value']);
        $this->assertAttributeEquals(array(1, 2, 3), 'b', $testRow['value']);
    }

    function test_itDoesNotAttemptToDecodeManuallyEncodedArrays() {
        $db    = db::create('mysql', self::$driverOptions);
        $stmt  = $db->query("INSERT INTO `dbObjectTesting` (value) VALUES(?)", FALSE);
        $array = serialize(array());
        $this->assertTrue($stmt->execute($array));

        $testRow = $db->query("SELECT * FROM `dbObjectTesting` WHERE id=".$stmt->insertId()." LIMIT 1")->fetch();
        $this->assertInternalType('string', $testRow['value']);
        $this->assertEquals($array, $testRow['value']);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    function test_encodingCanBeTurnedOffAndReturnsFalseWhenPassedAnArray() {
        $db = db::create('mysql', self::$driverOptions);

        // Turn encoding off!
        $db->autoEncode = FALSE;

        // Try and pass an array
        $stmt = $db->query("INSERT INTO `dbObjectTesting` (value) VALUES(?)", FALSE);
        $this->assertFalse($stmt->execute(array()));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    function test_encodingCanBeTurnedOffAndReturnsFalseWhenPassedAnObject() {
        $db = db::create('mysql', self::$driverOptions);

        // Turn encoding off!
        $db->autoEncode = FALSE;

        // Try and pass an array
        $stmt = $db->query("INSERT INTO `dbObjectTesting` (value) VALUES(?)", FALSE);
        $this->assertFalse($stmt->execute(new stdClass));
    }
}
 