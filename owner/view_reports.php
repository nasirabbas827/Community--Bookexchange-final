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
    mysqli_query($conn, "
        DELETE rep FROM reports rep
        JOIN books b ON rep.book_id = b.id
        WHERE rep.id = $delete_id AND b.user_id = $owner_id
    ");
    header("Location: view_reports.php");
    exit;
}

$reports = mysqli_query($conn, "
    SELECT rep.*, b.title AS book_title, u.username AS reporter
    FROM reports rep
    JOIN books b ON rep.book_id = b.id
    JOIN users u ON rep.reporter_id = u.id
    WHERE b.user_id = $owner_id
    ORDER BY rep.reported_at DESC
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>View Reports</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h3>Reports on Your Books</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Book</th>
                <th>Reporter</th>
                <th>Reason</th>
                <th>Reported At</th>
            </tr>
        </thead>
        <tbody>
            <?php while($r = mysqli_fetch_assoc($reports)) { ?>
            <tr>
                <td><?= htmlspecialchars($r['book_title']) ?></td>
                <td><?= htmlspecialchars($r['reporter']) ?></td>
                <td><?= htmlspecialchars($r['reason']) ?></td>
                <td><?= $r['reported_at'] ?></td>
                
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
