<?php
// Pagina per la migrazione del database alla nuova struttura
require_once '../config/db.php';
require_once '../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se l'utente è loggato e se è un amministratore
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    redirect('../login.php');
}

$message = '';
$messageType = '';

// Esegui la migrazione quando richiesto
if (isset($_POST['esegui_migrazione']) && $_POST['esegui_migrazione'] === 'yes') {
    // Leggi il file SQL di migrazione
    $sql_file = file_get_contents('../config/migration_tabelle_separate.sql');
    
    if ($sql_file === false) {
        $message = "Errore nella lettura del file di migrazione";
        $messageType = 'error';
    } else {
        // Dividi gli statement SQL
        $statements = explode(';', $sql_file);
        
        // Flag per tenere traccia del successo
        $success = true;
        $errors = [];
        
        // Esegui ogni statement SQL
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                if ($conn->query($statement) === false) {
                    $success = false;
                    $errors[] = $conn->error;
                }
            }
        }
        
        if ($success) {
            $message = "Migrazione eseguita con successo!";
            $messageType = 'success';
        } else {
            $message = "Si sono verificati errori durante la migrazione: " . implode(", ", $errors);
            $messageType = 'error';
        }
    }
}

include '../includes/header.php';
?>

<div class="container px-6 mx-auto grid">
    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
        Migrazione Database
    </h2>
    
    <!-- Messaggi di notifica -->
    <?php if (!empty($message)): ?>
        <div class="mb-4 px-4 py-3 rounded-lg 
            <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <p class="text-gray-700 dark:text-gray-400 mb-4">
            Questa pagina consente di eseguire la migrazione del database alla nuova struttura con tabelle separate per ogni tipo di documento.
        </p>
        
        <p class="text-gray-700 dark:text-gray-400 mb-4">
            <strong>Importante:</strong> Prima di procedere assicurati di avere fatto un backup del database esistente. 
            La migrazione creerà nuove tabelle e migrerà i dati dalla tabella <code>documenti</code>.
        </p>
        
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-2">La migrazione eseguirà:</h3>
        
        <ol class="list-decimal pl-5 mb-4 text-gray-700 dark:text-gray-400">
            <li>Creazione della tabella <code>documenti_base</code> per i campi comuni</li>
            <li>Creazione delle tabelle <code>documenti_libri</code>, <code>documenti_riviste</code> e <code>documenti_video</code> per i campi specifici</li>
            <li>Migrazione dei dati dalla tabella <code>documenti</code> alle nuove tabelle</li>
        </ol>
        
        <form action="" method="post" class="mt-6">
            <div class="flex flex-col">
                <label class="inline-flex items-center text-gray-600 dark:text-gray-400 mb-4">
                    <input type="checkbox" name="confermo" required
                        class="text-purple-600 form-checkbox focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray">
                    <span class="ml-2">Confermo di aver fatto un backup del database</span>
                </label>
                
                <input type="hidden" name="esegui_migrazione" value="yes">
                
                <button type="submit" 
                    class="px-4 py-2 w-full sm:w-auto text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                    Esegui Migrazione
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
