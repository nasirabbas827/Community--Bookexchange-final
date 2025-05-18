<?php
session_start();
include('config.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';


if (!isset($_SESSION["id"]) || $_SESSION["user_type"] != "Book Borrower") {
    header("location: login.php");
    exit;
}

$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
$user_id = $_SESSION["id"];
$success = "";
$error = "";

// Fetch book details early to get email for mailing
$sql = "SELECT b.*, u.email FROM books b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $book_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    echo "Book not found.";
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST["type"];
    $borrow_duration = !empty($_POST["borrow_duration"]) ? intval($_POST["borrow_duration"]) : null;
    $requested_at = date("Y-m-d H:i:s");

    // Check for existing pending request
    $check_sql = "SELECT id FROM requests WHERE book_id = ? AND requester_id = ? AND status = 'Pending'";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $book_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $error = "You have already requested this book and it's pending.";
    } else {
        $insert_sql = "INSERT INTO requests (book_id, requester_id, type, status, borrow_duration, requested_at) 
                       VALUES (?, ?, ?, 'Pending', ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "iisis", $book_id, $user_id, $type, $borrow_duration, $requested_at);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Your request has been submitted successfully.";

            // Send email to book owner
            $mail = new PHPMailer(true);
            try {
                // SMTP settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nasiryt.827@gmail.com';  // Your Gmail address
                $mail->Password = 'mtvp ruzp aqfu tfxt';    // Your Gmail app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('nasiryt.827@gmail.com', 'Book Request System');
                $mail->addAddress($book['email']); // Book owner's email

                // Get requester details
                $requester_sql = "SELECT username, email FROM users WHERE id = ?";
                $requester_stmt = mysqli_prepare($conn, $requester_sql);
                mysqli_stmt_bind_param($requester_stmt, "i", $user_id);
                mysqli_stmt_execute($requester_stmt);
                $requester_result = mysqli_stmt_get_result($requester_stmt);
                $requester = mysqli_fetch_assoc($requester_result);

                // Email content
                $mail->isHTML(true);
                $mail->Subject = 'New Book Request for "' . htmlspecialchars($book['title']) . '"';

                $mailBody = "<p>Hello,</p>";
                $mailBody .= "<p>You have received a new request for your book <strong>" . htmlspecialchars($book['title']) . "</strong>.</p>";
                $mailBody .= "<p><strong>Requester Name:</strong> " . htmlspecialchars($requester['username']) . "<br>";
                $mailBody .= "<strong>Requester Email:</strong> " . htmlspecialchars($requester['email']) . "<br>";
                $mailBody .= "<strong>Request Type:</strong> " . htmlspecialchars($type) . "<br>";
                if ($type === 'Borrow') {
                    $mailBody .= "<strong>Borrow Duration:</strong> " . intval($borrow_duration) . " days<br>";
                }
                $mailBody .= "<strong>Requested At:</strong> " . $requested_at . "</p>";
                $mailBody .= "<p>Please log in to your account to review this request.</p>";
                $mailBody .= "<p>Regards,<br>Book Request System</p>";

                $mail->Body = $mailBody;

                $mail->send();
            } catch (Exception $e) {
                error_log('Mail error: ' . $mail->ErrorInfo);
                // Optionally add a user message or ignore silently
            }
        } else {
            $error = "Error submitting request.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Book</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4">Request Book</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="row no-gutters">
            <?php if (!empty($book['picture']) && file_exists("../owner/" . $book['picture'])): ?>
                <div class="col-md-4">
                    <img src="<?php echo "../owner/" . htmlspecialchars($book['picture']); ?>" class="card-img" style="height: 100%; object-fit: cover;">
                </div>
            <?php endif; ?>
            <div class="col-md-8">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                    <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <p><strong>Genre:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                    <p><strong>Condition:</strong> <?php echo htmlspecialchars($book['condition']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($book['status']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($book['location']); ?></p>
                    <p><strong>Owner Email:</strong> <?php echo htmlspecialchars($book['email']); ?></p>
                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <h4>Send a Request</h4>
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" class="mb-3">
                <div class="form-group">
                    <label for="type">Request Type</label>
                    <select name="type" id="type" class="form-control" required onchange="toggleDuration()">
                        <option value="">-- Select --</option>
                        <option value="Borrow">Borrow</option>
                        <option value="Swap">Swap</option>
                    </select>
                </div>

                <div class="form-group" id="durationField" style="display: none;">
                    <label for="borrow_duration">Borrow Duration (in days)</label>
                    <input type="number" name="borrow_duration" id="borrow_duration" class="form-control" min="1">
                </div>

                <button type="submit" class="btn btn-primary">Submit Request</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDuration() {
    const type = document.getElementById("type").value;
    const durationField = document.getElementById("durationField");
    if (type === "Borrow") {
        durationField.style.display = "block";
        document.getElementById("borrow_duration").setAttribute("required", "required");
    } else {
        durationField.style.display = "none";
        document.getElementById("borrow_duration").removeAttribute("required");
    }
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
