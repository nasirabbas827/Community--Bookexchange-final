<?php
session_start();
include('config.php');

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id'];
$user_type = $_SESSION['user_type'];

// Validate request_id param
if (!isset($_GET['request_id']) || !is_numeric($_GET['request_id'])) {
    die("Invalid request.");
}

$request_id = intval($_GET['request_id']);

// Check if this user (borrower or owner) is allowed to view this request and messages
$check_sql = "
    SELECT r.id, r.book_id, r.requester_id, b.user_id AS owner_id, b.title 
    FROM requests r
    JOIN books b ON r.book_id = b.id
    WHERE r.id = ?
";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "i", $request_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$request = mysqli_fetch_assoc($result);

if (!$request) {
    die("Request not found.");
}

// Only borrower or owner can access messages of this request
if ($user_id != $request['requester_id'] && $user_id != $request['owner_id']) {
    die("Access denied.");
}

// Handle message send
$msg_sent = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['message'])) {
    $content = trim($_POST['message']);
    if ($content !== "") {
        $insert_sql = "INSERT INTO messages (request_id, sender_id, content) VALUES (?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "iis", $request_id, $user_id, $content);
        if (mysqli_stmt_execute($insert_stmt)) {
            $msg_sent = "Message sent.";
        } else {
            $msg_sent = "Failed to send message.";
        }
        mysqli_stmt_close($insert_stmt);
    }
}

// Fetch messages for this request
$msg_sql = "
    SELECT m.content, m.sent_at, u.email, u.id AS sender_id
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.request_id = ?
    ORDER BY m.sent_at ASC
";
$msg_stmt = mysqli_prepare($conn, $msg_sql);
mysqli_stmt_bind_param($msg_stmt, "i", $request_id);
mysqli_stmt_execute($msg_stmt);
$msg_result = mysqli_stmt_get_result($msg_stmt);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Messages for <?php echo htmlspecialchars($request['title']); ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">

    <style>
        .message-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f9f9f9;
        }
        .message {
            margin-bottom: 15px;
        }
        .message .sender {
            font-weight: bold;
        }
        .message .time {
            font-size: 0.85em;
            color: #666;
        }
        .message.own {
            text-align: right;
        }
        .message.own .content {
            background-color: #d1e7dd;
            display: inline-block;
            padding: 10px;
            border-radius: 10px;
        }
        .message.other .content {
            background-color: #f8d7da;
            display: inline-block;
            padding: 10px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h3>Messages for: <?php echo htmlspecialchars($request['title']); ?></h3>

    <?php if ($msg_sent): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($msg_sent); ?></div>
    <?php endif; ?>

    <div class="message-container mb-3">
        <?php if (mysqli_num_rows($msg_result) > 0): ?>
            <?php while ($msg = mysqli_fetch_assoc($msg_result)): ?>
                <div class="message <?php echo ($msg['sender_id'] == $user_id) ? 'own' : 'other'; ?>">
                    <div class="sender"><?php echo ($msg['sender_id'] == $user_id) ? "You" : htmlspecialchars($msg['email']); ?></div>
                    <div class="content"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></div>
                    <div class="time"><?php echo htmlspecialchars($msg['sent_at']); ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No messages yet.</p>
        <?php endif; ?>
    </div>

    <form method="POST">
        <div class="form-group">
            <label for="message">Send a message</label>
            <textarea id="message" name="message" class="form-control" rows="3" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send</button>
        <a href="<?php echo ($user_type == 'Book Owner') ? 'owner_request.php' : 'borrower_requests.php'; ?>" class="btn btn-secondary ml-2">Back</a>
    </form>
</div>

</body>
</html>
