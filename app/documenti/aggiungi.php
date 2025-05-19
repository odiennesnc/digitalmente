<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('../login.php');
}

$message = '';
$messageType = '';

// Get all topics for dropdown
$stmt = $conn->prepare("SELECT * FROM argomenti ORDER BY argomento");
$stmt->execute();
$argomenti = $stmt->get_result();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log incoming form data for debugging
    error_log("Form POST data: " . json_encode($_POST));
    
    // Check if required fields are present
    if (!isset($_POST['titolo']) || !isset($_POST['tipologia_doc'])) {
        $message = "Campi obbligatori mancanti";
        $messageType = 'error';
        goto display_form;
    }
    
    // Common fields for all document types
    $titolo = cleanData($_POST['titolo']);
    // Per evitare problemi con bind_param, usiamo 0 invece di null
    // poi convertiamo 0 in NULL con NULLIF nella query SQL
    $argomenti_id = isset($_POST['argomenti_id']) && !empty($_POST['argomenti_id']) ? (int)$_POST['argomenti_id'] : 0;
    
    // Ensure tipologia_doc is valid
    if (!isset($_POST['tipologia_doc']) || !in_array((int)$_POST['tipologia_doc'], [1, 2, 3])) {
        $message = "Tipologia documento non valida";
        $messageType = 'error';
        goto display_form;
    }
    
    $tipologia_doc = (int)$_POST['tipologia_doc'];
    
    // Check title
    if (empty($titolo)) {
        $message = "Il titolo è obbligatorio";
        $messageType = 'error';
    } else {
        // Prepare SQL statement depending on document type
        $foto = '';
        
        // Handle file upload if present
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto = uploadFile($_FILES['foto'], '../uploads/documents/');
            
            if ($foto === false) {
                $message = "Errore durante il caricamento dell'immagine. Sono ammessi solo formati JPG, JPEG, PNG e GIF.";
                $messageType = 'error';
            }
        }
        
        // If no error with file upload or no file uploaded, proceed with database insertion
        if ($messageType !== 'error') {
            // Approccio semplificato per gestire l'inserimento
            // Inizializza i campi - Empty strings per i campi stringa
            $autore = '';
            $collana = '';
            $traduzione = '';
            $editore = '';
            $anno_pubblicazione = '';
            $pagine = '';
            $indice = '';
            $bibliografia = '';
            $mese = '';
            $numero = '';
            $sommario = '';
            $regia = '';
            $montaggio = '';
            $argomento_trattato = '';
            
            // Set values based on document type
            switch ($tipologia_doc) {
                case 1: // Book
                    $autore = cleanData($_POST['autore'] ?? '');
                    $collana = cleanData($_POST['collana'] ?? '');
                    $traduzione = cleanData($_POST['traduzione'] ?? '');
                    $editore = cleanData($_POST['editore'] ?? '');
                    $anno_pubblicazione = cleanData($_POST['anno_pubblicazione'] ?? '');
                    $pagine = cleanData($_POST['pagine'] ?? '');
                    $indice = cleanData($_POST['indice'] ?? '');
                    $bibliografia = cleanData($_POST['bibliografia'] ?? '');
                    break;
                    
                case 2: // Magazine
                    $anno_pubblicazione = cleanData($_POST['anno'] ?? '');
                    $mese = cleanData($_POST['mese'] ?? '');
                    $numero = cleanData($_POST['numero'] ?? '');
                    $editore = cleanData($_POST['editore'] ?? '');
                    $sommario = cleanData($_POST['sommario'] ?? '');
                    break;
                    
                case 3: // Video
                    $autore = cleanData($_POST['autore'] ?? '');
                    $anno_pubblicazione = cleanData($_POST['anno'] ?? '');
                    $regia = cleanData($_POST['regia'] ?? '');
                    $montaggio = cleanData($_POST['montaggio'] ?? '');
                    $argomento_trattato = cleanData($_POST['argomento_trattato'] ?? '');
                    break;
            }
            
            // Debug log prima dell'inserimento
            error_log("Debug - Preparazione inserimento documento - Tipo: $tipologia_doc, Argomento ID: $argomenti_id");

            if ($argomenti_id == 0) {
                // Versione senza argomenti_id (NULL diretto)
                $sql = "INSERT INTO documenti (titolo, tipologia_doc, foto, 
                        autore, collana, traduzione, editore, anno_pubblicazione, pagine, indice, bibliografia,
                        mese, numero, sommario, regia, montaggio, argomento_trattato) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    // 's' per titolo, 'i' per tipologia_doc, seguiti da 's' per tutti gli altri campi stringa
                    $stmt->bind_param("sisssssssssssssss", 
                        $titolo, $tipologia_doc, $foto,
                        $autore, $collana, $traduzione, $editore, $anno_pubblicazione, $pagine, $indice, $bibliografia,
                        $mese, $numero, $sommario, $regia, $montaggio, $argomento_trattato);
                }
            } else {
                // Versione con argomenti_id
                $sql = "INSERT INTO documenti (argomenti_id, titolo, tipologia_doc, foto, 
                        autore, collana, traduzione, editore, anno_pubblicazione, pagine, indice, bibliografia,
                        mese, numero, sommario, regia, montaggio, argomento_trattato) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("isisssssssssssssss",
                        $argomenti_id, $titolo, $tipologia_doc, $foto,
                        $autore, $collana, $traduzione, $editore, $anno_pubblicazione, $pagine, $indice, $bibliografia,
                        $mese, $numero, $sommario, $regia, $montaggio, $argomento_trattato);
                }
            }
            // Check if statement preparation was successful
            if (!$stmt) {
                error_log("Errore nella preparazione della query: " . $conn->error);
                $message = "Errore durante l'aggiunta del documento: errore nella preparazione della query";
                $messageType = 'error';
                // Don't proceed further
                goto display_form;
            }
            
            // Assicuriamoci che tutti i valori siano del tipo corretto
            $tipologia_doc = (int)$tipologia_doc;
            $argomenti_id = (int)$argomenti_id;
            
            // Converti tutti i valori stringa in stringhe effettive (non null)
            $titolo = (string)$titolo;
            $foto = (string)$foto;
            $autore = (string)$autore;
            $collana = (string)$collana;
            $traduzione = (string)$traduzione;
            $editore = (string)$editore;
            $anno_pubblicazione = (string)$anno_pubblicazione;
            $pagine = (string)$pagine;
            $indice = (string)$indice;
            $bibliografia = (string)$bibliografia;
            $mese = (string)$mese;
            $numero = (string)$numero;
            $sommario = (string)$sommario;
            $regia = (string)$regia;
            $montaggio = (string)$montaggio;
            $argomento_trattato = (string)$argomento_trattato;
            
            // Log completo dei dati per debug
            error_log("Debug - Binding parametri con tipi corretti: " . 
                     "Tipo doc: $tipologia_doc, ArgID: $argomenti_id, Titolo: $titolo");
            
            // Debug output
            error_log("Debug - Form data received: " . json_encode($_POST));
            error_log("Debug - Document type: " . $tipologia_doc);
            error_log("Debug - Binding parameters: argomenti_id=" . var_export($argomenti_id, true) . 
                      ", titolo=" . $titolo . ", tipologia_doc=" . $tipologia_doc .
                      ", foto=" . $foto);
            
            try {
                // Try to execute the statement
                if (!$stmt->execute()) {
                    // Log dettagliato dell'errore
                    error_log("Errore nell'esecuzione della query: " . $stmt->error);
                    error_log("Stato dei parametri: " . json_encode([
                        'argomenti_id' => $argomenti_id,
                        'titolo' => $titolo,
                        'tipologia_doc' => $tipologia_doc
                    ]));
                    
                    $message = "Errore durante l'aggiunta del documento: " . $stmt->error;
                    $messageType = 'error';
                } else {
                    $message = "Documento aggiunto con successo";
                    $messageType = 'success';
                    
                    // Clear form fields by redirecting
                    header("Refresh: 1; URL=aggiungi.php");
                }
            } catch (Exception $e) {
                error_log("Exception durante l'esecuzione della query: " . $e->getMessage());
                $message = "Errore durante l'aggiunta del documento: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
    // Label for goto statement
    display_form:
}

