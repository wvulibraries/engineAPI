--
-- Table structure for table `authorizations`
--

DROP TABLE IF EXISTS `authorizations`;
CREATE TABLE `authorizations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userID` int(10) unsigned DEFAULT NULL,
  `groupID` int(10) unsigned DEFAULT NULL,
  `authToken` varchar(32) NOT NULL DEFAULT '',
  `permissions` varchar(100) NOT NULL DEFAULT '0',
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userIDtoken` (`userID`,`authToken`),
  UNIQUE KEY `groupIDtoken` (`groupID`,`authToken`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
