<?php
require_once '../vendor/autoload.php'; // Include libraries installed by Composer

// 1. Initialize Google Client
$client = new Google_Client();

// ==========================================
// 🔴 Replace the values below with the ones you obtained in Step 1
// ==========================================
$clientID = '1001467190198-7tgn3spplpn728pf0ll7v4rfoe8go2hv.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/ecotrip/module1/google_callback.php'; // Must match the value set in the backend exactly

$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

// We need access to the user's email and profile (name/avatar)
$client->addScope("email");
$client->addScope("profile");
?>