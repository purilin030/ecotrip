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

    // 1. My Activity Chart
    const ctxMyGrowth = document.getElementById('myGrowthChart');
    if (ctxMyGrowth) {
        if(dashboardData.chartLabels.length > 0) {
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
}