<?php
session_start();
include('config.php');

// Check if the user is logged in as an admin
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Fetch counts
$userCount    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
$bookCount    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM books"))['total'];
$reportCount  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM reports"))['total'];
$requestCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM requests"))['total'];
$reviewCount  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM reviews"))['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .card {
            margin-bottom: 20px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
    </style>
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Admin Dashboard</h2>

    <div class="dashboard-grid">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <p class="card-text"><?= $userCount ?></p>
                <a href="view_users.php" class="btn btn-light btn-sm">Manage Users</a>
            </div>
        </div>

        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Total Books</h5>
                <p class="card-text"><?= $bookCount ?></p>
                <a href="view_books.php" class="btn btn-light btn-sm">Manage Books</a>
            </div>
        </div>

        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title">Total Reports</h5>
                <p class="card-text"><?= $reportCount ?></p>
                <a href="view_reports.php" class="btn btn-light btn-sm">View Reports</a>
            </div>
        </div>

        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title">Total Requests</h5>
                <p class="card-text"><?= $requestCount ?></p>
                <a href="view_requests.php" class="btn btn-light btn-sm">Manage Requests</a>
            </div>
        </div>

        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Total Reviews</h5>
                <p class="card-text"><?= $reviewCount ?></p>
                <a href="view_reviews.php" class="btn btn-light btn-sm">Manage Reviews</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
