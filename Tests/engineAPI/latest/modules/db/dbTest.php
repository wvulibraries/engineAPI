<?php
require_once 'pdoMocks.php';

class dbTest extends PHPUnit_Framework_TestCase {
    function testItReturnsItselfViaGetInstance() {
        $db = db::getInstance();
        $this->assertInstanceOf('db', $db);
    }

    # Tests for exists()
    ###########################################################################################
    function testItAllowsYouToTestThatAConnectionExists(){
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();

        $this->assertFalse(db::exists($dbAlias));
        $driver = $db->create('mysql', $mockPDO, $dbAlias);
        $this->assertTrue(db::exists($dbAlias));
    }

    # Tests for get()
    ###########################################################################################
    function testProvidesGetMethodToEasilyGetNammedConnections() {
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();

        $this->assertNull(db::get($dbAlias));
        $driver = $db->create('mysql', $mockPDO, $dbAlias);
        $this->assertNotNull(db::get($dbAlias));
        $this->assertEquals($driver, db::get($dbAlias));
    }

    # Tests for __get()
    ###########################################################################################
    function testItUsesMagicGetToAllowEasyAccessToCreatedConnections() {
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();

        $this->assertNull($db->$dbAlias);
        $driver = $db->create('mysql', $mockPDO, $dbAlias);
        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
    }

    # Tests for uegisterAs()
    ###########################################################################################
    function testRegisterAs() {
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();
        $driver  = $db->create('mysql', $mockPDO);

        $this->assertNull($db->$dbAlias);
        $db->registerAs($driver, $dbAlias);
        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
    }

    function testRegisterAsWithNameConflict() {
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();
        $driver  = $db->create('mysql', $mockPDO, $dbAlias);

        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
        $this->assertFalse($db->registerAs($driver, $dbAlias));
        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
    }

    # Tests for unregisterAlias()
    ###########################################################################################
    function testUnregisterAlias() {
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();

        $this->assertNull($db->$dbAlias);
        $driver = $db->create('mysql', $mockPDO, $dbAlias);
        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
        $db->unregisterAlias($dbAlias);
        $this->assertNull($db->$dbAlias);
    }

    function testUnregisterAliasUndefinedAlias() {
        $dbAlias = md5(__METHOD__);
        $this->assertFalse(db::unregisterAlias($dbAlias));
    }

    # Tests for unregisterObject()
    ###########################################################################################
    function testUnregisterObject() {
        $dbAlias = md5(__METHOD__);
        $mockPDO = $this->getMock('mockPDO');
        $db      = db::getInstance();

        $this->assertNull($db->$dbAlias);
        $driver = $db->create('mysql', $mockPDO, $dbAlias);
        $this->assertNotNull($db->$dbAlias);
        $this->assertEquals($driver, $db->$dbAlias);
        $db->unregisterObject($driver);
        $this->assertNull($db->$dbAlias);
    }

    function testUnregisterObjectUsingUnregisteredObject() {
        $dbAlias = md5(__METHOD__);
        $db      = db::getInstance();
        $mockPDO = $this->getMock('mockPDO');

        db::reset();
        $this->assertEquals(0, sizeof($db));
        $this->assertNull($db->$dbAlias);
        $driver = $db->create('mysql', $mockPDO);
        $this->assertNull($db->$dbAlias);
        $this->assertFalse($db->unregisterObject($driver));
        $this->assertNull($db->$dbAlias);
    }

    # Tests for listDrivers()
    ###########################################################################################
    function testListDrivers() {
        $ds            = DIRECTORY_SEPARATOR;
        db::$driverDir = __DIR__.$ds.'testData'.$ds.'drivers';
        $drivers       = db::listDrivers();
        $this->assertTrue(is_array($drivers));
        $this->assertEquals(1, sizeof($drivers));
        $this->assertContains('mysql', $drivers);
    }

}