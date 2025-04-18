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

// Process form submission for adding income
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_income'])) {
        $source = trim($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = $_POST['date'];
        $description = trim($_POST['description']);
        
        // Validate input
        if (empty($source)) {
            $error_message = "Income source is required";
        } elseif ($amount <= 0) {
            $error_message = "Amount must be greater than zero";
        } elseif (empty($date)) {
            $error_message = "Date is required";
        } else {
            // Insert income record
            $stmt = $conn->prepare("INSERT INTO INCOME (user_id, source, amount, date, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdss", $user_id, $source, $amount, $date, $description);
            
            if ($stmt->execute()) {
                $success_message = "Income record added successfully!";
                // Clear form data
                unset($source, $amount, $date, $description);
            } else {
                $error_message = "Error adding income record: " . $stmt->error;
            }
        }
    } elseif (isset($_POST['delete_income'])) {
        // Delete income record
        $income_id = $_POST['income_id'];
        
        $stmt = $conn->prepare("DELETE FROM INCOME WHERE income_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $income_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Income record deleted successfully!";
        } else {
            $error_message = "Error deleting income record: " . $stmt->error;
        }
    }
}

// Get all income records for the user
$sql = "SELECT * FROM INCOME WHERE user_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$income_records = $stmt->get_result();

// Get total income
$sql = "SELECT SUM(amount) as total FROM INCOME WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_income = 0;
if ($row = $result->fetch_assoc()) {
    $total_income = $row['total'] ?: 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Income Management</h1>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card form-container">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Add New Income</h5>
                            
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
                            
                            <form action="income.php" method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="source" class="form-label">Income Source</label>
                                    <input type="text" class="form-control" id="source" name="source" value="<?php echo isset($source) ? htmlspecialchars($source) : ''; ?>" required>
                                    <div class="invalid-feedback">Please enter income source</div>
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
                                    <button type="submit" name="add_income" class="btn btn-primary">Add Income</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Income Summary</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Total Income:</span>
                                <span class="h4 mb-0 text-success">$<?php echo number_format($total_income, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Income Records</h5>
                            
                            <?php if ($income_records->num_rows == 0): ?>
                                <p class="text-muted">No income records found. Add your first income source using the form.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Source</th>
                                                <th>Description</th>
                                                <th class="text-end">Amount</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($income = $income_records->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($income['date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($income['source']); ?></td>
                                                    <td>
                                                        <?php 
                                                            echo !empty($income['description']) 
                                                                ? htmlspecialchars($income['description']) 
                                                                : '<span class="text-muted">No description</span>'; 
                                                        ?>
                                                    </td>
                                                    <td class="text-end text-success">
                                                        $<?php echo number_format($income['amount'], 2); ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <form action="income.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this income record?');">
                                                            <input type="hidden" name="income_id" value="<?php echo $income['income_id']; ?>">
                                                            <button type="submit" name="delete_income" class="btn btn-sm btn-outline-danger">
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
        });
    </script>
</body>
</html> 