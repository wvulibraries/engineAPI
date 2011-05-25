--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `authToken` varchar(64) NOT NULL,
  `permission` varchar(128) NOT NULL,
  `isEmpty` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`authToken`,`permission`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
