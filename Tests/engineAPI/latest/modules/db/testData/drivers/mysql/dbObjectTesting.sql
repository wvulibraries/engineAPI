SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for dbObjectTesting
-- ----------------------------
CREATE TABLE IF NOT EXISTS `dbObjectTesting` (
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `a` varchar(255) DEFAULT NULL,
  `b` varchar(255) DEFAULT NULL,
  `c` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of dbObjectTesting
-- ----------------------------
TRUNCATE `dbObjectTesting`;
INSERT INTO `dbObjectTesting` VALUES ('simpleSelect', 'some', 'intersting', 'data', 'here');
INSERT INTO `dbObjectTesting` VALUES ('transTest', '', null, null, null);
