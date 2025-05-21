<?php
/**
 * Funzioni per la gestione dei documenti con tabelle separate
 * Data: 20 maggio 2025
 * 
 * Questo file contiene le funzioni necessarie per gestire le operazioni CRUD sui documenti
 * utilizzando la nuova struttura del database con tabelle separate per tipologia.
 */

/**
 * Recupera un documento dal database in base al suo ID
 * @param mysqli $conn Connessione al database
 * @param int $id ID del documento da recuperare
 * @return array|null Array con i dati del documento o null se non trovato
 */
function getDocumentById($conn, $id) {
    try {
        // Prima recuperiamo i dati di base
        $query = "SELECT * FROM documenti_base WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Errore nella preparazione query base: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log("Errore nell'esecuzione query base: " . $stmt->error);
            return null;
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            return null;
        }
        
        $documento = $result->fetch_assoc();
        $stmt->close();
        
        // Ora recuperiamo i dati specifici in base alla tipologia
        $tipologia = $documento['tipologia_doc'];
        $specificTable = "";
        
        switch($tipologia) {
            case 1:
                $specificTable = "documenti_libri";
                break;
            case 2:
                $specificTable = "documenti_riviste";
                break;
            case 3:
                $specificTable = "documenti_video";
                break;
            default:
                return $documento; // Se non ha una tipologia specifica, restituisce solo i dati di base
        }
        
        $query = "SELECT * FROM {$specificTable} WHERE documento_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Errore nella preparazione query specifica: " . $conn->error);
            return $documento; // Restituisce almeno i dati di base
        }
        
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log("Errore nell'esecuzione query specifica: " . $stmt->error);
            return $documento; // Restituisce almeno i dati di base
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            // Unisci i dati specifici con quelli di base
            $specificData = $result->fetch_assoc();
            $documento = array_merge($documento, $specificData);
        }
        
        $stmt->close();
        return $documento;
        
    } catch (Exception $e) {
        error_log("Errore nel recupero del documento: " . $e->getMessage());
        return null;
    }
}

/**
 * Recupera tutti i documenti dal database
 * @param mysqli $conn Connessione al database
 * @param array $filters Filtri opzionali per la ricerca
 * @return array Array di documenti
 */
function getAllDocuments($conn, $filters = []) {
    try {
        // Query base per recuperare tutti i documenti
        $query = "
            SELECT db.*, a.argomento 
            FROM documenti_base db
            LEFT JOIN argomenti a ON db.argomenti_id = a.id";
        
        // Aggiungi filtri se presenti
        if (!empty($filters)) {
            $query .= " WHERE ";
            $whereConditions = [];
            
            if (isset($filters['tipologia_doc'])) {
                $whereConditions[] = "db.tipologia_doc = " . (int)$filters['tipologia_doc'];
            }
            
            if (isset($filters['argomenti_id'])) {
                $whereConditions[] = "db.argomenti_id = " . (int)$filters['argomenti_id'];
            }
            
            if (isset($filters['search'])) {
                $search = $conn->real_escape_string($filters['search']);
                $whereConditions[] = "(db.titolo LIKE '%{$search}%')";
            }
            
            $query .= implode(" AND ", $whereConditions);
        }
        
        $query .= " ORDER BY db.data_inserimento DESC";
        
        $result = $conn->query($query);
        if (!$result) {
            error_log("Errore nella query: " . $conn->error);
            return [];
        }
        
        $documenti = [];
        while ($row = $result->fetch_assoc()) {
            // Per ogni documento di base, recuperiamo i dati specifici
            $documento = $row;
            $id = $row['id'];
            $tipologia = $row['tipologia_doc'];
            
            // Determina la tabella specifica in base alla tipologia
            $specificTable = "";
            switch($tipologia) {
                case 1:
                    $specificTable = "documenti_libri";
                    break;
                case 2:
                    $specificTable = "documenti_riviste";
                    break;
                case 3:
                    $specificTable = "documenti_video";
                    break;
                default:
                    $documenti[] = $documento;
                    continue 2; // Salta al prossimo documento nell'iterazione esterna
            }
            
            // Recupera i dati specifici
            $specificQuery = "SELECT * FROM {$specificTable} WHERE documento_id = {$id}";
            $specificResult = $conn->query($specificQuery);
            
            if ($specificResult && $specificResult->num_rows > 0) {
                $specificData = $specificResult->fetch_assoc();
                $documento = array_merge($documento, $specificData);
            }
            
            $documenti[] = $documento;
        }
        
        return $documenti;
        
    } catch (Exception $e) {
        error_log("Errore nel recupero dei documenti: " . $e->getMessage());
        return [];
    }
}

