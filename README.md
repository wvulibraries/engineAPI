# EngineAPI
EngineAPI is the PHP Framework developed by the Library Systems Dept of West Virginia University

## Developer Documentation
EngineAPI uses phpDocs to provide developer documentation. The documentation isn't included in version control, and is up to the developer to generate it on their local machine. This does require a few components be installed locally, but is not too painful.

### Install phpDocumentor
You can view phpDocumentor's install docs [here](https://github.com/phpDocumentor/phpDocumentor2#installation) or follow the following steps.

**Install phpDocumentor via PEAR:** *(Recommended)*

1. `pear channel-discover pear.phpdoc.org`<br>
   `pear install phpdoc/phpDocumentor-alpha`

**Install phpDocumentor manually:**

1. You need to decide where you want to to install phpDocumentor.<br>*We will refer to this as `<PHPDOC_PATH>`*
2. Install phpDocumentor dependencies: XSL and Graphviz
   - Linux: `yum install graphviz-php php-intl`
   - Windows: TODO
   - MAC OSX: TODO
3. Move into the install directory. `cd <PHPDOC_PATH>`
4. Download the the phpDocumentor install script.<br>
   `wget https://raw.github.com/phpDocumentor/phpDocumentor2/develop/installer.php`
5. Run the install script. `php installer.php`
6. *(Optional)* Add phpDocumentor to your PATH
   - Linux or MAC OSX:<br>
     Symlink `<PHPDOC_PATH>/bin/phpdoc.php` into your bin folder (usually /usr/bin) named `phpdoc`.
   - Windows:<br>
     Add `<PHPDOC_PATH>/bin` to your PATH


### Generate/Update Documentation
Navigate to the root directory of EngineAPI and inkoke `phpdoc`. That's it! (It couldn't be any simpler than that!)<br>
Now, an HTML website will be generated located at `engineAPI/engine/engineAPI/latest/documentation/developer` which is your developer docs for EngineAPI!
