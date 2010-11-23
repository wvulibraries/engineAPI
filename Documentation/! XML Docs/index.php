<?
$engineDir = "/home/library/phpincludes/engineAPI/engine";
include($engineDir ."/engine.php");
$engine = new EngineCMS();

$engine->localVars('pageTitle',"EngineAPI Documentation");
$engine->localVars('siteRoot',$engineVars['WEBROOT']."/engineapi");

$engine->eTemplate("load","systems");
$engine->eTemplate("include","header");
?>

<!-- Page Content Goes Below This Line -->

<h1>EngineAPI Documentation</h1> 
 
<?
$func = isset($engine->cleanGet['HTML']['f']) ? $engine->cleanGet['HTML']['f'] : NULL;

$dirs = getDirectoryTree(getcwd()."/docs",array("*_old"));
$path = findDocPath($dirs,$func);

$xml = simplexml_load_file($path);


// File Name
if (!is_empty($xml->name)) {
	print '<span class="name">';
	if (!is_empty($xml->return->type)) {
		print $xml->return->type." ";
	}
	if (!is_empty($xml->memberOf)) {
		print $xml->visibility." ";
	}
	if (!is_empty($xml->memberOf)) {
		print $xml->memberOf."::";
	}
	
	print $xml->name.'(';
	
	// params
	if (isset($xml->parameters)) {
		$countOptional = 0;
		foreach ($xml->parameters->parameter as $param) {
			if (!is_empty($param->optional) && $param->optional == "TRUE") {
				print ' [, ';
				$countOptional++;
			}
			if (!is_empty($param->type)) {
				print $param->type.' ';
			}
			print $param->name;
			if (!is_empty($param->defaultValue)) {
				print ' = '.$param->defaultValue.'';
			}
		}
		for ($i=0; $i < $countOptional; $i++) { 
			print ']';
		}
	}

	print ')';

	if (!is_empty($xml->extends)) {
		print " extends ".$xml->extends;
	}
	print '</span><br /><br />';
}


// Description
if (!is_empty($xml->description)) {
	print '<div class="docSection">';
	print '<h2>Description</h2>';
	print '<p class="description">'.$xml->description.'</p>';
	print '</div>';
}

// Parameters
if (isset($xml->parameters)) {
	print '<div class="docSection">';
	print '<h2>Parameters</h2>';

	foreach ($xml->parameters->parameter as $param) {
	
		print '<div class="docSection">';
		
		print '<h3>';
		if (!is_empty($param->type)) {
			print '<span class="paramType">'.$param->type.' </span>';
		}
		print '<span class="paramName">'.$param->name.'</span>';
		if (!is_empty($param->defaultValue)) {
			print '<span class="paramDefault"> = '.$param->defaultValue.'</span>';
		}
		print '</h3>';


		if (!is_empty($param->description)) {
			print '<h4>Description</h4>';
			print '<p class="paramDescription">'.$param->description.'</p>';
		}

		if (!is_empty($param->expectedValues->value->name)) {
			print '<h4>Expected Values</h4>';
			print '<div class="paramValue">';
			foreach ($param->expectedValues->value as $value) {
				print '<h5>'.$value->name.'</h5>';
				print '<div class="docSection">'.$value->description.'</div>';
			}
			print '</div>';
		}

		print '</div>';

	}

	print '</div>';
}

// Return Value
if (isset($xml->return)) {
	print '<div class="docSection">';
	print '<h2>Return Value</h2>';
	print '<p class="return">'.$xml->return->description.'</p>';
	print '</div>';
}

// Examples
if (isset($xml->examples)) {
	print '<div class="docSection">';
	print '<h2>Examples</h2>';
	
	foreach ($xml->examples->example as $example) {
	
		print '<div class="docSection">';
		
		if (!is_empty($example->name)) {
			print '<h3>'.$example->name.'</h3>';

			if (!is_empty($example->description)) {
				print '<p class="exampleDescription">'.$example->description.'</p>';
			}
			
			if (!is_empty($example->code)) {
				print '<div class="exampleCode"><code>'.htmlSanitize(leftIndentTrim($example->code)).'</code></div>';
			}
		}

		print '</div>';

	}

	print '</div>';
}

// Notes
if (isset($xml->notes)) {
	print '<div class="docSection">';
	print '<h2>Notes</h2>';
	print '<p class="notes">'.$xml->notes.'</p>';
	print '</div>';
}

// See Also
if (isset($xml->seeAlso)) {
	print '<div class="docSection">';
	print '<h2>See Also</h2>';
	print '<p class="seeAlso"><code>'.htmlSanitize(leftIndentTrim($xml->seeAlso)).'</code></p>';
	print '</div>';
}

?>

<!-- Page Content Goes Above This Line -->

<?
$engine->eTemplate("include","footer");
?>