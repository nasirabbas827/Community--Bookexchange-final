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

    if ($rating < 1 || $rating > 5) {
        $review_msg = '<div class="alert alert-danger">Rating must be between 1 and 5.</div>';
    } else {
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

// Calculate average rating
$average_rating = 0;
$total_reviews = mysqli_num_rows($result_reviews);
if ($total_reviews > 0) {
    $total_rating = 0;
    mysqli_data_seek($result_reviews, 0); // Reset result pointer
    while ($review = mysqli_fetch_assoc($result_reviews)) {
        $total_rating += $review['rating'];
    }
    $average_rating = round($total_rating / $total_reviews, 1);
    mysqli_data_seek($result_reviews, 0); // Reset again for display
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($book['title']); ?> - Book Details</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            padding: 2rem 1rem;
        }

        /* Book Details Section */
        .book-details {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .book-image {
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .book-info h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #131921;
            margin-bottom: 0.5rem;
        }

        .book-info .author {
            color: #007185;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .book-info .genre, .book-info .location, .book-info .owner {
            font-size: 1rem;
            color: #565959;
            margin-bottom: 0.5rem;
        }

        .rating-stars {
            color: #f0c14b;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .rating-stars .empty {
            color: #e4e5e9;
        }

        .rating-text {
            color: #007185;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }

        /* Review Section */
        .reviews-section {
            background: #fff;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .reviews-section h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #131921;
            margin-bottom: 1.5rem;
        }

        .review-item {
            border-bottom: 1px solid #e7e7e7;
            padding: 1rem 0;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-item .reviewer {
            font-weight: 500;
            color: #131921;
        }

        .review-item .comment {
            color: #565959;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .review-item .date {
            color: #999;
            font-size: 0.85rem;
        }

        /* Form Section */
        .form-section {
            background: #fff;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-section h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #131921;
            margin-bottom: 1.5rem;
        }

        .form-control {
            border-radius: 5px;
            border: 1px solid #888;
            padding: 0.5rem;
        }

        .btn-primary, .btn-danger {
            border-radius: 5px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }

        .btn-primary {
            background-color: #f0c14b;
            border-color: #f0c14b;
            color: #111;
        }

        .btn-primary:hover {
            background-color: #e0b038;
            border-color: #e0b038;
        }

        .btn-danger {
            background-color: #d9534f;
            border-color: #d9534f;
        }

        .btn-danger:hover {
            background-color: #c9302c;
            border-color: #c9302c;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .book-info h1 {
                font-size: 1.8rem;
            }

            .book-image {
                max-height: 300px;
            }

            .form-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container">
    <!-- Book Details Section -->
    <div class="book-details row">
        <div class="col-md-4 mb-4">
            <?php if (!empty($book['picture']) && file_exists("../owner/" . $book['picture'])): ?>
                <img src="<?php echo "../owner/" . htmlspecialchars($book['picture']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-image img-fluid">
            <?php else: ?>
                <img src="https://via.placeholder.com/300x400?text=Book+Cover" alt="Book Placeholder" class="book-image img-fluid">
            <?php endif; ?>
        </div>
        <div class="col-md-8">
            <div class="book-info">
                <h1><?php echo htmlspecialchars($book['title']); ?></h1>
                <p class="author">by <?php echo htmlspecialchars($book['author']); ?></p>
                <div class="rating-stars">
                    <?php
                        $fullStars = floor($average_rating);
                        $emptyStars = 5 - $fullStars;
                        for ($i = 0; $i < $fullStars; $i++) echo '★';
                        for ($i = 0; $i < $emptyStars; $i++) echo '<span class="empty">★</span>';
                    ?>
                    <span class="rating-text"><?php echo $average_rating; ?> out of 5 (<?php echo $total_reviews; ?> reviews)</span>
                </div>
                <p class="genre"><strong>Genre:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                <p class="location"><strong>Location:</strong> <?php echo htmlspecialchars($book['location']); ?></p>
                <p class="owner"><strong>Contact Owner:</strong> <?php echo htmlspecialchars($book['owner_email']); ?></p>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="reviews-section">
        <h2>Customer Reviews</h2>
        <?php if ($review_msg) echo $review_msg; ?>
        <?php if ($total_reviews > 0): ?>
            <?php while ($review = mysqli_fetch_assoc($result_reviews)): ?>
                <div class="review-item">
                    <div class="reviewer"><?php echo htmlspecialchars($review['reviewer_email']); ?></div>
                    <div class="rating-stars">
                        <?php
                            $fullStars = intval($review['rating']);
                            $emptyStars = 5 - $fullStars;
                            for ($i = 0; $i < $fullStars; $i++) echo '★';
                            for ($i = 0; $i < $emptyStars; $i++) echo '<span class="empty">★</span>';
                        ?>
                    </div>
                    <div class="comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
                    <div class="date"><?php echo $review['created_at']; ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews yet for this book owner.</p>
        <?php endif; ?>
    </div>

    <!-- Review and Report Forms -->
    <div class="form-section">
        <div class="row">
            <div class="col-md-6 mb-4">
                <h3>Write a Review</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="rating">Rating (1–5):</label>
                        <select name="rating" id="rating" class="form-control" required>
                            <option value="">Select rating</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
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
            <div class="col-md-6">
                <h3>Report this Book</h3>
                <?php if ($report_msg) echo $report_msg; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="report_reason">Reason for reporting:</label>
                        <textarea name="report_reason" id="report_reason" class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">Report Book</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>