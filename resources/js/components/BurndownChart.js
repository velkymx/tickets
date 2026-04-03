import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

document.addEventListener('DOMContentLoaded', function() {
    const burndownChartEl = document.getElementById('burndownChart');
    
    if (!burndownChartEl) {
        return;
    }
    
    const burndownData = window.burndownChartData;
    
    if (!burndownData || !burndownData.labels || burndownData.labels.length === 0) {
        return;
    }
    
    const ctx = burndownChartEl.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: burndownData.labels,
            datasets: [
                {
                    label: 'Ideal',
                    data: burndownData.ideal,
                    borderColor: 'rgba(200, 200, 200, 1)',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0
                },
                {
                    label: 'Actual',
                    data: burndownData.actual,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    tension: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Story Points'
                    }
                }
            }
        }
    });
});
