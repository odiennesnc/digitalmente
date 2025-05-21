-- Schema aggiornato per il database digitalmente
-- Data: 20 maggio 2025

-- Create database
CREATE DATABASE IF NOT EXISTS `myodnit_digitalmente` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `myodnit_digitalmente`;

-- Table structure for argomenti (topics)
CREATE TABLE IF NOT EXISTS `argomenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `argomento` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for documenti (documents) - Versione ottimizzata
CREATE TABLE IF NOT EXISTS `documenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `argomenti_id` int(11) DEFAULT NULL,
  `tipologia_doc` int(11) NOT NULL COMMENT '1=libro, 2=rivista, 3=video',
  `titolo` varchar(250) NOT NULL,
  
  -- Campi comuni
  `foto` varchar(250) DEFAULT NULL,
  `anno_pubblicazione` varchar(50) DEFAULT NULL,
  `data_inserimento` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  -- Campi per libri (tipologia_doc = 1)
  `autore` varchar(250) DEFAULT NULL,
  `editore` varchar(250) DEFAULT NULL,
  `collana` varchar(250) DEFAULT NULL,
  `traduzione` varchar(250) DEFAULT NULL,
  `pagine` varchar(50) DEFAULT NULL,
  `indice` text DEFAULT NULL,
  `bibliografia` text DEFAULT NULL,
  
  -- Campi per riviste (tipologia_doc = 2)
  `mese` varchar(50) DEFAULT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `sommario` text DEFAULT NULL,
  
  -- Campi per video (tipologia_doc = 3)
  `regia` varchar(250) DEFAULT NULL,
  `montaggio` varchar(250) DEFAULT NULL,
  `argomento_trattato` text DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  KEY `argomenti_id` (`argomenti_id`),
  KEY `tipologia_doc` (`tipologia_doc`),
  CONSTRAINT `documenti_ibfk_1` FOREIGN KEY (`argomenti_id`) REFERENCES `argomenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for utenti (users)
CREATE TABLE IF NOT EXISTS `utenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nominativo` varchar(250) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `ruolo` int(11) DEFAULT NULL COMMENT '1=admin, 2=editor',
  `last_login` date DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `reset_id` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for todo tasks
CREATE TABLE IF NOT EXISTS `todo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utente_id` int(11) NOT NULL,
  `task` text NOT NULL,
  `data_scadenza` date DEFAULT NULL,
  `completato` tinyint(1) DEFAULT 0,
  `data_inserimento` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utente_id` (`utente_id`),
  CONSTRAINT `todo_ibfk_1` FOREIGN KEY (`utente_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
