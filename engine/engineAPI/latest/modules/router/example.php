
<?php 

// This file assumes that it is inside of "/test" in your document root. 
// It also assumes that you have properly setup your .htaccess as described
// in the router.php module


// Change this to the proper engine path
include '/phpincludes/engine/engineAPI/latest/engine.php';

$engine = EngineAPI::singleton();

/* Example 1 
 * Define expected route as part of the constructor. 
 *
 * matches on "/test/users/anything"
 */

$router = router::getInstance("/test/users/{ID}");

if ($router->match()) {
	$variables = $router->getVariables();

	print "Example: 1<pre>";
	var_dump($variables);
	print "</pre>";
}

/* Example 2 
 * Define using the defineRoute method
 * matches on "/test/group/anything"
 */

router::resetInstance();

$router = router::getInstance();

$router->defineRoute("/test/group/{groupName}");

if ($router->match()) {
	$variables = $router->getVariables();

	print "Example: 2<pre>";
	var_dump($variables);
	print "</pre>";
}

/* Example 3
 * define multiple routes
 * matches on "/test/group/anything"
 * matches on "/test/users/anything"
 */

router::resetInstance();

$router = router::getInstance();

$router->defineRoute("/test/group/{groupName}");
$router->defineRoute("/test/users/{ID}");

if ($router->match()) {
	$variables = $router->getVariables();

	print "Example: 3<pre>";
	var_dump($variables);
	print "</pre>";
}

/* Example 4
 * With a callback function
 * matches on "/test/users/anything"
 */

router::resetInstance();

$router = router::getInstance();

$router->defineRoute("/test/users/{ID}","callBackTest");

$router->route();

function callbackTest($serverURI,$variables) {

	print "Example 4:<h1>Inside the Callback</h1>";
	print "<pre>";
	var_dump($variables);
	print "</pre>";

}

/* Example 5:
 * Multiple Variables
 * matches on "/test/post/{year}/{month}/{day}/{title}"
 * example: /test/post/2014/01/02/This%20Is%20A%20Test
 */

router::resetInstance();

$router = router::getInstance("/test/post/{year}/{month}/{day}/{title}");

if ($router->match()) {
	$variables = $router->getVariables();

	print "Example: 5<pre>";
	var_dump($variables);
	print "</pre>";
}

/* Example 6
 * Validation (not regex) 
 * matches on "/test/users/%integer%"
 * example: 
 *    displays variables: /test/users/1
 *    returns false : /test/users/foo
 */
router::resetInstance();

$router = router::getInstance("/test/users/{ID=integer}");

if ($router->match()) {
	$variables = $router->getVariables();

	print "Example: 6<pre>";
	var_dump($variables);
	print "</pre>";
}

/* Example 7
 * Validation (Regex) 
 * matches on "/test/users/%integer%"
 * example: 
 *    displays variables: /test/users/1
 *    returns false : /test/users/foo
 */
router::resetInstance();

$router = router::getInstance("/test/users/{ID=/\d+/}");

if ($router->match()) {
	$variables = $router->getVariables();

	print "Example: 7<pre>";
	var_dump($variables);
	print "</pre>";
}

/* Example 8
 * Validation (Regex) part 2
 *
 * Note nested {} are not currently supported, so you cannot use {} notation in your regex
 * 
 * matches on "/test/users/%integer%"
 * example: 
 *    displays variables: /test/users/111
 *    returns false : /test/users/foo
 */
router::resetInstance();

// This is not valid:
// $router = router::getInstance("/test/users/{ID=/\d{3}/}");

// Instead, use this:
$router = router::getInstance("/test/users/{ID=/\d\d\d/}");

if ($router->match()) {
	$variables = $router->getVariables();

	print "Example: 8<pre>";
	var_dump($variables);
	print "</pre>";
}

?>
