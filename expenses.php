<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Process form submission for adding expense
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_expense'])) {
        $category = trim($_POST['category']);
        $amount = floatval($_POST['amount']);
        $date = $_POST['date'];
        $description = trim($_POST['description']);
        
        // Validate input
        if (empty($category)) {
            $error_message = "Expense category is required";
        } elseif ($amount <= 0) {
            $error_message = "Amount must be greater than zero";
        } elseif (empty($date)) {
            $error_message = "Date is required";
        } else {
            // Insert expense record
            $stmt = $conn->prepare("INSERT INTO EXPENSE (user_id, category, amount, date, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdss", $user_id, $category, $amount, $date, $description);
            
            if ($stmt->execute()) {
                $success_message = "Expense record added successfully!";
                // Clear form data
                unset($category, $amount, $date, $description);
            } else {
                $error_message = "Error adding expense record: " . $stmt->error;
            }
        }
    } elseif (isset($_POST['delete_expense'])) {
        // Delete expense record
        $expense_id = $_POST['expense_id'];
        
        $stmt = $conn->prepare("DELETE FROM EXPENSE WHERE expense_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $expense_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Expense record deleted successfully!";
        } else {
            $error_message = "Error deleting expense record: " . $stmt->error;
        }
    }
}

// Get all expense records for the user
$sql = "SELECT * FROM EXPENSE WHERE user_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$expense_records = $stmt->get_result();

// Get total expenses
$sql = "SELECT SUM(amount) as total FROM EXPENSE WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_expenses = 0;
if ($row = $result->fetch_assoc()) {
    $total_expenses = $row['total'] ?: 0;
}

// Get expense categories for dropdown
$expense_categories = [
    'Housing', 'Food', 'Transportation', 'Utilities', 'Healthcare', 
    'Entertainment', 'Education', 'Clothing', 'Savings', 'Investments', 
    'Debt Payments', 'Insurance', 'Personal Care', 'Gifts', 'Taxes', 'Other'
];

// Get expense distribution by category
$sql = "SELECT category, SUM(amount) as total FROM EXPENSE WHERE user_id = ? GROUP BY category ORDER BY total DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$expense_distribution = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Expense Management</h1>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card form-container">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Add New Expense</h5>
                            
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success">
                                    <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger">
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="expenses.php" method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="category" class="form-label">Expense Category</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="" disabled selected>Select a category</option>
                                        <?php foreach ($expense_categories as $cat): ?>
                                            <option value="<?php echo $cat; ?>" <?php echo (isset($category) && $category === $cat) ? 'selected' : ''; ?>>
                                                <?php echo $cat; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a category</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount ($)</label>
                                    <input type="number" class="form-control" id="amount" name="amount" min="0.01" step="0.01" value="<?php echo isset($amount) ? $amount : ''; ?>" required>
                                    <div class="invalid-feedback">Please enter a valid amount</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control date-picker" id="date" name="date" value="<?php echo isset($date) ? $date : date('Y-m-d'); ?>" required>
                                    <div class="invalid-feedback">Please select a date</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description (Optional)</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="add_expense" class="btn btn-primary">Add Expense</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Expense Summary</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Total Expenses:</span>
                                <span class="h4 mb-0 text-danger">$<?php echo number_format($total_expenses, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Expense Distribution</h5>
                            <canvas id="expenseDistChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Expense Records</h5>
                            
                            <?php if ($expense_records->num_rows == 0): ?>
                                <p class="text-muted">No expense records found. Add your first expense using the form.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th class="text-end">Amount</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($expense = $expense_records->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($expense['date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                                    <td>
                                                        <?php 
                                                            echo !empty($expense['description']) 
                                                                ? htmlspecialchars($expense['description']) 
                                                                : '<span class="text-muted">No description</span>'; 
                                                        ?>
                                                    </td>
                                                    <td class="text-end text-danger">
                                                        $<?php echo number_format($expense['amount'], 2); ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <form action="expenses.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this expense record?');">
                                                            <input type="hidden" name="expense_id" value="<?php echo $expense['expense_id']; ?>">
                                                            <button type="submit" name="delete_expense" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
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
            // Initialize date picker
            flatpickr('.date-picker', {
                dateFormat: 'Y-m-d',
                allowInput: true
            });
            
            // Expense Distribution Chart
            const expenseDistCtx = document.getElementById('expenseDistChart').getContext('2d');
            
            // Create the data array from PHP expense distribution
            const expenseLabels = [];
            const expenseData = [];
            const backgroundColors = [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(201, 203, 207, 0.7)',
                'rgba(255, 99, 132, 0.5)',
                'rgba(54, 162, 235, 0.5)',
                'rgba(255, 206, 86, 0.5)'
            ];
            
            <?php while ($cat = $expense_distribution->fetch_assoc()): ?>
                expenseLabels.push("<?php echo addslashes($cat['category']); ?>");
                expenseData.push(<?php echo $cat['total']; ?>);
            <?php endwhile; ?>
            
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
                            position: 'right',
                            labels: {
                                boxWidth: 15,
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html> 