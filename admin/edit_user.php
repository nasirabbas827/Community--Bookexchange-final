<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$id = (int)$_GET['id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $id"));

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $age = $_POST["age"];
    $user_type = $_POST["user_type"];

    mysqli_query($conn, "UPDATE users SET username='$username', email='$email', phone='$phone', age='$age', user_type='$user_type' WHERE id=$id");
    header("Location: view_users.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
        <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
    <h2>Edit User</h2>
    <form method="post">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required class="form-control">
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="form-control">
        </div>
        <div class="form-group">
            <label>Phone:</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="form-control">
        </div>
        <div class="form-group">
            <label>Age:</label>
            <input type="number" name="age" value="<?= htmlspecialchars($user['age']) ?>" class="form-control">
        </div>
        <div class="form-group">
            <label>User Type:</label>
            <select name="user_type" class="form-control">
                <option value="Book Owner" <?= $user['user_type'] === 'Book Owner' ? 'selected' : '' ?>>Book Owner</option>
                <option value="Book Borrower" <?= $user['user_type'] === 'Book Borrower' ? 'selected' : '' ?>>Book Borrower</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update User</button>
    </form>
</div>
</div>
</div>
</body>
</html>
