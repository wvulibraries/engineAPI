--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ldapDN` varchar(256) DEFAULT NULL,
  `name` varchar(32) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ldapDN` (`ldapDN`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
