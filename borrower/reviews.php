<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"])) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];

if (!isset($_GET['book_id']) || !is_numeric($_GET['book_id'])) {
    die("Invalid book ID.");
}

$book_id = intval($_GET['book_id']);

// Handle review submission
$review_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'], $_POST['comment'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $review_msg = '<div class="alert alert-danger">Rating must be between 1 and 5.</div>';
    } else {
        // Get owner of the book to set as reviewee
        $sql_owner = "SELECT user_id FROM books WHERE id = ?";
        $stmt_owner = mysqli_prepare($conn, $sql_owner);
        mysqli_stmt_bind_param($stmt_owner, "i", $book_id);
        mysqli_stmt_execute($stmt_owner);
        mysqli_stmt_bind_result($stmt_owner, $reviewee_id);
        mysqli_stmt_fetch($stmt_owner);
        mysqli_stmt_close($stmt_owner);

        if ($reviewee_id && $reviewee_id != $user_id) {
            $insert_sql = "INSERT INTO reviews (reviewer_id, reviewee_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt_insert = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt_insert, "iiis", $user_id, $reviewee_id, $rating, $comment);

            if (mysqli_stmt_execute($stmt_insert)) {
                $review_msg = '<div class="alert alert-success">Review posted successfully.</div>';
            } else {
                $review_msg = '<div class="alert alert-danger">Failed to post review.</div>';
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $review_msg = '<div class="alert alert-danger">Invalid reviewee.</div>';
        }
    }
}

// Handle report submission
$report_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_reason'])) {
    $reason = trim($_POST['report_reason']);

    if (!empty($reason)) {
        $insert_report = "INSERT INTO reports (book_id, reporter_id, reason, reported_at) VALUES (?, ?, ?, NOW())";
        $stmt_report = mysqli_prepare($conn, $insert_report);
        mysqli_stmt_bind_param($stmt_report, "iis", $book_id, $user_id, $reason);

        if (mysqli_stmt_execute($stmt_report)) {
            $report_msg = '<div class="alert alert-success">Book reported successfully.</div>';
        } else {
            $report_msg = '<div class="alert alert-danger">Failed to report book.</div>';
        }
        mysqli_stmt_close($stmt_report);
    } else {
        $report_msg = '<div class="alert alert-danger">Please provide a reason for reporting.</div>';
    }
}

// Fetch book details
$sql_book = "SELECT b.title, b.author, b.genre, b.picture, b.location, u.email AS owner_email 
             FROM books b 
             JOIN users u ON b.user_id = u.id 
             WHERE b.id = ?";
$stmt_book = mysqli_prepare($conn, $sql_book);
mysqli_stmt_bind_param($stmt_book, "i", $book_id);
mysqli_stmt_execute($stmt_book);
$result_book = mysqli_stmt_get_result($stmt_book);

if (mysqli_num_rows($result_book) === 0) {
    die("Book not found.");
}

$book = mysqli_fetch_assoc($result_book);
mysqli_stmt_close($stmt_book);

// Fetch all reviews for the book (reviews about the book owner)
$sql_reviews = "SELECT r.rating, r.comment, r.created_at, u.email AS reviewer_email 
                FROM reviews r 
                JOIN users u ON r.reviewer_id = u.id 
                WHERE r.reviewee_id = (SELECT user_id FROM books WHERE id = ?) 
                ORDER BY r.created_at DESC";
$stmt_reviews = mysqli_prepare($conn, $sql_reviews);
mysqli_stmt_bind_param($stmt_reviews, "i", $book_id);
mysqli_stmt_execute($stmt_reviews);
$result_reviews = mysqli_stmt_get_result($stmt_reviews);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reviews - <?php echo htmlspecialchars($book['title']); ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .star-rating {
            color: #ffc107;
            font-size: 1.1rem;
        }
        .star-rating .empty {
            color: #e4e5e9;
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">

    <div class="row">

        <!-- Book Details -->
        <div class="col-md-4">
            <h2>Book Details</h2>
            <?php if (!empty($book['picture']) && file_exists("../owner/" . $book['picture'])): ?>
                <img src="<?php echo "../owner/" . htmlspecialchars($book['picture']); ?>" alt="Book Image" class="img-fluid mb-3" style="max-height: 300px; object-fit: cover;">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($book['title']); ?></h3>
            <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
            <p><strong>Genre:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($book['location']); ?></p>
            <p><strong>Owner Email:</strong> <?php echo htmlspecialchars($book['owner_email']); ?></p>
        </div>

        <!-- Reviews -->
        <div class="col-md-4">
            <h2>Reviews</h2>
            <?php if ($review_msg) echo $review_msg; ?>
            <?php if (mysqli_num_rows($result_reviews) > 0): ?>
                <ul class="list-group mb-4">
                    <?php while ($review = mysqli_fetch_assoc($result_reviews)): ?>
                        <li class="list-group-item mt-2">
                            <strong><?php echo htmlspecialchars($review['reviewer_email']); ?></strong> — 
                            <span class="star-rating">
                                <?php
                                    $fullStars = intval($review['rating']);
                                    $emptyStars = 5 - $fullStars;
                                    for ($i=0; $i < $fullStars; $i++) echo '★';
                                    for ($i=0; $i < $emptyStars; $i++) echo '<span class="empty">★</span>';
                                ?>
                            </span>
                            <br>
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            <br>
                            <small class="text-muted"><?php echo $review['created_at']; ?></small>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No reviews yet for this book owner.</p>
            <?php endif; ?>
        </div>

        <!-- Review Form and Report Form -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h3>Add Your Review</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="rating">Rating (1–5):</label>
                            <select name="rating" id="rating" class="form-control" required>
                                <option value="">Select rating</option>
                                <?php for ($i=1; $i<=5; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="comment">Comment (optional):</label>
                            <textarea name="comment" id="comment" class="form-control" rows="4"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3>Report this Book</h3>
                    <?php if ($report_msg) echo $report_msg; ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="report_reason">Reason for reporting:</label>
                            <textarea name="report_reason" id="report_reason" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger">Report Book</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
