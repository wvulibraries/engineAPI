DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ldapDN` varchar(256) DEFAULT NULL,
  `name` varchar(32) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ldapDN` (`ldapDN`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(16) DEFAULT NULL,
  `password` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `authToken` varchar(64) NOT NULL,
  `permission` varchar(128) NOT NULL,
  `isEmpty` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`authToken`,`permission`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `groups_groups`;
CREATE TABLE `groups_groups` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `childGroup` int(10) unsigned NOT NULL,
  `parentGroup` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `childGroup` (`childGroup`,`parentGroup`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `users_groups`;
CREATE TABLE `users_groups` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(10) unsigned NOT NULL,
  `group` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user` (`user`,`group`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
