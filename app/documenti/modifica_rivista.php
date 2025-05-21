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

// Verifica che sia effettivamente una rivista
if ($documento['tipologia_doc'] != 2) {
    // Reindirizza alla pagina di modifica corretta in base alla tipologia
    switch ($documento['tipologia_doc']) {
        case 1: // Libro
            redirect("modifica_libro.php?id={$document_id}");
            break;
        case 3: // Video
            redirect("modifica_video.php?id={$document_id}");
            break;
        default:
            // Nel caso di tipologia sconosciuta, usa il form generico
            redirect("modifica.php?id={$document_id}&generic=1");
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Campi comuni per tutti i documenti
    $titolo = cleanData(isset($_POST['titolo']) ? $_POST['titolo'] : '');
    $argomento_id = !empty($_POST['argomento_id']) ? (int)$_POST['argomento_id'] : null;
    $tipologia = 2; // Rivista - forzato
    $anno = cleanData(isset($_POST['anno_pubblicazione']) ? $_POST['anno_pubblicazione'] : '');
    
    // Validazione campi obbligatori comuni
    if (empty($titolo)) {
        $errors[] = "Il campo titolo è obbligatorio";
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
        // Prepara i parametri per la rivista
        $params = [
            'id' => $document_id,
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
        
        // Aggiornamento nel database
        if (updateDocument($conn, $params)) {
            $message = "Rivista aggiornata con successo";
            $messageType = 'success';
            
            // Aggiorna i dati del documento dopo la modifica
            $documento = getDocumentById($conn, $document_id);
            
            // Reindirizza dopo 2 secondi alla pagina index
            header("refresh:2;url=index.php");
        } else {
            $message = "Errore durante l'aggiornamento della rivista";
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
        Modifica Rivista
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
            <form action="modifica_rivista.php?id=<?php echo $document_id; ?>" method="POST" enctype="multipart/form-data">
                <!-- Campi comuni a tutti i documenti -->
                <div class="mb-4">
                    <label for="titolo" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Titolo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="titolo" name="titolo" value="<?php echo htmlspecialchars($documento['titolo']); ?>" 
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
                            <option value="<?php echo $id; ?>" <?php echo ($documento['argomenti_id'] == $id ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($argomento); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="anno_pubblicazione" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Anno di pubblicazione
                    </label>
                    <input type="text" id="anno_pubblicazione" name="anno_pubblicazione" value="<?php echo htmlspecialchars($documento['anno_pubblicazione']); ?>" 
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                </div>
                
                <div class="mb-4">
                    <label for="foto" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Copertina
                    </label>
                    <?php if (!empty($documento['foto'])): ?>
                        <div class="mt-2 mb-2">
                            <img src="../uploads/documents/<?php echo htmlspecialchars($documento['foto']); ?>" 
                                 alt="Copertina attuale" class="max-w-xs">
                            <p class="mt-1 text-sm text-gray-500">Immagine attuale</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="foto" name="foto" accept="image/*"
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                </div>
                
                <!-- Campi specifici per le riviste -->
                <div class="mb-4">
                    <label for="editore" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Editore
                    </label>
                    <input type="text" id="editore" name="editore" value="<?php echo htmlspecialchars(isset($documento['editore']) ? $documento['editore'] : ''); ?>" 
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                </div>
                
                <div class="mb-4">
                    <label for="mese" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Mese di pubblicazione
                    </label>
                    <select id="mese" name="mese" 
                            class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-select focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray">
                        <option value="">Seleziona mese</option>
                        <?php 
                        $mesi = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
                        foreach ($mesi as $mese): ?>
                            <option value="<?php echo $mese; ?>" <?php echo ($documento['mese'] == $mese ? 'selected' : ''); ?>>
                                <?php echo $mese; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="numero" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Numero
                    </label>
                    <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars(isset($documento['numero']) ? $documento['numero'] : ''); ?>" 
                           class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-input">
                </div>
                
                <div class="mb-4">
                    <label for="sommario" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Sommario
                    </label>
                    <textarea id="sommario" name="sommario" rows="3"
                              class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray form-textarea"><?php echo htmlspecialchars(isset($documento['sommario']) ? $documento['sommario'] : ''); ?></textarea>
                </div>
                
                <!-- Pulsanti -->
                <div class="flex justify-between">
                    <button type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                        Salva modifiche
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