/**
 * Inserisce un nuovo documento nel database
 * @param mysqli $conn Connessione al database
 * @param array $data Dati del documento da inserire
 * @return int|bool ID del documento inserito o false in caso di errore
 */
function insertDocument($conn, $data) {
    try {
        // Validazione dati
        if (!isset($data['tipologia_doc']) || !isset($data['titolo'])) {
            error_log("Dati del documento non validi: mancano tipologia o titolo");
            return false;
        }
        
        // Inizia la transazione
        $conn->begin_transaction();
        
        // 1. Inserimento nella tabella base
        $queryBase = "INSERT INTO documenti_base (
                        titolo, 
                        tipologia_doc, 
                        argomenti_id, 
                        anno_pubblicazione, 
                        foto, 
                        data_inserimento
                    ) VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmtBase = $conn->prepare($queryBase);
        if (!$stmtBase) {
            error_log("Errore nella preparazione della query base: " . $conn->error);
            $conn->rollback();
            return false;
        }
        
        $titolo = $data['titolo'];
        $tipologia = (int)$data['tipologia_doc'];
        $argomento_id = isset($data['argomenti_id']) ? $data['argomenti_id'] : null;
        $anno = isset($data['anno_pubblicazione']) ? $data['anno_pubblicazione'] : null;
        $foto = isset($data['foto']) ? $data['foto'] : null;
        
        $stmtBase->bind_param("siiss", $titolo, $tipologia, $argomento_id, $anno, $foto);
        
        if (!$stmtBase->execute()) {
            error_log("Errore nell'inserimento dei dati base: " . $stmtBase->error);
            $conn->rollback();
            return false;
        }
        
        $documento_id = $stmtBase->insert_id;
        $stmtBase->close();
        
        // 2. Inserimento nella tabella specifica in base alla tipologia
        $success = false;
        
        switch ($tipologia) {
            case 1: // Libro
                $queryLibro = "INSERT INTO documenti_libri (
                                documento_id, 
                                autore, 
                                editore, 
                                collana, 
                                traduzione, 
                                pagine, 
                                indice, 
                                bibliografia
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmtLibro = $conn->prepare($queryLibro);
                if (!$stmtLibro) {
                    error_log("Errore nella preparazione della query libro: " . $conn->error);
                    $conn->rollback();
                    return false;
                }
                
                $autore = isset($data['autore']) ? $data['autore'] : null;
                $editore = isset($data['editore']) ? $data['editore'] : null;
                $collana = isset($data['collana']) ? $data['collana'] : null;
                $traduzione = isset($data['traduzione']) ? $data['traduzione'] : null;
                $pagine = isset($data['pagine']) ? $data['pagine'] : null;
                $indice = isset($data['indice']) ? $data['indice'] : null;
                $bibliografia = isset($data['bibliografia']) ? $data['bibliografia'] : null;
                
                $stmtLibro->bind_param("issssss", $documento_id, $autore, $editore, $collana, $traduzione, $pagine, $indice, $bibliografia);
                $success = $stmtLibro->execute();
                
                if (!$success) {
                    error_log("Errore nell'inserimento dei dati del libro: " . $stmtLibro->error);
                }
                
                $stmtLibro->close();
                break;
                
            case 2: // Rivista
                $queryRivista = "INSERT INTO documenti_riviste (
                                documento_id, 
                                editore, 
                                mese, 
                                numero, 
                                sommario
                            ) VALUES (?, ?, ?, ?, ?)";
                
                $stmtRivista = $conn->prepare($queryRivista);
                if (!$stmtRivista) {
                    error_log("Errore nella preparazione della query rivista: " . $conn->error);
                    $conn->rollback();
                    return false;
                }
                
                $editore = isset($data['editore']) ? $data['editore'] : null;
                $mese = isset($data['mese']) ? $data['mese'] : null;
                $numero = isset($data['numero']) ? $data['numero'] : null;
                $sommario = isset($data['sommario']) ? $data['sommario'] : null;
                
                $stmtRivista->bind_param("issss", $documento_id, $editore, $mese, $numero, $sommario);
                $success = $stmtRivista->execute();
                
                if (!$success) {
                    error_log("Errore nell'inserimento dei dati della rivista: " . $stmtRivista->error);
                }
                
                $stmtRivista->close();
                break;
                
            case 3: // Video
                $queryVideo = "INSERT INTO documenti_video (
                                documento_id, 
                                autore, 
                                regia, 
                                montaggio, 
                                argomento_trattato
                            ) VALUES (?, ?, ?, ?, ?)";
                
                $stmtVideo = $conn->prepare($queryVideo);
                if (!$stmtVideo) {
                    error_log("Errore nella preparazione della query video: " . $conn->error);
                    $conn->rollback();
                    return false;
                }
                
                $autore = isset($data['autore']) ? $data['autore'] : null;
                $regia = isset($data['regia']) ? $data['regia'] : null;
                $montaggio = isset($data['montaggio']) ? $data['montaggio'] : null;
                $argomento_trattato = isset($data['argomento_trattato']) ? $data['argomento_trattato'] : null;
                
                $stmtVideo->bind_param("issss", $documento_id, $autore, $regia, $montaggio, $argomento_trattato);
                $success = $stmtVideo->execute();
                
                if (!$success) {
                    error_log("Errore nell'inserimento dei dati del video: " . $stmtVideo->error);
                }
                
                $stmtVideo->close();
                break;
                
            default:
                $success = true; // Nessuna tabella specifica per questo tipo
                break;
        }
        
        if (!$success) {
            $conn->rollback();
            return false;
        }
        
        // Commit della transazione
        $conn->commit();
        return $documento_id;
        
    } catch (Exception $e) {
        error_log("Errore nell'inserimento del documento: " . $e->getMessage());
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        return false;
    }
}

