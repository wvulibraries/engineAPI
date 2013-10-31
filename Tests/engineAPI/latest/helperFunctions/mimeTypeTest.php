<?php
class mimeTypeTest extends PHPUnit_Framework_TestCase {
    private $mimeTypes = array(
        'tar'  => 'application/x-tar',
        'gz'   => 'application/x-gzip',
        'tgz'  => 'application/x-gzip',
        'zip'  => 'application/zip',
        'aiff' => 'audio/x-aiff',
        'flac' => 'audio/x-flac',
        'm4a'  => 'audio/mp4',
        'mp3'  => 'audio/mpeg',
        'ogg'  => 'application/ogg',
        'wav'  => 'audio/x-wav',
        'doc'  => 'application/msword',
        'odt'  => 'application/vnd.oasis.opendocument.text',
        'pdf'  => 'application/pdf',
        'txt'  => 'text/plain',
        'bmp'  => 'application/octet-stream',
        'gif'  => 'image/gif',
        'ico'  => 'image/x-icon',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
        'tiff' => 'image/tiff'
    );


    function test_get_file_mime_type(){
        foreach(glob(__DIR__.'/testData/mimeTypes/*') as $filename){
            $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
            $this->assertEquals($this->mimeTypes[$fileExt], get_file_mime_type($filename));
        }
    }
}
 