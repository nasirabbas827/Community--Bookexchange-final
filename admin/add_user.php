<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $phone = $_POST["phone"];
    $age = $_POST["age"];
    $user_type = $_POST["user_type"];

    $sql = "INSERT INTO users (username, email, password, phone, age, user_type) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $username, $email, $password, $phone, $age, $user_type);
    if (mysqli_stmt_execute($stmt)) {
        $message = "User added successfully.";
    } else {
        $message = "Failed to add user.";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5 mb-5">
        <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
    <h2>Add New User</h2>
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required class="form-control">
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required class="form-control">
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required class="form-control">
        </div>
        <div class="form-group">
            <label>Phone:</label>
            <input type="text" name="phone" required class="form-control">
        </div>
        <div class="form-group">
            <label>Age:</label>
            <input type="number" name="age" required class="form-control">
        </div>
        <div class="form-group">
            <label>User Type:</label>
            <select name="user_type" class="form-control">
                <option value="Book Owner">Book Owner</option>
                <option value="Book Borrower">Book Borrower</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Add User</button>
        <a class="btn btn-dark" href="view_users.php">View Users</a>
    </form>
</div>
</div>
</div>
</body>
</html>
