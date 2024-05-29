SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
-- --------------------------------------------------------

--
-- Table structure for table `auth_authorizations`
--
DROP TABLE IF EXISTS `auth_authorizations`;
CREATE TABLE IF NOT EXISTS `auth_authorizations` (
	`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`authEntity` varchar(32) NOT NULL,
	`permissionID` int(10) unsigned NOT NULL,
	`policy` enum('allow','deny') NOT NULL DEFAULT 'allow',
	`authObjectID` varchar(64) NOT NULL,
	`inheritable` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`inheritedFrom` varchar(64) NOT NULL,
	PRIMARY KEY (`ID`),
	UNIQUE KEY `Authorization` (`authEntity`,`permissionID`,`policy`,`authObjectID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `auth_groups`
--
DROP TABLE IF EXISTS `auth_groups`;
CREATE TABLE IF NOT EXISTS `auth_groups` (
	`ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`ldapDN` varchar(256) DEFAULT NULL,
	`name` varchar(32) NOT NULL,
	`description` text NOT NULL,
	PRIMARY KEY (`ID`),
	UNIQUE KEY `ldapDN` (`ldapDN`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `auth_groups_groups`
--
DROP TABLE IF EXISTS `auth_groups_groups`;
CREATE TABLE IF NOT EXISTS `auth_groups_groups` (
	`ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`childGroup` int(10) unsigned NOT NULL,
	`parentGroup` int(10) unsigned NOT NULL,
	PRIMARY KEY (`ID`),
	UNIQUE KEY `childGroup` (`childGroup`,`parentGroup`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `auth_objects`
--
DROP TABLE IF EXISTS `auth_objects`;
CREATE TABLE IF NOT EXISTS `auth_objects` (
	`ID` varchar(256) NOT NULL,
	`parent` varchar(256) NOT NULL,
	`inherits` tinyint(1) NOT NULL DEFAULT '1',
	`metaData` text NOT NULL,
	PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `auth_permissions`
--
DROP TABLE IF EXISTS `auth_permissions`;
CREATE TABLE IF NOT EXISTS `auth_permissions` (
	`ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`container` varchar(32) NOT NULL,
	`permission` varchar(256) NOT NULL,
	`name` varchar(32) NOT NULL,
	`description` text NOT NULL,
	PRIMARY KEY (`ID`),
	UNIQUE KEY `permission` (`container`,`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `auth_users`
--
DROP TABLE IF EXISTS `auth_users`;
CREATE TABLE IF NOT EXISTS `auth_users` (
	`ID` int(11) NOT NULL AUTO_INCREMENT,
	`username` varchar(16) DEFAULT NULL,
	`password` varchar(32) NOT NULL,
	`firstname` varchar(25) DEFAULT NULL,
	`lastname` varchar(25) DEFAULT NULL,
	`email` varchar(256) DEFAULT NULL,
	PRIMARY KEY (`ID`),
	UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `auth_users_groups`
--
DROP TABLE IF EXISTS `auth_users_groups`;
CREATE TABLE IF NOT EXISTS `auth_users_groups` (
	`ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`user` int(10) unsigned NOT NULL,
	`group` int(10) unsigned NOT NULL,
	PRIMARY KEY (`ID`),
	UNIQUE KEY `user` (`user`,`group`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;