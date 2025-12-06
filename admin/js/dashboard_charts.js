// admin/js/dashboard_charts.js

document.addEventListener('DOMContentLoaded', function () {
    console.log('Dashboard Charts: Initializing...');

    if (typeof Chart === 'undefined') {
        console.error('CRITICAL: Chart.js library not loaded.');
        return;
    }

    const API_URL = 'api/stats.php';

    // Helper: Initialize a chart safely
    function initChart(canvasId, type, dataConfig, optionsConfig) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.warn(`Canvas element '${canvasId}' not found. Skipping.`);
            return;
        }

        try {
            return new Chart(canvas.getContext('2d'), {
                type: type,
                data: dataConfig,
                options: optionsConfig
            });
        } catch (e) {
            console.error(`Error initializing chart '${canvasId}':`, e);
        }
    }

    // Display Visual Status Helpers
    const chartIds = ['transactionChart', 'typeChart', 'userGrowthChart', 'depositStatusChart', 'billCategoryChart'];

    // Show Loading
    chartIds.forEach(id => {
        const canvas = document.getElementById(id);
        if (canvas && canvas.parentNode) {
            // Check if loader already exists to avoid duplicates
            if (!document.getElementById(id + '-loader')) {
                const loader = document.createElement('div');
                loader.id = id + '-loader';
                loader.innerHTML = '<p class="text-center text-gray-500 py-10">Loading chart data...</p>';
                canvas.parentNode.appendChild(loader);
                canvas.style.display = 'none'; // Hide canvas until ready
            }
        }
    });

    function showError(msg) {
        console.error(msg);
        chartIds.forEach(id => {
            const loader = document.getElementById(id + '-loader');
            if (loader) {
                loader.innerHTML = `<p class="text-center text-red-500 py-10 font-bold">Error loading data: ${msg}</p>`;
            }
        });
    }

    function removeLoaders() {
        chartIds.forEach(id => {
            const loader = document.getElementById(id + '-loader');
            if (loader) loader.remove();
            const canvas = document.getElementById(id);
            if (canvas) canvas.style.display = 'block';
        });
    }

    // Fetch Data
    fetch(API_URL)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
            return response.json();
        })
        .then(json => {
            if (!json.success) throw new Error(json.error || 'Unknown API Error');

            removeLoaders(); // Data received, show canvas
            const data = json.charts;
            console.log('Dashboard Data Received:', data);

            // 1. Transaction Volume (Line)
            initChart('transactionChart', 'line', {
                labels: data.volume.labels,
                datasets: [{
                    label: 'Transaction Volume (₱)',
                    data: data.volume.data,
                    borderColor: '#7c3aed',
                    backgroundColor: 'rgba(124, 58, 237, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            }, {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    x: { grid: { display: false } }
                }
            });

            // 2. Transaction Types (Doughnut)
            initChart('typeChart', 'doughnut', {
                labels: data.types.labels,
                datasets: [{
                    data: data.types.data,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0
                }]
            }, {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } },
                cutout: '70%'
            });

            // 3. User Growth (Bar)
            initChart('userGrowthChart', 'bar', {
                labels: data.user_growth.labels,
                datasets: [{
                    label: 'New Users',
                    data: data.user_growth.data,
                    backgroundColor: '#3b82f6',
                    borderRadius: 4
                }]
            }, {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            });

            // 4. Deposit Status (Doughnut)
            initChart('depositStatusChart', 'doughnut', {
                labels: data.deposit_status.labels,
                datasets: [{
                    data: data.deposit_status.data,
                    backgroundColor: ['#fbbf24', '#10b981', '#ef4444'],
                    borderWidth: 0
                }]
            }, {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 10 } } },
                cutout: '60%'
            });

            // 5. Bill Categories (Pie)
            initChart('billCategoryChart', 'pie', {
                labels: data.bill_categories.labels,
                datasets: [{
                    data: data.bill_categories.data,
                    backgroundColor: ['#8b5cf6', '#ec4899', '#06b6d4', '#f97316', '#6366f1'],
                    borderWidth: 1
                }]
            }, {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 10 } } }
            });

        })
        .catch(err => {
            showError(err.message);
        });
});
