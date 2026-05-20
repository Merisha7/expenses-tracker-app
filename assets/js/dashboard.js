// Format numbers like 1200 -> Rs. 1,200.00
function formatCurrency(value) {
    return `Rs. ${Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
}

function setText(id, text) {
    const element = document.getElementById(id);
    if (element) element.textContent = text;
}

function formatShortDate(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

// Fill the activity table with rows from the API.
function renderActivity(rows) {
    const body = document.getElementById('activityBody');
    body.innerHTML = '';

    if (!rows || rows.length === 0) {
        body.innerHTML = '<tr><td colspan="5">No recent activity found.</td></tr>';
        return;
    }

    // Icon mapping for common descriptions
    const iconMap = {
        'salary': '💰',
        'income': '💰zx',
        'freelance': '💼',
        'rent': '🏠',
        'groceries': '🛒',
        'food': '🍔',
        'restaurant': '🍽️',
        'utilities': '⚡',
        'gas': '⛽',
        'transport': '🚗',
        'entertainment': '🎬',
        'shopping': '🛍️',
        'movie': '🎬',
        'coffee': '☕'
    };

    rows.forEach((item) => {
        const tr = document.createElement('tr');
        const sign = item.type === 'income' ? '+' : '-';
        const cls = item.type === 'income' ? 'amount-income' : 'amount-expense';
        const statusCls = item.type === 'income' ? 'status-income' : 'status-expense';
        const statusLabel = item.type === 'income' ? 'INCOME' : 'EXPENSE';
        const editPath = item.type === 'income'
            ? 'edit_income.php'
            : 'edit_expenses.php';
        const deletePath = 'delete.php';
        
        // Get icon based on description
        const descLower = item.description.toLowerCase();
        let icon = '📍';
        for (const [key, val] of Object.entries(iconMap)) {
            if (descLower.includes(key)) {
                icon = val;
                break;
            }
        }
        
        tr.innerHTML = `
            <td>${formatShortDate(item.date)}</td>
                <td>${item.description}</td>
            <td class="${cls}">${sign}${formatCurrency(item.amount)}</td>
            <td><span class="status-pill ${statusCls}">${statusLabel}</span></td>
            <td>
                <a class="btn btn-edit" href="${editPath}?id=${item.id}">Edit</a>
                <a class="btn btn-delete" href="${deletePath}?type=${item.type}&id=${item.id}">Del</a>
            </td>
        `;
        body.appendChild(tr);
    });
}

// Draw the bar chart using Chart.js.
function renderChart(chartData) {
    const chartElement = document.getElementById('overviewChart');
    if (!chartElement) return;

    const ctx = chartElement.getContext('2d');
    if (window.dashboardChart) {
        window.dashboardChart.destroy();
    }

    const labels = chartData && chartData.labels ? chartData.labels : [];
    const income = chartData && chartData.income ? chartData.income : [];
    const expenses = chartData && chartData.expenses ? chartData.expenses : [];

    window.dashboardChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'income',
                    data: income,
                    backgroundColor: '#74c986',
                    borderRadius: 8,
                    barPercentage: 0.65,
                    categoryPercentage: 0.72
                },
                {
                    label: 'expenses',
                    data: expenses,
                    backgroundColor: '#df8f9d',
                    borderRadius: 8,
                    barPercentage: 0.65,
                    categoryPercentage: 0.72
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 9
                    }
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
                        color: '#ece7f6'
                    },
                    ticks: {
                        callback: (value) => {
                            if (value >= 1000) return `Rs. ${value / 1000}k`;
                            return `Rs. ${value}`;
                        }
                    }
                }
            }
        }
    });
}

// Load data from PHP API and update the page.
async function loadDashboard() {
    const hello = document.getElementById('helloTitle');

    try {
        const response = await fetch(`dashboard.php?ts=${Date.now()}`, { cache: 'no-store' });

        if (!response.ok) {
            const text = await response.text();
            console.error('Dashboard API error', response.status, text);
            hello.textContent = 'Dashboard data failed to load (server error)';
            return;
        }

        const data = await response.json();

        if (!data || data.success === false) {
            console.error('Dashboard API returned error:', data);
            hello.textContent = 'Dashboard data failed to load (invalid response)';
            return;
        }

        hello.textContent = `HELLO, ${String(data.userName || 'USER').toUpperCase()}!`;
        setText('userAvatar', String(data.userName || 'U').trim().charAt(0).toUpperCase() || 'U');
        setText('totalBalance', formatCurrency(data.totalBalance));
        setText('monthlyIncome', formatCurrency(data.monthlyIncome));
        setText('monthlyExpenses', formatCurrency(data.monthlyExpenses));
        setText('budgetBalance', formatCurrency(data.totalIncome));
        setText('budgetExpenses', formatCurrency(data.totalExpenses));
        setText('budgetRemaining', formatCurrency(Math.max((data.totalIncome || 0) - (data.totalExpenses || 0), 0)));

        renderActivity(data.recentActivity);
        renderChart(data.chart);
        
        // Render goals if goalsHtml is provided
        if (data.goalsHtml) {
            const goalsContainer = document.getElementById('goalsContainer');
            if (goalsContainer) {
                goalsContainer.innerHTML = data.goalsHtml;
            }
        }
    } catch (error) {
        console.error('Dashboard load failed', error);
        hello.textContent = 'Dashboard data failed to load (network/error)';
    }
}

// Run after HTML is ready.
document.addEventListener('DOMContentLoaded', loadDashboard);
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        loadDashboard();
    }
});

// Listen for goals updates from other pages (localStorage) and reload dashboard
window.addEventListener('storage', (e) => {
    if (!e) return;
    if (e.key === 'goals-updated') {
        loadDashboard();
    }
});