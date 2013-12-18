<?php
require_once 'pdoMocks.php';

class dbDriverTest extends PHPUnit_Framework_TestCase {
    /**
     * @var dbDriver
     */
    private $driver;

    function setUp() {
        $mockPDO      = $this->getMock('mockPDO');
        $this->driver = db::create('mysql', $mockPDO);
    }


    # Tests for extractParam()
    ##############################################################################
    function testExtractParamModifiesPassedArrayByReference() {
        $array = array(
            'a' => 1,
            'b' => 2,
            'c' => 3);

        $this->assertEquals(2, $this->driver->extractParam('b', $array));
        $this->assertEquals(2, sizeof($array));
        $this->assertArrayHasKey('a', $array);
        $this->assertArrayNotHasKey('b', $array);
        $this->assertArrayHasKey('c', $array);
    }

    function testExtractParamsDefaultValueIsNull() {
        $array = array();
        $this->assertNull($this->driver->extractParam('a', $array));
    }

    function testExtractParamsReturnsPassedDefault() {
        $array = array();
        $this->assertTrue($this->driver->extractParam('a', $array, TRUE));
        $this->assertFalse($this->driver->extractParam('a', $array, FALSE));
        $this->assertEquals('fooBar', $this->driver->extractParam('a', $array, 'fooBar'));
    }

    # Tests for buildDSN()
    ##############################################################################
    function testBuildDSN() {
        $this->assertEquals('foo:', $this->driver->buildDSN('foo', array()));
        $this->assertEquals('foo:a=1', $this->driver->buildDSN('foo', array('a' => 1)));
        $this->assertEquals('bar:a=1;b=abc;c=1.25', $this->driver->buildDSN('bar', array('a' => 1, 'b' => 'abc', 'c' => 1.25)));
    }

    # Tests for getPDO()
    ##############################################################################
    function testGetPdoReturnsPdoObject() {
        $this->assertInstanceOf('pdo', $this->driver->getPDO());
    }

    # Tests for chkReadOnlySQL()
    ##############################################################################
    function testChkReadOnlySQL_alterDatabase() {
        $this->assertFalse($this->driver->chkReadOnlySQL('ALTER DATABASE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('ALTER SCHEMA'));
    }

    function testChkReadOnlySQL_alterFunction() {
        $this->assertFalse($this->driver->chkReadOnlySQL('ALTER FUNCTION'));
    }

    function testChkReadOnlySQL_alterProcedure() {
        $this->assertFalse($this->driver->chkReadOnlySQL('ALTER PROCEDURE'));
    }

    function testChkReadOnlySQL_alterTable() {
        $this->assertFalse($this->driver->chkReadOnlySQL('ALTER TABLE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('ALTER IGNORE TABLE'));
    }

    function testChkReadOnlySQL_alterView() {
        $this->assertFalse($this->driver->chkReadOnlySQL('ALTER VIEW'));
        // TODO: Add more variants to ALTER VIEW?
    }

    function testChkReadOnlySQL_createDatabase() {
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE DATABASE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE SCHEMA'));
    }

    function testChkReadOnlySQL_createFunction() {
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE FUNCTION'));
        // TODO: Add more variants to CREATE FUNCTION?
    }

    function testChkReadOnlySQL_createIndex() {
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE INDEX'));
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE UNIQUE INDEX'));
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE FULLTEXT INDEX'));
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE SPATIAL INDEX'));
    }

    function testChkReadOnlySQL_createProcedure() {
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE PROCEDURE'));
        // TODO: Add more variants to CREATE PROCEDURE?
    }

    function testChkReadOnlySQL_createTable() {
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE TABLE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE TABLE IF NOT EXISTS'));
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE TEMPORARY TABLE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE TEMPORARY TABLE IF NOT EXISTS'));
    }

    function testChkReadOnlySQL_createTrigger() {
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE TRIGGER'));
    }

    function testChkReadOnlySQL_createView() {
        $this->assertFalse($this->driver->chkReadOnlySQL('CREATE VIEW'));
        // TODO: Add more variants to CREATE VIEW?
    }

    function testChkReadOnlySQL_dropDatabase() {
        $this->assertFalse($this->driver->chkReadOnlySQL('DROP DATABASE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('DROP SCHEMA'));
    }

    function testChkReadOnlySQL_dropFunction() {
        $this->assertFalse($this->driver->chkReadOnlySQL('DROP FUNCTION'));
        // TODO: Add more variants to DROP FUNCTION?
    }

    function testChkReadOnlySQL_dropIndex() {
        $this->assertFalse($this->driver->chkReadOnlySQL('DROP INDEX'));
    }

    function testChkReadOnlySQL_dropProcedure() {
        $this->assertFalse($this->driver->chkReadOnlySQL('DROP PROCEDURE'));
        // TODO: Add more variants to DROP PROCEDURE?
    }

    function testChkReadOnlySQL_dropTable() {
        $this->assertFalse($this->driver->chkReadOnlySQL('DROP TABLE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('DROP TEMPORARY TABLE'));
    }

    function testChkReadOnlySQL_dropTrigger() {
        $this->assertFalse($this->driver->chkReadOnlySQL('DROP TRIGGER'));
    }

    function testChkReadOnlySQL_dropView() {
        $this->assertFalse($this->driver->chkReadOnlySQL('DROP VIEW'));
    }

    function testChkReadOnlySQL_renameTable() {
        $this->assertFalse($this->driver->chkReadOnlySQL('RENAME TABLE'));

    }

    function testChkReadOnlySQL_truncateTable() {
        $this->assertFalse($this->driver->chkReadOnlySQL('TRUNCATE TABLE'));
    }

    function testChkReadOnlySQL_delete() {
        $this->assertFalse($this->driver->chkReadOnlySQL('DELETE FROM table'));
        $this->assertFalse($this->driver->chkReadOnlySQL('DELETE * FROM table'));
    }

    function testChkReadOnlySQL_update() {
        $this->assertFalse($this->driver->chkReadOnlySQL('UPDATE table'));
        $this->assertFalse($this->driver->chkReadOnlySQL('UPDATE IGNORE table'));
        $this->assertFalse($this->driver->chkReadOnlySQL('UPDATE LOW_PRIORITY table'));
    }

    function testChkReadOnlySQL_insert() {
        $this->assertFalse($this->driver->chkReadOnlySQL('INSERT INTO table'));
    }

    function testChkReadOnlySQL_replace() {
        $this->assertFalse($this->driver->chkReadOnlySQL('REPLACE table'));
        $this->assertFalse($this->driver->chkReadOnlySQL('REPLACE INTO table'));
        $this->assertFalse($this->driver->chkReadOnlySQL('REPLACE DELAYED table'));
        $this->assertFalse($this->driver->chkReadOnlySQL('REPLACE DELAYED INTO table'));
        $this->assertFalse($this->driver->chkReadOnlySQL('REPLACE LOW_PRIORITY table'));
        $this->assertFalse($this->driver->chkReadOnlySQL('REPLACE LOW_PRIORITY INTO table'));
    }

    function testChkReadOnlySQL_loadDataInfile() {
        $this->assertFalse($this->driver->chkReadOnlySQL('LOAD DATA INFILE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('LOAD DATA LOCAL INFILE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('LOAD DATA CONCURRENT INFILE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('LOAD DATA CONCURRENT LOCAL INFILE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('LOAD DATA LOW_PRIORITY INFILE'));
        $this->assertFalse($this->driver->chkReadOnlySQL('LOAD DATA LOW_PRIORITY LOCAL INFILE'));
    }


    function tearDown() {
        $this->driver = NULL;
    }
}
 