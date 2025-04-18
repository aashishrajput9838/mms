<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user's financial summary
$income_total = 0;
$expense_total = 0;
$investment_total = 0;
$income_sources = [];
$expense_categories = [];

// Get total income
$sql = "SELECT SUM(amount) as total FROM INCOME WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $income_total = $row['total'] ?: 0;
}

// Get total expenses
$sql = "SELECT SUM(amount) as total FROM EXPENSE WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $expense_total = $row['total'] ?: 0;
}

// Get total investments
$sql = "SELECT SUM(amount) as total FROM INVESTMENT WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $investment_total = $row['total'] ?: 0;
}

// Get income sources
$sql = "SELECT source, SUM(amount) as total FROM INCOME WHERE user_id = ? GROUP BY source ORDER BY total DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $income_sources[] = $row;
}

// Get expense categories
$sql = "SELECT category, SUM(amount) as total FROM EXPENSE WHERE user_id = ? GROUP BY category ORDER BY total DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $expense_categories[] = $row;
}

// Get recent transactions
$sql = "SELECT 'income' as type, source as description, amount, date FROM INCOME WHERE user_id = ?
        UNION
        SELECT 'expense' as type, category as description, amount, date FROM EXPENSE WHERE user_id = ?
        ORDER BY date DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$recent_transactions = $stmt->get_result();

// Calculate savings
$savings = $income_total - $expense_total;

// Get monthly income and expenses for chart (last 6 months)
$months = [];
$monthly_income = [];
$monthly_expenses = [];

for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    // Get monthly income
    $sql = "SELECT SUM(amount) as total FROM INCOME WHERE user_id = ? AND date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $monthly_income[] = $row['total'] ?: 0;
    } else {
        $monthly_income[] = 0;
    }
    
    // Get monthly expenses
    $sql = "SELECT SUM(amount) as total FROM EXPENSE WHERE user_id = ? AND date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $monthly_expenses[] = $row['total'] ?: 0;
    } else {
        $monthly_expenses[] = 0;
    }
}

// Convert to JSON for chart
$chart_data = [
    'labels' => $months,
    'income' => $monthly_income,
    'expenses' => $monthly_expenses
];
$chart_data_json = json_encode($chart_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container">
            <h1 class="mb-4">Financial Dashboard</h1>
            
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card dashboard-card income h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Total Income</h5>
                                <div class="icon-bg rounded-circle bg-success bg-opacity-10 p-2">
                                    <i class="fas fa-money-bill-wave text-success"></i>
                                </div>
                            </div>
                            <h3 class="mb-0">$<?php echo number_format($income_total, 2); ?></h3>
                            <a href="income.php" class="mt-3 text-decoration-none">View Details <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card dashboard-card expense h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Total Expenses</h5>
                                <div class="icon-bg rounded-circle bg-danger bg-opacity-10 p-2">
                                    <i class="fas fa-credit-card text-danger"></i>
                                </div>
                            </div>
                            <h3 class="mb-0">$<?php echo number_format($expense_total, 2); ?></h3>
                            <a href="expenses.php" class="mt-3 text-decoration-none">View Details <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Savings</h5>
                                <div class="icon-bg rounded-circle bg-primary bg-opacity-10 p-2">
                                    <i class="fas fa-piggy-bank text-primary"></i>
                                </div>
                            </div>
                            <h3 class="mb-0">$<?php echo number_format($savings, 2); ?></h3>
                            <span class="text-<?php echo $savings >= 0 ? 'success' : 'danger'; ?> mt-3 d-inline-block">
                                <i class="fas fa-<?php echo $savings >= 0 ? 'arrow-up' : 'arrow-down'; ?> me-1"></i>
                                <?php echo $savings >= 0 ? 'Positive Balance' : 'Negative Balance'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card dashboard-card investment h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Investments</h5>
                                <div class="icon-bg rounded-circle bg-warning bg-opacity-10 p-2">
                                    <i class="fas fa-chart-line text-warning"></i>
                                </div>
                            </div>
                            <h3 class="mb-0">$<?php echo number_format($investment_total, 2); ?></h3>
                            <a href="investments.php" class="mt-3 text-decoration-none">View Details <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card chart-container h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Income vs Expenses (Last 6 Months)</h5>
                            <canvas id="incomeExpenseChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Expense Distribution</h5>
                            <canvas id="expenseDistChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">Top Income Sources</h5>
                                <a href="income.php" class="text-decoration-none">View All</a>
                            </div>
                            
                            <?php if (empty($income_sources)): ?>
                                <p class="text-muted">No income sources found. <a href="income.php" class="text-decoration-none">Add income source</a></p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Source</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($income_sources as $source): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($source['source']); ?></td>
                                                    <td class="text-end">$<?php echo number_format($source['total'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">Top Expense Categories</h5>
                                <a href="expenses.php" class="text-decoration-none">View All</a>
                            </div>
                            
                            <?php if (empty($expense_categories)): ?>
                                <p class="text-muted">No expense categories found. <a href="expenses.php" class="text-decoration-none">Add expense</a></p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($expense_categories as $category): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($category['category']); ?></td>
                                                    <td class="text-end">$<?php echo number_format($category['total'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">Recent Transactions</h5>
                                <div>
                                    <a href="income.php" class="btn btn-outline-success btn-sm me-2">Add Income</a>
                                    <a href="expenses.php" class="btn btn-outline-danger btn-sm">Add Expense</a>
                                </div>
                            </div>
                            
                            <?php if ($recent_transactions->num_rows == 0): ?>
                                <p class="text-muted">No recent transactions found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Description</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                                                    <td>
                                                        <?php if ($transaction['type'] == 'income'): ?>
                                                            <span class="badge bg-success">Income</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Expense</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                    <td class="text-end <?php echo $transaction['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo $transaction['type'] == 'income' ? '+' : '-'; ?>
                                                        $<?php echo number_format($transaction['amount'], 2); ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Income vs Expense Chart
            const chartData = <?php echo $chart_data_json; ?>;
            const ctx = document.getElementById('incomeExpenseChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: chartData.income,
                            backgroundColor: 'rgba(40, 167, 69, 0.2)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Expenses',
                            data: chartData.expenses,
                            backgroundColor: 'rgba(220, 53, 69, 0.2)',
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
            
            // Expense Distribution Chart
            const expenseDistCtx = document.getElementById('expenseDistChart').getContext('2d');
            
            // Create the data array from PHP expense categories
            const expenseLabels = [];
            const expenseData = [];
            const backgroundColors = [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)'
            ];
            
            <?php foreach ($expense_categories as $index => $category): ?>
                expenseLabels.push("<?php echo addslashes($category['category']); ?>");
                expenseData.push(<?php echo $category['total']; ?>);
            <?php endforeach; ?>
            
            // If no expense data, show "No Data"
            if (expenseLabels.length === 0) {
                expenseLabels.push("No Data");
                expenseData.push(1);
            }
            
            new Chart(expenseDistCtx, {
                type: 'pie',
                data: {
                    labels: expenseLabels,
                    datasets: [{
                        data: expenseData,
                        backgroundColor: backgroundColors.slice(0, expenseLabels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html> 