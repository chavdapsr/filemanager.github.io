$(document).ready(function() {
    // Chart.js for Activity Chart
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    const activityChartData = <?php echo json_encode($activity_data); ?>;

    new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: activityChartData.labels,
            datasets: [{
                label: 'Activity',
                data: activityChartData.data,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4, // Makes the line curved
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                }
            }
        }
    });

    // Chart.js for Storage Chart (Doughnut/Pie chart for used storage)
    const storageCtx = document.getElementById('storageChart').getContext('2d');
    const storageDetails = <?php echo json_encode($storage_details); ?>;
    const usedStorage = storageDetails.used;
    const totalStorage = storageDetails.total;
    const freeStorage = totalStorage - usedStorage;

    new Chart(storageCtx, {
        type: 'doughnut',
        data: {
            labels: ['Used Storage', 'Free Storage'],
            datasets: [{
                data: [usedStorage, freeStorage],
                backgroundColor: ['#007bff', '#e9ecef'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            cutout: '70%', // Make it a doughnut chart
            plugins: {
                legend: {
                    display: false // Hide legend
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return tooltipItem.label + ': ' + tooltipItem.raw + 'GB';
                        }
                    }
                }
            }
        },
        plugins: [{ // Plugin to display text in the center of the doughnut chart
            id: 'centerText',
            beforeDraw: function(chart) {
                const width = chart.width,
                    height = chart.height,
                    ctx = chart.ctx;
                ctx.restore();
                const fontSize = (height / 114).toFixed(2);
                ctx.font = fontSize + "em sans-serif";
                ctx.textBaseline = "middle";
                const text = `${usedStorage}GB`;
                const text2 = `used of ${totalStorage}GB`;
                const textX = Math.round((width - ctx.measureText(text).width) / 2);
                const textY = height / 2 - 10;
                ctx.fillText(text, textX, textY);
                ctx.font = (fontSize * 0.5).toFixed(2) + "em sans-serif";
                const text2X = Math.round((width - ctx.measureText(text2).width) / 2);
                ctx.fillText(text2, text2X, textY + 20);
                ctx.save();
            }
        }]
    });

    // Toggle sidebar on small screens
    $('[data-bs-toggle="collapse"][data-bs-target="#sidebarCollapse"]').on('click', function() {
        $('#sidebarCollapse').toggleClass('show');
    });
});
