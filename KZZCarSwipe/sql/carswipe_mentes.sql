-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: carswipe
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
-- Current Database: `carswipe`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `carswipe` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_hungarian_ci */;

USE `carswipe`;

--
-- Table structure for table `car_images`
--

DROP TABLE IF EXISTS `car_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `car_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`image_id`),
  KEY `car_id` (`car_id`),
  CONSTRAINT `car_images_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `car_images`
--

LOCK TABLES `car_images` WRITE;
/*!40000 ALTER TABLE `car_images` DISABLE KEYS */;
INSERT INTO `car_images` VALUES (1,1,'Autok/bmwm4f82.jpg',0),(2,2,'Autok/bmwm3g80.jpg',0),(3,3,'Autok/e46320d.jpg',0),(4,4,'Autok/e60535d.jpg',0),(10,9,'uploads/8d9ebb087ec8d98ea269dfdd.webp',0),(11,11,'Autok/porschetaycan.jpg',0),(12,12,'Autok/teslamodels.jpg',0),(13,13,'Autok/bmw530eg30.jpg',0),(14,1,'Autok/bmwm4f82interior.jpg',1),(15,2,'Autok/bmwm3g80interior.webp',1),(16,3,'Autok/e46320dinterior.jpg',1),(17,11,'Autok/taycaninterior.avif',1),(21,15,'Autok/ferrari458spider.jpg',0),(22,16,'Autok/ferrari488pista.jpg',0),(24,15,'Autok/ferrari458spiderinterior.webp',1),(25,16,'Autok/ferrari488pistainterior.jpg',1);
/*!40000 ALTER TABLE `car_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cars`
--

DROP TABLE IF EXISTS `cars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cars` (
  `car_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(80) NOT NULL,
  `year` int(11) NOT NULL,
  `mileage` int(11) NOT NULL,
  `fuel` varchar(20) NOT NULL,
  `transmission` varchar(20) NOT NULL,
  `body_type` varchar(30) NOT NULL,
  `power` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `letrehozva` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`car_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cars`
--

