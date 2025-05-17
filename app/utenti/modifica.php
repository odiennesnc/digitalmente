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

$message = '';
$messageType = '';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}

$id = (int)$_GET['id'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM utenti WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    redirect('index.php');
}

$utente = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominativo = cleanData($_POST['nominativo']);
    $email = cleanData($_POST['email']);
    $password = $_POST['password'];
    $ruolo = (int)$_POST['ruolo'];
    
    if (empty($nominativo) || empty($email)) {
        $message = "Nome e email sono obbligatori";
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email non valida";
        $messageType = 'error';
    } else {
        // Check if email already exists for another user
        $stmt = $conn->prepare("SELECT id FROM utenti WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = "Email già in uso";
            $messageType = 'error';
        } else {
            // Update user
            if (!empty($password)) {
                // Password is being changed
                if (strlen($password) < 8) {
                    $message = "La password deve essere lunga almeno 8 caratteri";
                    $messageType = 'error';
                } else {
                    // Hash new password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Update user with new password
                    $stmt = $conn->prepare("UPDATE utenti SET nominativo = ?, email = ?, password = ?, ruolo = ? WHERE id = ?");
                    $stmt->bind_param("sssii", $nominativo, $email, $hashed_password, $ruolo, $id);
                }
            } else {
                // Password not changed
                $stmt = $conn->prepare("UPDATE utenti SET nominativo = ?, email = ?, ruolo = ? WHERE id = ?");
                $stmt->bind_param("ssii", $nominativo, $email, $ruolo, $id);
            }
            
            if (!isset($messageType) || $messageType !== 'error') {
                if ($stmt->execute()) {
                    $message = "Utente aggiornato con successo";
                    $messageType = 'success';
                    
                    // Update local user data
                    $utente['nominativo'] = $nominativo;
                    $utente['email'] = $email;
                    $utente['ruolo'] = $ruolo;
                    
                    // If updating the current user, update session
                    if ($id == $_SESSION['user_id']) {
                        $_SESSION['user_name'] = $nominativo;
                        $_SESSION['user_role'] = $ruolo;
                    }
                } else {
                    $message = "Errore durante l'aggiornamento dell'utente: " . $stmt->error;
                    $messageType = 'error';
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Modifica utente
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
        <div class="mb-4">
            <label class="block text-sm">
                <span class="text-gray-700 dark:text-gray-400">Nome</span>
                <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                       name="nominativo" 
                       placeholder="Nome completo" 
                       value="<?php echo htmlspecialchars($utente['nominativo']); ?>"
                       required>
            </label>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm">
                <span class="text-gray-700 dark:text-gray-400">Email</span>
                <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                       type="email"
                       name="email" 
                       placeholder="Email" 
                       value="<?php echo htmlspecialchars($utente['email']); ?>"
                       required>
            </label>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm">
                <span class="text-gray-700 dark:text-gray-400">Password</span>
                <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" 
                       type="password"
                       name="password" 
                       placeholder="Nuova password">
                <span class="text-xs text-gray-600 dark:text-gray-400">
                    Lascia vuoto per mantenere la password attuale. La nuova password deve contenere almeno 8 caratteri.
                </span>
            </label>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm">
                <span class="text-gray-700 dark:text-gray-400">Ruolo</span>
                <select class="block w-full mt-1 text-sm dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700 form-select focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:focus:shadow-outline-gray" 
                        name="ruolo">
                    <option value="1" <?php echo $utente['ruolo'] == 1 ? 'selected' : ''; ?>>Amministratore</option>
                    <option value="2" <?php echo $utente['ruolo'] == 2 ? 'selected' : ''; ?>>Editor</option>
                </select>
            </label>
        </div>
        
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
