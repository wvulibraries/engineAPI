CREATE TABLE IF NOT EXISTS `sessions` (
  `ID` varchar(256) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL,
  `fingerprint` varchar(32) NOT NULL,
  `name` varchar(32) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `fingerprint` (`fingerprint`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;