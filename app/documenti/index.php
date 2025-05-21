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

// Messaggio di notifica
$message = '';
$messageType = '';

// Gestione dell'eliminazione tramite GET (conferma avverrà via modal)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $document_id = (int)$_GET['delete'];
    
    if (deleteDocument($conn, $document_id)) {
        $message = "Documento eliminato con successo";
        $messageType = 'success';
    } else {
        $message = "Errore durante l'eliminazione del documento";
        $messageType = 'error';
    }
}

// Gestione degli errori passati tramite GET
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_type':
            $message = "Tipo di documento non valido. Il sistema ha rilevato un'incongruenza nel tipo di documento. Prova ad accedere al documento dalla lista principale.";
            $messageType = 'error';
            break;
        // Aggiungi altri casi di errore se necessario
    }
}

// Query per recuperare tutti i documenti dalla nuova struttura del database
// Per ogni documento recuperiamo anche i dati specifici dalla tabella appropriata
$documenti = getAllDocumentsWithDetails($conn);

// La funzione getAllDocumentsWithDetails è definita nel document_manager.php

include '../includes/header.php';
?>

<div class="container px-6 mx-auto grid" x-data="data">
    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
        Gestione Documenti
    </h2>
    
    <!-- Messaggi di notifica -->
    <?php if (!empty($message)): ?>
        <div class="mb-4 px-4 py-3 rounded-lg 
            <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <!-- CTA Aggiungi Documento -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="scegli_tipo.php" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                Aggiungi Documento
            </a>
        </div>
    </div>
    
    <!-- Tabella Documenti -->
    <div class="w-full overflow-hidden rounded-lg shadow-xs">
        <div class="w-full overflow-x-auto">
            <table id="documentiTable" class="w-full whitespace-no-wrap">
                <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                        <th class="px-4 py-3">Titolo</th>
                        <th class="px-4 py-3">Tipologia</th>
                        <th class="px-4 py-3">Autore/Editore</th>
                        <th class="px-4 py-3">Anno</th>
                        <th class="px-4 py-3">Argomento</th>
                        <th class="px-4 py-3">Data Inserimento</th>
                        <th class="px-4 py-3">Azioni</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                    <?php foreach ($documenti as $documento): ?>
                        <tr class="text-gray-700 dark:text-gray-400">
                            <td class="px-4 py-3 text-sm">
                                <?php echo htmlspecialchars($documento['titolo']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php echo getDocumentTypeName($documento['tipologia_doc']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php 
                                    switch ($documento['tipologia_doc']) {
                                        case 1: // Libro
                                            echo htmlspecialchars(isset($documento['autore']) ? $documento['autore'] : 'N/A');
                                            break;
                                        case 2: // Rivista
                                            echo htmlspecialchars(isset($documento['editore']) ? $documento['editore'] : 'N/A');
                                            break;
                                        case 3: // Video
                                            if (isset($documento['autore'])) {
                                                echo htmlspecialchars($documento['autore']);
                                            } elseif (isset($documento['regia'])) {
                                                echo htmlspecialchars($documento['regia']);
                                            } else {
                                                echo 'N/A';
                                            }
                                            break;
                                        default:
                                            echo 'N/A';
                                    }
                                ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php echo htmlspecialchars($documento['anno_pubblicazione']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php echo htmlspecialchars(isset($documento['argomento']) ? $documento['argomento'] : 'Non specificato'); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php echo date('d/m/Y', strtotime($documento['data_inserimento'])); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <!-- Pulsanti azioni -->
                                <div class="flex items-center space-x-4">
                                    <?php 
                                    // Link di modifica basato sulla tipologia del documento
                                    $modifica_url = '';
                                    switch($documento['tipologia_doc']) {
                                        case 1: // Libro
                                            $modifica_url = "modifica_libro.php?id={$documento['id']}";
                                            break;
                                        case 2: // Rivista
                                            $modifica_url = "modifica_rivista.php?id={$documento['id']}";
                                            break;
                                        case 3: // Video
                                            $modifica_url = "modifica_video.php?id={$documento['id']}";
                                            break;
                                        default:
                                            // Se la tipologia è sconosciuta, usa il form generico
                                            $modifica_url = "modifica.php?id={$documento['id']}&generic=1";
                                    }
                                    ?>
                                    <a href="<?php echo $modifica_url; ?>" 
                                       class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-purple-600 rounded-lg dark:text-gray-400 focus:outline-none focus:shadow-outline-gray" 
                                       aria-label="Modifica">
                                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                        </svg>
                                    </a>
                                    <button @click="openDeleteModal(<?php echo $documento['id']; ?>)" 
                                            class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-purple-600 rounded-lg dark:text-gray-400 focus:outline-none focus:shadow-outline-gray" 
                                            aria-label="Elimina">
                                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($documenti)): ?>
                        <tr class="text-gray-700 dark:text-gray-400">
                            <td colspan="7" class="px-4 py-3 text-sm text-center">
                                Nessun documento presente.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal di conferma eliminazione -->
<div x-cloak x-show="isModalOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" @click="closeModal"></div>
        <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left transition-all transform bg-white rounded-lg shadow-xl dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-300" id="modal-title">
                    Conferma Eliminazione
                </h3>
                <button class="text-gray-400 focus:outline-none" @click="closeModal">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mt-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Sei sicuro di voler eliminare questo documento?<br>
                    Questa azione non può essere annullata.
                </p>
            </div>
            <div class="flex justify-end mt-6 space-x-4">
                <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Annulla
                </button>
                <a id="deleteConfirmBtn" href="#" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Elimina
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Script per DataTables -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('data', () => ({
        isModalOpen: false,
        documentIdToDelete: null,
        
        openDeleteModal(documentId) {
            this.isModalOpen = true;
            this.documentIdToDelete = documentId;
            
            // Aggiorna l'URL del pulsante di conferma
            document.getElementById('deleteConfirmBtn').href = `index.php?delete=${documentId}`;
        },
        
        closeModal() {
            this.isModalOpen = false;
        }
    }));
});

// Inizializza DataTables dopo che il DOM è caricato completamente
$(document).ready(function() {
    $('#documentiTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Italian.json"
        },
        "responsive": true,
        "pageLength": 10,
        "order": [[5, 'desc']] // Ordina per data inserimento (decrescente)
    });
});
</script>

<?php include '../includes/footer.php'; ?>
