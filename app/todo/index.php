<?php
// filepath: /Users/Odn/Documents/Lavori O(n)/digitalmente/app/todo/index.php
require_once '../includes/header.php';

// Check if a message should be displayed
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Get all todo tasks for the current user
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT id, task, data_scadenza, completato, data_inserimento
    FROM todo
    WHERE utente_id = ?
    ORDER BY completato ASC, data_scadenza ASC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Todo
</h2>

<!-- Display message if present -->
<?php if (!empty($message)): ?>
    <?php echo $message; ?>
<?php endif; ?>

<!-- Add new task button -->
<div class="mb-6">
    <a href="aggiungi.php" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
        Aggiungi nuovo task
    </a>
</div>

<!-- Todo list table -->
<div class="w-full overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <table class="w-full whitespace-no-wrap" id="todoTable">
            <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                    <th class="px-4 py-3">Task</th>
                    <th class="px-4 py-3">Data scadenza</th>
                    <th class="px-4 py-3">Stato</th>
                    <th class="px-4 py-3">Data inserimento</th>
                    <th class="px-4 py-3">Azioni</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="text-gray-700 dark:text-gray-400">
                            <td class="px-4 py-3">
                                <?php if ($row['completato']): ?>
                                    <span class="line-through"><?php echo htmlspecialchars($row['task']); ?></span>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($row['task']); ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php
                                $data_scadenza = !empty($row['data_scadenza']) ? date('d/m/Y', strtotime($row['data_scadenza'])) : 'N/A';
                                $is_overdue = !$row['completato'] && !empty($row['data_scadenza']) && strtotime($row['data_scadenza']) < strtotime(date('Y-m-d'));
                                
                                if ($is_overdue) {
                                    echo '<span class="text-red-600 font-semibold">' . $data_scadenza . '</span>';
                                } else {
                                    echo $data_scadenza;
                                }
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($row['completato']): ?>
                                    <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
                                        Completato
                                    </span>
                                <?php else: ?>
                                    <form method="post" action="modifica.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <button type="submit" class="px-2 py-1 font-semibold leading-tight text-orange-700 bg-orange-100 rounded-full dark:bg-orange-600 dark:text-white">
                                            Da fare
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php echo date('d/m/Y', strtotime($row['data_inserimento'])); ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-4 text-sm">
                                    <a href="modifica.php?id=<?php echo $row['id']; ?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-purple-600 rounded-lg dark:text-gray-400 focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                        </svg>
                                    </a>
                                    <button @click="openDeleteModal(<?php echo $row['id']; ?>)" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-purple-600 rounded-lg dark:text-gray-400 focus:outline-none focus:shadow-outline-gray" aria-label="Delete">
                                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr class="text-gray-700 dark:text-gray-400">
                        <td colspan="5" class="px-4 py-3 text-center">Nessun task presente.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete confirmation modal -->
<div x-show="isDeleteModalOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-30 flex items-end bg-black bg-opacity-50 sm:items-center sm:justify-center">
    <div x-show="isDeleteModalOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 transform translate-y-1/2" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0  transform translate-y-1/2" @click.away="closeDeleteModal" @keydown.escape="closeDeleteModal" class="w-full px-6 py-4 overflow-hidden bg-white rounded-t-lg dark:bg-gray-800 sm:rounded-lg sm:m-4 sm:max-w-xl">
        <header class="flex justify-between">
            <h2 class="text-lg font-medium text-gray-700 dark:text-gray-300">
                Conferma eliminazione
            </h2>
        </header>
        <div class="mt-4 mb-6">
            <p class="text-sm text-gray-700 dark:text-gray-400">
                Sei sicuro di voler eliminare questo task? Questa azione non può essere annullata.
            </p>
        </div>
        <footer class="flex flex-col items-center justify-end px-6 py-3 -mx-6 -mb-4 space-y-4 sm:space-y-0 sm:space-x-6 sm:flex-row bg-gray-50 dark:bg-gray-800">
            <button @click="closeDeleteModal" class="w-full px-5 py-3 text-sm font-medium leading-5 text-gray-700 transition-colors duration-150 border border-gray-300 rounded-lg dark:text-gray-400 sm:px-4 sm:py-2 sm:w-auto active:bg-transparent hover:border-gray-500 focus:border-gray-500 active:text-gray-500 focus:outline-none focus:shadow-outline-gray">
                Annulla
            </button>
            <form id="deleteForm" method="post" action="elimina.php">
                <input type="hidden" name="id" id="deleteId" value="">
                <button type="submit" class="w-full px-5 py-3 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-red-600 border border-transparent rounded-lg sm:w-auto sm:px-4 sm:py-2 active:bg-red-600 hover:bg-red-700 focus:outline-none focus:shadow-outline-red">
                    Elimina
                </button>
            </form>
        </footer>
    </div>
</div>

<script>
    // Initialize DataTables
    $(document).ready(function() {
        $('#todoTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Italian.json"
            },
            "order": [[ 2, "asc" ], [ 1, "asc" ]],
            "columnDefs": [
                { "orderable": false, "targets": 4 }
            ]
        });
    });
    
    // Add to Alpine.js data
    document.addEventListener('alpine:init', () => {
        Alpine.data('data', () => ({
            isDeleteModalOpen: false,
            openDeleteModal(id) {
                this.isDeleteModalOpen = true;
                document.getElementById('deleteId').value = id;
            },
            closeDeleteModal() {
                this.isDeleteModalOpen = false;
            }
        }));
    });
</script>

<?php require_once '../includes/footer.php'; ?>
