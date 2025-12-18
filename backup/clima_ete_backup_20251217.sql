-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: terr6836_clima_ete
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `clima_config`
--

DROP TABLE IF EXISTS `clima_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clima_config` (
  `chave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clima_config`
--

LOCK TABLES `clima_config` WRITE;
/*!40000 ALTER TABLE `clima_config` DISABLE KEYS */;
INSERT INTO `clima_config` VALUES ('cron_key','protegido'),('setup_done','2025-12-11T20:38:59+01:00'),('thinger_device','nodemcu_clima'),('thinger_resource','weather'),('thinger_token','eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJkZXYiOiJub2RlbWN1X2NsaW1hIiwiaWF0IjoxNzY1MzIyOTUyLCJqdGkiOiI2OTM4YjBjOGI0ODc0NGY0MTAxMGNkZmYiLCJyZXMiOlsid2VhdGhlciJdLCJzdnIiOiJ1cy1lYXN0LmF3cy50aGluZ2VyLmlvIiwidXNyIjoibGVvbGltbWFiciJ9.Y0t_F5PcIFPye0Pno0CsGj4b9INTM_yYO09ccLZ_V8A'),('thinger_user','leolimmabr');
/*!40000 ALTER TABLE `clima_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clima_historico`
--

DROP TABLE IF EXISTS `clima_historico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clima_historico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_registro` datetime DEFAULT current_timestamp(),
  `temp` float DEFAULT NULL,
  `hum` int(11) DEFAULT NULL,
  `pres` float DEFAULT NULL,
  `uv` float DEFAULT NULL,
  `gas` float DEFAULT NULL,
  `chuva` float DEFAULT NULL,
  `chuva_status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clima_historico`
--

LOCK TABLES `clima_historico` WRITE;
/*!40000 ALTER TABLE `clima_historico` DISABLE KEYS */;
INSERT INTO `clima_historico` VALUES (1,'2025-12-09 20:36:37',33.5475,40,958.68,0.548387,138.546,100,'Chovendo'),(2,'2025-12-09 20:36:40',33.7757,40,958.68,0.258065,148.809,100,'Chovendo'),(3,'2025-12-09 20:36:43',33.6541,41,958.7,0.258065,143.984,100,'Chovendo'),(4,'2025-12-09 20:36:48',33.7948,41,958.68,0.548387,149.468,100,'Chovendo'),(5,'2025-12-09 20:38:25',33.5037,40,958.69,0.516129,137.195,100,'Chovendo'),(6,'2025-12-09 20:38:27',33.6957,40,958.69,0.516129,148.416,100,'Chovendo'),(7,'2025-12-09 20:40:11',33.5237,40,958.71,0.516129,137.755,100,'Chovendo'),(8,'2025-12-09 20:42:52',33.7268,41,958.71,0.548387,142.766,100,'Chovendo'),(9,'2025-12-09 20:43:13',33.4843,41,958.74,0.548387,131.939,100,'Chovendo'),(10,'2025-12-09 21:55:24',33.1472,42,958.93,0.548387,134.569,100,'Chovendo'),(11,'2025-12-09 22:05:32',33.2104,42,958.94,0.516129,137.867,100,'Chovendo'),(12,'2025-12-11 16:41:32',34.8426,36,954.57,0.548387,20.166,100,'Chovendo'),(13,'2025-12-11 16:41:45',34.7883,35,954.58,0.290323,28.399,100,'Chovendo'),(14,'2025-12-11 16:58:12',35.9012,32,954.57,0.548387,136.639,100,'Chovendo'),(15,'2025-12-11 17:05:48',35.7739,33,954.58,0.516129,145.474,100,'Chovendo'),(16,'2025-12-11 17:29:09',35.7447,32,954.67,0.516129,158.005,100,'Chovendo'),(17,'2025-12-11 17:29:14',35.6225,31,954.65,0.516129,155.961,100,'Chovendo'),(18,'2025-12-11 17:33:28',35.4504,31,954.68,0.548387,150.133,100,'Chovendo'),(19,'2025-12-11 17:33:47',35.6206,31,954.69,0.516129,156.539,100,'Chovendo'),(20,'2025-12-11 17:44:20',35.4422,32,954.74,0.548387,150.401,100,'Chovendo'),(21,'2025-12-11 23:57:41',33.3275,39,957.62,0.548387,138.546,100,'Chovendo');
/*!40000 ALTER TABLE `clima_historico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clima_password_resets`
--

DROP TABLE IF EXISTS `clima_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clima_password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clima_password_resets`
--

LOCK TABLES `clima_password_resets` WRITE;
/*!40000 ALTER TABLE `clima_password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `clima_password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clima_users`
--

DROP TABLE IF EXISTS `clima_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clima_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clima_users`
--

LOCK TABLES `clima_users` WRITE;
/*!40000 ALTER TABLE `clima_users` DISABLE KEYS */;
INSERT INTO `clima_users` VALUES (5,'admin','$2y$10$JH1g8jWDMJdXI2q62UWoMOd7Udxl4igLIZHRt2ljCZ2Rh8SrHeN6i','Francisco Leonardo de Lima','leolimma.br@gmail.com','2025-12-14 09:58:26','admin'),(6,'professor','$2y$10$BN3mzk7jU3Vg3tR/PGRDcuZH2SUrjpRtw2EsKjQQpbvDBv8NTrrlW','Cleuton','eletivaete@gmail.com','2025-12-14 19:21:50','user');
/*!40000 ALTER TABLE `clima_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration` (`migration`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'V1__init_tables','2025-12-11 18:08:55');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-17 21:29:29
