<?php

// Matches the following
// [+]CountryCode Delimiter [(]AreaCode[)] Delimiter Exchange Delimiter Number [x|ext|extension]delimiter extension
// country code is optional (plus sign is optional)
// Area code is required
// Delimiters between numbers are required, and can be "-", ".", or " "
// Extension is optional, delimiter is required and can be "." or ":"
function validPhoneNumber($number) {
	$phoneRegex = "/^\s*(\+?\d+\s*(\-|\ |\.)\s*)?\(?\d{3}\)?\s*(\-|\ |\.)\s*\d{3}\s*(\-|\ |\.)\s*\d{4}(\s*(\s|ext(\.|\:)?|extension\:?|x(\.|\:)?)\s*\d+)?$/";
	$match = preg_match($phoneRegex,$number);
	
	if ($match == 1) {
		return(TRUE);
	}
	
	return(FALSE);
}

// Checks against regex for valid IP range. doesn't break apparent octets and 
// look at values (999.999.999.999 will return correctly)
function validIPAddr($ip) {
	$ipRegex = "/((\d{1,3}(-\d{1,3})?)|\*)\.((\d{1,3}(-\d{1,3})?)|\*)\.((\d{1,3}(-\d{1,3})?)|\*)\.((\d{1,3}(-\d{1,3})?)|\*)/";	
	$match = preg_match($ipRegex,$ip);
	
	if ($match == 1) {
		return(TRUE);
	}
	
	return(FALSE);
}

// Allow just about anything, but if it appears to be a URL it must be a valid URL
function validOptionalURL($url) {
	$urlCheckRegex = "/^(https?|ftp|ssh|telnet)\:\/\/.+/";
	$match = preg_match($urlCheckRegex,$url);
	
	if ($match == 1) {
		return(validURL($url));
	}
	
	return(TRUE);
}

function validURL($url) {
	
	// Regex stolen from
	// http://phpcentral.com/208-url-validation-in-php.html
	
	$urlregex = "/^(https?|ftp|ssh|telnet)\:\/\/([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-z0-9+\$_-]\.?)+)*\/?(\?[a-z+&\$_.-][a-z0-9;:@\/&%=+\$_.-]*)?(#[a-z_.-][a-z0-9+\$_.-]*)?\$/i";
	
	$match = preg_match($urlregex,$url);
	
	if ($match == 1) {
		return(TRUE);
	}
	
	return(FALSE);
	
}

function validateEmailAddr($email,$internal=FALSE) {
	return(email::validate($email,$internal));
}	



?>