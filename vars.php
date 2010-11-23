<?php

//URLS
$engineVars = array();
$engineVars['WVULSERVER'] = "http://systemsdev.lib.wvu.edu";
$engineVars['CSSURL']     = $engineVars['WVULSERVER'] ."/css";
$engineVars['JAVAURL']    = $engineVars['WVULSERVER'] ."/javascript";
$engineVars['WEBROOT']    = $engineVars['WVULSERVER'] ."";
$engineVars['loginPage']  = $engineVars['WEBROOT'] ."/login.php"; 
$engineVars['logoutPage']  = $engineVars['WEBROOT'] ."/logout.php"; 

//Directories
$engineVars['rootPHPDir']   = "/home/library/phpincludes/engineCMS";
$engineVars['tempDir']      = $engineVars['rootPHPDir'] ."/template";
$engineVars['documentRoot'] = "/home/library/library_home";
$engineVars['fileListings'] = $engineVars['rootPHPDir'] ."/filelistings";
$engineVars['emailInclude'] = $engineVars['rootPHPDir'] ."/phpmailer/phpmailer-fe.php";
$engineVars['webHelper']    = $engineVars['rootPHPDir'] ."/engine/webHelper";

//Logging
$engineVars['log'] = TRUE;

//$onCampus represents the IP ranges that are considered to be "on site"
$engineVars['onCampus'] = array();
$engineVars['onCampus'][] = "157.182.0-252.*";
$engineVars['onCampus'][] = "72.50.128-191.*";

//MySQL Information
$engineVars['mysql']['server']   = "localhost";
$engineVars['mysql']['port']     = "3306";
//User with permissions to engineCMS database
$engineVars['mysql']['username'] = "dbusername";
$engineVars['mysql']['password'] = "dbpassword";

//Active Directory (ldap?) Information
// WVU Libraries Staff
$engineVars['domains']['wvulibs']['ldapServer'] = "ldap://aldonova.wvulibs.wvu.edu";
$engineVars['domains']['wvulibs']['ldapDomain'] = "wvulibs.wvu.edu";
$engineVars['domains']['wvulibs']['dn']         = "DC=wvulibs,DC=wvu,DC=edu";
$engineVars['domains']['wvulibs']['filter']     = "(|(sAMAccountName=%USERNAME%))";
$engineVars['domains']['wvulibs']['attributes'] = array("memberof","displayname");
// WVU Libraries Public
$engineVars['domains']['wvulibpub']['ldapServer'] = "ldap://alicecooper.wvulibpub.wvu.edu";
$engineVars['domains']['wvulibpub']['ldapDomain'] = "wvulibpub.wvu.edu";
$engineVars['domains']['wvulibpub']['dn']         = "DC=wvulibpub,DC=wvu,DC=edu";
$engineVars['domains']['wvulibpub']['filter']     = "(|(sAMAccountName=%USERNAME%))";
$engineVars['domains']['wvulibpub']['attributes'] = array("memberof","displayname");

// HTML stuff
// These are just default values. These can be over ridden using local variables, or in 
// the filetemplate engine call
$engineVars['oddColor']  = "#0f0f0f";
$engineVars['evenColor'] = "#f0f0f0";
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
?>