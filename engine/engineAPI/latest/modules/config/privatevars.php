<?php

class privatevars extends config {

	const CONFIG_TYPE     = "private";

	public static function set($name,$value,$null=FALSE) {
		return parent::set(self::CONFIG_TYPE,$name,$value,$null);
	}

	public static function is_set($name) {
		return parent::is_set(self::CONFIG_TYPE,$name);
	}

	public static function get($name,$default="") {
		return parent::get(self::CONFIG_TYPE,$name,$default);
	}

	public static function remove($var) {
		
		return parent::remove(self::CONFIG_TYPE,$var);
		
	}

	public static function variable($var,$value=NULL,$null=FALSE) {
		
		return parent::variable(self::CONFIG_TYPE,$var,$value,$null);
		
	}

	public static function export() {
		return parent::export(self::CONFIG_TYPE);
	}

}

?>