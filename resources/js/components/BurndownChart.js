async function renderBurndown(canvas, data) {
    const { Chart, registerables } = await import('chart.js');

    Chart.register(...registerables);

    new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Ideal',
                    data: data.ideal,
                    borderColor: 'rgba(200, 200, 200, 1)',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0
                },
                {
                    label: 'Actual',
                    data: data.actual,
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
}

function readBurndownData(canvas) {
    if (!canvas.dataset.burndown) {
        return null;
    }

    return JSON.parse(canvas.dataset.burndown);
}

export default async function initBurndownChart(root = document) {
    const canvas = root.querySelector('#burndownChart');

    if (!canvas) {
        return;
    }

    const data = readBurndownData(canvas);

    if (!data || !Array.isArray(data.labels) || data.labels.length === 0) {
        return;
    }

    await renderBurndown(canvas, data).catch((error) => {
        console.error('Error rendering burndown chart:', error);
    });
}

document.addEventListener('DOMContentLoaded', () => initBurndownChart());