include '../includes/header.php';
?>

<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Aggiungi documento
</h2>

<?php if ($message !== '') : ?>
    <?php if ($messageType === 'success') : ?>
        <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4">
            <?php echo $message; ?>
        </div>
    <?php else : ?>
        <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md dark:bg-gray-800">
    <form method="POST" enctype="multipart/form-data">
        <!-- Common fields -->
        <div class="mb-4">
            <label class="block text-sm">
                <span class="text-gray-700 dark:text-gray-400">Tipologia documento</span>
                <select class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-select focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray" 
                        name="tipologia_doc" 
                        id="tipologia_doc" 
                        required
                        onchange="showRelevantFields()">
                    <option value="">Seleziona tipologia</option>
                    <option value="1">Libro</option>
                    <option value="2">Rivista</option>
                    <option value="3">Video/Documentario</option>
                </select>
            </label>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm">
                <span class="text-gray-700 dark:text-gray-400">Argomento</span>
                <select class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-select focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray" 
                        name="argomenti_id">
                    <option value="">Seleziona argomento</option>
                    <?php while ($argomento = $argomenti->fetch_assoc()) : ?>
                        <option value="<?php echo $argomento['id']; ?>"><?php echo htmlspecialchars($argomento['argomento']); ?></option>
                    <?php endwhile; ?>
                </select>
            </label>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm">
                <span class="text-gray-700 dark:text-gray-400">Titolo</span>
                <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                       name="titolo" 
                       placeholder="Titolo del documento" 
                       required>
            </label>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm">
                <span class="text-gray-700 dark:text-gray-400">Immagine</span>
                <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray" 
                       type="file" 
                       name="foto" 
                       accept="image/*">
            </label>
        </div>
        
        <!-- Book fields -->
        <div id="libro-fields" class="hidden">
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Autore</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="autore" 
                           placeholder="Autore">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Collana</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="collana" 
                           placeholder="Collana">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Traduzione</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="traduzione" 
                           placeholder="Traduzione">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Editore</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="editore" 
                           placeholder="Editore">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Anno di pubblicazione</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="anno_pubblicazione" 
                           placeholder="Anno di pubblicazione">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Pagine</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="pagine" 
                           placeholder="Numero di pagine">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Indice</span>
                    <textarea class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-textarea focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray" 
                              name="indice" 
                              rows="3" 
                              placeholder="Indice"></textarea>
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Bibliografia</span>
                    <textarea class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-textarea focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray" 
                              name="bibliografia" 
                              rows="3" 
                              placeholder="Bibliografia"></textarea>
                </label>
            </div>
        </div>
        
        <!-- Magazine fields -->
        <div id="rivista-fields" class="hidden">
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Anno</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="anno" 
                           placeholder="Anno">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Mese</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="mese" 
                           placeholder="Mese">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Numero</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="numero" 
                           placeholder="Numero">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Editore</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="editore" 
                           placeholder="Editore">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Sommario</span>
                    <textarea class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-textarea focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray" 
                              name="sommario" 
                              rows="3" 
                              placeholder="Sommario"></textarea>
                </label>
            </div>
        </div>
        
        <!-- Video fields -->
        <div id="video-fields" class="hidden">
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Autore</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="autore" 
                           placeholder="Autore">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Anno</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="anno" 
                           placeholder="Anno">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Regia</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="regia" 
                           placeholder="Regia">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Montaggio</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="montaggio" 
                           placeholder="Montaggio">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Argomento trattato</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="argomento_trattato" 
                           placeholder="Argomento trattato">
                </label>
            </div>
        </div>
        
        <div class="mt-4">
            <button type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                Salva
            </button>
            <a href="index.php" class="px-4 py-2 text-sm font-medium leading-5 text-gray-700 transition-colors duration-150 border border-gray-300 rounded-lg active:bg-gray-100 hover:bg-gray-100 focus:outline-none focus:shadow-outline-gray">
                Annulla
            </a>
        </div>
    </form>
</div>

<script>
    function showRelevantFields() {
        var docType = document.getElementById('tipologia_doc').value;
        
        // Hide all fields first
        document.getElementById('libro-fields').classList.add('hidden');
        document.getElementById('rivista-fields').classList.add('hidden');
        document.getElementById('video-fields').classList.add('hidden');
        
        // Show fields based on selected type
        if (docType === '1') {
            document.getElementById('libro-fields').classList.remove('hidden');
        } else if (docType === '2') {
            document.getElementById('rivista-fields').classList.remove('hidden');
        } else if (docType === '3') {
            document.getElementById('video-fields').classList.remove('hidden');
        }
    }
</script>

<?php include '../includes/footer.php'; ?>
