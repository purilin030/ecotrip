<?php
// 引入配置
require_once 'config_google.php';

// ==========================================
// 🔴 关键修改：强制显示“选择账号”页面
// ==========================================
$client->setPrompt('select_account');

// 生成 Google 登录链接
$authUrl = $client->createAuthUrl();

// 跳转到 Google
header('Location: ' . $authUrl);
exit();
?>