<?php
session_start();
require '../database.php';
require '../header.php';
require '../background.php';

// 获取所有活动
$stmt = $pdo->query("SELECT * FROM donation_campaign WHERE Status != 'Closed' ORDER BY Status ASC, Created_At DESC");
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取用户余额 (用于前端判断)
$user_id = $_SESSION['user_id'] ?? 0;
$stmtUser = $pdo->prepare("SELECT RedeemPoint FROM user WHERE User_ID = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
$my_balance = $user['RedeemPoint'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eco Donation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans">

<main class="max-w-7xl mx-auto px-8 py-10">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">❤️ Community Donation</h1>
            <p class="text-gray-500 mt-1">Use your points to make a real-world impact.</p>
        </div>
        
        <div class="bg-white px-5 py-2 rounded-full shadow-sm border border-gray-200 flex items-center gap-2">
            <span class="text-xs text-gray-400 uppercase font-bold">Your Balance</span>
            <span class="text-xl font-bold text-brand-600"><?= number_format($my_balance) ?> pts</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($campaigns as $camp): 
            $percent = min(100, round(($camp['Current_Points'] / $camp['Target_Points']) * 100));
            $isCompleted = ($camp['Status'] === 'Completed');
            $imgSrc = !empty($camp['Image']) ? $camp['Image'] : "https://placehold.co/600x400/e2e8f0/94a3b8?text=Charity";
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col hover:shadow-lg transition-shadow duration-300">
            <div class="h-48 w-full bg-gray-100 relative">
                <img src="<?= htmlspecialchars($imgSrc) ?>" class="w-full h-full object-cover">
                <?php if($isCompleted): ?>
                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center backdrop-blur-sm">
                        <span class="text-white font-bold text-xl uppercase tracking-widest border-2 border-white px-4 py-2 rounded">Completed</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="p-6 flex-1 flex flex-col">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-xl text-gray-900 leading-tight"><?= htmlspecialchars($camp['Title']) ?></h3>
                </div>
                
                <p class="text-sm text-gray-500 mb-6 flex-grow"><?= htmlspecialchars($camp['Description']) ?></p>

                <div class="mb-4">
                    <div class="flex justify-between text-xs font-bold mb-1">
                        <span class="text-brand-600"><?= number_format($camp['Current_Points']) ?> raised</span>
                        <span class="text-gray-400">Goal: <?= number_format($camp['Target_Points']) ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-400 to-green-600 h-2.5 rounded-full transition-all duration-1000" style="width: <?= $percent ?>%"></div>
                    </div>
                    <p class="text-right text-xs text-gray-400 mt-1"><?= $percent ?>% Funded</p>
                </div>

                <button onclick="donate(<?= $camp['Campaign_ID'] ?>, '<?= addslashes($camp['Title']) ?>', <?= $isCompleted ? 'true' : 'false' ?>)" 
                    class="w-full py-3 rounded-xl font-bold text-sm transition transform active:scale-95 
                    <?= $isCompleted ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-brand-600 text-white hover:bg-brand-700 shadow-md hover:shadow-lg' ?>"
                    <?= $isCompleted ? 'disabled' : '' ?>>
                    <?= $isCompleted ? 'Goal Reached 🎉' : 'Donate Points' ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</main>

<script>
    function donate(id, title, isCompleted) {
        if(isCompleted) return;

        Swal.fire({
            title: 'Donate to ' + title,
            text: 'How many points would you like to donate?',
            input: 'number',
            inputAttributes: {
                min: 1,
                max: <?= $my_balance ?>,
                step: 10
            },
            showCancelButton: true,
            confirmButtonText: 'Confirm Donation',
            confirmButtonColor: '#16a34a',
            showLoaderOnConfirm: true,
            preConfirm: (amount) => {
                if (!amount || amount <= 0) {
                    Swal.showValidationMessage(`Please enter a valid amount`)
                } else if (amount > <?= $my_balance ?>) {
                    Swal.showValidationMessage(`Insufficient balance! You have <?= $my_balance ?> pts.`)
                } else {
                    // 发送 AJAX 请求
                    return fetch('process_donation.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `campaign_id=${id}&amount=${amount}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message)
                        }
                        return data
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`)
                    })
                }
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Thank You!',
                    text: 'Your donation has been received.',
                    icon: 'success'
                }).then(() => location.reload());
            }
        })
    }
</script>

<?php include '../footer.php'; ?>
</body>
</html>