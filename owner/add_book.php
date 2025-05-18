<?php
include('config.php');
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION["id"];
$upload_error = $success_msg = $error_msg = "";
$title = $author = $genre = $condition = $location = $status = $description = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $author = trim($_POST["author"]);
    $genre = trim($_POST["genre"]);
    $condition = trim($_POST["condition"]);
    $location = trim($_POST["location"]);
    $status = $_POST["status"];
    $description = trim($_POST["description"]);
    $picture_path = "";

    // Handle picture upload
    if (!empty($_FILES["picture"]["name"])) {
        $target_dir = "images/";
        $file_name = basename($_FILES["picture"]["name"]);
        $target_file = $target_dir . time() . "_" . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file)) {
                $picture_path = $target_file;
            } else {
                $upload_error = "Error uploading the book picture.";
            }
        } else {
            $upload_error = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    }

    if (empty($upload_error)) {
        $query = "INSERT INTO books (user_id, picture, title, author, genre, `condition`, location, status, description, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "issssssss", $user_id, $picture_path, $title, $author, $genre, $condition, $location, $status, $description);

        if (mysqli_stmt_execute($stmt)) {
            $success_msg = '<div class="alert alert-success">Book added successfully.</div>';
            $title = $author = $genre = $condition = $location = $description = "";
        } else {
            $error_msg = '<div class="alert alert-danger">Error saving book: ' . mysqli_error($conn) . '</div>';
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Book</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-body">
            <h2 class="mb-4">Add a Book</h2>
            <?php echo $success_msg . $upload_error . $error_msg; ?>

            <form method="post" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Book Title:</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
                </div>
                <div class="form-group">
                    <label>Author:</label>
                    <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($author); ?>" required>
                </div>
                <div class="form-group">
                    <label>Genre:</label>
                    <input type="text" name="genre" class="form-control" value="<?php echo htmlspecialchars($genre); ?>" required>
                </div>
                <div class="form-group">
                    <label>Condition:</label>
                    <input type="text" name="condition" class="form-control" value="<?php echo htmlspecialchars($condition); ?>" required>
                </div>
                <div class="form-group">
                    <label>Location:</label>
                    <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($location); ?>">
                </div>
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" class="form-control" required>
                        <option value="Available">Available</option>
                        <option value="Lent Out">Lent Out</option>
                        <option value="Reserved">Reserved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description (optional):</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Book Picture:</label>
                    <input type="file" name="picture" class="form-control-file">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Add Book</button>
                    <a href="view_books.php" class="btn btn-secondary">View Books</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
