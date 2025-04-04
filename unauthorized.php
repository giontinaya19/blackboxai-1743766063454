<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access | DYCI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col justify-center items-center p-4">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full text-center">
            <div class="text-red-500 text-6xl mb-4">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Unauthorized Access</h1>
            <p class="text-gray-600 mb-6">
                You don't have permission to access this page. Please contact your administrator if you believe this is an error.
            </p>
            <div class="space-y-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin-dashboard.php' : 'org-officer-dashboard.php'; ?>" 
                        class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-arrow-left mr-2"></i> Return to Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php" 
                        class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-sign-in-alt mr-2"></i> Login
                    </a>
                <?php endif; ?>
                <a href="index.php" 
                    class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-200">
                    <i class="fas fa-home mr-2"></i> Go to Homepage
                </a>
            </div>
        </div>
    </div>
</body>
</html>