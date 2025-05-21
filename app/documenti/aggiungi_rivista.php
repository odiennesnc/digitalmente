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

// Get all available topics
$stmt = $conn->prepare("SELECT id, argomento FROM argomenti ORDER BY argomento");
if ($stmt === false) {
    error_log("Error preparing statement for argomenti: " . $conn->error);
    $lista_argomenti = [];
} else {
    $stmt->execute();
    $argomenti = $stmt->get_result();
    $lista_argomenti = [];

    while ($row = $argomenti->fetch_assoc()) {
        $lista_argomenti[$row['id']] = $row['argomento'];
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Campi comuni per tutti i documenti
    $titolo = cleanData(isset($_POST['titolo']) ? $_POST['titolo'] : '');
    $argomento_id = !empty($_POST['argomento_id']) ? (int)$_POST['argomento_id'] : null;
    $tipologia = 2; // Rivista
    $anno = cleanData(isset($_POST['anno_pubblicazione']) ? $_POST['anno_pubblicazione'] : '');
    
    // Validazione campi obbligatori comuni
    if (empty($titolo)) {
        $errors[] = "Il campo titolo è obbligatorio";
    }
    
    // Gestione upload dell'immagine
    $foto = '';
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
            $foto = $filename;
        } else {
            $errors[] = "Si è verificato un errore durante il caricamento dell'immagine.";
        }
    }
    
    // Se non ci sono errori, procedi con l'inserimento
    if (empty($errors)) {
        // Prepara i valori specifici per le riviste
        $params = [
            'titolo' => $titolo,
            'tipologia_doc' => $tipologia,
            'argomenti_id' => $argomento_id,
            'anno_pubblicazione' => $anno,
            'foto' => $foto,
            'editore' => cleanData(isset($_POST['editore']) ? $_POST['editore'] : ''),
            'mese' => cleanData(isset($_POST['mese']) ? $_POST['mese'] : ''),
            'numero' => cleanData(isset($_POST['numero']) ? $_POST['numero'] : ''),
            'sommario' => cleanData(isset($_POST['sommario']) ? $_POST['sommario'] : '')
        ];
        
        // Inserimento nel database
        if (insertDocument($conn, $params)) {
            $message = "Rivista inserita con successo";
            $messageType = 'success';
            // Reindirizza dopo 2 secondi alla pagina index
            header("refresh:2;url=index.php");
        } else {
            $message = "Errore durante l'inserimento della rivista";
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
        Aggiungi Nuova Rivista
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
    
    <!-- Form -->
    <div class="w-full overflow-hidden rounded-lg shadow-xs">
        <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md dark:bg-gray-800">
            <form action="aggiungi_rivista.php" method="POST" enctype="multipart/form-data">
                <!-- Input nascosto per la tipologia -->
                <input type="hidden" name="tipologia" value="2">
                
                <!-- Campi comuni a tutti i documenti -->
                <div class="mb-4">
                    <label for="titolo" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Titolo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="titolo" name="titolo" 
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input"
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
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($argomento); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="anno_pubblicazione" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Anno di pubblicazione
                    </label>
                    <input type="text" id="anno_pubblicazione" name="anno_pubblicazione" 
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                </div>
                
                <div class="mb-4">
                    <label for="foto" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Copertina
                    </label>
                    <input type="file" id="foto" name="foto" accept="image/*"
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                </div>
                
                <!-- Campi specifici per le riviste -->
                <div class="mb-4">
                    <label for="editore" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Editore
                    </label>
                    <input type="text" id="editore" name="editore" 
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                </div>
                
                <div class="mb-4">
                    <label for="mese" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Mese di pubblicazione
                    </label>
                    <select id="mese" name="mese" 
                            class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-select focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray">
                        <option value="">Seleziona mese</option>
                        <option value="Gennaio">Gennaio</option>
                        <option value="Febbraio">Febbraio</option>
                        <option value="Marzo">Marzo</option>
                        <option value="Aprile">Aprile</option>
                        <option value="Maggio">Maggio</option>
                        <option value="Giugno">Giugno</option>
                        <option value="Luglio">Luglio</option>
                        <option value="Agosto">Agosto</option>
                        <option value="Settembre">Settembre</option>
                        <option value="Ottobre">Ottobre</option>
                        <option value="Novembre">Novembre</option>
                        <option value="Dicembre">Dicembre</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="numero" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Numero
                    </label>
                    <input type="text" id="numero" name="numero" 
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                </div>
                
                <div class="mb-4">
                    <label for="sommario" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Sommario
                    </label>
                    <textarea id="sommario" name="sommario" rows="3"
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-textarea"></textarea>
                </div>
                
                <!-- Pulsanti -->
                <div class="flex justify-between">
                    <button type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                        Salva rivista
                    </button>
                    <a href="index.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Annulla
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
