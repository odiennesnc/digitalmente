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

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}

$id = (int)$_GET['id'];

// Get all topics for dropdown
$stmt = $conn->prepare("SELECT * FROM argomenti ORDER BY argomento");
$stmt->execute();
$argomenti = $stmt->get_result();

// Get document data
$stmt = $conn->prepare("SELECT * FROM documenti WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    redirect('index.php');
}

$documento = $result->fetch_assoc();
$tipologia_doc = $documento['tipologia_doc'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Common fields for all document types
    $titolo = cleanData($_POST['titolo']);
    $argomenti_id = isset($_POST['argomenti_id']) ? (int)$_POST['argomenti_id'] : null;
    $tipologia_doc = (int)$_POST['tipologia_doc'];
    
    // Check title
    if (empty($titolo)) {
        $message = "Il titolo è obbligatorio";
        $messageType = 'error';
    } else {
        // Prepare SQL statement depending on document type
        $foto = $documento['foto']; // Keep existing photo if no new one is uploaded
        
        // Handle file upload if present
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $new_foto = uploadFile($_FILES['foto'], '../uploads/documents/');
            
            if ($new_foto === false) {
                $message = "Errore durante il caricamento dell'immagine. Sono ammessi solo formati JPG, JPEG, PNG e GIF.";
                $messageType = 'error';
            } else {
                // Delete old photo if exists
                if (!empty($foto)) {
                    $old_photo_path = '../uploads/documents/' . $foto;
                    if (file_exists($old_photo_path)) {
                        unlink($old_photo_path);
                    }
                }
                $foto = $new_foto;
            }
        }
        
        // If no error with file upload or no file uploaded, proceed with database update
        if ($messageType !== 'error') {
            // Common fields for all types
            $stmt = $conn->prepare("UPDATE documenti SET argomenti_id = ?, titolo = ?, tipologia_doc = ?, foto = ?, 
                                autore = ?, collana = ?, traduzione = ?, editore = ?, anno_pubblicazione = ?, 
                                pagine = ?, indice = ?, bibliografia = ?, mese = ?, numero = ?, 
                                sommario = ?, regia = ?, montaggio = ?, argomento_trattato = ? 
                                WHERE id = ?");
            
            // Initialize fields with null values
            $autore = null;
            $collana = null;
            $traduzione = null;
            $editore = null;
            $anno_pubblicazione = null;
            $pagine = null;
            $indice = null;
            $bibliografia = null;
            $mese = null;
            $numero = null;
            $sommario = null;
            $regia = null;
            $montaggio = null;
            $argomento_trattato = null;
            
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
            
            $stmt->bind_param("isiisssssssssssssi",
                $argomenti_id, $titolo, $tipologia_doc, $foto,
                $autore, $collana, $traduzione, $editore, $anno_pubblicazione, $pagine, $indice, $bibliografia,
                $mese, $numero, $sommario, $regia, $montaggio, $argomento_trattato, $id);
            
            if ($stmt->execute()) {
                $message = "Documento aggiornato con successo";
                $messageType = 'success';
                
                // Update local document data
                $documento['titolo'] = $titolo;
                $documento['argomenti_id'] = $argomenti_id;
                $documento['tipologia_doc'] = $tipologia_doc;
                $documento['foto'] = $foto;
                $documento['autore'] = $autore;
                $documento['collana'] = $collana;
                $documento['traduzione'] = $traduzione;
                $documento['editore'] = $editore;
                $documento['anno_pubblicazione'] = $anno_pubblicazione;
                $documento['pagine'] = $pagine;
                $documento['indice'] = $indice;
                $documento['bibliografia'] = $bibliografia;
                $documento['mese'] = $mese;
                $documento['numero'] = $numero;
                $documento['sommario'] = $sommario;
                $documento['regia'] = $regia;
                $documento['montaggio'] = $montaggio;
                $documento['argomento_trattato'] = $argomento_trattato;
            } else {
                $message = "Errore durante l'aggiornamento del documento: " . $stmt->error;
                $messageType = 'error';
            }
        }
    }
}

include '../includes/header.php';
?>

