DROP TABLE IF EXISTS `auth_groups`;
CREATE TABLE `auth_groups` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ldapDN` varchar(256) DEFAULT NULL,
  `name` varchar(32) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `auth_users`;
CREATE TABLE `auth_users` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(16) DEFAULT NULL,
  `password` varchar(32) NOT NULL,
  `firstname` varchar(25) DEFAULT NULL,
  `lastname` varchar(25) DEFAULT NULL,
  `email` varchar(25) DEFAULT NULL,
  `cellPhone` varchar(25) DEFAULT NULL,
  `officePhone` varchar(25) DEFAULT NULL,
  `homePhone` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `auth_permissions`;
CREATE TABLE `auth_permissions` (
	`ID` int(11) NOT NULL AUTO_INCREMENT,
  `authToken` varchar(64) NOT NULL,
  `permission` varchar(128) NOT NULL,
  `isEmpty` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`ID`,`authToken`,`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `auth_authorizations`;
CREATE TABLE `auth_authorizations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userID` int(10) unsigned DEFAULT NULL,
  `groupID` int(10) unsigned DEFAULT NULL,
  `authToken` varchar(32) NOT NULL DEFAULT '',
  `permissions` varchar(100) NOT NULL DEFAULT '0',
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userIDtoken` (`userID`,`authToken`),
  UNIQUE KEY `groupIDtoken` (`groupID`,`authToken`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `auth_groups_groups`;
CREATE TABLE `auth_groups_groups` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `childGroup` int(10) unsigned NOT NULL,
  `parentGroup` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `childGroup` (`childGroup`,`parentGroup`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `auth_users_groups`;
CREATE TABLE `auth_users_groups` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(10) unsigned NOT NULL,
  `group` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user` (`user`,`group`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
