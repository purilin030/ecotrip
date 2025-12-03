<?php
require '../database.php';
session_start();

if (!isset($_GET['id'])) die("Invalid Request");
$record_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 查询数据
$stmt = $pdo->prepare("
    SELECT r.*, u.First_Name, u.Last_Name 
    FROM redeemrecord r 
    JOIN user u ON r.Redeem_By = u.User_ID 
    WHERE r.RedeemRecord_ID = ? AND r.Redeem_By = ?
");
$stmt->execute([$record_id, $user_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) die("Access Denied.");

// --- 核心逻辑：判断是“证书”还是“券” ---
$rewardName = $record['Reward_Name'];
$isCert = (stripos($rewardName, 'Tree') !== false || stripos($rewardName, 'Cert') !== false);

// 生成 Code (通用)
$code = "ECO-" . strtoupper(substr(md5($record_id . 'salt'), 0, 8)) . "-2025";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reward: <?= htmlspecialchars($rewardName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @media print { .no-print { display: none !important; } body { background: white; } }
        .font-serif-custom { font-family: 'Cinzel', serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">

    <?php if ($isCert): ?>
        <div class="bg-white w-full max-w-3xl p-2 shadow-2xl relative border-8 border-double border-green-800 landscape:aspect-[1.414]">
            <div class="h-full border border-green-600 p-8 flex flex-col items-center text-center relative overflow-hidden">
                
                <i class="fa-solid fa-tree absolute text-[300px] text-green-50 opacity-10 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 pointer-events-none"></i>

                <div class="mb-6 text-green-800">
                    <i class="fa-solid fa-seedling text-5xl"></i>
                </div>
                
                <h1 class="font-serif-custom text-4xl md:text-5xl text-green-900 font-bold mb-2 uppercase tracking-widest">Certificate</h1>
                <h2 class="font-serif-custom text-xl text-green-700 uppercase tracking-widest mb-8">of Appreciation</h2>

                <p class="text-gray-500 italic mb-4 text-lg">This certificate is proudly presented to</p>
                
                <div class="font-serif-custom text-3xl md:text-4xl text-black border-b-2 border-green-800 pb-2 mb-6 px-10">
                    <?= htmlspecialchars($record['First_Name'] . ' ' . $record['Last_Name']) ?>
                </div>

                <p class="text-gray-600 max-w-lg mx-auto leading-relaxed mb-8">
                    For their outstanding contribution to a greener planet by redeeming the 
                    <strong class="text-green-800"><?= htmlspecialchars($rewardName) ?></strong>. 
                    A tree has been planted in your name.
                </p>

                <div class="flex justify-between w-full max-w-xl mt-auto items-end">
                    <div class="text-center">
                        <div class="text-sm font-bold text-gray-800"><?= date("M d, Y", strtotime($record['Redeem_Date'])) ?></div>
                        <div class="border-t border-gray-400 w-32 mt-1"></div>
                        <div class="text-xs text-gray-500 uppercase mt-1">Date</div>
                    </div>
                    
                    <div class="w-20 h-20 rounded-full border-2 border-green-600 flex items-center justify-center text-green-800">
                        <div class="text-center">
                            <i class="fa-solid fa-earth-americas text-xl"></i>
                            <div class="text-[8px] uppercase font-bold mt-1">EcoTrip<br>Verified</div>
                        </div>
                    </div>

                    <div class="text-center">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/f/f8/Signature_sample.svg" class="h-8 mx-auto opacity-70">
                        <div class="border-t border-gray-400 w-32 mt-1"></div>
                        <div class="text-xs text-gray-500 uppercase mt-1">Director</div>
                    </div>
                </div>
                
                <div class="absolute bottom-2 right-2 text-[10px] text-gray-300">ID: <?= $record_id ?></div>
            </div>
        </div>

    <?php else: ?>
        <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden relative">
            <div class="h-3 bg-gradient-to-r from-indigo-500 to-purple-600"></div>
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4 text-indigo-600 text-3xl">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">E-Voucher</h2>
                <h1 class="text-xl font-extrabold text-gray-900 mb-2 leading-tight"><?= htmlspecialchars($rewardName) ?></h1>
                
                <div class="my-6 border-b-2 border-dashed border-gray-100 relative"></div>

                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <p class="text-[10px] text-gray-400 uppercase mb-1 font-bold">Voucher Code</p>
                    <div class="text-2xl font-mono font-bold tracking-widest text-gray-800 select-all">
                        <?= $code ?>
                    </div>
                </div>
                
                <p class="text-xs text-gray-400 mt-4">Issued to: <?= htmlspecialchars($record['First_Name']) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-6 flex gap-3 no-print">
        <button onclick="window.print()" class="bg-gray-900 text-white px-5 py-2 rounded-lg font-bold shadow hover:bg-black transition text-sm flex items-center gap-2">
            <i class="fa-solid fa-print"></i> Print / Save
        </button>
        <button onclick="window.close()" class="text-gray-500 hover:text-gray-800 font-bold px-4 py-2 text-sm">Close</button>
    </div>

</body>
</html>