/**
 * Aggiorna un documento esistente nel database
 * @param mysqli $conn Connessione al database
 * @param array $data Dati del documento da aggiornare
 * @return bool True se l'aggiornamento ha avuto successo, False altrimenti
 */
function updateDocument($conn, $data) {
    try {
        if (!isset($data['id']) || empty($data['id'])) {
            throw new Exception("ID documento non specificato");
        }
        
        $document_id = (int)$data['id'];
        $tipologia = (int)$data['tipologia_doc'];
        
        // Verifica che il documento esista
        $documento_esistente = getDocumentById($conn, $document_id);
        if (!$documento_esistente) {
            throw new Exception("Documento non trovato");
        }
        
        // Inizia la transazione
        $conn->begin_transaction();
        
        // 1. Aggiornamento tabella base
        $queryBase = "UPDATE documenti_base SET 
                        titolo = ?,
                        tipologia_doc = ?,
                        argomenti_id = ?,
                        anno_pubblicazione = ?";
        
        // Gestione del campo foto (opzionale)
        if (isset($data['foto']) && !empty($data['foto'])) {
            $queryBase .= ", foto = ?";
        }
        
        $queryBase .= " WHERE id = ?";
        
        $stmtBase = $conn->prepare($queryBase);
        if (!$stmtBase) {
            error_log("Errore nella preparazione della query base: " . $conn->error);
            $conn->rollback();
            return false;
        }
        
        $titolo = $data['titolo'];
        $argomento_id = isset($data['argomenti_id']) ? $data['argomenti_id'] : null;
        $anno = isset($data['anno_pubblicazione']) ? $data['anno_pubblicazione'] : null;
        
        // Determina il binding in base alla presenza del campo foto
        if (isset($data['foto']) && !empty($data['foto'])) {
            $foto = $data['foto'];
            $stmtBase->bind_param("siissi", $titolo, $tipologia, $argomento_id, $anno, $foto, $document_id);
        } else {
            $stmtBase->bind_param("siisi", $titolo, $tipologia, $argomento_id, $anno, $document_id);
        }
        
        if (!$stmtBase->execute()) {
            error_log("Errore nell'aggiornamento dei dati base: " . $stmtBase->error);
            $conn->rollback();
            return false;
        }
        
        $stmtBase->close();
        
        // 2. Aggiornamento tabella specifica in base alla tipologia
        $success = false;
        
        switch ($tipologia) {
            case 1: // Libro
                // Prima controlliamo se esiste già un record per questo documento
                $checkQuery = "SELECT COUNT(*) as count FROM documenti_libri WHERE documento_id = ?";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bind_param("i", $document_id);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                $row = $result->fetch_assoc();
                $exists = ($row['count'] > 0);
                $checkStmt->close();
                
                if ($exists) {
                    // Aggiorna il record esistente
                    $queryLibro = "UPDATE documenti_libri SET 
                                    autore = ?,
                                    editore = ?,
                                    collana = ?,
                                    traduzione = ?,
                                    pagine = ?,
                                    indice = ?,
                                    bibliografia = ?
                                WHERE documento_id = ?";
                } else {
                    // Crea un nuovo record
                    $queryLibro = "INSERT INTO documenti_libri (
                                    autore,
                                    editore,
                                    collana,
                                    traduzione,
                                    pagine,
                                    indice,
                                    bibliografia,
                                    documento_id
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                }
                
                $stmtLibro = $conn->prepare($queryLibro);
                if (!$stmtLibro) {
                    error_log("Errore nella preparazione della query libro: " . $conn->error);
                    $conn->rollback();
                    return false;
                }
                
                $autore = isset($data['autore']) ? $data['autore'] : null;
                $editore = isset($data['editore']) ? $data['editore'] : null;
                $collana = isset($data['collana']) ? $data['collana'] : null;
                $traduzione = isset($data['traduzione']) ? $data['traduzione'] : null;
                $pagine = isset($data['pagine']) ? $data['pagine'] : null;
                $indice = isset($data['indice']) ? $data['indice'] : null;
                $bibliografia = isset($data['bibliografia']) ? $data['bibliografia'] : null;
                
                $stmtLibro->bind_param("sssssssi", $autore, $editore, $collana, $traduzione, $pagine, $indice, $bibliografia, $document_id);
                $success = $stmtLibro->execute();
                
                if (!$success) {
                    error_log("Errore nell'aggiornamento dei dati del libro: " . $stmtLibro->error);
                }
                
                $stmtLibro->close();
                break;
                
            case 2: // Rivista
                // Prima controlliamo se esiste già un record per questo documento
                $checkQuery = "SELECT COUNT(*) as count FROM documenti_riviste WHERE documento_id = ?";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bind_param("i", $document_id);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                $row = $result->fetch_assoc();
                $exists = ($row['count'] > 0);
                $checkStmt->close();
                
                if ($exists) {
                    // Aggiorna il record esistente
                    $queryRivista = "UPDATE documenti_riviste SET 
                                    editore = ?,
                                    mese = ?,
                                    numero = ?,
                                    sommario = ?
                                WHERE documento_id = ?";
                } else {
                    // Crea un nuovo record
                    $queryRivista = "INSERT INTO documenti_riviste (
                                    editore,
                                    mese,
                                    numero,
                                    sommario,
                                    documento_id
                                ) VALUES (?, ?, ?, ?, ?)";
                }
                
                $stmtRivista = $conn->prepare($queryRivista);
                if (!$stmtRivista) {
                    error_log("Errore nella preparazione della query rivista: " . $conn->error);
                    $conn->rollback();
                    return false;
                }
                
                $editore = isset($data['editore']) ? $data['editore'] : null;
                $mese = isset($data['mese']) ? $data['mese'] : null;
                $numero = isset($data['numero']) ? $data['numero'] : null;
                $sommario = isset($data['sommario']) ? $data['sommario'] : null;
                
                $stmtRivista->bind_param("ssssi", $editore, $mese, $numero, $sommario, $document_id);
                $success = $stmtRivista->execute();
                
                if (!$success) {
                    error_log("Errore nell'aggiornamento dei dati della rivista: " . $stmtRivista->error);
                }
                
                $stmtRivista->close();
                break;
                
            case 3: // Video
                // Prima controlliamo se esiste già un record per questo documento
                $checkQuery = "SELECT COUNT(*) as count FROM documenti_video WHERE documento_id = ?";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bind_param("i", $document_id);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                $row = $result->fetch_assoc();
                $exists = ($row['count'] > 0);
                $checkStmt->close();
                
                if ($exists) {
                    // Aggiorna il record esistente
                    $queryVideo = "UPDATE documenti_video SET 
                                    autore = ?,
                                    regia = ?,
                                    montaggio = ?,
                                    argomento_trattato = ?
                                WHERE documento_id = ?";
                } else {
                    // Crea un nuovo record
                    $queryVideo = "INSERT INTO documenti_video (
                                    autore,
                                    regia,
                                    montaggio,
                                    argomento_trattato,
                                    documento_id
                                ) VALUES (?, ?, ?, ?, ?)";
                }
                
                $stmtVideo = $conn->prepare($queryVideo);
                if (!$stmtVideo) {
                    error_log("Errore nella preparazione della query video: " . $conn->error);
                    $conn->rollback();
                    return false;
                }
                
                $autore = isset($data['autore']) ? $data['autore'] : null;
                $regia = isset($data['regia']) ? $data['regia'] : null;
                $montaggio = isset($data['montaggio']) ? $data['montaggio'] : null;
                $argomento_trattato = isset($data['argomento_trattato']) ? $data['argomento_trattato'] : null;
                
                $stmtVideo->bind_param("ssssi", $autore, $regia, $montaggio, $argomento_trattato, $document_id);
                $success = $stmtVideo->execute();
                
                if (!$success) {
                    error_log("Errore nell'aggiornamento dei dati del video: " . $stmtVideo->error);
                }
                
                $stmtVideo->close();
                break;
                
            default:
                $success = true; // Nessuna tabella specifica per questo tipo
                break;
        }
        
        if (!$success) {
            $conn->rollback();
            return false;
        }
        
        // Commit della transazione
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        error_log("Errore nell'aggiornamento del documento: " . $e->getMessage());
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        return false;
    }
}