LOCK TABLES `cars` WRITE;
/*!40000 ALTER TABLE `cars` DISABLE KEYS */;
INSERT INTO `cars` VALUES (1,1,'BMW','M4 (F82)',2016,86000,'Benzin','Automata','Kupé',431,16900000,'Szép állapotú BMW M4 F82, végig márkaszervizben szervizelve. M Sport ülések, Harman Kardon hangrendszer, adaptív futómű, karbon tető.','2026-11-16 14:32:49'),(2,1,'BMW','M3 Competition (G80)',2022,34000,'Benzin','Automata','Szedán',510,33500000,'BMW M3 Competition G80, karbon tető, lézer fényszóró, head-up display. Garázsban tartott, sérülésmentes autó.','2026-11-16 14:32:49'),(3,1,'BMW','320d (E46)',2003,284000,'Dízel','Manuális','Szedán',150,1000000,'Megbízható E46 320d, friss műszakival. Klíma, tempomat, ülésfűtés. Korának megfelelő állapotban, rozsdamentes küszöbökkel.','2026-11-16 14:32:49'),(4,1,'BMW','535d (E60)',2006,312000,'Dízel','Automata','Szedán',272,2650000,'E60 535d M-csomaggal, bőr belső, navigáció, xenon. Vezérlés és turbók cserélve, számlákkal igazolt szerviztörténet.','2026-11-16 14:32:49'),(9,4,'BMW','M1',1979,2000,'Benzin','Manuális','Szedán',273,14000000,'nem rossz kis auto','2026-11-16 14:54:42'),(11,1,'Porsche','Taycan 4S',2021,42000,'Elektromos','Automata','Szedán',530,31900000,'Porsche Taycan 4S nagyobb akkumulátorral, légrugóval és panorámatetővel. Gyári garanciás akkumulátor, 800 voltos villámtöltés.','2026-11-16 16:17:00'),(12,1,'Tesla','Model S',2018,96000,'Elektromos','Automata','Szedán',422,15500000,'Tesla Model S összkerékhajtással, Autopilottal és prémium belsővel. Az akkumulátor jó állapotú, a hatótáv továbbra is 450 km körüli.','2026-11-16 16:17:00'),(13,1,'BMW','530e (G30)',2019,118000,'Hibrid','Automata','Szedán',252,12900000,'BMW 530e plug-in hibrid, tisztán elektromosan kb. 40 km-t megy. Bőr belső, adaptív LED, digitális műszerfal, vezetett szervizkönyv.','2026-11-16 16:17:00'),(15,1,'Ferrari','458 Spider',2013,31000,'Benzin','Automata','Kabrió',570,89900000,'Ferrari 458 Spider szívó V8-as motorral és nyitható keménytetővel. Karbon-kerámia fékek, sportkipufogó, teljes márkaszerviz-történet.','2026-11-16 16:48:05'),(16,1,'Ferrari','488 Pista',2019,12000,'Benzin','Automata','Kupé',720,149000000,'Ferrari 488 Pista, a 488 pályára hangolt változata. Karbon ülések és karbon belső, versenycsíkos fényezés, garázsban tartott gyűjtői darab.','2026-11-16 16:48:05');
/*!40000 ALTER TABLE `cars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `from_buyer` tinyint(1) NOT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `letrehozva` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `car_id` (`car_id`),
  KEY `buyer_id` (`buyer_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (8,13,4,1,'Hello',0,'2026-11-16 16:22:45'),(9,13,4,0,'Szia! Igen, a BMW 530e (G30) még eladó. Miben segíthetek?',1,'2026-11-16 16:22:47'),(10,13,4,1,'menyibe kerul?',0,'2026-11-16 16:23:03'),(11,13,4,0,'Ezt most nem tudom pontosan megmondani, de személyesen mindent megmutatok. Mikor tudnád megnézni?',1,'2026-11-16 16:23:05'),(12,13,4,1,'rendben holnap 12 kor',0,'2026-11-16 16:23:26'),(13,13,4,0,'Persze, meg lehet nézni és ki is lehet próbálni. Hétköznap délután vagy hétvégén érek rá, neked mikor lenne jó?',1,'2026-11-16 16:23:28'),(14,13,4,1,'12 kor indulok  hargita megyábol',0,'2026-11-16 16:23:56'),(15,13,4,0,'Köszi az üzenetet! A BMW 530e (G30) még eladó. Kérdezhetsz az áráról, a futott km-ről, az állapotáról, vagy egyeztethetünk megtekintést.',1,'2026-11-16 16:23:58'),(16,13,4,1,'es menyi kilometer van benne pontosan ?',0,'2026-11-16 16:24:27'),(17,13,4,0,'118 000 km van benne, a kilométeróra-állás igazolt.',1,'2026-11-16 16:24:29'),(18,13,4,1,'phu az eleg sok nem tudod visza tekerni',0,'2026-11-16 16:24:53'),(19,13,4,0,'Jó kérdés. Írd meg, mi érdekel pontosan: ár, km, évjárat, fogyasztás vagy állapot?',1,'2026-11-16 16:24:55'),(20,13,4,1,'ár',0,'2026-11-16 16:25:01'),(21,13,4,0,'Az irányár 12.900.000 Ft.',1,'2026-11-16 16:25:03'),(22,13,4,1,'az sok ide adod masfélért ?',0,'2026-11-16 16:25:15'),(23,13,4,0,'Ezt most nem tudom pontosan megmondani, de személyesen mindent megmutatok. Mikor tudnád megnézni?',1,'2026-11-16 16:25:17'),(24,13,4,1,'szia',0,'2026-11-16 16:25:26'),(25,13,4,0,'Szia! Igen, a BMW 530e (G30) még eladó. Miben segíthetek?',1,'2026-11-16 16:25:28'),(26,13,4,1,'viszlát',0,'2026-11-16 16:25:33'),(27,13,4,0,'Köszi az üzenetet! A BMW 530e (G30) még eladó. Kérdezhetsz az áráról, a futott km-ről, az állapotáról, vagy egyeztethetünk megtekintést.',1,'2026-11-16 16:25:35');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `swipes`
--

DROP TABLE IF EXISTS `swipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `swipes` (
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `liked` tinyint(1) NOT NULL,
  `letrehozva` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`car_id`),
  KEY `car_id` (`car_id`),
  CONSTRAINT `swipes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `swipes_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `swipes`
--

LOCK TABLES `swipes` WRITE;
/*!40000 ALTER TABLE `swipes` DISABLE KEYS */;
INSERT INTO `swipes` VALUES (5,2,1,'2026-11-16 15:50:01'),(5,4,1,'2026-11-16 15:42:04'),(5,9,1,'2026-11-16 15:42:03');
/*!40000 ALTER TABLE `swipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `letrehozva` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'CarSwipe Demo','demo@carswipe.local','!nincs-belepes','2026-11-16 14:32:49'),(4,'Alma','alma@gmail.com','$2y$10$RD/mbcGoDinU9JITIIVj1O/N32ovhweXtdcdsOUgSeloVD0q45sti','2026-11-16 14:43:16'),(5,'Korte','korte@gmail.com','$2y$10$3PuBp7PewVsBRcRBWRg7uuq0MPecYmzN7i0MgCVVzEphLuwlMAumu','2026-11-16 15:02:03');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-16 16:48:24
