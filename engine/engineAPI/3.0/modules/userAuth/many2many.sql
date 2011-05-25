--
-- Table structure for table `groups_groups`
--

DROP TABLE IF EXISTS `groups_groups`;
CREATE TABLE `groups_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `childGroup` int(10) unsigned NOT NULL,
  `parentGroup` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `childGroup` (`childGroup`,`parentGroup`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;



--
-- Table structure for table `users_groups`
--

DROP TABLE IF EXISTS `users_groups`;
CREATE TABLE `users_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(10) unsigned NOT NULL,
  `group` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user` (`user`,`group`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
