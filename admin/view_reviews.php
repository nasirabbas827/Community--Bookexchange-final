<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM reviews WHERE id = $id");
    header("Location: view_reviews.php");
    exit;
}

$reviews = mysqli_query($conn, "
    SELECT r.*, 
           reviewer.username AS reviewer_name, 
           reviewee.username AS reviewee_name 
    FROM reviews r
    JOIN users reviewer ON r.reviewer_id = reviewer.id
    JOIN users reviewee ON r.reviewee_id = reviewee.id
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Reviews</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <h2>All Reviews</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Reviewer</th>
                <th>Reviewee</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($reviews)) { ?>
            <tr>
                <td><?= htmlspecialchars($row['reviewer_name']) ?></td>
                <td><?= htmlspecialchars($row['reviewee_name']) ?></td>
                <td><?= $row['rating'] ?></td>
                <td><?= htmlspecialchars($row['comment']) ?></td>
                <td><?= $row['created_at'] ?></td>
                <td><a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this review?')" class="btn btn-danger btn-sm">Delete</a></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
