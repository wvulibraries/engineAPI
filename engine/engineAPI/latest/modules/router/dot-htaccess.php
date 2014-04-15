# router makes the assumption that there is a .htaccess in your application that looks
# like this, where 'myapp' is replaced with the document root (URI) of your application:
# It recursively looks backwards for the closest index.php file and puts all the 
# 'fake' URI information there. 

<IfModule mod_rewrite.c>
RewriteEngine On

## recursively search parent dir
# if index.php is not found then
# forward to the parent directory of current URI
RewriteCond %{DOCUMENT_ROOT}/myapp/$1$2/index.php !-f
RewriteRule ^(.*?)([^/]+)/[^/]+/?$ /myapp/$1$2/ [L]

# if current index.php is found in parent dir then load it
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{DOCUMENT_ROOT}/myapp/$1/index.php -f
RewriteRule ^(.*?)[^/]+/?$ /myapp/$1/index.php [L]

</IfModule>