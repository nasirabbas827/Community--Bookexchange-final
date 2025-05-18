<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || $_SESSION["user_type"] != "Book Owner") {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$update_msg = "";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';


// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    $request_id = intval($_POST['request_id']);
    $new_status = $_POST['status'];

    // Validate status value
    $allowed_status = ['Pending', 'Accepted', 'Declined', 'Returned'];
    if (in_array($new_status, $allowed_status)) {
        // Make sure this request belongs to one of the user's books
        $check_sql = "SELECT r.id FROM requests r 
                      JOIN books b ON r.book_id = b.id
                      WHERE r.id = ? AND b.user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ii", $request_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            // Update status
            $update_sql = "UPDATE requests SET status = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "si", $new_status, $request_id);
            if (mysqli_stmt_execute($update_stmt)) {
                $update_msg = '<div class="alert alert-success">Request status updated successfully.</div>';

                // Fetch requester email and book title
                $email_sql = "SELECT u.email, u.username, b.title FROM requests r
                              JOIN users u ON r.requester_id = u.id
                              JOIN books b ON r.book_id = b.id
                              WHERE r.id = ?";
                $email_stmt = mysqli_prepare($conn, $email_sql);
                mysqli_stmt_bind_param($email_stmt, "i", $request_id);
                mysqli_stmt_execute($email_stmt);
                $email_result = mysqli_stmt_get_result($email_stmt);
                $email_data = mysqli_fetch_assoc($email_result);
                mysqli_stmt_close($email_stmt);

                if ($email_data && filter_var($email_data['email'], FILTER_VALIDATE_EMAIL)) {
                    // Send email using PHPMailer
                    $mail = new PHPMailer(true);
                    try {
                        // SMTP settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'nasiryt.827@gmail.com'; // Your Gmail
                        $mail->Password = 'mtvp ruzp aqfu tfxt';   // Your Gmail App password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        // Recipients
                        $mail->setFrom('nasiryt.827@gmail.com', 'Book Request System');
                        $mail->addAddress($email_data['email'], $email_data['username']);

                        // Email subject and body
                        $mail->isHTML(true);
                        $mail->Subject = 'Update on your book request for "' . htmlspecialchars($email_data['title']) . '"';

                        $mailBody = "<p>Dear " . htmlspecialchars($email_data['username']) . ",</p>";
                        $mailBody .= "<p>The status of your request for the book <strong>" . htmlspecialchars($email_data['title']) . "</strong> has been updated to <strong>" . htmlspecialchars($new_status) . "</strong>.</p>";
                        $mailBody .= "<p>Please log in to your account to view more details.</p>";
                        $mailBody .= "<p>Thank you,<br>Book Request System</p>";

                        $mail->Body = $mailBody;

                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Mailer Error: " . $mail->ErrorInfo);
                        // You can optionally show a message or silently fail
                    }
                }
            } else {
                $update_msg = '<div class="alert alert-danger">Failed to update request status.</div>';
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $update_msg = '<div class="alert alert-danger">Invalid request ID or you don\'t have permission.</div>';
        }
        mysqli_stmt_close($check_stmt);
    } else {
        $update_msg = '<div class="alert alert-danger">Invalid status selected.</div>';
    }
}

// Fetch all requests for the owner’s books
$sql = "SELECT r.id AS request_id, r.type, r.status, r.borrow_duration, r.requested_at,
               b.title, b.author, b.genre, b.picture, b.location,
               u.email AS requester_email
        FROM requests r
        JOIN books b ON r.book_id = b.id
        JOIN users u ON r.requester_id = u.id
        WHERE b.user_id = ?
        ORDER BY r.requested_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Requests Received</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4">Book Requests Received</h2>

    <?php echo $update_msg; ?>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Book Image</th>
                    <th>Title</th>
                    <th>Requester Email</th>
                    <th>Request Type</th>
                    <th>Status</th>
                    <th>Borrow Duration (days)</th>
                    <th>Update Status</th>
                    <th>View Messages</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td style="width: 120px;">
                            <?php if (!empty($row['picture']) && file_exists($row['picture'])): ?>
                                <img src="<?php echo $row['picture']; ?>" alt="Book Image" style="width: 100px; height: auto; object-fit: cover;">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['requester_email']); ?></td>
                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo !empty($row['borrow_duration']) ? $row['borrow_duration'] : '-'; ?></td>
                        <td>
                            <form method="POST" class="form-inline">
                                <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                <select name="status" class="form-control form-control-sm mr-2" required>
                                    <option value="Pending" <?php if($row['status']=='Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="Accepted" <?php if($row['status']=='Accepted') echo 'selected'; ?>>Accepted</option>
                                    <option value="Declined" <?php if($row['status']=='Declined') echo 'selected'; ?>>Declined</option>
                                    <option value="Returned" <?php if($row['status']=='Returned') echo 'selected'; ?>>Returned</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm mt-2">Update</button>
                            </form>
                        </td>
                        <td>
                            <a href="messages.php?request_id=<?php echo $row['request_id']; ?>" class="btn btn-sm btn-info">Messages</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No requests found for your books.</div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
