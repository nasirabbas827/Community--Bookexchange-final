<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["user_type"] != "Book Owner") {
    header("location: login.php");
    exit;
}

$owner_id = $_SESSION["id"];

// Handle delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM reviews WHERE id = $delete_id AND reviewee_id = $owner_id");
    header("Location: view_reviews.php");
    exit;
}

$reviews = mysqli_query($conn, "
    SELECT r.*, u.username AS reviewer
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    WHERE r.reviewee_id = $owner_id
    ORDER BY r.created_at DESC
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>View Reviews</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h3>All Reviews About You</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Reviewer</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($r = mysqli_fetch_assoc($reviews)) { ?>
            <tr>
                <td><?= htmlspecialchars($r['reviewer']) ?></td>
                <td><?= $r['rating'] ?>/5</td>
                <td><?= htmlspecialchars($r['comment']) ?></td>
                <td><?= $r['created_at'] ?></td>
                <td>
                    <a href="view_reviews.php?delete_id=<?= $r['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
