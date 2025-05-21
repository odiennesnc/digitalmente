<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/document_manager.php';

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Get latest documents (20 as requested in scheda_progetto.md)
// Utilizzo la funzione per ottenere i documenti con i dettagli dalla nuova struttura del database
try {
    $latest_docs_array = getAllDocumentsWithDetails($conn);
    if (!is_array($latest_docs_array)) {
        error_log("Error retrieving documents: Invalid return type from getAllDocumentsWithDetails");
        $latest_docs_array = [];
    }
    // Limitiamo ai primi 20 documenti
    $latest_docs_array = array_slice($latest_docs_array, 0, 20);
} catch (Exception $e) {
    error_log("Exception in getAllDocumentsWithDetails: " . $e->getMessage());
    $latest_docs_array = [];
}

// Per versioni di PHP più vecchie, usiamo un approccio più compatibile
// invece della classe anonima
$latest_docs = new stdClass();
$latest_docs->num_rows = count($latest_docs_array);
$latest_docs->data = $latest_docs_array;
$latest_docs->position = 0;

// Nota: la funzione custom_fetch_assoc è definita in functions.php

// Get todo items for the current user
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM todo WHERE utente_id = ? AND completato = 0 ORDER BY data_scadenza ASC LIMIT 5");
if ($stmt === false) {
    error_log("Error preparing todo statement: " . $conn->error);
    $todos = null; // Set a default value that can be checked later
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $todos = $stmt->get_result();
}

include 'includes/header.php';
?>

<!-- Dashboard header -->
<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Dashboard
</h2>

<!-- Cards -->
<div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
    <!-- Document Card -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
        </div>
        <div>
            <?php
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM documenti_base");
            if ($stmt === false) {
                error_log("Error preparing statement: " . $conn->error);
                $count = "N/A"; // Default value if query fails
            } else {
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_assoc()['total'];
            }
            ?>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Documenti Totali
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                <?php echo $count; ?>
            </p>
        </div>
    </div>
    <!-- Topics Card -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500">
            <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
        </div>
        <div>
            <?php
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM argomenti");
            if ($stmt === false) {
                error_log("Error preparing statement for argomenti count: " . $conn->error);
                $count = "N/A"; // Default value if query fails
            } else {
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_assoc()['total'];
            }
            ?>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Argomenti
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                <?php echo $count; ?>
            </p>
        </div>
    </div>
    <!-- Todo Card -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
            </svg>
        </div>
        <div>
            <?php
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM todo WHERE utente_id = ? AND completato = 0");
            if ($stmt === false) {
                error_log("Error preparing statement for todo count: " . $conn->error);
                $count = "N/A"; // Default value if query fails
            } else {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_assoc()['total'];
            }
            ?>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Todo Attivi
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                <?php echo $count; ?>
            </p>
        </div>
    </div>
    <!-- Users Card -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-teal-500 bg-teal-100 rounded-full dark:text-teal-100 dark:bg-teal-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
            </svg>
        </div>
        <div>
            <?php
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM utenti");
            if ($stmt === false) {
                error_log("Error preparing statement for utenti count: " . $conn->error);
                $count = "N/A"; // Default value if query fails
            } else {
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_assoc()['total'];
            }
            ?>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Utenti
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                <?php echo $count; ?>
            </p>
        </div>
    </div>
</div>

<!-- Latest Documents Section -->
<div class="w-full mb-8">
    <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-semibold text-gray-800 dark:text-gray-300">
                Ultimi 20 documenti inseriti
            </h4>
            <a href="documenti/index.php" class="px-4 py-2 text-sm font-medium text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                Vedi tutti
            </a>
        </div>
        <div class="w-full overflow-x-auto">
            <table id="latestDocsTable" class="w-full whitespace-no-wrap">
                <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                        <th class="px-4 py-3">Titolo</th>
                        <th class="px-4 py-3">Tipologia</th>
                        <th class="px-4 py-3">Autore/Editore</th>
                        <th class="px-4 py-3">Anno</th>
                        <th class="px-4 py-3">Argomento</th>
                        <th class="px-4 py-3">Data Ins.</th>
                        <th class="px-4 py-3">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                    <?php if (isset($latest_docs) && isset($latest_docs->num_rows) && $latest_docs->num_rows > 0) : ?>
                        <?php while ($doc = custom_fetch_assoc($latest_docs)) : ?>
                            <tr class="text-gray-700 dark:text-gray-400">
                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars($doc['titolo']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo getDocumentTypeName($doc['tipologia_doc']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php 
                                        if ($doc['tipologia_doc'] == 1 || $doc['tipologia_doc'] == 3) { // Libro o Video
                                            echo htmlspecialchars(isset($doc['autore']) && $doc['autore'] ? $doc['autore'] : 'N/D');
                                        } else { // Rivista
                                            echo htmlspecialchars(isset($doc['editore']) && $doc['editore'] ? $doc['editore'] : 'N/D');
                                        }
                                    ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars(isset($doc['anno_pubblicazione']) && $doc['anno_pubblicazione'] ? $doc['anno_pubblicazione'] : 'N/D'); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars(isset($doc['argomento']) && $doc['argomento'] ? $doc['argomento'] : 'Non specificato'); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo date('d/m/Y', strtotime($doc['data_inserimento'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center space-x-2">
                                        <a href="documenti/modifica.php?id=<?php echo $doc['id']; ?>" 
                                           class="flex items-center justify-between px-1 py-1 text-sm font-medium leading-5 text-purple-600 rounded-lg dark:text-gray-400 focus:outline-none focus:shadow-outline-gray" 
                                           aria-label="Modifica">
                                            <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr class="text-gray-700 dark:text-gray-400">
                            <td colspan="7" class="px-4 py-3 text-sm text-center">
                                Nessun documento trovato
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Todo List -->
<div class="min-w-0 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <h4 class="mb-4 font-semibold text-gray-800 dark:text-gray-300">
            Todo List
        </h4>
        <div class="overflow-x-auto">
            <table class="w-full whitespace-no-wrap">
                <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                        <th class="px-4 py-3">Task</th>
                        <th class="px-4 py-3">Scadenza</th>
                        <th class="px-4 py-3">Stato</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                    <?php if ($todos !== null && $todos->num_rows > 0) : ?>
                        <?php while ($todo = $todos->fetch_assoc()) : ?>
                            <tr class="text-gray-700 dark:text-gray-400">
                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars($todo['task']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo $todo['data_scadenza']; ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if ($todo['completato']) : ?>
                                        <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
                                            Completato
                                        </span>
                                    <?php else : ?>
                                        <span class="px-2 py-1 font-semibold leading-tight text-orange-700 bg-orange-100 rounded-full dark:bg-orange-700 dark:text-orange-100">
                                            In corso
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr class="text-gray-700 dark:text-gray-400">
                            <td colspan="3" class="px-4 py-3 text-sm text-center">
                                Nessun todo trovato
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-right">
            <a href="todo/index.php" class="px-4 py-2 text-sm text-white bg-purple-600 rounded-lg">
                Vedi tutti
            </a>
        </div>
    </div>
</div>

<!-- Script per DataTables -->
<script>
$(document).ready(function() {
    // Inizializza DataTable per la tabella dei documenti recenti
    $('#latestDocsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Italian.json"
        },
        "responsive": true,
        "pageLength": 10,
        "order": [[5, 'desc']], // Ordina per data inserimento (decrescente)
        "columnDefs": [
            { "orderable": false, "targets": 6 } // Disabilita ordinamento per colonna azioni
        ]
    });
});
</script>

<?php include 'includes/footer.php'; ?>
