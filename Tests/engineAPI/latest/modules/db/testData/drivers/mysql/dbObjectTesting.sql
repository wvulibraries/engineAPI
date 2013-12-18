SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for dbObjectTesting
-- ----------------------------
DROP TABLE IF EXISTS `dbObjectTesting`;
CREATE TABLE `dbObjectTesting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `value` varchar(255) NOT NULL,
  `a` varchar(255) NULL,
  `b` varchar(255) NULL,
  `c` varchar(255) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
