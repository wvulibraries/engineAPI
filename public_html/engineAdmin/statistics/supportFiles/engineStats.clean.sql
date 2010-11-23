-- MySQL dump 10.13  Distrib 5.1.50, for redhat-linux-gnu (i686)
--
-- Host: localhost    Database: engineCMS
-- ------------------------------------------------------
-- Server version	5.1.50

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `engineConfig`
--

DROP TABLE IF EXISTS `engineConfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `engineConfig` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `engineConfig`
--

LOCK TABLES `engineConfig` WRITE;
/*!40000 ALTER TABLE `engineConfig` DISABLE KEYS */;
INSERT INTO `engineConfig` VALUES (1,'lastLogProcessed','0');
/*!40000 ALTER TABLE `engineConfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logBrowsers`
--

DROP TABLE IF EXISTS `logBrowsers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logBrowsers` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint(4) unsigned NOT NULL DEFAULT '0',
  `month` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `resource` varchar(255) NOT NULL DEFAULT '',
  `browser` varchar(255) NOT NULL DEFAULT '',
  `nonHuman` tinyint(1) NOT NULL DEFAULT '0',
  `onCampusCount` bigint(20) unsigned NOT NULL DEFAULT '0',
  `offCampusCount` bigint(20) unsigned NOT NULL DEFAULT '0',
  `os` varchar(255) NOT NULL DEFAULT '',
  `mobile` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logBrowsers`
--

LOCK TABLES `logBrowsers` WRITE;
/*!40000 ALTER TABLE `logBrowsers` DISABLE KEYS */;
/*!40000 ALTER TABLE `logBrowsers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logHits`
--

DROP TABLE IF EXISTS `logHits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logHits` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint(4) unsigned NOT NULL,
  `month` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `day` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `hour` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `mobilevisits` bigint(20) NOT NULL DEFAULT '0',
  `nonmobilevisits` bigint(20) NOT NULL DEFAULT '0',
  `mobilehits` bigint(20) NOT NULL DEFAULT '0',
  `nonmobilehits` bigint(20) NOT NULL DEFAULT '0',
  `resource` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `logHits` (`year`,`month`,`day`,`hour`,`resource`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logHits`
--

LOCK TABLES `logHits` WRITE;
/*!40000 ALTER TABLE `logHits` DISABLE KEYS */;
/*!40000 ALTER TABLE `logHits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logURLs`
--

DROP TABLE IF EXISTS `logURLs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logURLs` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint(4) unsigned NOT NULL DEFAULT '0',
  `month` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `mobilehits` bigint(20) NOT NULL DEFAULT '0',
  `nonmobilehits` bigint(20) NOT NULL DEFAULT '0',
  `url` varchar(255) DEFAULT NULL,
  `referrer` text,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logURLs`
--

LOCK TABLES `logURLs` WRITE;
/*!40000 ALTER TABLE `logURLs` DISABLE KEYS */;
/*!40000 ALTER TABLE `logURLs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'engineCMS'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2010-10-13 14:08:17
