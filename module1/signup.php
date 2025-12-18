<?php
// 1. Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Include DB connection (with error checks)
require('database.php');

if (!isset($con)) {
    die("Database connection failed. Variable \$con is not defined.");
}

$error_msg = "";
$register_success = false; // Initialize success flag

// 3. Handle registration logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    
    // Sanitize data
    $firstname = mysqli_real_escape_string($con, stripslashes($_POST['Firstname']));
    $lastname  = mysqli_real_escape_string($con, stripslashes($_POST['Lastname']));
    $email     = mysqli_real_escape_string($con, stripslashes($_POST['Email']));
    $password  = mysqli_real_escape_string($con, stripslashes($_POST['password']));
    
    $trn_date  = date("Y-m-d H:i:s");

    // [Added] Backend validation of Email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format. Please check your input.";
    } else {
        // Format correct; continue duplicate check
        $check_query = "SELECT Email FROM `user` WHERE Email='$email'";
        $check_result = mysqli_query($con, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $error_msg = "This email is already registered. Please login.";
        } else {
            // Insert new user
            $hashed_password = md5($password);
            $query = "INSERT INTO `user` (First_Name, Last_Name, Email, Password, Register_Date, Role, Point, Account_Status) 
                      VALUES ('$firstname', '$lastname', '$email', '$hashed_password', '$trn_date', 0, 0, 'Active')";
            
            $result = mysqli_query($con, $query);

            if ($result) {
                // Registration success: set flag only; do not use exit()
                $register_success = true;
            } else {
                $error_msg = "Database Error: " . mysqli_error($con);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - ecoTrip</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { brand: { 50: '#f0fdf4', 100: '#dcfce7', 500: '#22c55e', 600: '#16a34a', 700: '#15803d' } }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 min-h-screen flex overflow-hidden">

    <div class="hidden lg:flex lg:w-1/2 relative bg-gray-900">
        <img src="https://images.unsplash.com/photo-1511497584788-876760111969?ixlib=rb-4.0.3&auto=format&fit=crop&w=1932&q=80" 
             class="absolute inset-0 w-full h-full object-cover opacity-50" 
             alt="Nature Background"
             onerror="this.style.display='none'"> 
        
        <div class="relative z-10 w-full flex flex-col justify-center px-12 text-white">
            <div class="mb-6">
                <i class="fa-solid fa-earth-americas text-5xl text-brand-400"></i>
            </div>
            <h2 class="text-5xl font-bold leading-tight mb-4">Join the <br>Movement</h2>
            <p class="text-xl text-gray-200">Start your journey towards a sustainable lifestyle today.</p>
        </div>
    </div>

    <div class="w-full lg:w-1/2 flex items-center justify-center bg-white overflow-y-auto h-screen">
        <div class="w-full max-w-lg px-8 py-12">
            
            <div class="lg:hidden flex items-center gap-2 mb-6 justify-center">
                <i class="fa-solid fa-leaf text-brand-600 text-3xl"></i>
                <span class="font-bold text-2xl text-gray-900">ecoTrip</span>
            </div>

            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Create Account</h2>
                <p class="text-sm text-gray-500 mt-2">Fill in your details to get started.</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r flex items-center">
                    <i class="fa-solid fa-circle-exclamation mr-3"></i>
                    <div>
                        <p class="font-bold">Error</p>
                        <p class="text-sm"><?php echo $error_msg; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form action="" method="post" class="space-y-5">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <div class="relative">
                            <input type="text" name="Firstname" required 
                                   class="block w-full rounded-lg border-gray-300 border bg-gray-50 py-2.5 px-4 text-gray-900 focus:bg-white focus:border-brand-500 focus:ring-brand-500 sm:text-sm outline-none transition" 
                                   placeholder="John">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <div class="relative">
                            <input type="text" name="Lastname" required 
                                   class="block w-full rounded-lg border-gray-300 border bg-gray-50 py-2.5 px-4 text-gray-900 focus:bg-white focus:border-brand-500 focus:ring-brand-500 sm:text-sm outline-none transition" 
                                   placeholder="Doe">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" name="Email" required 
                               class="pl-10 block w-full rounded-lg border-gray-300 border bg-gray-50 py-2.5 text-gray-900 focus:bg-white focus:border-brand-500 focus:ring-brand-500 sm:text-sm outline-none transition" 
                               placeholder="you@example.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="password" required minlength="8"
                               class="pl-10 block w-full rounded-lg border-gray-300 border bg-gray-50 py-2.5 text-gray-900 focus:bg-white focus:border-brand-500 focus:ring-brand-500 sm:text-sm outline-none transition" 
                               placeholder="••••••••">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" name="submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all duration-200 transform hover:-translate-y-0.5">
                        Create Account
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="index.php" class="font-bold text-brand-600 hover:text-brand-500 hover:underline">
                        Log In
                    </a>
                </p>
            </div>
            
            <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                <p class="text-xs text-gray-400">&copy; 2025 ecoTrip Inc. All rights reserved.</p>
            </div>
        </div>
    </div>

    <?php if ($register_success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Welcome Aboard!',
                text: 'Your account has been created successfully.',
                icon: 'success',
                confirmButtonColor: '#16a34a',
                confirmButtonText: 'Go to Login',
                allowOutsideClick: false,
                backdrop: `rgba(0,0,0,0.5)`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php';
                }
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>