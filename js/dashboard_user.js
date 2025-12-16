document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if data exists
    initializeCharts();
});

// --- Tab Switching Logic ---
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
        // Reset to inactive gray
        btn.classList.remove('border-green-500', 'text-green-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });

    // Set active green
    const activeBtn = document.getElementById('tab-' + tabName);
    if(activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-gray-500');
        activeBtn.classList.add('border-green-500', 'text-green-600');
    }
}

// --- Chart Initialization ---
function initializeCharts() {
    if (typeof dashboardData === 'undefined') return;

    // 1. My Activity Chart (Growth/Eco Journey)
    const ctxMyGrowth = document.getElementById('myGrowthChart');
    if (ctxMyGrowth) {
        if(dashboardData.chartLabels && dashboardData.chartLabels.length > 0) {
            new Chart(ctxMyGrowth.getContext('2d'), {
                type: 'line',
                data: {
                    labels: dashboardData.chartLabels,
                    datasets: [{
                        label: 'Points Earned',
                        data: dashboardData.chartData,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { 
                        y: { beginAtZero: true, grid: { borderDash: [2, 4] } }, 
                        x: { grid: { display: false } } 
                    }
                }
            });
        } else {
            const ctx2d = ctxMyGrowth.getContext('2d');
            ctx2d.font = "14px Inter";
            ctx2d.fillStyle = "#9ca3af";
            ctx2d.textAlign = "center";
            ctx2d.fillText("No recent activity data to display", ctxMyGrowth.width/2, ctxMyGrowth.height/2);
        }
    }

    // 2. User Submission Status (Pie Chart)
    const ctxUserStatus = document.getElementById('userStatusChart');
    if (ctxUserStatus && dashboardData.userStatus) {
        new Chart(ctxUserStatus.getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['Pending', 'Approved', 'Rejected'],
                datasets: [{
                    data: [
                        dashboardData.userStatus.Pending, 
                        dashboardData.userStatus.Approved, 
                        dashboardData.userStatus.Rejected
                    ],
                    backgroundColor: ['#f59e0b', '#22c55e', '#ef4444'], // Amber, Green, Red
                    borderWidth: 1,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) { label += ': '; }
                                label += context.raw + ' submissions';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Top 5 Global Challenges (Bar Chart)
    const ctxTop5 = document.getElementById('userTopChallengesChart');
    if (ctxTop5 && dashboardData.top5Labels) {
        new Chart(ctxTop5.getContext('2d'), {
            type: 'bar',
            data: {
                labels: dashboardData.top5Labels,
                datasets: [{
                    label: 'Total Submissions',
                    data: dashboardData.top5Data,
                    backgroundColor: '#3b82f6', // Blue
                    borderRadius: 4,
                    barThickness: 30
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
    }

    // 4. Consistency Score (Trend Line)
    const ctxConsistency = document.getElementById('consistencyChart');
    if (ctxConsistency && dashboardData.weeklyLabels) {
        new Chart(ctxConsistency.getContext('2d'), {
            type: 'line',
            data: {
                labels: dashboardData.weeklyLabels,
                datasets: [{
                    label: 'Submissions',
                    data: dashboardData.weeklyCounts,
                    borderColor: '#6366f1', // Indigo
                    backgroundColor: 'rgba(99, 102, 241, 0.05)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { display: false, beginAtZero: true }, 
                    x: { display: false } 
                }
            }
        });
    }

    // 5. NEW: Difficulty Breakdown (Doughnut Chart)
    const ctxDiff = document.getElementById('userDifficultyChart');
    if (ctxDiff && dashboardData.difficultyCounts) {
        new Chart(ctxDiff.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Easy', 'Medium', 'Hard'],
                datasets: [{
                    data: [
                        dashboardData.difficultyCounts.Easy || 0,
                        dashboardData.difficultyCounts.Medium || 0,
                        dashboardData.difficultyCounts.Hard || 0
                    ],
                    backgroundColor: ['#4ade80', '#fbbf24', '#f87171'], // Green, Yellow, Red
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } }
                }
            }
        });
    }
}