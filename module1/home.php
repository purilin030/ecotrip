<?php
session_start();
require 'database.php'; // 必须引入，因为 header.php 需要连接数据库查头像

// 安全检查
if (!isset($_SESSION['Firstname'])) {
    header("Location: index.php");
    exit();
}

// 设置页面参数 (传给 header.php 使用)
$page_title = "ecoTrip - Challenges";
// 保留你原本引用的 login.css
$extra_css = '<link rel="stylesheet" href="/../login.css">';

// 引入通用头部
include '../header.php';
?>

    <main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-12">
        <div class="max-w-7xl mx-auto">
            <br><br><br><br><br><br><br><br><br><br><br>
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Welcome to EcoTrip</h1>
            <p class="text-gray-600">This website is currently on maintenance.</p>

        </div>
    </main>
    
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-400">
                &copy; 2025 ecoTrip Inc. All rights reserved. Designed for a greener tomorrow.
            </p>
        </div>
    </footer>

</body>
</html>