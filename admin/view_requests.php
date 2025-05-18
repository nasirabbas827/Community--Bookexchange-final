<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM requests WHERE id = $id");
    header("Location: view_requests.php");
    exit;
}

$requests = mysqli_query($conn, "
    SELECT r.*, u.username AS requester, b.title 
    FROM requests r
    JOIN users u ON r.requester_id = u.id
    JOIN books b ON r.book_id = b.id
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Requests</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <h2>All Book Requests</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Book</th>
                <th>Requester</th>
                <th>Type</th>
                <th>Status</th>
                <th>Duration</th>
                <th>Requested At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($requests)) { ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['requester']) ?></td>
                <td><?= htmlspecialchars($row['type']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= $row['borrow_duration'] ?> days</td>
                <td><?= $row['requested_at'] ?></td>
                <td><a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this request?')" class="btn btn-danger btn-sm">Delete</a></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
