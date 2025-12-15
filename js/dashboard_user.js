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

    // 1. My Activity Chart (Existing)
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
            // Placeholder text if no data
            const ctx2d = ctxMyGrowth.getContext('2d');
            ctx2d.font = "14px Inter";
            ctx2d.fillStyle = "#9ca3af";
            ctx2d.textAlign = "center";
            ctx2d.fillText("No recent activity data to display", ctxMyGrowth.width/2, ctxMyGrowth.height/2);
        }
    }

    // --- NEW STATISTICAL CHARTS ---

    // 2. My Success Rate (Doughnut)
    // Requires: dashboardData.successRate { approved: int, rejected: int }
    const ctxSuccess = document.getElementById('successRateChart');
    if (ctxSuccess && dashboardData.successRate) {
        new Chart(ctxSuccess.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Rejected'],
                datasets: [{
                    data: [dashboardData.successRate.approved, dashboardData.successRate.rejected],
                    backgroundColor: ['#22c55e', '#ef4444'], 
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // 3. Consistency Score (Trend Line)
    // Requires: dashboardData.weeklyLabels (e.g. ['Wk1', 'Wk2']) and dashboardData.weeklyCounts
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
}