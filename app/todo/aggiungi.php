<?php
// filepath: /Users/Odn/Documents/Lavori O(n)/digitalmente/app/todo/aggiungi.php
require_once '../includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login.php');
}

$task = '';
$data_scadenza = '';
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate task input
    if (empty($_POST['task'])) {
        $error = displayError('Il campo task è obbligatorio');
    } else {
        $task = cleanData($_POST['task']);
        $data_scadenza = !empty($_POST['data_scadenza']) ? $_POST['data_scadenza'] : null;
        $utente_id = $_SESSION['user_id'];
        
        // Prepare statement for insertion
        $stmt = $conn->prepare("INSERT INTO todo (utente_id, task, data_scadenza) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $utente_id, $task, $data_scadenza);
        
        // Execute statement
        if ($stmt->execute()) {
            // Set success message and redirect
            $_SESSION['message'] = displaySuccess('Task aggiunto con successo');
            redirect('index.php');
        } else {
            $error = displayError('Errore nell\'aggiunta del task: ' . $conn->error);
        }
        
        $stmt->close();
    }
}
?>

<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Aggiungi nuovo task
</h2>

<!-- Display error message if present -->
<?php if (!empty($error)): ?>
    <?php echo $error; ?>
<?php endif; ?>

<!-- Display success message if present -->
<?php if (!empty($success)): ?>
    <?php echo $success; ?>
<?php endif; ?>

<!-- Form for adding a new todo task -->
<div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md dark:bg-gray-800">
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
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
            ><?php echo htmlspecialchars($task); ?></textarea>
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
                value="<?php echo htmlspecialchars($data_scadenza); ?>"
            >
        </div>

        <div class="flex mt-6 text-sm">
            <button type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                Salva
            </button>
            <a href="index.php" class="px-4 py-2 ml-4 text-sm font-medium leading-5 text-gray-700 transition-colors duration-150 border border-gray-300 rounded-lg dark:text-gray-400 active:bg-transparent hover:border-gray-500 focus:border-gray-500 active:text-gray-500 focus:outline-none focus:shadow-outline-gray">
                Annulla
            </a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
