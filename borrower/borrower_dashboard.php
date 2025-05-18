<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["user_type"] != "Book Borrower") {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];

// Due date reminder alert logic
$alert_msgs = [];

$reminder_sql = "SELECT r.requested_at, r.borrow_duration, b.title 
                 FROM requests r
                 JOIN books b ON r.book_id = b.id
                 WHERE r.requester_id = ? AND r.status = 'Accepted'";

$reminder_stmt = mysqli_prepare($conn, $reminder_sql);
mysqli_stmt_bind_param($reminder_stmt, "i", $user_id);
mysqli_stmt_execute($reminder_stmt);
$reminder_result = mysqli_stmt_get_result($reminder_stmt);

$today = new DateTime();

while ($row = mysqli_fetch_assoc($reminder_result)) {
    $requested_at = new DateTime($row['requested_at']);
    $borrow_days = intval($row['borrow_duration']);
    $due_date = clone $requested_at;
    $due_date->modify("+$borrow_days days");

    $diff = $today->diff($due_date);
    $days_left = (int)$diff->format("%r%a");

    if ($days_left < 0) {
        $alert_msgs[] = "The book <strong>" . htmlspecialchars($row['title']) . "</strong> is overdue by " . abs($days_left) . " day(s). Please return it ASAP.";
    } elseif ($days_left <= 3) {
        $alert_msgs[] = "The book <strong>" . htmlspecialchars($row['title']) . "</strong> is due in $days_left day(s). Please prepare to return it.";
    }
}
mysqli_stmt_close($reminder_stmt);

// Handle search
$search = "";
$filter_sql = "";
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $like = '%' . $search . '%';
    $filter_sql = " AND (title LIKE ? OR author LIKE ? OR genre LIKE ? OR location LIKE ?)";
}

// Fetch books from other users that are Available
$sql = "SELECT b.id, b.title, b.author, b.genre, b.picture, b.location, b.status, u.email 
        FROM books b
        JOIN users u ON b.user_id = u.id
        WHERE b.user_id != ? AND b.status = 'Available' $filter_sql
        ORDER BY b.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($filter_sql)) {
    mysqli_stmt_bind_param($stmt, "issss", $user_id, $like, $like, $like, $like);
} else {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Borrower Dashboard</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4">Hello, <?php echo htmlspecialchars($_SESSION["email"]); ?></h2>

    <?php if (!empty($alert_msgs)): ?>
        <?php foreach ($alert_msgs as $msg): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?php echo $msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

        <h2>Search Your Book</h2>
    <!-- Search Form -->
<form method="GET" class="search-form mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by title, author, genre or location..." value="<?= htmlspecialchars($search) ?>">
            <div class="input-group-append">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <h2>Available Books</h2>

        <div class="row">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($row['picture']) && file_exists("../owner/" . $row['picture'])): ?>
                            <img src="<?php echo "../owner/" . htmlspecialchars($row['picture']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Book Image">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                            <p><strong>Author:</strong> <?php echo htmlspecialchars($row['author']); ?></p>
                            <p><strong>Genre:</strong> <?php echo htmlspecialchars($row['genre']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars($row['status']); ?></p>
                            <p><strong>Owner Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                            <a href="request_book.php?book_id=<?php echo $row['id']; ?>" class="btn btn-success mt-2">Request Book</a>
                            <a href="reviews.php?book_id=<?php echo $row['id']; ?>" class="btn btn-info mt-2 ml-2">Reviews</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No available books found matching your search.</div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
