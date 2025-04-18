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

// Process form submission for adding investment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_investment'])) {
        $type = trim($_POST['type']);
        $amount = floatval($_POST['amount']);
        $start_date = $_POST['start_date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $interest_rate = !empty($_POST['interest_rate']) ? floatval($_POST['interest_rate']) : null;
        $description = trim($_POST['description']);
        
        // Validate input
        if (empty($type)) {
            $error_message = "Investment type is required";
        } elseif ($amount <= 0) {
            $error_message = "Amount must be greater than zero";
        } elseif (empty($start_date)) {
            $error_message = "Start date is required";
        } else {
            // Insert investment record
            $stmt = $conn->prepare("INSERT INTO INVESTMENT (user_id, type, amount, start_date, end_date, interest_rate, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdssds", $user_id, $type, $amount, $start_date, $end_date, $interest_rate, $description);
            
            if ($stmt->execute()) {
                $success_message = "Investment record added successfully!";
                // Clear form data
                unset($type, $amount, $start_date, $end_date, $interest_rate, $description);
            } else {
                $error_message = "Error adding investment record: " . $stmt->error;
            }
        }
    } elseif (isset($_POST['delete_investment'])) {
        // Delete investment record
        $investment_id = $_POST['investment_id'];
        
        $stmt = $conn->prepare("DELETE FROM INVESTMENT WHERE investment_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $investment_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Investment record deleted successfully!";
        } else {
            $error_message = "Error deleting investment record: " . $stmt->error;
        }
    }
}

// Get all investment records for the user
$sql = "SELECT * FROM INVESTMENT WHERE user_id = ? ORDER BY start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$investment_records = $stmt->get_result();

// Get total investments
$sql = "SELECT SUM(amount) as total FROM INVESTMENT WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_investments = 0;
if ($row = $result->fetch_assoc()) {
    $total_investments = $row['total'] ?: 0;
}

// Get investment types for dropdown
$investment_types = [
    'Fixed Deposit', 'Term Deposit', 'LIC Policy', 'Mutual Fund', 'Stocks', 
    'Bonds', 'Real Estate', 'Gold', 'Retirement Fund', 'Other'
];

// Get investment distribution by type
$sql = "SELECT type, SUM(amount) as total FROM INVESTMENT WHERE user_id = ? GROUP BY type ORDER BY total DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$investment_distribution = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investments - MMS</title>
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
                <h1>Investment Management</h1>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card form-container">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Add New Investment</h5>
                            
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
                            
                            <form action="investments.php" method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="type" class="form-label">Investment Type</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="" disabled selected>Select a type</option>
                                        <?php foreach ($investment_types as $inv_type): ?>
                                            <option value="<?php echo $inv_type; ?>" <?php echo (isset($type) && $type === $inv_type) ? 'selected' : ''; ?>>
                                                <?php echo $inv_type; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select an investment type</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount ($)</label>
                                    <input type="number" class="form-control" id="amount" name="amount" min="0.01" step="0.01" value="<?php echo isset($amount) ? $amount : ''; ?>" required>
                                    <div class="invalid-feedback">Please enter a valid amount</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control date-picker" id="start_date" name="start_date" value="<?php echo isset($start_date) ? $start_date : date('Y-m-d'); ?>" required>
                                    <div class="invalid-feedback">Please select a start date</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date (Optional)</label>
                                    <input type="date" class="form-control date-picker" id="end_date" name="end_date" value="<?php echo isset($end_date) ? $end_date : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="interest_rate" class="form-label">Interest Rate (% - Optional)</label>
                                    <input type="number" class="form-control" id="interest_rate" name="interest_rate" min="0" step="0.01" value="<?php echo isset($interest_rate) ? $interest_rate : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description (Optional)</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="add_investment" class="btn btn-primary">Add Investment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Investment Summary</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Total Investments:</span>
                                <span class="h4 mb-0 text-warning">$<?php echo number_format($total_investments, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Investment Distribution</h5>
                            <canvas id="investmentDistChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Investment Records</h5>
                            
                            <?php if ($investment_records->num_rows == 0): ?>
                                <p class="text-muted">No investment records found. Add your first investment using the form.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Interest Rate</th>
                                                <th>Description</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($investment = $investment_records->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($investment['type']); ?></td>
                                                    <td class="text-warning">$<?php echo number_format($investment['amount'], 2); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($investment['start_date'])); ?></td>
                                                    <td>
                                                        <?php 
                                                            echo !empty($investment['end_date']) 
                                                                ? date('M d, Y', strtotime($investment['end_date'])) 
                                                                : '<span class="text-muted">N/A</span>'; 
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            echo !empty($investment['interest_rate']) 
                                                                ? number_format($investment['interest_rate'], 2) . '%' 
                                                                : '<span class="text-muted">N/A</span>'; 
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            echo !empty($investment['description']) 
                                                                ? htmlspecialchars($investment['description']) 
                                                                : '<span class="text-muted">No description</span>'; 
                                                        ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <form action="investments.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this investment record?');">
                                                            <input type="hidden" name="investment_id" value="<?php echo $investment['investment_id']; ?>">
                                                            <button type="submit" name="delete_investment" class="btn btn-sm btn-outline-danger">
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
            
            // Investment Distribution Chart
            const investmentDistCtx = document.getElementById('investmentDistChart').getContext('2d');
            
            // Create the data array from PHP investment distribution
            const investmentLabels = [];
            const investmentData = [];
            const backgroundColors = [
                'rgba(255, 205, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(255, 99, 132, 0.7)',
                'rgba(201, 203, 207, 0.7)',
                'rgba(255, 99, 132, 0.5)',
                'rgba(54, 162, 235, 0.5)',
                'rgba(255, 206, 86, 0.5)'
            ];
            
            <?php while ($inv = $investment_distribution->fetch_assoc()): ?>
                investmentLabels.push("<?php echo addslashes($inv['type']); ?>");
                investmentData.push(<?php echo $inv['total']; ?>);
            <?php endwhile; ?>
            
            // If no investment data, show "No Data"
            if (investmentLabels.length === 0) {
                investmentLabels.push("No Data");
                investmentData.push(1);
            }
            
            new Chart(investmentDistCtx, {
                type: 'doughnut',
                data: {
                    labels: investmentLabels,
                    datasets: [{
                        data: investmentData,
                        backgroundColor: backgroundColors.slice(0, investmentLabels.length),
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