<?php

class email {

	public static function validate($email,$internal=FALSE) {
		return(validate::emailAddr($email,$internal));
	}

	public static function internalEmailAddr($email) {
		return(validate::internalEmailAddr($email));
	}

}

?>