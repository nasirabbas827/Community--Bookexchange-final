<?php
include('../config.php');
session_start();

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$update_msg = $upload_error = "";

// Fetch book info and verify ownership
$sql = "SELECT user_id, picture, title, author, genre, `condition`, location, status, description FROM books WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $book_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) != 1) {
    // Book not found
    header("Location: view_books.php");
    exit;
}

mysqli_stmt_bind_result($stmt, $owner_id, $picture, $title, $author, $genre, $condition, $location, $status, $description);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($owner_id != $user_id) {
    // Prevent editing other's books
    header("Location: view_books.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title_new = trim($_POST['title']);
    $author_new = trim($_POST['author']);
    $genre_new = trim($_POST['genre']);
    $condition_new = trim($_POST['condition']);
    $location_new = trim($_POST['location']);
    $status_new = trim($_POST['status']);
    $description_new = trim($_POST['description']);

    // Handle picture upload
    if (!empty($_FILES['picture']['name'])) {
        $target_dir = "../images/";
        $file_name = basename($_FILES['picture']['name']);
        $target_file = $target_dir . time() . "_" . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowed)) {
            if (move_uploaded_file($_FILES['picture']['tmp_name'], $target_file)) {
                // Delete old picture file if exists
                if (!empty($picture) && file_exists($picture)) {
                    unlink($picture);
                }
                $picture = $target_file;
            } else {
                $upload_error = "Error uploading the picture.";
            }
        } else {
            $upload_error = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    }

    // Update book in DB
    if (empty($upload_error)) {
        $update_sql = "UPDATE books SET picture = ?, title = ?, author = ?, genre = ?, `condition` = ?, location = ?, status = ?, description = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ssssssssi", $picture, $title_new, $author_new, $genre_new, $condition_new, $location_new, $status_new, $description_new, $book_id);

        if (mysqli_stmt_execute($update_stmt)) {
            $update_msg = '<div class="alert alert-success">Book updated successfully.</div>';
            // Update variables to reflect the changes on form
            $title = $title_new;
            $author = $author_new;
            $genre = $genre_new;
            $condition = $condition_new;
            $location = $location_new;
            $status = $status_new;
            $description = $description_new;
        } else {
            $update_msg = '<div class="alert alert-danger">Error updating book: ' . mysqli_error($conn) . '</div>';
        }
        mysqli_stmt_close($update_stmt);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Book</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
        <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
    <h2>Edit Book</h2>
    <?php
    echo $update_msg;
    if (!empty($upload_error)) {
        echo '<div class="alert alert-warning">' . $upload_error . '</div>';
    }
    ?>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $book_id); ?>" enctype="multipart/form-data">
        <div class="form-group">
            <label>Current Picture:</label><br />
            <?php if (!empty($picture) && file_exists($picture)): ?>
                <img src="<?php echo $picture; ?>" alt="Book Picture" style="width:150px; height:auto; object-fit:cover; margin-bottom:10px;">
            <?php else: ?>
                <p>No picture uploaded.</p>
            <?php endif; ?>
            <input type="file" name="picture" class="form-control-file" />
        </div>

        <div class="form-group">
            <label>Title:</label>
            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($title); ?>" />
        </div>

        <div class="form-group">
            <label>Author:</label>
            <input type="text" name="author" class="form-control" required value="<?php echo htmlspecialchars($author); ?>" />
        </div>

        <div class="form-group">
            <label>Genre:</label>
            <input type="text" name="genre" class="form-control" value="<?php echo htmlspecialchars($genre); ?>" />
        </div>

        <div class="form-group">
            <label>Condition:</label>
            <input type="text" name="condition" class="form-control" value="<?php echo htmlspecialchars($condition); ?>" />
        </div>

        <div class="form-group">
            <label>Location:</label>
            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($location); ?>" />
        </div>

        <div class="form-group">
            <label>Status:</label>
            <select name="status" class="form-control" required>
                <?php
                $statuses = ['Available', 'Lent Out', 'Reserved'];
                foreach ($statuses as $s) {
                    $selected = ($status === $s) ? 'selected' : '';
                    echo "<option value=\"$s\" $selected>$s</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>Description:</label>
            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Book</button>
        <a href="view_books.php" class="btn btn-secondary ml-2">Cancel</a>
    </form>
</div>
</div>
</div>
</body>
</html>
