document.addEventListener('DOMContentLoaded', function() {
    // Initialize default view
    // (Optional: You can add logic here to restore the active tab from local storage)
    
    initializeCharts();
});

// ---------------------- Tab Switching Logic ----------------------
function switchTab(tabName) {
    // 1. Hide all sections
    document.querySelectorAll('.dashboard-section').forEach(el => {
        el.classList.add('hidden');
    });

    // 2. Show selected section
    const selectedSection = document.getElementById('view-' + tabName);
    if(selectedSection) {
        selectedSection.classList.remove('hidden');
    }

    // 3. Update Tab Styles
    document.querySelectorAll('.tab-btn').forEach(btn => {
        // Reset to inactive style
        btn.classList.remove('border-green-500', 'text-green-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });

    // Set active style
    const activeBtn = document.getElementById('tab-' + tabName);
    if(activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-gray-500');
        activeBtn.classList.add('border-green-500', 'text-green-600');
    }
}

// ---------------------- Chart Initialization ----------------------
function initializeCharts() {
    // Ensure dashboardData is loaded from PHP
    if (typeof dashboardData === 'undefined') {
        console.error('Dashboard data not loaded properly.');
        return;
    }

    // --- 1. User Growth Chart (Line) ---
    const ctxGrowth = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(ctxGrowth, {
        type: 'line',
        data: {
            labels: dashboardData.weeks,
            datasets: [{
                label: 'New Users',
                data: dashboardData.userCounts,
                borderColor: '#16a34a', // ecoTrip Green
                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#16a34a',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [2, 4], color: '#f3f4f6' }, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });

    // --- 2. Team Distribution (Doughnut) ---
    const ctxTeam = document.getElementById('teamChart').getContext('2d');
    new Chart(ctxTeam, {
        type: 'doughnut',
        data: {
            labels: dashboardData.teamNames,
            datasets: [{
                data: dashboardData.teamMembers,
                backgroundColor: ['#16a34a', '#22c55e', '#4ade80', '#86efac', '#bbf7d0'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, font: { size: 11 } } }
            }
        }
    });

    // --- 3. Points Economy (Bar) ---
    const ctxPoints = document.getElementById('pointsFlowChart').getContext('2d');
    new Chart(ctxPoints, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Points Earned',
                    data: dashboardData.earnedData,
                    backgroundColor: '#22c55e',
                    borderRadius: 4
                },
                {
                    label: 'Points Spent',
                    data: dashboardData.spentData,
                    backgroundColor: '#ef4444',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // --- 4. Popular Rewards (Pie) ---
    const ctxRewards = document.getElementById('popularRewardsChart').getContext('2d');
    new Chart(ctxRewards, {
        type: 'pie',
        data: {
            labels: dashboardData.rewardLabels,
            datasets: [{
                data: dashboardData.rewardData,
                backgroundColor: ['#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#6366f1'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { boxWidth: 10 } } }
        }
    });

    // --- 5. Wealth Distribution (Horizontal Bar) ---
    const ctxWealth = document.getElementById('wealthChart').getContext('2d');
    new Chart(ctxWealth, {
        type: 'bar',
        data: {
            labels: ['0-2000 pts', '2001-5000 pts', '5001-10000 pts', '10000+ pts'],
            datasets: [{
                label: 'Users',
                data: dashboardData.wealthCounts,
                backgroundColor: ['#94a3b8', '#60a5fa', '#34d399', '#fbbf24'],
                borderRadius: 4,
                barThickness: 20
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, grid: { display: false } }, y: { grid: { display: false } } }
        }
    });

    // --- 6. Challenge Popularity (Bar) ---
    const ctxChalPop = document.getElementById('challengePopChart').getContext('2d');
    new Chart(ctxChalPop, {
        type: 'bar',
        data: {
            labels: dashboardData.chalLabels,
            datasets: [{
                label: 'Submissions',
                data: dashboardData.chalData,
                backgroundColor: '#16a34a',
                borderRadius: 6,
                barThickness: 30,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // --- 7. Submission Status (Doughnut) ---
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [{
                data: [
                    dashboardData.statusCounts.Pending,
                    dashboardData.statusCounts.Approved,
                    dashboardData.statusCounts.Rejected
                ],
                backgroundColor: ['#f59e0b', '#22c55e', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
            }
        }
    });
}