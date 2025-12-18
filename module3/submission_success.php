<?php
// === 1. Configuration & connection ===
$path_to_db = __DIR__ . '/../database.php';


// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



// Permission check (prevent direct access)
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = '../index.php';</script>";
    exit();
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="style.css"> 

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

<div class="min-h-[80vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 text-center">

            <div class="mb-6">
                <i class="fa-solid fa-circle-check text-green-500 text-7xl animate-bounce-slow"></i>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-2">Submission Received!</h2>

            <p class="text-gray-500 mb-8 text-sm leading-relaxed">
                Great job! Your photo proof has been uploaded successfully.<br>
                Our team will verify it shortly.
            </p>

            <div class="space-y-4">
                <a href="submission_list.php"
                    class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition">
                    <i class="fa-solid fa-list-check mr-2"></i> Track My Status
                </a>

                <div class="pt-2">
                    <a href="/ecotrip/module1/home.php" class="text-sm font-medium text-gray-400 hover:text-gray-600 transition">
                        Back to Home
                    </a>
                </div>
            </div>

        </div>

        <div class="text-center mt-6 text-xs text-gray-400">
            &copy; 2025 ecoTrip Inc.
        </div>
    </div>
</div>

<style>
    /* Custom slow bounce animation, slightly slower than Tailwind's bounce */
    @keyframes bounce-slow {

        0%,
        100% {
            transform: translateY(-5%);
            animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
        }

        50% {
            transform: translateY(0);
            animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
        }
    }

    .animate-bounce-slow {
        animation: bounce-slow 2s infinite;
    }
</style>

</body>

</html>