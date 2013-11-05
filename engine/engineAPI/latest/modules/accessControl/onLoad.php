<?php

	// This is how this should be done, but won't work
	// 
	// Access Control and login inits can't be off loaded to onLoad.php for the 
	// modules because engine and private vars needs to be created with engineAPI
	// constructor variables first. (enginedir and site)

	// accessControl::init();

?>