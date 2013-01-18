-- MySQL dump 10.13  Distrib 5.1.66, for redhat-linux-gnu (x86_64)
--
-- Host: localhost    Database: testy
-- ------------------------------------------------------
-- Server version	5.1.66

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
-- Table structure for table `linking`
--

DROP TABLE IF EXISTS `linking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `linking` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `test` int(11) DEFAULT NULL,
  `tset` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `linking`
--

LOCK TABLES `linking` WRITE;
/*!40000 ALTER TABLE `linking` DISABLE KEYS */;
INSERT INTO `linking` VALUES (2,1,3),(1,1,2),(3,2,1),(4,2,2),(5,1,1),(6,4,2);
/*!40000 ALTER TABLE `linking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revisions`
--

DROP TABLE IF EXISTS `revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revisions` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `productionTable` varchar(30) DEFAULT NULL,
  `primaryID` int(11) DEFAULT NULL,
  `secondaryID` int(10) unsigned DEFAULT NULL,
  `metadata` text,
  `digitalObjects` blob,
  `relatedData` text,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revisions`
--

LOCK TABLES `revisions` WRITE;
/*!40000 ALTER TABLE `revisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `revisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test`
--

DROP TABLE IF EXISTS `test`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `modifiedTime` int(10) unsigned DEFAULT NULL,
  `name` varchar(20) DEFAULT NULL,
  `digitalObjects` blob,
  `sentence` varchar(100) DEFAULT NULL,
  `html` text,
  `test2` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test`
--

LOCK TABLES `test` WRITE;
/*!40000 ALTER TABLE `test` DISABLE KEYS */;
INSERT INTO `test` VALUES (1,1358272517,'Custom Disp Function','data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBhASEBQUExQREBQWFx8ZGRYVFRgdGxkeFR4eGxkXGB4ZIigeHxkrHRwfHy8jJCcpLS04HB49NTcqNScrLSkBCQoKBQUFDQUFDSkYEhgpKSkpKSkpKSkpKSkpKSkpKSkpKSkpKSkpKSkpKSkpKSkpKSkpKSkpKSkpKSkpKSkpKf/AABEIAEQAVgMBIgACEQEDEQH/xAAbAAEAAgMBAQAAAAAAAAAAAAAABgcBBAUDAv/EADsQAAIBAwIDBQYDBQkBAAAAAAECAwAEEQUhBhIxBxMiQVEUIzJhcYEzQrEVUpGhwRckQ1Nyg5Kywgj/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AvClYzWc0ClKUEH7V4nlgtYA7RpcXccUrKcHkbm2+5AFa7cOXGkjvLEzXNqu8lm78xA83t2O4IG/Idj9a6HatC37MkkQZaB0nX/ZYMf5ZqTWN2s0Uci7q6BgR6MMj9aDz0fV4bmFJoWDxuMg/0I6hh0IPSt6oJrFu2lTtdwqTaStm6iXpGTt7Sg/7gddj5VNba6R0V0YOrDIYHIIPQgig9qUpmgUpSgrfipdQm1hYLe7a05LXvY1xlJHDkOJB5jGB548sVJ+EOKPakZJV7m6hbkniJ3Vh+ZfVGG4PzqO9pt8tldafqByRFI0TqvxMkq9APPBGa50sGrXl5De2lqlgQpUvcy7yxnoskab7Hcb5FBaeacwqBalb6tHGZbrU7WyiX4jDbDAzsN5STmtC5sLMPbLc6vqEzXO8XLMERweh92uwOQOo60FgaxDHJBJHIVVHRkJY4HiBHnUF7OuNbOHS4UurmCJ4S0JDSLn3TFVIA3xy4rRjs+HO8vFMUlxJZqWmErTSHwHDcvOxDHm2Neq8T6RFZW11Bp3Ok8ndqFt05lIODk4PmMD1oO7cdqWkNzIJTcZBBWOGR+YEYI2XBqJcLcWzWcssMFlqVzZE80AMDK8RY5aMc+AY9yRUzi4nddRazSzkWNYu8EwACEkZ5egA/d+tc2PirWZdOmlSwEd0svIkLkkMmRl8Eg+vnv1oPf8AtDuhudJ1LH0j/Tmrb4Y7TLC9kMSO0U42MMq8rZHUDyP2Nd62WSW2UTDu5HjxIqn4WZfEAfrVU6f/APPSRv3hvZldWyrRoAVwcqck55vpQXIGpVP6lxZqdhem1hnXWD3fOysiq8RyB4mQjPXoTmlBINb0hNZumhkz7FasQSpw0s+Nwp/dQHB9ST6VxrmS+tZhBpt9JqTggGCaNZREPPvJhjl+mc/KujoPZddIndXWoTyw8zHuYcxB+c8zGRh42JJJOf41PNK0W3toxHBGkKD8qDH3PmT8zvQQjU7rUpIGhv8ASo7yJsc3sswIODkEI5DD7Ma804p0hDAJ7O4tWtgFiM1pJ7vG3hYAjy61Y+Kw0YPXegidnxvoZd2S5sleT42yFZ8beMkAn71q6z2lWUJWG1H7QuG+CG2w3/JhlVH86lN3oNrIPHBC/wDqjU/qKgvZlpkXt2rSRokaC5ESBVAACDLAAbDcjpQbEek6/eYae5i0xDv3VsgeQfJnbbP0P2rT1rgDUIIXmtdUv3lQcwSdwyMF3KkdOnTarMAqDdoWqSTPFplsxWa6/FYf4UA/EY+hPwj7/Kg5vDva489tERY3txOVHN3UWIyRsSGbw4863LiHiC98PudIhbqVbvZ8egIwqnH8PWprpumxwQpDEoSONQqqPID+tbWKCO8I8C2unoREGeR95JXPM7n5n0+QrFSSlBjFZpSgUpSgwagvY8vNZTTec93NKfuwT/xU1vc92+OvKf0NQrsmuETRYGZlRV7wsxOAPG2ST5b0En4j16KztpJ5ThUGcebE7Kq/Mnb71H+z/QpQJL67H97u8MR/lRj8OEfQYJHr9K59gh1m8W5cEafbN7hSCPaJBsZmB/IMDlFWHQZpSlApSlApSlApSlB8v0qhtA4eSXV7jTnluDZRSs4t+8IQkkHDAdRk9KxSgva0gVEVVUIqjAUDAAHQD5V7UpQKUpQKUpQf/9k=','this is a sentence. it is not to long.','<!DOCTYPE html>\n<html>\n\n<head>\n	<title>Revision Control Tests</title>\n\n</head>\n\n<body>\n\n<p>Hello World!<p>\n\n</body>\n</html>','test2 test');
/*!40000 ALTER TABLE `test` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'testy'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-01-18 10:21:00
