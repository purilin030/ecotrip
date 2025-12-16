<?php
require_once '../vendor/autoload.php'; // 引入 Composer 下载的库

// 1. 初始化 Google Client
$client = new Google_Client();

// ==========================================
// 🔴 请把下面的内容换成你第一步里获取到的
// ==========================================
$clientID = '696175720006-s73l6g5sc6rdpg4plknm9b2h7v1qb2om.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/ecotrip/module1/google_callback.php'; // 必须和后台填的一模一样

$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

// 我们需要获取用户的 Email 和 个人资料(名字/头像)
$client->addScope("email");
$client->addScope("profile");
?>