<?php

class privatevars extends config {

	public static function set($name,$value,$null=FALSE) {
		return parent::set("private",$name,$value,$null);
	}

	public static function isset($name) {
		return parent::isset("private",$name);
	}

	public static function get($name,$default="") {
		return parent::get("private",$name,$default);
	}

	public static function remove($var) {
		
		return parent::remove("private",$var);
		
	}

	public static function variable($var,$value=NULL,$null=FALSE) {
		
		return parent::variable("private",$var,$value,$null);
		
	}

	public static function export() {
		return parent::export("private");
	}

}

?>