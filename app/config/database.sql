-- Create database
CREATE DATABASE IF NOT EXISTS `digitalmente` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `digitalmente`;

-- Table structure for argomenti (topics)
CREATE TABLE IF NOT EXISTS `argomenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `argomento` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for documenti (documents)
CREATE TABLE IF NOT EXISTS `documenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `argomenti_id` int(11) DEFAULT NULL,
  `autore` varchar(250) DEFAULT NULL,
  `titolo` varchar(250) DEFAULT NULL,
  `collana` varchar(250) DEFAULT NULL,
  `traduzione` varchar(250) DEFAULT NULL,
  `editore` varchar(250) DEFAULT NULL,
  `anno_pubblicazione` varchar(50) DEFAULT NULL,
  `pagine` varchar(50) DEFAULT NULL,
  `tipologia_doc` int(11) DEFAULT NULL COMMENT '1=libro, 2=rivista, 3=video',
  `indice` text DEFAULT NULL,
  `bibliografia` text DEFAULT NULL,
  `mese` varchar(50) DEFAULT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `sommario` text DEFAULT NULL,
  `regia` varchar(250) DEFAULT NULL,
  `montaggio` varchar(250) DEFAULT NULL,
  `argomento_trattato` varchar(250) DEFAULT NULL,
  `foto` varchar(250) DEFAULT NULL,
  `data_inserimento` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `argomenti_id` (`argomenti_id`),
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

-- Insert default admin user (password: admin123)
INSERT INTO `utenti` (`nominativo`, `password`, `email`, `ruolo`) VALUES
('Amministratore', '$2y$10$mFvKUZ9XLd5QP8BZnVrO8.vQ0WGoawE04YZ8NRgQYs7wCt.8Ns1s6', 'admin@example.com', 1);
