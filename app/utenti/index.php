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

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Process delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Don't allow deleting your own account or the only admin account
    if ($id == $_SESSION['user_id']) {
        $message = "Non puoi eliminare il tuo account";
        $messageType = 'error';
    } else {
        // Check if this is the last admin
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM utenti WHERE ruolo = 1");
        $stmt->execute();
        $admin_count = $stmt->get_result()->fetch_assoc()['count'];
        
        $stmt = $conn->prepare("SELECT ruolo FROM utenti WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user_role = $stmt->get_result()->fetch_assoc()['ruolo'];
        
        if ($user_role == 1 && $admin_count <= 1) {
            $message = "Non puoi eliminare l'unico account amministratore";
            $messageType = 'error';
        } else {
            // Delete the user
            $stmt = $conn->prepare("DELETE FROM utenti WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Utente eliminato con successo";
                $messageType = 'success';
            } else {
                $message = "Errore durante l'eliminazione dell'utente";
                $messageType = 'error';
            }
        }
    }
}

// Get all users
$stmt = $conn->prepare("SELECT * FROM utenti ORDER BY nominativo");
$stmt->execute();
$utenti = $stmt->get_result();

include '../includes/header.php';
?>

<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Utenti
</h2>

<?php if (isset($message)) : ?>
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

<div class="w-full overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap datatable">
            <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                    <th class="px-4 py-3">Nome</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Ruolo</th>
                    <th class="px-4 py-3">Ultimo accesso</th>
                    <th class="px-4 py-3">Data creazione</th>
                    <th class="px-4 py-3">Azioni</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                <?php if ($utenti->num_rows > 0) : ?>
                    <?php while ($utente = $utenti->fetch_assoc()) : ?>
                        <tr class="text-gray-700 dark:text-gray-400">
                            <td class="px-4 py-3">
                                <?php echo htmlspecialchars($utente['nominativo']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php echo htmlspecialchars($utente['email']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if ($utente['ruolo'] == 1): ?>
                                    <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
                                        Amministratore
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 font-semibold leading-tight text-orange-700 bg-orange-100 rounded-full dark:bg-orange-700 dark:text-orange-100">
                                        Editor
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php echo $utente['last_login'] ? date('d/m/Y', strtotime($utente['last_login'])) : 'Mai'; ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php echo date('d/m/Y', strtotime($utente['created_at'])); ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-4 text-sm">
                                    <a href="modifica.php?id=<?php echo $utente['id']; ?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-purple-600 rounded-lg dark:text-gray-400 focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                        </svg>
                                    </a>
                                    <?php if ($utente['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="confirmDelete(<?php echo $utente['id']; ?>, '<?php echo htmlspecialchars($utente['nominativo']); ?>')" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-purple-600 rounded-lg dark:text-gray-400 focus:outline-none focus:shadow-outline-gray" aria-label="Delete">
                                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr class="text-gray-700 dark:text-gray-400">
                        <td colspan="6" class="px-4 py-3 text-sm text-center">
                            Nessun utente trovato
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">
    <a href="aggiungi.php" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
        Aggiungi utente
    </a>
</div>

<!-- Delete confirmation modal -->
<div id="deleteModal" class="fixed inset-0 z-30 hidden items-end bg-black bg-opacity-50 sm:items-center sm:justify-center">
    <div class="w-full px-6 py-4 overflow-hidden bg-white rounded-t-lg dark:bg-gray-800 sm:rounded-lg sm:m-4 sm:max-w-xl" role="dialog" id="modal">
        <header class="flex justify-end">
            <button class="inline-flex items-center justify-center w-6 h-6 text-gray-400 transition-colors duration-150 rounded dark:hover:text-gray-200 hover:text-gray-700" aria-label="close" onclick="closeModal()">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" role="img" aria-hidden="true">
                    <path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" fill-rule="evenodd"></path>
                </svg>
            </button>
        </header>
        <!-- Modal body -->
        <div class="mt-4 mb-6">
            <p class="mb-2 text-lg font-semibold text-gray-700 dark:text-gray-300">
                Conferma eliminazione
            </p>
            <p class="text-sm text-gray-700 dark:text-gray-400" id="deleteModalText">
                Sei sicuro di voler eliminare questo utente?
            </p>
        </div>
        <footer class="flex flex-col items-center justify-end px-6 py-3 -mx-6 -mb-4 space-y-4 sm:space-y-0 sm:space-x-6 sm:flex-row bg-gray-50 dark:bg-gray-800">
            <button onclick="closeModal()" class="w-full px-5 py-3 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg sm:w-auto sm:px-4 sm:py-2 active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                Annulla
            </button>
            <a id="confirmDeleteButton" href="#" class="w-full px-5 py-3 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-red-600 border border-transparent rounded-lg sm:w-auto sm:px-4 sm:py-2 active:bg-red-600 hover:bg-red-700 focus:outline-none focus:shadow-outline-red">
                Elimina
            </a>
        </footer>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        document.getElementById('deleteModalText').innerText = 'Sei sicuro di voler eliminare l\'utente "' + name + '"?';
        document.getElementById('confirmDeleteButton').href = 'index.php?delete=' + id;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }
    
    function closeModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }
</script>

<?php include '../includes/footer.php'; ?>
