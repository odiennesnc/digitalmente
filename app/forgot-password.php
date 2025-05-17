<?php
// Start session
session_start();

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Include database connection
require_once 'config/db.php';
require_once 'includes/functions.php';

$message = '';
$messageType = '';

// Process forgot password form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanData($_POST['email']);
    
    if (empty($email)) {
        $message = 'Inserisci la tua email';
        $messageType = 'error';
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, nominativo FROM utenti WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $reset_id = bin2hex(random_bytes(16));
            
            // Save reset token in database
            $stmt = $conn->prepare("UPDATE utenti SET reset_id = ? WHERE id = ?");
            $stmt->bind_param("si", $reset_id, $user['id']);
            
            if ($stmt->execute()) {
                // In a real application, send email with reset link
                // For this demo, just show the link
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $reset_id;
                
                $message = "Un'email con le istruzioni per il reset della password è stata inviata a {$email}.<br>";
                $message .= "Link per il reset: <a href='{$resetLink}'>{$resetLink}</a>";
                $messageType = 'success';
            } else {
                $message = "Si è verificato un errore, riprova più tardi";
                $messageType = 'error';
            }
        } else {
            // Don't reveal if email doesn't exist for security
            $message = "Se l'email esiste nel nostro database, riceverai un link per reimpostare la password";
            $messageType = 'success';
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html :class="{ 'theme-dark': dark }" x-data="data()" lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password dimenticata - Digitalmente</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tailwind.output.css">
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <script src="assets/js/init-alpine.js"></script>
</head>
<body>
    <div class="flex items-center min-h-screen p-6 bg-gray-50 dark:bg-gray-900">
        <div class="flex-1 h-full max-w-4xl mx-auto overflow-hidden bg-white rounded-lg shadow-xl dark:bg-gray-800">
            <div class="flex flex-col overflow-y-auto md:flex-row">
                <div class="h-32 md:h-auto md:w-1/2">
                    <img aria-hidden="true" class="object-cover w-full h-full dark:hidden" src="assets/img/forgot-password-office.jpeg" alt="Office">
                    <img aria-hidden="true" class="hidden object-cover w-full h-full dark:block" src="assets/img/forgot-password-office-dark.jpeg" alt="Office">
                </div>
                <div class="flex items-center justify-center p-6 sm:p-12 md:w-1/2">
                    <div class="w-full">
                        <h1 class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200">
                            Password dimenticata
                        </h1>
                        <?php if ($message !== '') : ?>
                            <?php if ($messageType === 'error') : ?>
                                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4">
                                    <p><?php echo $message; ?></p>
                                </div>
                            <?php else : ?>
                                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4">
                                    <p><?php echo $message; ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <form method="POST">
                            <label class="block text-sm">
                                <span class="text-gray-700 dark:text-gray-400">Email</span>
                                <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" placeholder="Inserisci la tua email" type="email" name="email" required>
                            </label>

                            <button type="submit" class="block w-full px-4 py-2 mt-4 text-sm font-medium leading-5 text-center text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                                Recupera password
                            </button>
                        </form>

                        <hr class="my-8">

                        <p class="mt-4">
                            <a class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline" href="login.php">
                                Torna al login
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function data() {
            return {
                dark: false,
                toggleTheme() {
                    this.dark = !this.dark;
                }
            };
        }
    </script>
</body>
</html>
