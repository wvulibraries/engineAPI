<?php

class email {

	public static function validate($email,$internal=FALSE) {

		if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
			if($internal) {
				if (self::internalEmailAddr($email) === TRUE) {
					return(TRUE);
				}
				return(FALSE);
			}
			return(TRUE);
		}

		return(FALSE);
	}

	public static function internalEmailAddr($email) {
		global $engineVars;

		foreach ($engineVars['internalEmails'] as $key => $regex) {
			if(preg_match($regex,$email)) {
				return(TRUE);
			}	
		}

		return(FALSE);
	}

}

?>