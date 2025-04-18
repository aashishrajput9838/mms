<?php
session_start();
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMS - Money Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero bg-primary text-white text-center py-5">
            <div class="container">
                <h1 class="display-4">Money Management System</h1>
                <p class="lead">Take control of your finances with our all-in-one money management solution</p>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <div class="mt-4">
                        <a href="register.php" class="btn btn-light btn-lg me-3">Sign Up</a>
                        <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
                    </div>
                <?php else: ?>
                    <div class="mt-4">
                        <a href="dashboard.php" class="btn btn-light btn-lg">Go to Dashboard</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features py-5">
            <div class="container">
                <h2 class="text-center mb-5">What Our System Offers</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                                <h3 class="card-title">Income Tracking</h3>
                                <p class="card-text">Monitor your income from multiple sources and get insights on your earning patterns.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-shopping-cart fa-3x text-primary mb-3"></i>
                                <h3 class="card-title">Expense Management</h3>
                                <p class="card-text">Track and categorize your expenses to understand your spending habits.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-wallet fa-3x text-primary mb-3"></i>
                                <h3 class="card-title">Investment Tracking</h3>
                                <p class="card-text">Keep track of your investments in FD/TD/LIC and other financial instruments.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section class="about py-5 bg-light">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <h2>About MMS</h2>
                        <p>MMS (Money Management System) is designed to help you take control of your financial life. Our platform provides tools to track income, manage expenses, and monitor investments all in one place.</p>
                        <p>With MMS, you can visualize your financial health through intuitive dashboards and reports, helping you make informed decisions about your money.</p>
                        <a href="#" class="btn btn-primary">Learn More</a>
                    </div>
                    <div class="col-lg-6">
                        <img src="assets/images/finance-illustration.svg" alt="Finance Illustration" class="img-fluid rounded">
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="testimonials py-5">
            <div class="container">
                <h2 class="text-center mb-5">What Our Users Say</h2>
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="testimonial-carousel">
                            <div class="card mb-4 shadow-sm">
                                <div class="card-body p-4">
                                    <p class="lead mb-3">"MMS has transformed how I manage my finances. I can now track all my income sources and expenses in one place!"</p>
                                    <div class="d-flex align-items-center">
                                        <div class="ms-3">
                                            <h5 class="mb-0">John Doe</h5>
                                            <small class="text-muted">Financial Analyst</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html> 