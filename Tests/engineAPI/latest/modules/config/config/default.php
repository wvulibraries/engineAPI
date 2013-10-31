<?php
/**
 * EngineAPI default config
 * @package EngineAPI
 */

$engineVersion = "3.0";

$accessControl   = array();
$moduleFunctions = array();
$engineDB        = NULL;
$DEBUG           = NULL;

// If set to TRUE engine will attempt to use the current server variables
// set in php. If it finds that they are not set, or this is set to false, it will use
// the hard-coded alternative.
//
// Note: Server variables are NOT set on the commandline
$serverVars = TRUE;

if (!isset($_SERVER['SERVER_NAME']) || !isset($_SERVER['DOCUMENT_ROOT']) || empty($_SERVER['SERVER_NAME']) || empty($_SERVER['DOCUMENT_ROOT'])) {

	$serverVars = FALSE;

}

//URLS
global $engineVars;

// Your domain
$engineVars['server']     = "my.domain.com";

// stick your protocol in front ... 'http' or 'https' or 'ftp' or whatever
// Trying to be a little smarter about http and https
$engineVars['WVULSERVER'] = "http".((isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']))?"s":"")."://".$engineVars['server'];

// If your engine applications don't use your apache document root as its
// document root. Setting this is important for recursion and redirection.
$engineVars['WEBROOT']    = $engineVars['WVULSERVER'] ."";

// your 'main' CSS directory.
$engineVars['CSSURL']     = $engineVars['WEBROOT'] ."/css";

// your 'main' javascript directory
$engineVars['JAVAURL']    = $engineVars['WEBROOT'] ."/javascript";

// Where you copied engineIncludes too.
$engineVars['engineInc']  = $engineVars['WEBROOT'] ."/engineIncludes";

//Support Pages
$engineVars['loginPage']    = $engineVars['engineInc'] ."/login-3.0.php";
$engineVars['logoutPage']   = $engineVars['engineInc'] ."/logout.php";
$engineVars['downloadPage'] = $engineVars['engineInc'] ."/download.php";

//javascripts
$engineVars['jquery']          = $engineVars['engineInc'] ."/jquery.js";
$engineVars['jqueryDate']      = $engineVars['engineInc'] ."/jquery.date.js";
$engineVars['jqueryCookie']    = $engineVars['engineInc'] ."/jquery.eikooc.js";
$engineVars['sortableTables']  = $engineVars['engineInc'] ."/sorttable.js";
$engineVars['tablesDragnDrop'] = $engineVars['engineInc'] ."/tablednd.js";
$engineVars['selectBoxJS']     = $engineVars['engineInc'] ."/engineSelectBoxes.js";
$engineVars['convert2TextJS']  = $engineVars['engineInc'] ."/convert2TextArea.js";
$engineVars['engineListObjJS'] = $engineVars['engineInc'] ."/engineListObj.js";
$engineVars['engineWYSIWYGJS'] = $engineVars['engineInc'] ."/engineWYSIWYG.js";
$engineVars['engineWYSIWYGJS'] = $engineVars['engineInc'] ."/engineWYSIWYG.js";
$engineVars['tiny_mce_JS']     = $engineVars['engineInc'] ."/wysiwyg/tiny_mce.js";

//images
$engineVars['imgDeleteIcon']        = $engineVars['engineInc'] ."/images/minusIcon.gif";
$engineVars['imgListRetractedIcon'] = $engineVars['engineInc'] ."/images/arrowRight.gif";
$engineVars['imgListExpandedIcon']  = $engineVars['engineInc'] ."/images/arrowDown.gif";
$engineVars['imgPlusSign']          = $engineVars['engineInc'] ."/images/plug.gif";
$engineVars['imgMinusSign']         = $engineVars['engineInc'] ."/images/minus.gif";

//Directories
// The EngineAPI directories should be OUTSIDE of your document root and
// NOT available to the public.

// baseRoot is where the base for the website is. Everything else is
//expected to be based in this directory. WVU likes to consider each
//website/domain to be a "user" on the system and drops everything in
///home/domain
$engineVars['baseRoot']      = __DIR__; #"/home/library";

// EngineAPI's base directory. ALl of engine's directories will be contained here
$engineVars['rootPHPDir']    = $engineVars['baseRoot'] ."/phpincludes/engine";

// Where the engine templates are stored
$engineVars['tempDir']       = __DIR__ ."/template";

// The directory that corrisponds to $engineVars['WEBROOT'], defined above
$engineVars['documentRoot']  = ($serverVars === TRUE)?$_SERVER['DOCUMENT_ROOT']:$engineVars['baseRoot'] ."/library";

// File listings. XML files that contain metadata about files so what
// they can be stored without a mysql database.
$engineVars['fileListings']  = __DIR__."/filelistings";

// phpMailing Include
$engineVars['emailInclude']  = __DIR__ ."/phpmailer/phpmailer-fe.php";

// less compiler include
$engineVars['lessHandler'] = __DIR__ ."/lessc/lessc.inc.php";

// Engine Modules directory
$engineVars['modules']       = __DIR__."/modules";

// Access Control Modules
$engineVars['accessModules'] = __DIR__."/accessControl";

// Helper Function Modules
$engineVars['helperFunctions'] = __DIR__."/helperFunctions";

// Login Modules
$engineVars['loginModules']  = __DIR__."/login";

// RSS Templates
$engineVars['rssDir']        = $engineVars['tempDir'] ."/rss";
$engineVars['magpieDir']     = dirname(dirname(__DIR__)) ."/magpie";

// Syndication Configuration (replacement for RSS above)
$engineVars['syndicationTemplateDir'] = $engineVars['tempDir'] ."/syndication";
$engineVars['syndicationCacheDir']    = "/tmp/engineSyndication";
$engineVars['syndicationCache']       = 300; // In seconds

// External URLs
// External URLs is an array of urls that can be used in applications.
// WVU sets up proxy servers here.
$engineVars['externalURLs']['proxy1'] = "http://proxy1.com/";
$engineVars['externalURLs']['proxy2'] = "http://proxy2.com";

//RSS Files
// Individual RSS files. The RSS system is due for a major overhaul, and this
// section will change
$engineVars['rss2.0'] = $engineVars['rssDir'] ."/rss20.xml";

//Behavior
$engineVars['stripCarriageReturns'] = FALSE;
$engineVars['replaceDoubleQuotes']  = FALSE; // if engine sees "" in attributes, replaces it
									    	//with "~" where ~ is replaceDQCharacter
$engineVars['replaceDQCharacter']   = "~"; // any string
$engineVars['forceSSLLogin']        = TRUE; // If set to TRUE, the Login page redirects to the https:// login
											// page defined above

//Logging
$engineVars['log']   = TRUE;
$engineVars['logDB'] = "engineCMS";

//Access Control Test, Site wide default
// Turns access controls lists on and off
$engineVars['accessExistsTest'] = TRUE;

//Default table for MySQL authentication
$engineVars['mysqlAuthTable'] = "users";

//Explode & Implode string delimiter
$engineVars['delim'] = "%|%engineCMSDelim%|%";

//Internal Email address Regex's
// regex that defines if an email is internal or external
$engineVars['internalEmails'] = array();
$engineVars['internalEmails']['wvu'] = '/.*wvu.edu$/';

//$onCampus represents the IP ranges that are considered to be "on site"
// FOR WVU Libraries it is important to know if someone's IP address
// is "on" or "off" campus ... onCampus is an array that lets you define a range of
// IPs that should be included or excluded
$engineVars['onCampus'] = array();
$engineVars['onCampus'][] = "157.182.0-252.*";
$engineVars['onCampus'][] = "72.50.128-161.*";
$engineVars['onCampus'][] = "72.50.180-185.*";
// Temp
$engineVars['onCampus'][] = "192.168.171.1";

//MySQL Information
$engineVars['mysql']['server']   = "localhost";
$engineVars['mysql']['port']     = "3306";
//User with permissions to engineCMS database
$engineVars['mysql']['username'] = "username";
$engineVars['mysql']['password'] = 'password';

//Active Directory (ldap?) Information
// As many active directories/ldaps can be defined as needed here.
// WVU Libraries Staff
$engineVars['domains']['wvulibs']['ldapServer'] = "ldap://your.ad.PDC.com";
$engineVars['domains']['wvulibs']['ldapDomain'] = "your.ad.domain.com";
$engineVars['domains']['wvulibs']['dn']         = "DC=your,DC=ad,DC=PDC,DC=com";
$engineVars['domains']['wvulibs']['filter']     = "(|(sAMAccountName=%USERNAME%))";
$engineVars['domains']['wvulibs']['attributes'] = array("memberof","displayname");


// LDAP Authoritative Sources
// As many active directories/LDAPs can be defined as needed here.

$engineVars['ldapDomain']['wvu-ad']['ldapServer']     = "ldap://domain.com"; // URL of the ldap server
$engineVars['ldapDomain']['wvu-ad']['ldapServerPort'] = 389;                 // IP port on which the LDAP server is listening
$engineVars['ldapDomain']['wvu-ad']['ldapDomain']     = "domain.com";        // The name of this domain
$engineVars['ldapDomain']['wvu-ad']['baseDN']         = "DC=domain,DC=com";  // The DN to use as a base for all searching
//$engineVars['ldapDomain']['wvu-ad']['bindUsername']   = NULL;              // A static username to bing with
//$engineVars['ldapDomain']['wvu-ad']['bindPassword']   = NULL;              // A static password to bind with


// User Authorization module settings (these override in-module defaults)
/*
$engineVars['userAuth']['dbName']            = '';
$engineVars['userAuth']['tblUsers']          = '';
$engineVars['userAuth']['tblGroups']         = '';
$engineVars['userAuth']['tblPermissions']    = '';
$engineVars['userAuth']['tblAuthorizations'] = '';
$engineVars['userAuth']['tblUsers2Groups']   = '';
$engineVars['userAuth']['tblGroups2Groups']  = '';
$engineVars['userAuth']['defaultToken']      = '';
*/

// HTML stuff
// These are just default values. These can be over ridden using local variables, or in
// the filetemplate engine call
$engineVars['oddColor']  = "#bfbfbf";
$engineVars['evenColor'] = "#f2f2f2";
$engineVars['oddClass']  = "evenClass";
$engineVars['evenClass'] = "oddClass";

// Email Stuff
$engineVars['emailSender']['recipient']               = "recipient";
$engineVars['emailSender']['sender']                  = "sender";
$engineVars['emailSender']['cc']                      = "cc";
$engineVars['emailSender']['bcc']                     = "bcc";
$engineVars['emailSender']['subject']                 = "subject";
$engineVars['emailSender']['redirectOnFail']          = "redirectOnFail";
$engineVars['emailSender']['redirect']                = "redirect";
$engineVars['emailSender']['required']                = "required";
$engineVars['emailSender']['return_link_url']         = "return_link_url";
$engineVars['emailSender']['return_link_title']       = "return_link_title";
$engineVars['emailSender']['missing_fields_redirect'] = "missing_fields_redirect";
$engineVars['emailSender']['replyEmailOnFail']        = "replyEmailOnFail";
$engineVars['emailSender']['replyEmailOnSuccess']     = "replyEmailOnSuccess";

//Template calls

// Default Template set, can be set locally in localVars['']
$engineVars['templateDefault'] = "default";

// Called before page content
$engineVars['templateHeader'] = $engineVars['tempDir'] ."/". $engineVars['templateDefault'] ."/templateHeader.php";

// Called after page content
$engineVars['templateFooter'] = $engineVars['tempDir'] ."/". $engineVars['templateDefault'] ."/templateFooter.php";

// Mediasite Defaults
$engineVars['mediaSite']['url']      = "http://mediasite.lib.wvu.edu/Mediasite1";
$engineVars['mediaSite']['username'] = "engineAPI";
$engineVars['mediaSite']['password'] = "password";
$engineVars['mediaSite']['authLen']  = "30";

// File Handler
$engineVars['mimeFilename'] = NULL;

$engineVars['session'] = array(
	// Default session name
	'name' => 'EngineAPI',
	/*
	 * Default session drivers
	 * This also controls the order in which they will be used (with the others as fall-backs
	 */
	'driver' => array(
		'database'   => array(), // See sessionDriverDatabase for list of options
		'filesystem' => array(), // See sessionDriverFilesystem for list of options
		'native'     => array(), // See sessionDriverNative for list of options
	),
	// Automatically start the session w/o having to call session::start()
	'autoStart' => TRUE,
	// Array or CSV of nodes of $_SERVER which will be used to calculate the browser fingerprint
	'fingerprintAttrs' => 'HTTP_USER_AGENT,REMOTE_ADDR',
	// Length of time after which a csrf token will no longer be accepted (will have no effect if cookieLifetime is less)
	'csrfTimeout' => 86400,
	// Length of time the session's cookie should live
	'cookieLifetime' => '',
	// The session cookie's path param (controls what paths the cookie is visible on)
	'cookiePath' => '',
	// The session cookie's domain param (controls what domain the cookie is visible on)
	'cookieDomain' => '',
	// If TRUE, the cookie will only be valid over https
	'cookieSecure' => FALSE,
	// If TRUE, the cookie will have the httponly flag set (making it visible only on the http(s) protocol)
	'cookieHttpOnly' => TRUE,
	// The probability of garbage collection running (will be the numerator for the probability)
	'gcProbability' => 1,
	// The divisor of probability for garbage collection (ex: 100 sets gcProbability to be %'s of 100)
	'gcDivisor' => 100,
	// At what point does the garbage collector see old data as 'garbage' (This should never be less than cookieLifetime)
	'gcMaxlifetime' => 604800

);
?>