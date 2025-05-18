<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Delete operation
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");
}
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Users</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <h2 class="mb-4">All Users</h2>
    <a href="add_user.php" class="btn btn-primary mb-3">Add New User</a>
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th><th>Username</th><th>Email</th><th>Phone</th><th>Age</th><th>Type</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['age']) ?></td>
                <td><?= htmlspecialchars($row['user_type']) ?></td>
                <td>
                    <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete user?')" class="btn btn-sm btn-danger">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
