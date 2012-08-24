# Engine Directory Structure

The directory structure has changed for EngineAPI 3.0 to make it easier to have multiple versions of engine without duplicating external components (such as php mailer, lessc, and templates)

The recommended structure, that WVU Libraries, uses is now

~/phpincludes/engine

where the directory ***phpincludes/*** is outside of your document root, and the directory ***engine*** contains all engine related files.

the files in ***public_html/*** are intended to live in your document root

* ~/phpincludes/engine/engineAPI/%version number%/%engine files%
* ~/phpincludes/engine/engineCMS/%version number%/%engineCMS files%
* ~/phpincludes/engine/filelistings/
* ~/phpincludes/engine/lessc/
* ~/phpincludes/engine/magpie/
* ~/phpincludes/engine/phpmailer/
* ~/phpincludes/engine/phpthumb/
* ~/phpincludes/engine/template/

* ~/phpincludes/engine/Licenses/ 

* ~/phpincludes/engine/public_html/engineIncludes/  

