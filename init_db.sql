SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `artikel` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pzn` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `df` varchar(5) DEFAULT NULL,
  `pm` varchar(20) DEFAULT NULL,
  `pe` varchar(10) DEFAULT NULL,
  `hersteller` varchar(8) DEFAULT NULL,
  `ek` decimal(6,2) DEFAULT NULL,
  `vk` decimal(6,2) DEFAULT NULL,
  `AM` tinyint(1) DEFAULT NULL,
  `DC` tinyint(1) DEFAULT NULL,
  `MP` tinyint(1) DEFAULT NULL,
  `LM` tinyint(1) DEFAULT NULL,
  `KM` tinyint(1) DEFAULT NULL,
  `ABDATA` tinyint(1) DEFAULT NULL,
  `dirty` tinyint(1) DEFAULT NULL,
  `stand` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pzn` (`pzn`)
) ENGINE=InnoDB AUTO_INCREMENT=6317 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci	

CREATE TABLE `preise` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `artikel_id` bigint(20) NOT NULL,
  `standort_id` int(11) NOT NULL,
  `eek` decimal(6,2) DEFAULT NULL,
  `evk` decimal(6,2) DEFAULT NULL,
  `avk` decimal(6,2) DEFAULT NULL,
  `lager` tinyint(1) DEFAULT NULL,
  `bestand` smallint(5) unsigned DEFAULT NULL,
  `preisaktion` varchar(50) DEFAULT NULL,
  `ap` decimal(6,2) DEFAULT NULL,
  `kalkulationsmodell` varchar(100) DEFAULT NULL,
  `dirty` tinyint(1) DEFAULT NULL,
  `stand` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `artikel_id` (`artikel_id`),
  KEY `standort_id` (`standort_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10513 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

CREATE TABLE `pzns` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `artikel_id` bigint(20) NOT NULL,
  `standort_id` int(11) DEFAULT NULL,
  `pzn` int(11) DEFAULT NULL,
  `dirty` tinyint(1) DEFAULT NULL,
  `stand` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `artikel_id` (`artikel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci	

CREATE TABLE `standorte` (
  `id` int(11) NOT NULL,
  `idf` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `kurz` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `standorte` (`id`, `idf`, `name`, `kurz`) VALUES
(1, 3129183, 'Kemnath', 'KEM'),
(2, 3321957, 'Eschenbach', 'ESB'),
(3, 4549705, 'Windischeschenbach', 'WESB'),
(4, 4517740, 'Weiden', 'AEWEN');

ALTER TABLE `standorte`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idf` (`idf`);

ALTER TABLE `standorte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;