<?php
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

// Inizializzazione
$message = '';
$messageType = '';
$errors = [];
$documento = null;

// Get document types
$tipi_documento = getDocumentTypes();

// Get all available topics
$stmt = $conn->prepare("SELECT id, argomento FROM argomenti ORDER BY argomento");
$stmt->execute();
$argomenti = $stmt->get_result();
$lista_argomenti = [];

while ($row = $argomenti->fetch_assoc()) {
    $lista_argomenti[$row['id']] = $row['argomento'];
}

// Verifica che l'ID del documento sia stato fornito
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}

$document_id = (int)$_GET['id'];
$documento = getDocumentById($conn, $document_id);

if (!$documento) {
    redirect('index.php');
}

// Verifica se siamo nella pagina di modifica generica e reindirizza alla pagina specifica
// Solo se non viene esplicitamente richiesto di usare il form generico
if (!isset($_GET['generic']) && basename($_SERVER['PHP_SELF']) === 'modifica.php') {
    // Reindirizza alla pagina specifica di modifica in base alla tipologia
    switch ($documento['tipologia_doc']) {
        case 1: // Libro
            redirect("modifica_libro.php?id={$document_id}");
            break;
        case 2: // Rivista
            redirect("modifica_rivista.php?id={$document_id}");
            break;
        case 3: // Video
            redirect("modifica_video.php?id={$document_id}");
            break;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Campi comuni per tutti i documenti
    $titolo = cleanData(isset($_POST['titolo']) ? $_POST['titolo'] : '');
    $argomento_id = !empty($_POST['argomento_id']) ? (int)$_POST['argomento_id'] : null;
    $tipologia = isset($_POST['tipologia']) ? (int)$_POST['tipologia'] : 0;
    $anno = cleanData(isset($_POST['anno_pubblicazione']) ? $_POST['anno_pubblicazione'] : '');
    
    // Validazione campi obbligatori comuni
    if (empty($titolo)) {
        $errors[] = "Il campo titolo è obbligatorio";
    }
    
    if ($tipologia <= 0 || $tipologia > 3) {
        $errors[] = "Seleziona una tipologia di documento valida";
    }
    
    // Gestione upload dell'immagine
    $foto = $documento['foto']; // Mantieni l'immagine esistente di default
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = "../uploads/documents/";
        
        // Crea la directory se non esiste
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $filename = time() . '_' . basename($_FILES['foto']['name']);
        $target_file = $upload_dir . $filename;
        
        // Verifica che sia un'immagine
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($imageFileType, $extensions)) {
            $errors[] = "Sono permessi solo file JPG, JPEG, PNG e GIF.";
        } elseif ($_FILES['foto']['size'] > 5000000) { // Limite 5MB
            $errors[] = "Il file è troppo grande. Dimensione massima: 5MB.";
        } elseif (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
            // Se il caricamento ha successo, aggiorna il nome del file
            $foto = $filename;
            
            // Se c'era un'immagine precedente, eliminala
            if (!empty($documento['foto']) && $documento['foto'] != $filename) {
                $old_file = $upload_dir . $documento['foto'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
        } else {
            $errors[] = "Si è verificato un errore durante il caricamento dell'immagine.";
        }
    }
    
    // Se non ci sono errori, procedi con l'aggiornamento
    if (empty($errors)) {
        // Prepara i valori comuni per tutti i tipi di documento
        $params = [
            'id' => $document_id,
            'titolo' => $titolo,
            'tipologia_doc' => $tipologia,
            'argomenti_id' => $argomento_id,
            'anno_pubblicazione' => $anno,
            'foto' => $foto
        ];
        
        // Aggiungi i campi specifici in base alla tipologia
        switch ($tipologia) {
            case 1: // Libro
                // Debug per verificare i valori arrivati
                error_log("POST autore: " . (isset($_POST['autore']) ? $_POST['autore'] : 'non presente'));
                error_log("POST editore: " . (isset($_POST['editore']) ? $_POST['editore'] : 'non presente'));
                
                $params['autore'] = cleanData(isset($_POST['autore']) ? $_POST['autore'] : '');
                $params['editore'] = cleanData(isset($_POST['editore']) ? $_POST['editore'] : '');
                $params['collana'] = cleanData(isset($_POST['collana']) ? $_POST['collana'] : '');
                $params['traduzione'] = cleanData(isset($_POST['traduzione']) ? $_POST['traduzione'] : '');
                $params['pagine'] = cleanData(isset($_POST['pagine']) ? $_POST['pagine'] : '');
                $params['indice'] = cleanData(isset($_POST['indice']) ? $_POST['indice'] : '');
                $params['bibliografia'] = cleanData(isset($_POST['bibliografia']) ? $_POST['bibliografia'] : '');
                break;
                
            case 2: // Rivista
                error_log("POST editore (rivista): " . (isset($_POST['editore']) ? $_POST['editore'] : 'non presente'));
                
                $params['editore'] = cleanData(isset($_POST['editore']) ? $_POST['editore'] : '');
                $params['mese'] = cleanData(isset($_POST['mese']) ? $_POST['mese'] : '');
                $params['numero'] = cleanData(isset($_POST['numero']) ? $_POST['numero'] : '');
                $params['sommario'] = cleanData(isset($_POST['sommario']) ? $_POST['sommario'] : '');
                break;
                
            case 3: // Video-documentario
                error_log("POST autore (video): " . (isset($_POST['autore']) ? $_POST['autore'] : 'non presente'));
                
                $params['autore'] = cleanData(isset($_POST['autore']) ? $_POST['autore'] : '');
                $params['regia'] = cleanData(isset($_POST['regia']) ? $_POST['regia'] : '');
                $params['montaggio'] = cleanData(isset($_POST['montaggio']) ? $_POST['montaggio'] : '');
                $params['argomento_trattato'] = cleanData(isset($_POST['argomento_trattato']) ? $_POST['argomento_trattato'] : '');
                break;
        }
        
        // Aggiornamento nel database
        if (updateDocument($conn, $params)) {
            $message = "Documento aggiornato con successo";
            $messageType = 'success';
            
            // Aggiorna i dati del documento dopo la modifica
            $documento = getDocumentById($conn, $document_id);
            
            // Reindirizza dopo 2 secondi alla pagina index
            header("refresh:2;url=index.php");
        } else {
            $message = "Errore durante l'aggiornamento del documento";
            $messageType = 'error';
        }
    } else {
        $message = "Ci sono errori nel form. Controlla i campi e riprova.";
        $messageType = 'error';
    }
}

include '../includes/header.php';
?>

<div class="container px-6 mx-auto grid">
    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
        Modifica Documento
    </h2>
    
    <!-- Messaggi di notifica -->
    <?php if (!empty($message)): ?>
        <div class="mb-4 px-4 py-3 rounded-lg 
            <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo $message; ?>
            
            <?php if (!empty($errors)): ?>
                <ul class="mt-2 list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Breadcrumb -->
    <div class="flex items-center mb-6">
        <a href="index.php" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
            &larr; Torna all'elenco
        </a>
    </div>
    
    <!-- Form -->
    <div class="w-full overflow-hidden rounded-lg shadow-xs">
        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <form action="modifica.php?id=<?php echo $document_id; ?>" method="POST" enctype="multipart/form-data">
                
                <!-- Tipologia documento (disabilitata per la modifica) -->
                <div class="mb-4">
                    <label for="tipologia" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Tipologia documento <span class="text-red-500">*</span>
                    </label>
                    <select id="tipologia" name="tipologia" 
                            class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-select focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray"
                            required>
                        <option value="">Seleziona tipologia</option>
                        <?php foreach ($tipi_documento as $id => $tipo): ?>
                            <option value="<?php echo $id; ?>" <?php echo $documento['tipologia_doc'] == $id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Campi comuni a tutti i documenti -->
                <div class="mb-4">
                    <label for="titolo" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Titolo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="titolo" name="titolo" 
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input"
                           value="<?php echo htmlspecialchars($documento['titolo']); ?>"
                           required>
                </div>
                
                <div class="mb-4">
                    <label for="argomento_id" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Argomento
                    </label>
                    <select id="argomento_id" name="argomento_id" 
                            class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-select focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray">
                        <option value="">Seleziona argomento</option>
                        <?php foreach ($lista_argomenti as $id => $argomento): ?>
                            <option value="<?php echo $id; ?>" <?php echo $documento['argomenti_id'] == $id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($argomento); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="anno_pubblicazione" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Anno di pubblicazione
                    </label>
                    <input type="text" id="anno_pubblicazione" name="anno_pubblicazione" 
                           value="<?php echo htmlspecialchars(isset($documento['anno_pubblicazione']) ? $documento['anno_pubblicazione'] : ''); ?>"
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                </div>
                
                <div class="mb-4">
                    <label for="foto" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Immagine
                    </label>
                    
                    <?php if (!empty($documento['foto'])): ?>
                        <div class="mb-2">
                            <img src="../uploads/documents/<?php echo htmlspecialchars($documento['foto']); ?>" 
                                 alt="Immagine documento" class="max-w-xs h-auto">
                            <p class="text-xs text-gray-600 mt-1">Immagine attuale</p>
                        </div>
                    <?php endif; ?>
                    
                    <input type="file" id="foto" name="foto" accept="image/*"
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    <p class="text-xs text-gray-500 mt-1">Formati accettati: JPG, PNG, GIF. Max 5MB. Lascia vuoto per mantenere l'immagine attuale.</p>
                </div>
                
                <!-- Campi specifici per Libri (tipologia_doc = 1) -->
                <div id="form-libro" style="display:none;">
                    <h3 class="my-4 font-semibold text-gray-700 dark:text-gray-200">Dettagli Libro</h3>
                    
                    <div class="mb-4">
                        <label for="autore" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Autore (Cognome e nome)
                        </label>
                        <input type="text" id="autore" name="autore" 
                               value="<?php echo htmlspecialchars(isset($documento['autore']) ? $documento['autore'] : ''); ?>"
                               class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    </div>
                    
                    <div class="mb-4">
                        <label for="editore" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Editore
                        </label>
                        <input type="text" id="editore" name="editore" 
                               value="<?php echo htmlspecialchars(isset($documento['editore']) ? $documento['editore'] : ''); ?>"
                               class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    </div>
                    
                    <div class="mb-4">
                        <label for="collana" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Collana
                        </label>
                        <input type="text" id="collana" name="collana" 
                               value="<?php echo htmlspecialchars(isset($documento['collana']) ? $documento['collana'] : ''); ?>"
                               class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    </div>
                    
                    <div class="mb-4">
                        <label for="traduzione" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Traduzione
                        </label>
                        <input type="text" id="traduzione" name="traduzione" 
                               value="<?php echo htmlspecialchars(isset($documento['traduzione']) ? $documento['traduzione'] : ''); ?>"
                               class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    </div>
                    
                    <div class="mb-4">
                        <label for="pagine" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Pagine
                        </label>
                        <input type="text" id="pagine" name="pagine" 
                               value="<?php echo htmlspecialchars(isset($documento['pagine']) ? $documento['pagine'] : ''); ?>"
                               class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    </div>
                    
                    <div class="mb-4">
                        <label for="indice" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Indice
                        </label>
                        <textarea id="indice" name="indice" rows="3"
                                  class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-textarea"><?php echo htmlspecialchars(isset($documento['indice']) ? $documento['indice'] : ''); ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="bibliografia" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Bibliografia
                        </label>
                        <textarea id="bibliografia" name="bibliografia" rows="3"
                                  class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-textarea"><?php echo htmlspecialchars(isset($documento['bibliografia']) ? $documento['bibliografia'] : ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Campi specifici per Riviste (tipologia_doc = 2) -->
                <div id="form-rivista" style="display:none;">
                    <h3 class="my-4 font-semibold text-gray-700 dark:text-gray-200">Dettagli Rivista</h3>
                    
                    <div class="mb-4">
                        <label for="editore-rivista" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Editore
                        </label>
                        <input type="text" id="editore-rivista" name="editore" 
                               value="<?php echo htmlspecialchars(isset($documento['editore']) ? $documento['editore'] : ''); ?>"
                               class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    </div>
                    
                    <div class="mb-4">
                        <label for="mese" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Mese
                        </label>
                        <input type="text" id="mese" name="mese" 
                               value="<?php echo htmlspecialchars(isset($documento['mese']) ? $documento['mese'] : ''); ?>"
                               class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    </div>
                    
                    <div class="mb-4">
                        <label for="numero" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Numero
                        </label>
                        <input type="text" id="numero" name="numero" 
                               value="<?php echo htmlspecialchars(isset($documento['numero']) ? $documento['numero'] : ''); ?>"
                               class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    </div>
                    
                    <div class="mb-4">
                        <label for="sommario" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Sommario
                        </label>
                        <textarea id="sommario" name="sommario" rows="3"
                                  class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-textarea"><?php echo htmlspecialchars(isset($documento['sommario']) ? $documento['sommario'] : ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Campi specifici per Video (tipologia_doc = 3) -->
                <div id="form-video" style="display:none;">
                    <h3 class="my-4 font-semibold text-gray-700 dark:text-gray-200">Dettagli Video-Documentario</h3>
                    
                    <div class="mb-4">
                        <label for="autore-video" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Autore
                        </label>
                        <input type="text" id="autore-video" name="autore" 
                               value="<?php echo htmlspecialchars(isset($documento['autore']) ? $documento['autore'] : ''); ?>"
                               class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    </div>
                    
                    <div class="mb-4">
                        <label for="regia" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Regia
                        </label>
                        <input type="text" id="regia" name="regia" 
                               value="<?php echo htmlspecialchars(isset($documento['regia']) ? $documento['regia'] : ''); ?>"
                               class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    </div>
                    
                    <div class="mb-4">
                        <label for="montaggio" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Montaggio
                        </label>
                        <input type="text" id="montaggio" name="montaggio" 
                               value="<?php echo htmlspecialchars(isset($documento['montaggio']) ? $documento['montaggio'] : ''); ?>"
                               class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                    </div>
                    
                    <div class="mb-4">
                        <label for="argomento_trattato" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Argomento Trattato
                        </label>
                        <textarea id="argomento_trattato" name="argomento_trattato" rows="3"
                                  class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-textarea"><?php echo htmlspecialchars(isset($documento['argomento_trattato']) ? $documento['argomento_trattato'] : ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Pulsanti form -->
                <div class="mt-6 flex">
                    <button type="submit" class="px-5 py-3 font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                        Aggiorna Documento
                    </button>
                    <a href="index.php" class="ml-4 px-5 py-3 font-medium leading-5 text-white transition-colors duration-150 bg-gray-600 border border-transparent rounded-lg active:bg-gray-600 hover:bg-gray-700 focus:outline-none focus:shadow-outline-gray">
                        Annulla
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script per mostrare/nascondere i campi in base alla tipologia di documento selezionata
document.addEventListener('DOMContentLoaded', function() {
    const tipologiaSelect = document.getElementById('tipologia');
    const formLibro = document.getElementById('form-libro');
    const formRivista = document.getElementById('form-rivista');
    const formVideo = document.getElementById('form-video');
    
    // Funzione per mostrare il form appropriato
    function showAppropriateForm() {
        // Nascondi tutti i form
        formLibro.style.display = 'none'; // Usiamo style.display invece di classList per garantire che i campi vengano inviati
        formRivista.style.display = 'none';
        formVideo.style.display = 'none';
        
        // Mostra il form corrispondente alla tipologia selezionata
        const tipologia = parseInt(tipologiaSelect.value);
        switch(tipologia) {
            case 1: // Libro
                formLibro.style.display = 'block';
                break;
            case 2: // Rivista
                formRivista.style.display = 'block';
                break;
            case 3: // Video
                formVideo.style.display = 'block';
                break;
        }
    }
    
    // Aggiungi l'event listener per il cambio di tipologia
    tipologiaSelect.addEventListener('change', showAppropriateForm);
    
    // Mostra il form appropriato all'avvio della pagina
    showAppropriateForm();
});
</script>

<?php include '../includes/footer.php'; ?>
