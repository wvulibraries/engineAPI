<?php
/**
 * revisionControlSystem example usage
 * @package EngineAPI\modules\revisionControlSystem
 * @ignore
 */

function relatedDataTranslation($value) {
	switch($value) {
		case 0:
			return("this");
			break;
		case 1: 
			return("that");
			break;
		case 2: 
			return("another");
			break;
		case 3: 
			return("thing");
			break;
		default:
			return("Forgot me!");
			break;
	}

	return("Broke");
}

require_once("/home/library/phpincludes/engine/engineAPI/3.1/engine.php");
$engine    = EngineAPI::singleton();

errorHandle::errorReporting(errorHandle::E_ALL);

$engine->dbConnect("database","testy",TRUE);

// Create Revision Control System Object
$rcs = new revisionControlSystem("test","revisions","ID","modifiedTime");

// Setup related data
$rcs->addRelatedDataMapping("linking","test");

$sql       = sprintf("TRUNCATE TABLE test");
$sqlResult = $engine->openDB->query($sql);
$sql       = sprintf("TRUNCATE TABLE revisions");
$sqlResult = $engine->openDB->query($sql);
$sql       = sprintf("TRUNCATE TABLE linking");
$sqlResult = $engine->openDB->query($sql);

$sql       = sprintf("INSERT INTO `test` (name,modifiedTime,digitalObjects,sentence,html,test2) VALUES('Custom Disp Function','1358272517','data:image/jpeg;base64,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','this is a sentence. it is not to long.','<!DOCTYPE html>
<html>

<head>
	<title>Revision Control Tests</title>

</head>

<body>

<p>Hello World!<p>

</body>
</html>','test2 test')");
$sqlResult = $engine->openDB->query($sql);

$sql       = sprintf("INSERT INTO `linking` (test, tset) VALUES('1','2'),('1','3'),('2','1'),('2','2'),('1','1'),('4','2')");
$sqlResult = $engine->openDB->query($sql);

$return = $rcs->insertRevision("1");

$sql       = sprintf("UPDATE `test` SET `modifiedTime`='1358272518' WHERE ID='1'");
$sqlResult = $engine->openDB->query($sql);

$return = $rcs->insertRevision("1");

$sql       = sprintf("UPDATE `test` SET `modifiedTime`='1358272519', `name`='test2' WHERE ID='1'");
$sqlResult = $engine->openDB->query($sql);

$sql       = sprintf("DELETE FROM `linking` WHERE test='1' AND tset='1'");
$sqlResult = $engine->openDB->query($sql);

$return = $rcs->insertRevision("1");

$sql       = sprintf("UPDATE `test` SET `modifiedTime`='1358272520', `name`='test3', `sentence`='this is a sentence. Test. it is not too long.', html='<!DOCTYPE html>
<html>

<head>
	<title>Revision Control Tests</title>

</head>

<body>

<p>Goodbye World!<p>

</body>
</html>' WHERE ID='1'");
$sqlResult = $engine->openDB->query($sql);


$return = $rcs->revert2Revision("1","1358272517");

$compare1 = "1358272517";
$compare2 = "1358272520";

if (isset($engine->cleanPost['MYSQL']['submit'])) {
	$return = $rcs->revert2Revision("1",$engine->cleanPost['MYSQL']['revert']);
}
else if (isset($engine->cleanPost['MYSQL']['compare'])) {

	if (!isset($engine->cleanPost['MYSQL']['compare1']) || !isset($engine->cleanPost['MYSQL']['compare2'])) {
		errorHandle::errorMsg("Must select both a 'Compare 1' and a 'Compare 2'.");
	}
	else {
		$compare1 = $engine->cleanPost['MYSQL']['compare1'];
		$compare2 = $engine->cleanPost['MYSQL']['compare2'];
	}
}


$displayFields = array();
$displayFields[] = array(
	"field" => "name",
	'label' => "Name"
	);
$displayFields[] = array(
	'field'       => "secondaryID",
	'label'       => "Last Updated",
	'translation' => create_function('$date','return date("m/d/Y H:i:s",$date);')
	);	

localvars::add("revisionTable",$rcs->generateRevisionTable("1",$displayFields));

// Get the current row from the database. we'll just vardump it below
$sql       = sprintf("SELECT * FROM test WHERE `ID`='1'");
$sqlResult = $engine->openDB->query($sql);
$row       = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);

$fields = array();
$fields['metadata']['name']['display'] = create_function('$value', 'return sprintf("<em>%s</em>",$value);');
$fields['metadata']['test2']['diff']   = create_function('$value', 'return "custom diff function!";');
$fields['relatedData']['test']         = create_function('$value', 'return sprintf("Object ID: %s",$value);');
$fields['relatedData']['tset']         = create_function('$value', 'return relatedDataTranslation($value);');
$fields['digitalObjects']              = create_function('$value', 'return sprintf("<img src=\"%s\" />",$value);');
localvars::add("staticCompare",$rcs->compare("1",$compare1,"1",$compare2,$fields));

if (array_key_exists("error",$engine->errorStack) && count($engine->errorStack['error'] > 0)) {
	$errorMsg = errorHandle::prettyPrint("error");
}
else if (array_key_exists('warning',$engine->errorStack) && count($engine->errorStack['warning'] > 0)) {
	$errorMsg  = errorHandle::prettyPrint("warning");
	// $errorMsg .= errorHandle::prettyPrint("success");
}
else {
	$errorMsg = "";//errorHandle::prettyPrint();
}
localvars::add("errorMsg",sprintf('<div id="actionResults">%s</div>',$errorMsg));

?>
<!DOCTYPE html>
<html>

<head>
	<title>Revision Control Tests</title>

	<style>
	.engineRCSCompareTable {
		border-width: 1px; border-style: outset;
	}
	.engineRCSCompareTable td, .engineRCSCompareTable th {
		border-width: 1px; border-style: inset;
	}

	.fieldName {
		vertical-align: top;
	}

	.engineRCSCompareTable del {
		background-color: red;
	}

	.engineRCSCompareTable ins {
		background-color: green;
	}

	</style>

</head>

<body>

<p>Hello World!<p>

{local var="errorMsg"}

<form action="{phpself query="true"}" method="post" />
{csrf insert="post"}

{local var="revisionTable"}

<input type="submit" name="submit" value="Revert" /> &nbsp;&nbsp; <input type="reset" name="reset" value="Reset" /> &nbsp;&nbsp; <input type="submit" name="compare" value="Compare" />

</form>

<h1>Compare</h1>
<p><strong>Should update after submitting a revision above</strong></p>

{local var="staticCompare"}

<h1>Row Vardump</h1>
<p><strong>Should update after submitting a revision above</strong></p>
<?php
print "<pre>";
var_dump($row);
print "</pre>";
?>

</body>
</html>