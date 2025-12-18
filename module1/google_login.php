<?php
// Include configuration
require_once 'config_google.php';

// ==========================================
// 🔴 Key change: force display of the account chooser page
// ==========================================
$client->setPrompt('select_account');

// Generate Google login link
$authUrl = $client->createAuthUrl();

// Redirect to Google
header('Location: ' . $authUrl);
exit();
?>