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
$argomento = '';
$id = 0;

// Check if ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    
    // Get topic data
    $stmt = $conn->prepare("SELECT * FROM argomenti WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $argomento = $row['argomento'];
    } else {
        redirect('index.php');
    }
} else {
    redirect('index.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $argomento = cleanData($_POST['argomento']);
    
    if (empty($argomento)) {
        $message = "Inserisci un argomento";
        $messageType = 'error';
    } else {
        // Check if topic already exists with a different ID
        $stmt = $conn->prepare("SELECT id FROM argomenti WHERE argomento = ? AND id != ?");
        $stmt->bind_param("si", $argomento, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = "Questo argomento esiste già";
            $messageType = 'error';
        } else {
            // Update topic
            $stmt = $conn->prepare("UPDATE argomenti SET argomento = ? WHERE id = ?");
            $stmt->bind_param("si", $argomento, $id);
            
            if ($stmt->execute()) {
                $message = "Argomento aggiornato con successo";
                $messageType = 'success';
            } else {
                $message = "Errore durante l'aggiornamento dell'argomento";
                $messageType = 'error';
            }
        }
    }
}

include '../includes/header.php';
?>

<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Modifica argomento
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
    <form method="POST">
        <label class="block text-sm">
            <span class="text-gray-700 dark:text-gray-400">Argomento</span>
            <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                   placeholder="Inserisci un argomento" 
                   type="text" 
                   name="argomento" 
                   value="<?php echo htmlspecialchars($argomento); ?>" 
                   required>
        </label>

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

<?php include '../includes/footer.php'; ?>
