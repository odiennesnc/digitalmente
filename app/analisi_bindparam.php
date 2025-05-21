<?php
// Script di test per analizzare il problema con bind_param
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test di analisi per problemi con bind_param<br>";

echo "Connessione al database...<br>";
require_once 'config/db.php';
echo "OK - Database caricato<br>";

require_once 'includes/functions.php';
echo "OK - Funzioni generali caricate<br>";

require_once 'includes/document_manager.php';
echo "OK - Document manager caricato<br>";

// Verifichiamo che getDocumentTypeName() funzioni
try {
    $type_name = getDocumentTypeName(1);
    echo "OK - getDocumentTypeName funziona: " . $type_name . "<br>";
} catch (Exception $e) {
    echo "ERRORE - getDocumentTypeName: " . $e->getMessage() . "<br>";
}

// Test di una query semplice
try {
    $query = "SELECT COUNT(*) AS total FROM documenti";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    echo "OK - Query semplice: " . $row['total'] . " documenti trovati<br>";
} catch (Exception $e) {
    echo "ERRORE - Query semplice: " . $e->getMessage() . "<br>";
}

// Test bind_param con una prepared statement
try {
    $query = "SELECT * FROM documenti WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    
    $id = 1;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "OK - Test bind_param: documento trovato<br>";
    } else {
        echo "OK - Test bind_param: nessun documento con id=1<br>";
    }
} catch (Exception $e) {
    echo "ERRORE - Test bind_param: " . $e->getMessage() . "<br>";
}

echo "<h2>Test costruzione query di update</h2>";

// Simuliamo la costruzione dei campi e tipi come in updateDocument
function simulateUpdateQuery($doc_type) {
    $fields = [
        'tipologia_doc = ?',
        'titolo = ?',
        'argomenti_id = ?',
        'anno_pubblicazione = ?'
    ];
    
    $values = [1, 'Titolo Test', 2, '2025'];
    $types = 'isss';
    
    // Aggiungi campi specifici in base alla tipologia
    switch ((int)$doc_type) {
        case 1: // Libro
            // Reset campi non pertinenti (questi non usano ?)
            $reset_fields = [
                'mese = NULL',
                'numero = NULL',
                'sommario = NULL',
                'regia = NULL',
                'montaggio = NULL',
                'argomento_trattato = NULL'
            ];
            
            // Campi specifici per i libri che usano parametri
            $param_fields = [
                'autore = ?',
                'editore = ?',
                'collana = ?',
                'traduzione = ?',
                'pagine = ?',
                'indice = ?',
                'bibliografia = ?'
            ];
            
            $param_values = ['Autore Test', 'Editore Test', 'Collana Test', 'Traduzione Test', '100', 'Indice Test', 'Bibliografia Test'];
            $param_types = 'sssssss';
            
            // Aggiungi i campi e valori
            $fields = array_merge($fields, $reset_fields, $param_fields);
            $values = array_merge($values, $param_values);
            $types .= $param_types;
            break;
    }
    
    // Calcola parametri
    $all_fields = implode(', ', $fields);
    $placeholder_count = substr_count($all_fields, '?');
    
    echo "<p>Tipo documento: $doc_type</p>";
    echo "<p>Fields totali: " . count($fields) . "</p>";
    echo "<p>Query: UPDATE documenti SET $all_fields WHERE id = ?</p>";
    echo "<p>Numero placeholder: $placeholder_count (più 1 per l'ID = " . ($placeholder_count + 1) . ")</p>";
    echo "<p>Tipi parametri: $types (lunghezza: " . strlen($types) . ")</p>";
    echo "<p>Numero valori: " . count($values) . " (più 1 per l'ID = " . (count($values) + 1) . ")</p>";
    
    // Calcola i parametri effettivi (solo quelli che usano ?)
    $param_fields_count = 0;
    foreach ($fields as $field) {
        if (strpos($field, '?') !== false) {
            $param_fields_count++;
        }
    }
    
    echo "<p>Campi che usano parametri: $param_fields_count</p>";
    
    // Verifica la corrispondenza
    if ($param_fields_count != count($values)) {
        echo "<p style='color:red'>ERRORE: Il numero di campi con parametri ($param_fields_count) non corrisponde al numero di valori (" . count($values) . ")</p>";
    } else {
        echo "<p style='color:green'>OK: Il numero di campi con parametri corrisponde al numero di valori</p>";
    }
    
    if ($placeholder_count != count($values)) {
        echo "<p style='color:red'>ERRORE: Il numero di placeholder ($placeholder_count) non corrisponde al numero di valori (" . count($values) . ")</p>";
    } else {
        echo "<p style='color:green'>OK: Il numero di placeholder corrisponde al numero di valori</p>";
    }
    
    if (strlen($types) != count($values)) {
        echo "<p style='color:red'>ERRORE: La lunghezza dei tipi (" . strlen($types) . ") non corrisponde al numero di valori (" . count($values) . ")</p>";
    } else {
        echo "<p style='color:green'>OK: La lunghezza dei tipi corrisponde al numero di valori</p>";
    }
    
    return $fields;
}

// Testa la costruzione della query per un libro
$fields = simulateUpdateQuery(1);

// Mostra come separare i campi
echo "<h3>Separazione dei campi:</h3>";
$param_fields = [];
$direct_fields = [];
foreach ($fields as $field) {
    if (strpos($field, '?') !== false) {
        $param_fields[] = $field;
        echo "<span style='color:blue'>Parametro: $field</span><br>";
    } else {
        $direct_fields[] = $field;
        echo "<span style='color:green'>Diretto: $field</span><br>";
    }
}

echo "<p>Totale campi con parametri: " . count($param_fields) . "</p>";
echo "<p>Totale campi diretti: " . count($direct_fields) . "</p>";
?>
