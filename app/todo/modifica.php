<?php
// filepath: /Users/Odn/Documents/Lavori O(n)/digitalmente/app/todo/modifica.php
require_once '../includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login.php');
}

$id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);
$error = '';
$success = '';

// Check if we're toggling the status of a task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $id = $_POST['id'];
    
    // First verify the task belongs to the current user
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT completato FROM todo WHERE id = ? AND utente_id = ?");
    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $newStatus = $row['completato'] ? 0 : 1; // Toggle the status
        
        // Update the task status
        $updateStmt = $conn->prepare("UPDATE todo SET completato = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $newStatus, $id);
        
        if ($updateStmt->execute()) {
            $status = $newStatus ? 'completato' : 'da fare';
            $_SESSION['message'] = displaySuccess("Task segnato come $status");
            redirect('index.php');
        } else {
            $error = displayError('Errore nell\'aggiornamento dello stato del task');
        }
        
        $updateStmt->close();
    } else {
        $error = displayError('Task non trovato o non autorizzato');
    }
    
    $stmt->close();
}

// If no ID is provided, redirect to index
if (!$id) {
    redirect('index.php');
}

// Fetch task data if we're editing
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT task, data_scadenza, completato FROM todo WHERE id = ? AND utente_id = ?");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$result = $stmt->get_result();

// Check if task exists and belongs to current user
if ($result->num_rows !== 1) {
    $_SESSION['message'] = displayError('Task non trovato o non autorizzato');
    redirect('index.php');
}

$task = $result->fetch_assoc();
$stmt->close();

// Process form submission for updating task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    // Validate task input
    if (empty($_POST['task'])) {
        $error = displayError('Il campo task è obbligatorio');
    } else {
        $taskText = cleanData($_POST['task']);
        $data_scadenza = !empty($_POST['data_scadenza']) ? $_POST['data_scadenza'] : null;
        $completato = isset($_POST['completato']) ? 1 : 0;
        
        // Prepare statement for update
        $stmt = $conn->prepare("UPDATE todo SET task = ?, data_scadenza = ?, completato = ? WHERE id = ? AND utente_id = ?");
        $stmt->bind_param("ssiii", $taskText, $data_scadenza, $completato, $id, $userId);
        
        // Execute statement
        if ($stmt->execute()) {
            // Set success message and redirect
            $_SESSION['message'] = displaySuccess('Task aggiornato con successo');
            redirect('index.php');
        } else {
            $error = displayError('Errore nell\'aggiornamento del task: ' . $conn->error);
        }
        
        $stmt->close();
    }
}
?>

<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Modifica task
</h2>

<!-- Display error message if present -->
<?php if (!empty($error)): ?>
    <?php echo $error; ?>
<?php endif; ?>

<!-- Display success message if present -->
<?php if (!empty($success)): ?>
    <?php echo $success; ?>
<?php endif; ?>

<!-- Form for editing a todo task -->
<div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md dark:bg-gray-800">
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400" for="task">
                Task <span class="text-red-500">*</span>
            </label>
            <textarea 
                class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-textarea focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray" 
                name="task" 
                id="task" 
                rows="3" 
                placeholder="Inserisci il task"
                required
            ><?php echo htmlspecialchars($task['task']); ?></textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400" for="data_scadenza">
                Data scadenza
            </label>
            <input 
                class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                type="date" 
                name="data_scadenza" 
                id="data_scadenza" 
                value="<?php echo htmlspecialchars($task['data_scadenza']); ?>"
            >
        </div>

        <div class="mb-4">
            <label class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-400" for="completato">
                <input 
                    type="checkbox" 
                    name="completato" 
                    id="completato" 
                    class="text-purple-600 form-checkbox focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray"
                    <?php echo $task['completato'] ? 'checked' : ''; ?>
                >
                <span class="ml-2">
                    Completato
                </span>
            </label>
        </div>

        <div class="flex mt-6 text-sm">
            <button type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                Aggiorna
            </button>
            <a href="index.php" class="px-4 py-2 ml-4 text-sm font-medium leading-5 text-gray-700 transition-colors duration-150 border border-gray-300 rounded-lg dark:text-gray-400 active:bg-transparent hover:border-gray-500 focus:border-gray-500 active:text-gray-500 focus:outline-none focus:shadow-outline-gray">
                Annulla
            </a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
