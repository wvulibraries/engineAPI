<?php
class httpTest extends PHPUnit_Framework_TestCase {
    function test_cleanGet_noGET(){
        $this->markTestIncomplete('Waiting for solution to legacy dbSanitize() function');
    }
    function test_cleanGet_withGET(){
        $this->markTestIncomplete('Waiting for solution to legacy dbSanitize() function');
    }
    function test_cleanPost_noGET(){
        $this->markTestIncomplete('Waiting for solution to legacy dbSanitize() function');
    }
    function test_cleanPost_withGET(){
        $this->markTestIncomplete('Waiting for solution to legacy dbSanitize() function');
    }
    function test_setGet(){
        $this->markTestIncomplete('Waiting for solution to legacy openDB object');
    }
    function test_setPost(){
        $this->markTestIncomplete('Waiting for solution to legacy openDB object');
    }

    /**
     * @requires function xdebug_get_headers
     */
    function test_redirect_defaultStatusCodeIs307(){
        header_remove();
        http::redirect('example.com', NULL, FALSE);
        $headers = xdebug_get_headers();
        $this->assertNotEmpty($headers);
        $this->assertContains('Status: 307 Temporary Redirect', $headers);
    }
    /**
     * @requires function xdebug_get_headers
     */
    function test_redirect_UrlsGetSetCorrectlyAsHeaderLocation(){
        $urls = array('example.com','google.com','yahoo.com');
        foreach($urls as $url){
            header_remove();
            http::redirect($url, NULL, FALSE);
            $headers = xdebug_get_headers();
            header_remove();
            $this->assertNotEmpty($headers);
            $this->assertContains("Location: $url", $headers);
        }
    }
    /**
     * @requires function xdebug_get_headers
     */
    function test_redirect_validHttpCodes(){
        $codes = array(
            301 => 'Status: 301 Moved Permanently',
            307 => 'Status: 307 Temporary Redirect');
        foreach($codes as $code => $headerText){
            header_remove();
            http::redirect('example.com', $code, FALSE);
            $headers = xdebug_get_headers();
            header_remove();
            $this->assertNotEmpty($headers);
            $this->assertContains($headerText, $headers);
        }
    }
    /**
     * @expectedException PHPUnit_Framework_Error_Notice
     * @requires function xdebug_get_headers
     */
    function test_redirect_invalidHttpCodes(){
        $codes = array(
            100, 101,
            200, 201, 202, 203, 204, 205, 206,
            300, 301, 302, 303, 304, 305, 306, 307,
            400, 401, 402, 403, 404, 405, 406, 407, 408, 409,410,411,412,413,414,415,416,417,
            500,501,502,503,504,505);
        foreach($codes as $code){
            header_remove();
            http::redirect('example.com', $code, FALSE);
            $headers = xdebug_get_headers();
            header_remove();
            $this->assertNotEmpty($headers);
            $this->assertContains('Status: 307 Temporary Redirect', $headers);
        }
    }


    function test_sendStatus_replaceHeader(){
        $this->markTestIncomplete("Untestable due to PHP's header_list() function on CLI");
    }
    function test_sendStatus_noReplaceHeader(){
        $this->markTestIncomplete("Untestable due to PHP's header_list() function on CLI");
    }
    /**
     * @requires function xdebug_get_headers
     */
    function test_sendStatus_validCodes(){
        $codes = array(
            101 => 'Status: 101 Switching Protocols',
            301 => 'Status: 301 Moved Permanently',
            307 => 'Status: 307 Temporary Redirect',
            400 => 'Status: 400 Bad Request',
            401 => 'Status: 401 Authorization Required',
            403 => 'Status: 403 Forbidden',
            404 => 'Status: 404 Not Found',
            407 => 'Status: 407 Proxy Authentication Required',
            426 => 'Status: 426 Upgrade Required',
            500 => 'Status: 500 Internal Server Error',
            503 => 'Status: 503 Service Temporarily Unavailable',
        );
        foreach($codes as $code => $headerText){
            header_remove();
            http::sendStatus($code);
            $headers = xdebug_get_headers();
            header_remove();
            $this->assertNotEmpty($headers);
            $this->assertContains($headerText, $headers);
        }
    }
    /**
     * @expectedException PHPUnit_Framework_Error_Notice
     * @requires function xdebug_get_headers
     */
    function test_sendStatus_invalidCodes(){
        header_remove();
        http::sendStatus(999);
        $headers = xdebug_get_headers();
        header_remove();
        $this->assertEmpty($headers);
    }

    function test_compressData_and_decompressData(){
        $data = serialize($this);
        $this->assertEquals($data, http::decompressData(http::compressData($data)));
    }


    function test_removeRequest() {
        $_REQUEST = "foo";
        http::removeRequest();

        if (isset($_REQUEST)) {
            $this->assertTrue(FALSE);
        }
        else {
            $this->assertTrue(TRUE);
        }
    }
}
 