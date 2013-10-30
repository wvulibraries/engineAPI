<?php

class enginevars extends config {

	public static function set($name,$value,$null=FALSE) {
		return parent::set("engine",$name,$value,$null);
	}

	public static function get($name,$default="") {
		return parent::get("engine",$name,$default);
	}

	public static function remove($var) {
		
		return parent::remove("engine",$var);
		
	}

	public static function variable($var,$value=NULL,$null=FALSE) {
		
		return parent::variable("engine",$var,$value,$null);
		
	}

	public static function export() {
		return parent::export("engine");
	}

}

?>