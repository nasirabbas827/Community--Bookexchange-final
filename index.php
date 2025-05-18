<?php
session_start();
include('config.php');

$user_id = isset($_SESSION["id"]) ? $_SESSION["id"] : 0;

// Handle search
$search = "";
$filter_sql = "";
$params = [];

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = trim($_GET['search']);
    $like = '%' . $search . '%';
    $filter_sql = " AND (b.title LIKE ? OR b.author LIKE ? OR b.genre LIKE ? OR b.location LIKE ?)";
    $params = [$like, $like, $like, $like];
}

// Prepare SQL
$sql = "SELECT b.id, b.title, b.author, b.genre, b.picture, b.location, b.status, u.email
        FROM books b
        JOIN users u ON b.user_id = u.id
        WHERE b.user_id != ? AND b.status = 'Available' $filter_sql
        ORDER BY b.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

// Bind params
if (!empty($filter_sql)) {
    mysqli_stmt_bind_param($stmt, "issss", $user_id, ...$params);
} else {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Community Book Exchange</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .jumbotron {
            height: 500px;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('./images/hotel.jpg') center/cover no-repeat;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .search-form {
            max-width: 500px;
            margin: 0 auto;
        }
        .feature-section {
            padding: 60px 0;
            background-color: #f8f9fa;
        }
        .feature-icon {
            font-size: 2em;
            color: #007bff;
            margin-bottom: 15px;
        }
        .cta-section {
            background-color: #e9ecef;
            padding: 50px 0;
            text-align: center;
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="jumbotron text-center">
    <h1>Welcome to Community Book Exchange</h1>
    <p>Share, Borrow & Discover Books Around You</p>
    <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
        <a href="login.php" class="btn btn-primary btn-lg">Login to Explore</a>
    <?php else: ?>
        <a href="dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard</a>
    <?php endif; ?>
</div>

<div class="container mt-5">
    <h2 class="text-center mb-4">Search for Books</h2>

    <form method="GET" class=" mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by title, author, genre or location..." value="<?= htmlspecialchars($search) ?>">
            <div class="input-group-append">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <h3 class="mb-3">Available Books</h3>
        <div class="row">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($row['picture']) && file_exists("owner/" . $row['picture'])): ?>
                            <img src="owner/<?= htmlspecialchars($row['picture']) ?>" class="card-img-top" alt="Book Image">
                        <?php else: ?>
                            <img src="images/default_book.jpg" class="card-img-top" alt="Default Book Image">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                            <p><strong>Author:</strong> <?= htmlspecialchars($row['author']) ?></p>
                            <p><strong>Genre:</strong> <?= htmlspecialchars($row['genre']) ?></p>
                            <p><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                            <p><strong>Status:</strong> <?= htmlspecialchars($row['status']) ?></p>
                            <p><strong>Owner Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                            <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
                                <a href="login.php" class="btn btn-success mt-2">Request Book</a>
                                <a href="login.php" class="btn btn-info mt-2 ml-2">Reviews</a>
                            <?php else: ?>
                                <a href="request_book.php?book_id=<?= $row['id'] ?>" class="btn btn-success mt-2">Request Book</a>
                                <a href="view_reviews.php?book_id=<?= $row['id'] ?>" class="btn btn-info mt-2 ml-2">Reviews</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">No available books found matching your search.</div>
    <?php endif; ?>
</div>

<section class="feature-section">
    <div class="container">
        <h2 class="text-center mb-5">Why Choose Our Book Exchange?</h2>
        <div class="row">
            <div class="col-md-4 text-center">
                <div class="feature-icon">📚</div>
                <h3>Discover New Reads</h3>
                <p>Explore a wide variety of books from different genres and authors within your community.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="feature-icon">🤝</div>
                <h3>Connect with Your Community</h3>
                <p>Meet fellow book lovers in your area and build connections through shared reading experiences.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="feature-icon">💰</div>
                <h3>Save Money</h3>
                <p>Borrow books for free instead of buying them, making reading more accessible and affordable.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2>Ready to Start Sharing and Discovering?</h2>
        <p class="lead">Join our community today and unlock a world of books!</p>
        <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
            <a href="register.php" class="btn btn-primary btn-lg">Sign Up Now</a>
        <?php else: ?>
            <a href="add_book.php" class="btn btn-primary btn-lg">Share Your Book</a>
        <?php endif; ?>
    </div>
</section>


<footer class="mt-5 py-3 bg-light">
    <div class="container text-center">
        <p>&copy; 2025 Community Book Exchange. All rights reserved.</p>
    </div>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>