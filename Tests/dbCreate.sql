CREATE DATABASE engineAPITest;

CREATE USER 'user'@'localhost' IDENTIFIED BY 'passwd';
GRANT ALL PRIVILEGES ON engineAPITest.* TO 'user'@'localhost';

CREATE TABLE `localvarsTest` (`id` INT(10), `content` varchar(100), `user` varchar(20), `created` varchar(50));