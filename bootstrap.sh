#!/bin/bash

# Base PRE Setup

GITDIR="/tmp/git"
ENGINEAPIHOME="/home/engineAPI"

SERVERURL="/home/engineAPI"
DOCUMENTROOT="public_html"
TEMPLATES=$GITDIR/engineAPITemplates
MODULES=$GITDIR/engineAPI-Modules

yum -y install httpd httpd-devel httpd-manual httpd-tools
yum -y install mysql-connector-java mysql-connector-odbc mysql-devel mysql-lib mysql-server
yum -y install mod_auth_kerb mod_auth_mysql mod_authz_ldap mod_evasive mod_perl mod_security mod_ssl mod_wsgi 
yum -y install php php-bcmath php-cli php-common php-gd php-ldap php-mbstring php-mcrypt php-mysql php-odbc php-pdo php-pear php-pear-Benchmark php-pecl-apc php-pecl-imagick php-pecl-memcache php-soap php-xml php-xmlrpc 
yum -y install emacs emacs-common emacs-nox
yum -y install git

mv /etc/httpd/conf.d/mod_security.conf /etc/httpd/conf.d/mod_security.conf.bak
/etc/init.d/httpd start

mkdir -p $GITDIR
cd $GITDIR
git clone https://github.com/wvulibraries/engineAPITemplates.git
git clone https://github.com/wvulibraries/engineAPI-Modules.git

mkdir -p $SERVERURL/phpincludes/
ln -s /vagrant/engine/ $SERVERURL/phpincludes/

# Application Specific

ln -s /vagrant/public_html $SERVERURL/$DOCUMENTROOT

rm -f /etc/php.ini
rm -f /etc/httpd/conf/httpd.conf

ln -s /vagrant/serverConfiguration/php.ini /etc/php.ini
ln -s /vagrant/serverConfiguration/vagrant_httpd.conf /etc/httpd/conf/httpd.conf

mkdir -p $SERVERURL/$DOCUMENTROOT/javascript/
ln -s /tmp/git/engineAPI/engine/template/distribution/public_html/js $SERVERURL/$DOCUMENTROOT/javascript/distribution

mkdir -p /vagrant/serverConfiguration/serverlogs
touch /vagrant/serverConfiguration/serverlogs/error_log
/etc/init.d/httpd restart

# Base Post Setup

## Setup the EngineAPI Database

/etc/init.d/mysqld start
mysql -u root < /vagrant/sql/vagrantSetup.sql
mysql -u root EngineAPI < /vagrant/sql/EngineAPI.sql

## The following blocks will link from templates and Modules. This is likely undesirable except in special situations. be sure to not include symlinks in commits

## Link the templates
# for f in $TEMPLATES/*
# do
# 	name=`basename $f`
# 	ln -s $f $ENGINEAPIHOME/phpincludes/engine/template/$name
# done

## Link the Modules
# for f in $MODULES/modules/*
# do
# 	name=`basename $f`
# 	ln -s $f $ENGINEAPIHOME/phpincludes/engine/engineAPI/latest/modules/$name
# done