/**
 * Elimina un documento dal database
 * @param mysqli $conn Connessione al database
 * @param int $id ID del documento da eliminare
 * @return bool True se l'eliminazione ha avuto successo, False altrimenti
 */
function deleteDocument($conn, $id) {
    try {
        // Grazie alle CASCADE DELETE, basta eliminare il record dalla tabella base
        $query = "DELETE FROM documenti_base WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Errore nella preparazione della query: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Errore nell'eliminazione del documento: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    } catch (Exception $e) {
        error_log("Errore nell'eliminazione del documento: " . $e->getMessage());
        return false;
    }
}

/**
 * Restituisce la tipologia di documento in formato testuale
 * @param int $typeId ID della tipologia
 * @return string Nome della tipologia
 */
function getDocumentTypeName($typeId) {
    $types = getDocumentTypes();
    return $types[$typeId] ?? 'Sconosciuto';
}

/**
 * Restituisce la lista delle tipologie di documento
 * @return array Array associativo id => nome
 */
function getDocumentTypes() {
    return [
        1 => 'Libro',
        2 => 'Rivista',
        3 => 'Video-Documentario'
    ];
}

/**
 * Validate document data
 * @param array $data Data to validate
 * @return array Result of validation
 */
function validateDocumentData($data) {
    if (!isset($data['titolo']) || empty($data['titolo'])) {
        return [
            'valid' => false,
            'message' => 'Il titolo è obbligatorio'
        ];
    }
    
    if (!isset($data['tipologia_doc']) || !in_array((int)$data['tipologia_doc'], [1, 2, 3])) {
        return [
            'valid' => false,
            'message' => 'La tipologia documento non è valida'
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'Validazione completata con successo'
    ];
}
