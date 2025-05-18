<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["user_type"] != "Book Borrower") {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];

// Fetch user's requests
$sql = "SELECT r.id, r.type, r.status, r.borrow_duration, r.requested_at, 
               b.title, b.author, b.genre, b.picture, b.location, u.email AS owner_email
        FROM requests r
        JOIN books b ON r.book_id = b.id
        JOIN users u ON b.user_id = u.id
        WHERE r.requester_id = ?
        ORDER BY r.requested_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Book Requests</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4">My Book Requests</h2>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Genre</th>
                        <th>Location</th>
                        <th>Owner Email</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Duration (days)</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['picture']) && file_exists("../owner/" . $row['picture'])): ?>
                                    <img src="<?php echo "../owner/" . $row['picture']; ?>" width="80" height="100" style="object-fit: cover;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['genre']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo htmlspecialchars($row['owner_email']); ?></td>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo !empty($row['borrow_duration']) ? $row['borrow_duration'] : '-'; ?></td>
                            <td>
    <a href="messages.php?request_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Messages</a>
</td>

                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">You have not sent any book requests yet.</div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
