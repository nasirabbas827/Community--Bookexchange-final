<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["user_type"] != "Book Owner") {
    header("location: login.php");
    exit;
}

$owner_id = $_SESSION["id"];

// Count Totals
$totalBooks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM books WHERE user_id = $owner_id"))['total'];
$totalRequests = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM requests r
    JOIN books b ON r.book_id = b.id
    WHERE b.user_id = $owner_id
"))['total'];
$totalReviews = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM reviews WHERE reviewee_id = $owner_id
"))['total'];
$totalReports = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM reports rep
    JOIN books b ON rep.book_id = b.id
    WHERE b.user_id = $owner_id
"))['total'];

// Recent Entries
$recentRequests = mysqli_query($conn, "
    SELECT r.*, b.title, u.username AS requester
    FROM requests r
    JOIN books b ON r.book_id = b.id
    JOIN users u ON r.requester_id = u.id
    WHERE b.user_id = $owner_id
    ORDER BY r.requested_at DESC
    LIMIT 5
");

$recentMessages = mysqli_query($conn, "
    SELECT m.*, u.username AS sender
    FROM messages m
    JOIN requests r ON m.request_id = r.id
    JOIN books b ON r.book_id = b.id
    JOIN users u ON m.sender_id = u.id
    WHERE b.user_id = $owner_id
    ORDER BY m.sent_at DESC
    LIMIT 5
");

$recentReports = mysqli_query($conn, "
    SELECT rep.*, b.title, u.username AS reporter
    FROM reports rep
    JOIN books b ON rep.book_id = b.id
    JOIN users u ON rep.reporter_id = u.id
    WHERE b.user_id = $owner_id
    ORDER BY rep.reported_at DESC
    LIMIT 5
");

$recentReviews = mysqli_query($conn, "
    SELECT r.*, u.username AS reviewer
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    WHERE r.reviewee_id = $owner_id
    ORDER BY r.created_at DESC
    LIMIT 5
");

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Owner Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .card-summary {
            border-left: 5px solid #007bff;
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .card-summary h5 {
            margin: 0;
            font-weight: bold;
        }
        .dashboard-grid {
            gap: 20px; /* Space between the rows */
        }
        .dashboard-grid .row {
            margin-bottom: 20px; /* Space between the pairs of tables */
        }
        .dashboard-grid .row:last-child {
            margin-bottom: 0; /* Remove margin for the last row */
        }
        table {
            font-size: 14px;
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="text-center mb-4">Welcome, <?= htmlspecialchars($_SESSION["email"]) ?>!</h2>

    <div class="row text-center">
        <div class="col-md-3">
            <div class="card-summary">
                <h5>Total Books</h5>
                <p><?= $totalBooks ?></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-summary">
                <h5>Total Requests</h5>
                <p><?= $totalRequests ?></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-summary">
                <h5>Total Reviews</h5>
                <p><?= $totalReviews ?></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-summary">
                <h5>Total Reports</h5>
                <p><?= $totalReports ?></p>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="row">
            <div class="col-md-6">
                <h5>Recent Requests</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Requester</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = mysqli_fetch_assoc($recentRequests)) { ?>
                        <tr>
                            <td><?= htmlspecialchars($r['title']) ?></td>
                            <td><?= htmlspecialchars($r['requester']) ?></td>
                            <td><?= $r['type'] ?></td>
                            <td><?= $r['status'] ?></td>
                            <td><?= $r['borrow_duration'] ?> days</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Recent Messages</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Sender</th>
                            <th>Message</th>
                            <th>Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($m = mysqli_fetch_assoc($recentMessages)) { ?>
                        <tr>
                            <td><?= htmlspecialchars($m['sender']) ?></td>
                            <td><?= htmlspecialchars($m['content']) ?></td>
                            <td><?= $m['sent_at'] ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h5>Recent Reports</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Reporter</th>
                            <th>Reason</th>
                            <th>Reported At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($rep = mysqli_fetch_assoc($recentReports)) { ?>
                        <tr>
                            <td><?= htmlspecialchars($rep['title']) ?></td>
                            <td><?= htmlspecialchars($rep['reporter']) ?></td>
                            <td><?= htmlspecialchars($rep['reason']) ?></td>
                            <td><?= $rep['reported_at'] ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Recent Reviews</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Reviewer</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($rev = mysqli_fetch_assoc($recentReviews)) { ?>
                        <tr>
                            <td><?= htmlspecialchars($rev['reviewer']) ?></td>
                            <td><?= $rev['rating'] ?>/5</td>
                            <td><?= htmlspecialchars($rev['comment']) ?></td>
                            <td><?= $rev['created_at'] ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>