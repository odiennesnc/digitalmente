-- Creazione delle nuove tabelle per la struttura a tabelle separate

-- 1. Tabella base per tutti i documenti
CREATE TABLE IF NOT EXISTS documenti_base (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titolo VARCHAR(255) NOT NULL,
    anno_pubblicazione VARCHAR(50),
    argomenti_id INT,
    foto VARCHAR(255),
    tipologia_doc TINYINT NOT NULL, -- Manteniamo questo campo per facilità di query
    data_inserimento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (argomenti_id) REFERENCES argomenti(id) ON DELETE SET NULL
);

-- 2. Tabella per i libri
CREATE TABLE IF NOT EXISTS documenti_libri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    autore VARCHAR(255),
    editore VARCHAR(255),
    collana VARCHAR(255),
    traduzione VARCHAR(255),
    pagine VARCHAR(50),
    indice TEXT,
    bibliografia TEXT,
    FOREIGN KEY (documento_id) REFERENCES documenti_base(id) ON DELETE CASCADE
);

-- 3. Tabella per le riviste
CREATE TABLE IF NOT EXISTS documenti_riviste (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    editore VARCHAR(255),
    mese VARCHAR(50),
    numero VARCHAR(50),
    sommario TEXT,
    FOREIGN KEY (documento_id) REFERENCES documenti_base(id) ON DELETE CASCADE
);

-- 4. Tabella per i video/documentari
CREATE TABLE IF NOT EXISTS documenti_video (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    autore VARCHAR(255),
    regia VARCHAR(255),
    montaggio VARCHAR(255),
    argomento_trattato TEXT,
    FOREIGN KEY (documento_id) REFERENCES documenti_base(id) ON DELETE CASCADE
);

-- Migrare i dati dalla tabella documenti alle nuove tabelle
INSERT INTO documenti_base (id, titolo, anno_pubblicazione, argomenti_id, foto, tipologia_doc, data_inserimento)
SELECT id, titolo, anno_pubblicazione, argomenti_id, foto, tipologia_doc, data_inserimento
FROM documenti;

-- Migrare i dati dei libri
INSERT INTO documenti_libri (documento_id, autore, editore, collana, traduzione, pagine, indice, bibliografia)
SELECT id, autore, editore, collana, traduzione, pagine, indice, bibliografia
FROM documenti
WHERE tipologia_doc = 1;

-- Migrare i dati delle riviste
INSERT INTO documenti_riviste (documento_id, editore, mese, numero, sommario)
SELECT id, editore, mese, numero, sommario
FROM documenti
WHERE tipologia_doc = 2;

-- Migrare i dati dei video
INSERT INTO documenti_video (documento_id, autore, regia, montaggio, argomento_trattato)
SELECT id, autore, regia, montaggio, argomento_trattato
FROM documenti
WHERE tipologia_doc = 3;

-- Per sicurezza, creiamo un backup della tabella originale
-- CREATE TABLE documenti_backup AS SELECT * FROM documenti;

-- Una volta verificato che tutto funziona, si può eliminare la vecchia tabella
-- DROP TABLE documenti;
