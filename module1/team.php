<?php
session_start();
require 'database.php';

// 1. 安全检查
if (!isset($_SESSION['Firstname']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 检查是否已有队伍
$check_team_sql = "SELECT Team_ID FROM user WHERE User_ID = '$user_id'";
$check_team_res = mysqli_query($con, $check_team_sql);
$user_data = mysqli_fetch_assoc($check_team_res);

if ($user_data['Team_ID'] != NULL && $user_data['Team_ID'] != 0) {
    header("Location: team_information.php");
    exit();
}

// 数据库查询：获取所有团队信息
$sql = "SELECT t.*, u.First_Name, u.Last_Name 
        FROM team t 
        LEFT JOIN user u ON t.Owner_ID = u.User_ID 
        ORDER BY t.Team_ID DESC";
$result = mysqli_query($con, $sql);

$page_title = "Join a Team - ecoTrip";
include '../header.php'; 

// 注意：这里移除了原本错误的 $points_sql 查询，因为要在循环里针对每个团队单独查
?>

<main class="flex-grow w-full px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-7xl mx-auto">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Team Hub</h1>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-4 animate-pulse">
                    <p class="font-bold">Error</p>
                    <p><?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded mb-4">
                    <p class="font-bold">Success</p>
                    <p><?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <div class="lg:col-span-1 space-y-6">
                <button onclick="openJoinModal()" class="block group w-full text-left">
                    <div class="bg-white border-2 border-gray-200 rounded-xl p-8 flex flex-col items-center justify-center text-center shadow-sm hover:border-brand-500 hover:shadow-md transition-all duration-300 h-48 cursor-pointer">
                        <div class="h-14 w-14 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <i class="fa-solid fa-keyboard text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 group-hover:text-brand-600">Join with code</h3>
                        <p class="text-sm text-gray-400 mt-2">Enter code to join a team</p>
                    </div>
                </button>
                <button onclick="openModal()" class="block group w-full text-left">
                    <div class="bg-white border-2 border-dashed border-gray-300 rounded-xl p-8 flex flex-col items-center justify-center text-center hover:border-brand-500 hover:bg-brand-50 transition-all duration-300 h-48 cursor-pointer">
                        <div class="h-14 w-14 bg-green-100 text-brand-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-plus text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Create a new team</h3>
                        <p class="text-sm text-gray-400 mt-2">Start your own journey</p>
                    </div>
                </button>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden relative z-0">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center rounded-t-xl">
                        <h2 class="text-lg font-bold text-gray-800">Available Teams</h2>
                        <span class="text-xs font-semibold text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
                            <?php echo $result ? mysqli_num_rows($result) : 0; ?> Active
                        </span>
                    </div>
                    
                    <div class="divide-y divide-gray-100"> 
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                
                                <?php
                                    $tid = $row['Team_ID'];

                                    // 1. 获取成员名字 (用于 Tooltip)
                                    $mem_sql = "SELECT First_Name FROM user WHERE Team_ID = '$tid' LIMIT 5";
                                    $mem_res = mysqli_query($con, $mem_sql);
                                    $members_names = [];
                                    while($m = mysqli_fetch_assoc($mem_res)){
                                        $members_names[] = $m['First_Name'];
                                    }
                                    $members_str = implode(', ', $members_names);
                                    if($row['Total_members'] > 5) {
                                        $members_str .= ", ...";
                                    }

                                    // 2. 【关键修改】计算该团队的总分 (Team Points)
                                    // 逻辑：SUM(所有成员的 Point)
                                    $pts_sql = "SELECT SUM(Point) as total FROM user WHERE Team_ID = '$tid'";
                                    $pts_res = mysqli_query($con, $pts_sql);
                                    $pts_data = mysqli_fetch_assoc($pts_res);
                                    // 如果没有成员或积分为空，默认为 0
                                    $current_team_points = $pts_data['total'] ? $pts_data['total'] : 0;
                                    
                                    // 准备数据用于 data 属性
                                    $initial = strtoupper(substr($row['Team_name'], 0, 1));
                                    $bio = !empty($row['Team_Bio']) ? $row['Team_Bio'] : 'No bio available.';
                                ?>

                                <div class="p-6 hover:bg-gray-50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4 cursor-pointer team-row"
                                     data-name="<?php echo htmlspecialchars($row['Team_name'], ENT_QUOTES); ?>"
                                     data-code="<?php echo htmlspecialchars($row['Team_code'], ENT_QUOTES); ?>"
                                     data-initial="<?php echo $initial; ?>"
                                     data-bio="<?php echo htmlspecialchars($bio, ENT_QUOTES); ?>"
                                     data-members="<?php echo htmlspecialchars($members_str, ENT_QUOTES); ?>"
                                     data-count="<?php echo $row['Total_members']; ?>"
                                >
                                    <div class="flex items-center gap-4 w-full">
                                        <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-brand-100 to-blue-100 flex-shrink-0 flex items-center justify-center text-brand-700 text-xl font-bold">
                                            <?php echo $initial; ?>
                                        </div>
                                        <div class="flex-grow">
                                            <div class="flex justify-between items-start">
                                                <h3 class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($row['Team_name']); ?></h3>
                                                <div class="flex items-center gap-3">
                                                    <div class="text-xs text-brand-600 font-semibold bg-brand-50 px-2 py-1 rounded">
                                                        <i class="fa-solid fa-trophy mr-1"></i> <?php echo number_format($current_team_points); ?>
                                                    </div>
                                                    <span class="text-sm text-gray-500 font-medium whitespace-nowrap">
                                                        <i class="fa-solid fa-users mr-1"></i> <?php echo $row['Total_members']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-0.5">Created by <?php echo $row['First_Name'] ? htmlspecialchars($row['First_Name']) : 'Unknown'; ?></p>
                                            
                                            <?php if (!empty($row['Team_Bio'])): ?>
                                                <p class="text-sm text-gray-600 mt-2 line-clamp-2 italic">"<?php echo htmlspecialchars($row['Team_Bio']); ?>"</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-12 text-center text-gray-400">No teams found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-gray-400">&copy; 2025 ecoTrip Inc. All rights reserved.</p>
    </div>
</footer>

<?php
// 以下是补充缺失的 Modal 和 Script 部分，以防万一
?>
<div id="global-team-tooltip" class="fixed hidden z-[100] w-80 bg-white shadow-2xl border border-gray-200 rounded-xl p-5 pointer-events-none transition-opacity duration-200">
    <div class="flex items-center gap-3 mb-3 border-b border-gray-100 pb-3">
        <div id="tooltip-initial" class="h-10 w-10 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-lg"></div>
        <div>
            <h4 id="tooltip-name" class="font-bold text-gray-900 text-base"></h4>
            <div class="text-[11px] bg-gray-100 px-2 py-0.5 rounded inline-block text-gray-500 font-mono mt-1">
                Code: <span id="tooltip-code"></span>
            </div>
        </div>
    </div>
    
    <div class="space-y-3">
        <div>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">About</p>
            <p id="tooltip-bio" class="text-sm text-gray-700 italic leading-relaxed"></p>
        </div>
        <div>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">
                Members (<span id="tooltip-count"></span>)
            </p>
            <p id="tooltip-members" class="text-sm text-gray-800 font-medium"></p>
        </div>
    </div>
</div>

<div id="createTeamModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
            <form action="create_team_action.php" method="POST">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fa-solid fa-flag text-brand-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Create a New Team</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">Give your team a unique name.</p>
                                <label for="team_name" class="block text-sm font-medium leading-6 text-gray-900">Team Name</label>
                                <div class="mt-2">
                                    <input type="text" name="team_name" id="team_name" required class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-brand-600 sm:text-sm sm:leading-6" placeholder="e.g. Green Avengers">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="submit" class="inline-flex w-full justify-center rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-500 sm:ml-3 sm:w-auto">Create</button>
                    <button type="button" onclick="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="joinTeamModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
            <form action="join_team_action.php" method="POST">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fa-solid fa-right-to-bracket text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Join a Team</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">Enter the team code.</p>
                                <label for="team_code" class="block text-sm font-medium leading-6 text-gray-900">Team Code</label>
                                <div class="mt-2">
                                    <input type="text" name="team_code" id="team_code" required class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm uppercase tracking-widest" placeholder="e.g. A1B2C3">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="submit" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500 sm:ml-3 sm:w-auto">Join</button>
                    <button type="button" onclick="closeJoinModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Modal Logic
    function openModal() { document.getElementById('createTeamModal').classList.remove('hidden'); }
    function closeModal() { document.getElementById('createTeamModal').classList.add('hidden'); }
    function openJoinModal() { document.getElementById('joinTeamModal').classList.remove('hidden'); }
    function closeJoinModal() { document.getElementById('joinTeamModal').classList.add('hidden'); }
    
    window.onclick = function(event) {
        let createModal = document.getElementById('createTeamModal');
        let joinModal = document.getElementById('joinTeamModal');
        if (event.target == createModal.querySelector('.fixed.inset-0')) closeModal();
        if (event.target == joinModal.querySelector('.fixed.inset-0')) closeJoinModal();
    }

    // Tooltip Logic
    const tooltip = document.getElementById('global-team-tooltip');
    const rows = document.querySelectorAll('.team-row');

    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            document.getElementById('tooltip-initial').innerText = this.getAttribute('data-initial');
            document.getElementById('tooltip-name').innerText = this.getAttribute('data-name');
            document.getElementById('tooltip-code').innerText = this.getAttribute('data-code');
            document.getElementById('tooltip-bio').innerText = '"' + this.getAttribute('data-bio') + '"';
            document.getElementById('tooltip-count').innerText = this.getAttribute('data-count');
            document.getElementById('tooltip-members').innerText = this.getAttribute('data-members');
            tooltip.classList.remove('hidden');
        });

        row.addEventListener('mousemove', function(e) {
            const x = e.clientX + 15;
            const y = e.clientY + 15;
            tooltip.style.left = x + 'px';
            tooltip.style.top = y + 'px';
        });

        row.addEventListener('mouseleave', function() {
            tooltip.classList.add('hidden');
        });
    });
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/ecotrip/background.php'; ?>
</body>
</html>