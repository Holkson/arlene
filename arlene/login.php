<?php
// login.php
session_start();

// --- CONFIGURATION: CHANGE THIS PASSWORD! ---
$ADMIN_USERNAME = 'admin';
$ADMIN_PASSWORD = 'Admin123!'; 
// --------------------------------------------

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === $ADMIN_USERNAME && $pass === $ADMIN_PASSWORD) {
        // Success! Set session and redirect
        $_SESSION['is_logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex justify-center items-center">
    <div class="bg-white p-8 rounded shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6 text-center text-green-800">Admin Login</h2>
        
        <?php if($error): ?>
            <div class="bg-red-100 text-red-700 p-2 rounded mb-4 text-sm text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" class="w-full border p-2 rounded" required autofocus>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" class="w-full border p-2 rounded" required>
            </div>
            <button type="submit" class="w-full bg-green-700 text-white font-bold py-2 rounded hover:bg-green-800">
                Login
            </button>
        </form>
    </div>
</body>
</html>