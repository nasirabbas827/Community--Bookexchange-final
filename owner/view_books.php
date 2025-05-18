<?php
include('config.php');
session_start();

if (!isset($_SESSION["id"])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION["id"];
$delete_msg = "";

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $book_id = intval($_GET['delete']);

    // First get the image path to delete file
    $img_query = "SELECT picture FROM books WHERE id = ? AND user_id = ?";
    $img_stmt = mysqli_prepare($conn, $img_query);
    mysqli_stmt_bind_param($img_stmt, "ii", $book_id, $user_id);
    mysqli_stmt_execute($img_stmt);
    mysqli_stmt_bind_result($img_stmt, $picture_path);
    mysqli_stmt_fetch($img_stmt);
    mysqli_stmt_close($img_stmt);

    // Delete image file if exists
    if (!empty($picture_path) && file_exists($picture_path)) {
        unlink($picture_path);
    }

    // Delete from DB
    $del_stmt = mysqli_prepare($conn, "DELETE FROM books WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($del_stmt, "ii", $book_id, $user_id);
    if (mysqli_stmt_execute($del_stmt)) {
        $delete_msg = '<div class="alert alert-success">Book deleted successfully.</div>';
    } else {
        $delete_msg = '<div class="alert alert-danger">Error deleting book.</div>';
    }
    mysqli_stmt_close($del_stmt);
}

// Fetch all books by user
$query = "SELECT id, picture, title, author, genre, `condition`, location, status, description, created_at 
          FROM books WHERE user_id = ? ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Books</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Your Book Listings</h2>
        <a href="add_book.php" class="btn btn-primary">Add New Book</a>
    </div>

    <?php echo $delete_msg; ?>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <table class="table table-bordered table-hover">
            <thead class="">
                <tr>
                    <th>Picture</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Genre</th>
                    <th>Condition</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Description</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td style="width: 120px;">
                            <?php if (!empty($row['picture']) && file_exists($row['picture'])): ?>
                                <img src="<?php echo $row['picture']; ?>" alt="Book Picture" style="width: 100px; height: auto; object-fit: cover;">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['author']); ?></td>
                        <td><?php echo htmlspecialchars($row['genre']); ?></td>
                        <td><?php echo htmlspecialchars($row['condition']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                        <td style="max-width: 200px; white-space: pre-wrap;"><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <a href="edit_book.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info mb-1">Edit</a>
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">You haven't added any books yet.</div>
    <?php endif; ?>

</div>
</body>
</html>
