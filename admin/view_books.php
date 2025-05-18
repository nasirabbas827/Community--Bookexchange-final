<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Delete book if requested
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM books WHERE id = $id");
    header("Location: view_books.php");
    exit;
}

$books = mysqli_query($conn, "SELECT b.*, u.username FROM books b JOIN users u ON b.user_id = u.id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Books</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <h2>All Books</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Picture</th>
                <th>Title</th>
                <th>Author</th>
                <th>Genre</th>
                <th>User</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($books)) { ?>
            <tr>
                <td><img src="../owner/<?= $row['picture'] ?>" width="60"></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['author']) ?></td>
                <td><?= htmlspecialchars($row['genre']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this book?')" class="btn btn-danger btn-sm">Delete</a></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
