SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `artikel`;
CREATE TABLE `artikel` (
  `id` bigint(20) NOT NULL,
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
  `MWST` varchar(25) DEFAULT NULL,
  `dirty` tinyint(1) DEFAULT NULL,
  `stand` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `preise`;
CREATE TABLE `preise` (
  `id` bigint(20) NOT NULL,
  `artikel_id` bigint(20) NOT NULL,
  `standort_id` int(11) NOT NULL,
  `eek` decimal(6,2) DEFAULT NULL,
  `evk` decimal(6,2) DEFAULT NULL,
  `avk` decimal(6,2) DEFAULT NULL,
  `lager` tinyint(1) DEFAULT NULL,
  `bestand` smallint(5) UNSIGNED DEFAULT NULL,
  `preisaktion` varchar(50) DEFAULT NULL,
  `ap` decimal(6,2) DEFAULT NULL,
  `kalkulationsmodell` varchar(100) DEFAULT NULL,
  `dirty` tinyint(1) DEFAULT NULL,
  `stand` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `pzns`;
CREATE TABLE `pzns` (
  `id` bigint(20) NOT NULL,
  `artikel_id` bigint(20) NOT NULL,
  `standort_id` int(11) DEFAULT NULL,
  `pzn` int(11) DEFAULT NULL,
  `dirty` tinyint(1) DEFAULT NULL,
  `stand` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `standorte`;
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

ALTER TABLE `artikel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pzn` (`pzn`);

ALTER TABLE `preise`
  ADD PRIMARY KEY (`id`),
  ADD KEY `artikel_id` (`artikel_id`),
  ADD KEY `standort_id` (`standort_id`);

ALTER TABLE `pzns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `artikel_id` (`artikel_id`);

ALTER TABLE `standorte`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idf` (`idf`);

ALTER TABLE `artikel`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

ALTER TABLE `preise`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pzns`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

ALTER TABLE `standorte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;