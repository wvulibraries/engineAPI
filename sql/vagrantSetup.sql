DROP DATABASE IF EXISTS `EngineAPI`;
CREATE DATABASE IF NOT EXISTS `EngineAPI`;

CREATE USER 'username'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON `EngineAPI`.* TO 'username'@'localhost';
USE `EngineAPI`;
