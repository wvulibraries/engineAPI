<?php
/**
 * EngineAPI image module
 * @package EngineAPI\modules\image
 */
class image{
    const GIF  = IMAGETYPE_GIF;
    const JPEG = IMAGETYPE_JPEG;
    const PNG  = IMAGETYPE_PNG;
    const WBMP = IMAGETYPE_WBMP;
	/**
	 * The image data
	 * @var string
	 */
	private $img;
	/**
	 * The image's filename
	 * @var string
	 */
	private $imgFilename;
	/**
	 * The image's metadata
	 * @var
	 */
	private $imgInfo;
	/**
	 * @todo This doesn't look used
	 * @var
	 */
	private $imgGdType;

	/**
	 * Class constructor
	 *
	 * @param string $imgSrc
	 * @param string $imgFilename
	 */
	public function __construct($imgSrc, $imgFilename=NULL){
        // If a manual filename was given, copy the src (which will be a file) to the manual filename
        if(isset($imgFilename) and is_readable($imgSrc)){
            copy($imgSrc,$imgFilename);
            $imgSrc = $imgFilename;
        }

        // Load the image
        if(is_string($imgSrc)){
            $this->load($imgSrc);
        }elseif(gettype($imgSrc) == "resource" and get_resource_type($imgSrc) == "gd"){
            $this->img = $imgSrc;
            $this->refreshImageInfo();
        }

        // If a manual filename was given, delete the tmp working file
        if(isset($imgFilename) and is_readable($imgFilename)){
            unlink($imgFilename);
        }
    }

    /**
     * Load an image into the class
	 *
     * @param $filename
     */
    private function load($filename){
        if(is_readable($filename)){
            $tmpFile = false;
            $loadFilename = $filename;
            $this->imgFilename = $filename;
        }else{
            $tmpFile = true;
            $loadFilename = tempnam(sys_get_temp_dir(), uniqid());
            file_put_contents($loadFilename, $filename);
        }

        $this->refreshImageInfo($loadFilename);
        switch($this->getImageInfo('gdType')){
            case IMAGETYPE_GIF:
                $this->img = imagecreatefromgif($loadFilename);
                break;
            case IMAGETYPE_JPEG:
                $this->img = imagecreatefromjpeg($loadFilename);
                break;
            case IMAGETYPE_PNG:
                $this->img = imagecreatefrompng($loadFilename);
                break;
            case IMAGETYPE_WBMP:
                $this->img = imagecreatefromwbmp($loadFilename);
                break;
            default:
                errorHandle::newError(__METHOD__."() - Unknown file type ({$this->getImageInfo('gdType')})", errorHandle::DEBUG);
                break;
        }

        if($tmpFile) unlink($loadFilename);
    }

    /**
     * @param null $imgFilename
	 *
     * @return mixed
     */
    private function refreshImageInfo($imgFilename=NULL){
        if(isset($imgFilename)){
            $imgInfo = getimagesize($imgFilename);
        }else{
            $tmpFilename = tempnam(sys_get_temp_dir(), uniqid());
            $this->output(NULL, $tmpFilename);
            $imgInfo = getimagesize($tmpFilename);
            unlink($tmpFilename);
        }

        if(!$imgInfo){
            errorHandle::newError(__METHOD__."() - Failed to get image info.", errorHandle::DEBUG);
        }else{
            $this->imgInfo = array(
                'width'    => isset($imgInfo[0])          ? $imgInfo[0]          : NULL,
                'height'   => isset($imgInfo[1])          ? $imgInfo[1]          : NULL,
                'gdType'   => isset($imgInfo[2])          ? $imgInfo[2]          : NULL,
                'htmlAttr' => isset($imgInfo[3])          ? $imgInfo[3]          : NULL,
                'bits'     => isset($imgInfo['bits'])     ? $imgInfo['bits']     : NULL,
                'channels' => isset($imgInfo['channels']) ? $imgInfo['channels'] : NULL,
                'mimeType' => isset($imgInfo['mime'])     ? $imgInfo['mime']     : NULL,
            );
        }
        return $imgInfo;
    }

    /**
     * Returns image info
	 *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getImageInfo($name, $default=NULL){
        $name    = strtolower($name);
        $imgInfo = array_change_key_case((array)$this->imgInfo, CASE_LOWER);
        return isset($imgInfo[$name]) ? $imgInfo[$name] : $default;
    }

    /**
     * Returns the original img filename
     * Note: this will only exist if this object was instatiated from a filename
	 *
     * @return string
     */
    public function getFilename(){
        return isset($this->imgFilename) ? $this->imgFilename : '';
    }

    /**
     * Returns the bar (binary) image data
	 *
     * @return string
     */
    public function rawImage(){
        $tmpFilename = tempnam(sys_get_temp_dir(), uniqid());
        $this->output(NULL, $tmpFilename);
        $rawData = file_get_contents($tmpFilename);
        unlink($tmpFilename);
        return $rawData;
    }

    /**
     * Output the image to either the browser, or a local file
	 *
     * @param int $imgType
     * @param string $filename
     *        If set, output the image to the filename. Otherwise, output the image to the browser
     */
    public function output($imgType=NULL, $filename=NULL){
        if(is_string($imgType) and defined(__CLASS__."::".strtoupper(trim($imgType)))){
            $imgType = constant(__CLASS__."::".strtoupper(trim($imgType)));
        }else{
            $imgType = is_int($imgType) ? $imgType : self::PNG;
        }

        switch($imgType){
            case IMAGETYPE_GIF:
                imagegif($this->img, $filename);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($this->img, $filename);
                break;
            case IMAGETYPE_PNG:
                imagepng($this->img, $filename);
                break;
            case IMAGETYPE_WBMP:
                imagewbmp($this->img, $filename);
                break;
            default:
                imagegd($this->getImageInfo('gdType'), $filename);
                break;
        }
    }

	/**
	 * Get the width
	 *
	 * @return int
	 */
	public function getWidth(){
        return imagesx($this->img);
    }

	/**
	 * Get the height
	 *
	 * @return int
	 */
	public function getHeight(){
        return imagesy($this->img);
    }

	/**
	 * Resize the image to the specified height (maintains ratio)
	 *
	 * @param int $height
	 * @param bool $returnObject
	 * @return bool|image
	 */
	public function resizeToHeight($height, $returnObject=FALSE){
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        return $this->resize($width,$height, $returnObject);
    }

	/**
	 * Resize the image to the specified width (maintains ratio)
	 *
	 * @param int $width
	 * @param bool $returnObject
	 * @return bool|image
	 */
	public function resizeToWidth($width, $returnObject=FALSE){
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        return $this->resize($width, $height, $returnObject);
    }

	/**
	 * Scale the image
	 *
	 * @param int $scale
	 * @param bool $returnObject
	 * @return bool|image
	 */
	public function scale($scale, $returnObject=FALSE){
        $width = $this->getWidth() * $scale;
        $height = $this->getheight() * $scale;
        return $this->resize($width, $height, $returnObject);
    }

	/**
	 * Resize the image to the given width and height
	 *
	 * @param int $width
	 * @param int $height
	 * @param bool $returnObject
	 * @return bool|image
	 */
	public function resize($width, $height, $returnObject=FALSE){
        $new_image = imagecreatetruecolor($width, $height);
        imagecopyresampled($new_image, $this->img, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        if($returnObject){
            return new self($new_image);
        }else{
            $this->img = $new_image;
            $this->refreshImageInfo();
            return true;
        }
    }
}