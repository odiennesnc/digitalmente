<?php
// Script di diagnosi per il problema di salvataggio autore/editore
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/document_manager.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('../login.php');
}

echo "<h1>Diagnosi Form Modifica Documenti</h1>";

// Verifica la presenza del parametro ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>Errore: ID documento non specificato</p>";
    echo "<a href='index.php'>Torna all'elenco documenti</a>";
    exit;
}

$document_id = (int)$_GET['id'];

// Recupera il documento dal database
try {
    $documento = getDocumentById($conn, $document_id);
    
    if (!$documento) {
        echo "<p>Errore: Documento non trovato</p>";
        echo "<a href='index.php'>Torna all'elenco documenti</a>";
        exit;
    }
    
    echo "<h2>Informazioni Documento</h2>";
    echo "<p><strong>ID:</strong> {$documento['id']}</p>";
    echo "<p><strong>Titolo:</strong> " . htmlspecialchars($documento['titolo']) . "</p>";
    echo "<p><strong>Tipologia:</strong> " . getDocumentTypeName($documento['tipologia_doc']) . " (ID: {$documento['tipologia_doc']})</p>";
    
    echo "<h3>Campi per autore/editore:</h3>";
    echo "<p><strong>Autore:</strong> " . (isset($documento['autore']) ? htmlspecialchars($documento['autore']) : 'non impostato') . "</p>";
    echo "<p><strong>Editore:</strong> " . (isset($documento['editore']) ? htmlspecialchars($documento['editore']) : 'non impostato') . "</p>";
    
    // Mostra altri campi in base alla tipologia
    switch ($documento['tipologia_doc']) {
        case 1: // Libro
            echo "<h3>Altri campi specifici per Libro:</h3>";
            echo "<p><strong>Collana:</strong> " . (isset($documento['collana']) ? htmlspecialchars($documento['collana']) : 'non impostato') . "</p>";
            echo "<p><strong>Traduzione:</strong> " . (isset($documento['traduzione']) ? htmlspecialchars($documento['traduzione']) : 'non impostato') . "</p>";
            break;
            
        case 2: // Rivista
            echo "<h3>Altri campi specifici per Rivista:</h3>";
            echo "<p><strong>Mese:</strong> " . (isset($documento['mese']) ? htmlspecialchars($documento['mese']) : 'non impostato') . "</p>";
            echo "<p><strong>Numero:</strong> " . (isset($documento['numero']) ? htmlspecialchars($documento['numero']) : 'non impostato') . "</p>";
            break;
            
        case 3: // Video
            echo "<h3>Altri campi specifici per Video:</h3>";
            echo "<p><strong>Regia:</strong> " . (isset($documento['regia']) ? htmlspecialchars($documento['regia']) : 'non impostato') . "</p>";
            echo "<p><strong>Montaggio:</strong> " . (isset($documento['montaggio']) ? htmlspecialchars($documento['montaggio']) : 'non impostato') . "</p>";
            break;
    }
    
    echo "<h2>Test Form</h2>";
    echo "<form action='diagnosi_form.php?id={$document_id}' method='post'>";
    
    // Campo per la tipologia (per simulare il comportamento del form principale)
    echo "<div>";
    echo "<label for='tipologia'>Tipologia documento:</label>";
    echo "<select id='tipologia' name='tipologia'>";
    echo "<option value='1'" . ($documento['tipologia_doc'] == 1 ? " selected" : "") . ">Libro</option>";
    echo "<option value='2'" . ($documento['tipologia_doc'] == 2 ? " selected" : "") . ">Rivista</option>";
    echo "<option value='3'" . ($documento['tipologia_doc'] == 3 ? " selected" : "") . ">Video</option>";
    echo "</select>";
    echo "</div>";
    
    // Campi comuni a tutte le tipologie
    echo "<div style='margin-top: 20px;'>";
    echo "<label for='titolo'>Titolo:</label>";
    echo "<input type='text' id='titolo' name='titolo' value='" . htmlspecialchars($documento['titolo']) . "'>";
    echo "</div>";
    
    // Campi specifici per Libro
    echo "<div id='form-libro' style='margin-top: 20px; padding: 10px; border: 1px solid #ccc;'>";
    echo "<h3>Campi per Libro</h3>";
    echo "<div>";
    echo "<label for='autore'>Autore:</label>";
    echo "<input type='text' id='autore' name='autore' value='" . htmlspecialchars($documento['autore'] ?? '') . "'>";
    echo "</div>";
    echo "<div style='margin-top: 10px;'>";
    echo "<label for='editore'>Editore:</label>";
    echo "<input type='text' id='editore' name='editore' value='" . htmlspecialchars($documento['editore'] ?? '') . "'>";
    echo "</div>";
    echo "</div>";
    
    // Campi specifici per Rivista
    echo "<div id='form-rivista' style='margin-top: 20px; padding: 10px; border: 1px solid #ccc;'>";
    echo "<h3>Campi per Rivista</h3>";
    echo "<div>";
    echo "<label for='editore-rivista'>Editore:</label>";
    echo "<input type='text' id='editore-rivista' name='editore' value='" . htmlspecialchars($documento['editore'] ?? '') . "'>";
    echo "</div>";
    echo "</div>";
    
    // Campi specifici per Video
    echo "<div id='form-video' style='margin-top: 20px; padding: 10px; border: 1px solid #ccc;'>";
    echo "<h3>Campi per Video</h3>";
    echo "<div>";
    echo "<label for='autore-video'>Autore:</label>";
    echo "<input type='text' id='autore-video' name='autore' value='" . htmlspecialchars($documento['autore'] ?? '') . "'>";
    echo "</div>";
    echo "</div>";
    
    // Pulsante di invio
    echo "<div style='margin-top: 20px;'>";
    echo "<input type='submit' name='submit' value='Invia Test'>";
    echo "</div>";
    echo "</form>";
    
    // Script per mostrare/nascondere i campi in base alla tipologia selezionata
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipologiaSelect = document.getElementById('tipologia');
            const formLibro = document.getElementById('form-libro');
            const formRivista = document.getElementById('form-rivista');
            const formVideo = document.getElementById('form-video');
            
            function showForm() {
                const tipologia = parseInt(tipologiaSelect.value);
                
                // Nascondi tutti i form
                formLibro.style.display = 'none';
                formRivista.style.display = 'none';
                formVideo.style.display = 'none';
                
                // Mostra il form appropriato
                switch(tipologia) {
                    case 1:
                        formLibro.style.display = 'block';
                        break;
                    case 2:
                        formRivista.style.display = 'block';
                        break;
                    case 3:
                        formVideo.style.display = 'block';
                        break;
                }
            }
            
            // Imposta lo stato iniziale
            showForm();
            
            // Aggiungi listener per i cambiamenti
            tipologiaSelect.addEventListener('change', showForm);
        });
    </script>";
    
    // Gestione del submit del form di test
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
        echo "<h2>Risultati Test Submit</h2>";
        echo "<pre>";
        echo "POST data:\n";
        print_r($_POST);
        echo "</pre>";
        
        // Verifica la presenza dei campi autore e editore
        echo "<p><strong>Verifica campo autore:</strong> ";
        if (isset($_POST['autore'])) {
            echo "Presente con valore: " . htmlspecialchars($_POST['autore']);
        } else {
            echo "NON presente nel form inviato!";
        }
        echo "</p>";
        
        echo "<p><strong>Verifica campo editore:</strong> ";
        if (isset($_POST['editore'])) {
            echo "Presente con valore: " . htmlspecialchars($_POST['editore']);
        } else {
            echo "NON presente nel form inviato!";
        }
        echo "</p>";
        
        echo "<p>Questo test verifica se i campi vengono correttamente inviati anche quando nascosti nel form.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Errore durante il recupero delle informazioni del documento: " . $e->getMessage() . "</p>";
}

echo "<p style='margin-top: 30px;'><a href='index.php'>Torna all'elenco documenti</a></p>";
?>
