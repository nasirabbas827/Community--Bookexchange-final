<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["user_type"] != "Book Borrower") {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];

// Fetch total requests count
$total_requests_sql = "SELECT COUNT(*) as total_requests FROM requests WHERE requester_id = ?";
$total_requests_stmt = mysqli_prepare($conn, $total_requests_sql);
mysqli_stmt_bind_param($total_requests_stmt, "i", $user_id);
mysqli_stmt_execute($total_requests_stmt);
$total_requests_result = mysqli_stmt_get_result($total_requests_stmt);
$total_requests = mysqli_fetch_assoc($total_requests_result)['total_requests'];
mysqli_stmt_close($total_requests_stmt);

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
<html lang="en">
<head>
    <title>Borrower Dashboard</title>
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

        /* Hero Section */
        .welcome-section {
            position: relative;
            background: linear-gradient(to right, #131921, #2a2a2a);
            color: white;
            padding: 4rem 2rem;
            border-radius: 12px;
            margin-bottom: 3rem;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            height:480px;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('https://images.pexels.com/photos/904616/pexels-photo-904616.jpeg');
            background-size: cover;
            background-position: center;
            opacity: 0.3;
            z-index: 1;
        }

        .welcome-section .content {
            position: relative;
            z-index: 2;
        }

        .welcome-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            margin-bottom: 0.75rem;
        }

        .welcome-section p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.95;
        }

        /* Alerts */
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
            border-radius: 5px;
        }

        /* Search Form */
        .search-form .form-control {
            border-radius: 5px 0 0 5px;
            border: 1px solid #888;
            padding: 0.6rem;
            font-size: 1rem;
        }

        .search-form .btn-primary {
            background-color: #f0c14b;
            border-color: #f0c14b;
            color: #111;
            border-radius: 0 5px 5px 0;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }

        .search-form .btn-primary:hover {
            background-color: #e0b038;
            border-color: #e0b038;
        }

        /* Book Cards */
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .card-img-top {
            height: 250px;
            object-fit: cover;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #131921;
            margin-bottom: 0.75rem;
        }

        .card-text {
            color: #565959;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .btn-success, .btn-info {
            border-radius: 5px;
            padding: 0.5rem 1.2rem;
            font-weight: 500;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .btn-info {
            background-color: #007185;
            border-color: #007185;
        }

        .btn-info:hover {
            background-color: #005a6a;
            border-color: #005a6a;
        }

        /* Requests Summary */
        .requests-summary {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .requests-summary h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #131921;
            margin-bottom: 1rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .welcome-section h2 {
                font-size: 2rem;
            }

            .welcome-section p {
                font-size: 1rem;
            }

            .card-img-top {
                height: 200px;
            }
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

    <div class="welcome-section">
        <div class="content">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION["email"]); ?>!</h2>
            <p>Discover and borrow books from our vibrant community of readers. Start exploring now!</p>
        </div>
    </div>
<div class="container">

    <!-- Requests Summary -->
    <div class="requests-summary">
        <h3>Your Activity</h3>
        <p>You have made <strong><?php echo $total_requests; ?></strong> total book request(s).</p>
    </div>

    <!-- Alerts -->
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

    <!-- Search Form -->
    <h2>Search for Books</h2>
    <form method="GET" class="search-form mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by title, author, genre, or location..." value="<?php echo htmlspecialchars($search); ?>">
            <div class="input-group-append">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>

    <!-- Available Books -->
    <?php if (mysqli_num_rows($result) > 0): ?>
        <h2>Available Books</h2>
        <div class="row">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($row['picture']) && file_exists("../owner/" . $row['picture'])): ?>
                            <img src="<?php echo "../owner/" . htmlspecialchars($row['picture']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['title']); ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/250x250?text=Book+Cover" class="card-img-top" alt="Book Placeholder">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                            <p class="card-text"><strong>Author:</strong> <?php echo htmlspecialchars($row['author']); ?></p>
                            <p class="card-text"><strong>Genre:</strong> <?php echo htmlspecialchars($row['genre']); ?></p>
                            <p class="card-text"><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                            <p class="card-text"><strong>Status:</strong> <?php echo htmlspecialchars($row['status']); ?></p>
                            <p class="card-text"><strong>Owner:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>