<?php
session_start();
include('config.php');

// Ensure only admins access the page
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Fetch all reports
$sql = "SELECT r.id, r.book_id, r.reporter_id, r.reason, r.reported_at, 
               b.title AS book_title, u.email AS reporter_email
        FROM reports r
        LEFT JOIN books b ON r.book_id = b.id
        LEFT JOIN users u ON r.reporter_id = u.id
        ORDER BY r.reported_at DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Reports - Admin</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4 text-center">User Reports</h2>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead >
                    <tr>
                        <th>#</th>
                        <th>Book Title</th>
                        <th>Reporter Email</th>
                        <th>Reason</th>
                        <th>Reported At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['book_title'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['reporter_email'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['reason']) ?></td>
                            <td><?= htmlspecialchars($row['reported_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No reports found.</div>
    <?php endif; ?>
</div>

</body>
</html>
