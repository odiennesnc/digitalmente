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

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanData($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Inserisci email e password';
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, nominativo, password, ruolo FROM utenti WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nominativo'];
                $_SESSION['user_role'] = $user['ruolo'];
                
                // Update last login
                $stmt = $conn->prepare("UPDATE utenti SET last_login = CURRENT_DATE() WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                // Redirect to dashboard
                header('Location: index.php');
                exit;
            } else {
                $error = 'Email o password non corretti';
            }
        } else {
            $error = 'Email o password non corretti';
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
    <title>Login - Digitalmente</title>
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
                    <img aria-hidden="true" class="object-cover w-full h-full dark:hidden" src="assets/img/login-office.jpeg" alt="Office">
                    <img aria-hidden="true" class="hidden object-cover w-full h-full dark:block" src="assets/img/login-office-dark.jpeg" alt="Office">
                </div>
                <div class="flex items-center justify-center p-6 sm:p-12 md:w-1/2">
                    <div class="w-full">
                        <h1 class="mb-4 text-xl font-semibold text-gray-700 dark:text-gray-200">
                            Login
                        </h1>
                        <?php if ($error !== '') : ?>
                            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4">
                                <p><?php echo $error; ?></p>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <label class="block text-sm">
                                <span class="text-gray-700 dark:text-gray-400">Email</span>
                                <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" placeholder="Email" type="email" name="email" required>
                            </label>
                            <label class="block mt-4 text-sm">
                                <span class="text-gray-700 dark:text-gray-400">Password</span>
                                <input class="block w-full mt-1 text-sm dark:border-gray-600 dark:bg-gray-700 focus:border-purple-400 focus:outline-none focus:shadow-outline-purple dark:text-gray-300 dark:focus:shadow-outline-gray form-input" placeholder="***************" type="password" name="password" required>
                            </label>

                            <button type="submit" class="block w-full px-4 py-2 mt-4 text-sm font-medium leading-5 text-center text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
                                Log in
                            </button>
                        </form>

                        <hr class="my-8">

                        <p class="mt-4">
                            <a class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline" href="forgot-password.php">
                                Password dimenticata?
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
