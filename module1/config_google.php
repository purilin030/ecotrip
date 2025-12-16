<?php
require_once '../vendor/autoload.php'; // 引入 Composer 下载的库

// 1. 初始化 Google Client
$client = new Google_Client();

// ==========================================
// 🔴 请把下面的内容换成你第一步里获取到的
// ==========================================
$clientID = '1001467190198-7tgn3spplpn728pf0ll7v4rfoe8go2hv.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/ecotrip/module1/google_callback.php'; // 必须和后台填的一模一样

$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

// 我们需要获取用户的 Email 和 个人资料(名字/头像)
$client->addScope("email");
$client->addScope("profile");
?>