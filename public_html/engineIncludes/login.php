<?php

$loginType = "mysql";
#$loginType = "ldap";

$engineDir = "/home/library/phpincludes/engine/engineAPI";
include($engineDir ."/engine.php");
$engine = EngineAPI::singleton();

$localVars['pageTitle'] = "Login Page";

// Domain for ldap login
$engine->localVars("domain","wvulibs");

$authFail  = FALSE; // Authorization to the current resource .. we may end up not using this
$loginFail = FALSE; // Login Success/Failure

if (isset($engine->cleanGet['HTML']['page'])) {
	$page = $engine->cleanGet['HTML']['page']; 
	if (isset($engine->cleanGet['HTML']['qs'])) {
		$qs = urldecode($engine->cleanGet['HTML']['qs']);
		$qs = preg_replace('/&amp;amp;/','&',$qs);
		$qs = preg_replace('/&amp;/','&',$qs);
	}
	else {
		$qs = "";
	}
}

//Login processing:
if (isset($engine->cleanPost['HTML']['loginSubmit'])) {
	if (!isset($engine->cleanPost['HTML']['username']) || !isset($engine->cleanPost['HTML']['password'])) {
		$authFail  = TRUE;
		$loginFail = TRUE;
	}
	else {
		global $engineVars;
		if ($engine->login($loginType)) {
			if(isset($engine->cleanGet['HTML']['url'])) {
				header("Location: ".$engine->cleanGet['HTML']['URL'] ) ;
			}
			else {
				
				if (debugNeeded("login")) {
					debugDisplay("login","\$_SESSION",1,"Contents of the \$_SESSION array.",$_SESSION);
				}
				else if (isset($page)) {
					header("Location: ".$page."?".$qs );
				}
				else {
					header("Location: ".$engineVars['WEBROOT'] );
				}

			}
		}
		else {
			$loginFail = TRUE;
		}
		
	}

}


$engine->eTemplate("include","header");
?>


<?php
if (debugNeeded("login")) {
	debugDisplay("login","\$_SESSION",1,"Contents of the \$_SESSION array.",$_SESSION);
}

?>


<h2>Login</h2>

<?php
if($loginFail) {
	print "<div style=\"\"><p>Login Failed</p></div>";
}
?>

<?php
if(isset($page)) {
	print "<div style=\"color:red;\"><p>You are either not logged in or do not have access to the requested page.</p></div>";
}
?>

<form name="loginForm" action="<?= $_SERVER['PHP_SELF']?><?php if(isset($page)){ echo "?page=".$page; if(isset($qs)) { echo "&qs=".(urlencode($qs)); } } ?>" method="post">
	{engine name="insertCSRF"}
	
	<table>
		<tr>
			<td>
				<label for="username">Username:</label>
			</td>
			<td>
				<input type="text" name="username" id="username" value="" />
			</td>
		</tr>
		<tr>
			<td>	
				<label for="password">Password:</label>
			</td>
			<td>
				<input type="password" name="password" id="password" value="" onkeypress="capsLockCheck(event);"/> <span id="capsLock" style="display:none;">Caps Lock is On</span>
			</td>
		</tr>
	</table>
	
	<br />
	
	<input type="submit" name="loginSubmit" value="Login" />
</form>


<script>
document.loginForm.username.focus();
</script>

<?php
$engine->eTemplate("include","footer");
?>