<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - ecoTrip</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../css/registration.css">
    <link rel="stylesheet" href="../css/style.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { brand: { 50: '#f0fdf4', 100: '#dcfce7', 500: '#22c55e', 600: '#16a34a', 700: '#15803d', 900: '#14532d' } }
                }
            }
        }
    </script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>

<body class="bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0 flex items-center gap-2">
                        <i class="fa-solid fa-leaf text-brand-600 text-2xl"></i>
                        <span class="font-bold text-xl tracking-tight text-gray-900">ecoTrip</span>
                    </a>
                    <div class="hidden md:ml-10 md:flex md:space-x-8">
                        <a href="#" class="text-gray-500 hover:text-gray-900 hover:border-gray-300 border-b-2 border-transparent px-1 pt-1 text-sm font-medium">Challenges</a>
                        <a href="#" class="text-gray-500 hover:text-gray-900 hover:border-gray-300 border-b-2 border-transparent px-1 pt-1 text-sm font-medium">Leaderboard</a>
                        <a href="#" class="text-gray-500 hover:text-gray-900 hover:border-gray-300 border-b-2 border-transparent px-1 pt-1 text-sm font-medium">Marketplace</a>
                    </div>
                </div>

                <div class="flex items-center">
                    <div class="flex flex-col items-end mr-3">
                        <span class="text-sm font-bold text-gray-900">Guest</span>
                        <span class="text-xs text-brand-600 font-semibold">Join Us</span>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-gray-200 overflow-hidden border-2 border-white shadow-sm">
                        <img src="https://ui-avatars.com/api/?name=Guest&background=f3f4f6&color=6b7280" alt="Guest Avatar" class="h-full w-full object-cover">
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <main class="flex-grow flex items-center justify-center py-12">
         <?php include 'registration.php';?>
    </main>

    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-400">
                &copy; 2025 ecoTrip Inc. All rights reserved. Designed for a greener tomorrow.
            </p>
        </div>
    </footer>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>
</html>