<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Modifica documento
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
                    <option value="1" <?php echo $tipologia_doc == 1 ? 'selected' : ''; ?>>Libro</option>
                    <option value="2" <?php echo $tipologia_doc == 2 ? 'selected' : ''; ?>>Rivista</option>
                    <option value="3" <?php echo $tipologia_doc == 3 ? 'selected' : ''; ?>>Video/Documentario</option>
                </select>
            </label>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm">
                <span class="text-gray-700 dark:text-gray-400">Argomento</span>
                <select class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-select focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray" 
                        name="argomenti_id">
                    <option value="">Seleziona argomento</option>
                    <?php 
                    // Reset argomenti result pointer
                    $argomenti->data_seek(0);
                    while ($argomento = $argomenti->fetch_assoc()) : 
                    ?>
                        <option value="<?php echo $argomento['id']; ?>" <?php echo $documento['argomenti_id'] == $argomento['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($argomento['argomento']); ?>
                        </option>
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
                       value="<?php echo htmlspecialchars($documento['titolo']); ?>"
                       required>
            </label>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm">
                <span class="text-gray-700 dark:text-gray-400">Immagine</span>
                <?php if (!empty($documento['foto'])) : ?>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Immagine attuale: 
                        <a href="../uploads/documents/<?php echo $documento['foto']; ?>" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline">
                            <?php echo $documento['foto']; ?>
                        </a>
                    </p>
                <?php endif; ?>
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
                           placeholder="Autore"
                           value="<?php echo htmlspecialchars($documento['autore']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Collana</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="collana" 
                           placeholder="Collana"
                           value="<?php echo htmlspecialchars($documento['collana']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Traduzione</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="traduzione" 
                           placeholder="Traduzione"
                           value="<?php echo htmlspecialchars($documento['traduzione']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Editore</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="editore" 
                           placeholder="Editore"
                           value="<?php echo htmlspecialchars($documento['editore']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Anno di pubblicazione</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="anno_pubblicazione" 
                           placeholder="Anno di pubblicazione"
                           value="<?php echo htmlspecialchars($documento['anno_pubblicazione']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Pagine</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="pagine" 
                           placeholder="Numero di pagine"
                           value="<?php echo htmlspecialchars($documento['pagine']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Indice</span>
                    <textarea class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-textarea focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray" 
                              name="indice" 
                              rows="3" 
                              placeholder="Indice"><?php echo htmlspecialchars($documento['indice']); ?></textarea>
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Bibliografia</span>
                    <textarea class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-textarea focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray" 
                              name="bibliografia" 
                              rows="3" 
                              placeholder="Bibliografia"><?php echo htmlspecialchars($documento['bibliografia']); ?></textarea>
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
                           placeholder="Anno"
                           value="<?php echo htmlspecialchars($documento['anno_pubblicazione']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Mese</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="mese" 
                           placeholder="Mese"
                           value="<?php echo htmlspecialchars($documento['mese']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Numero</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="numero" 
                           placeholder="Numero"
                           value="<?php echo htmlspecialchars($documento['numero']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Editore</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="editore" 
                           placeholder="Editore"
                           value="<?php echo htmlspecialchars($documento['editore']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Sommario</span>
                    <textarea class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-textarea focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray" 
                              name="sommario" 
                              rows="3" 
                              placeholder="Sommario"><?php echo htmlspecialchars($documento['sommario']); ?></textarea>
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
                           placeholder="Autore"
                           value="<?php echo htmlspecialchars($documento['autore']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Anno</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="anno" 
                           placeholder="Anno"
                           value="<?php echo htmlspecialchars($documento['anno_pubblicazione']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Regia</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="regia" 
                           placeholder="Regia"
                           value="<?php echo htmlspecialchars($documento['regia']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Montaggio</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="montaggio" 
                           placeholder="Montaggio"
                           value="<?php echo htmlspecialchars($documento['montaggio']); ?>">
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm">
                    <span class="text-gray-700 dark:text-gray-400">Argomento trattato</span>
                    <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                           name="argomento_trattato" 
                           placeholder="Argomento trattato"
                           value="<?php echo htmlspecialchars($documento['argomento_trattato']); ?>">
                </label>
            </div>
        </div>
        
        <div class="mt-4">
            <button type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                Aggiorna
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

    // Show relevant fields on page load
    document.addEventListener('DOMContentLoaded', function() {
        showRelevantFields();
    });
</script>

<?php include '../includes/footer.php'; ?>
