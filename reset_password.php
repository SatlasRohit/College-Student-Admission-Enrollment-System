<?php
session_start();
include 'db_connection.php';

// Initialize messages
$error_message = '';
$success_message = '';

// Debug: Display and log the query string and token
echo "Query string: " . $_SERVER['QUERY_STRING'] . "<br>";
echo "Token: " . (isset($_GET['token']) ? $_GET['token'] : "Not set") . "<br>";
error_log("DEBUG: Raw query string = " . $_SERVER['QUERY_STRING']);
error_log("DEBUG: \$_GET = " . print_r($_GET, true));

// Check if token is provided
if (!isset($_GET['token']) || trim($_GET['token']) === '') {
    $error_message = "No reset token provided.";
} else {
    $token = trim($_GET['token']);
    error_log("DEBUG: Token received = " . $token);

    // Verify token
    $sql = "SELECT id, email FROM users WHERE reset_token = ? AND reset_expiry > NOW()";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Database error: Prepare failed - " . $conn->error);
        $error_message = "An error occurred while preparing the database query.";
    } else {
        $stmt->bind_param("s", $token);
        if (!$stmt->execute()) {
            error_log("Database error: Execute failed - " . $stmt->error);
            $error_message = "An error occurred while executing the database query.";
        } else {
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $error_message = "Invalid or expired reset token.";
            } else {
                $user = $result->fetch_assoc();
                error_log("DEBUG: User found - ID: " . $user['id'] . ", Email: " . $user['email']);

                // Handle password reset form submission
                if ($_SERVER["REQUEST_METHOD"] === "POST") {
                    $new_password = $_POST['new_password'];
                    $confirm_password = $_POST['confirm_password'];

                    if (empty($new_password) || empty($confirm_password)) {
                        $error_message = "Both password fields are required.";
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = "Passwords do not match.";
                    } else {
                        // Hash the new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                        // Update password and clear token
                        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        if (!$stmt) {
                            error_log("Database error: Prepare failed - " . $conn->error);
                            $error_message = "An error occurred while preparing the database update.";
                        } else {
                            $stmt->bind_param("si", $hashed_password, $user['id']);
                            if ($stmt->execute()) {
                                header("Location: login.php?success=1");
                                exit;
                            } else {
                                error_log("Database error: Execute failed - " . $stmt->error);
                                $error_message = "An error occurred while resetting your password. Please try again later.";
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container" style="margin-top: 50px; max-width: 400px;">
    <h2 class="text-center">Reset Password</h2>
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php elseif (isset($user)): ?>
        <form method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
        </form>
        <script>
            function validateForm() {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                if (newPassword !== confirmPassword) {
                    alert('Passwords do not match.');
                    return false;
                }
                return true;
            }
        </script>
    <?php endif; ?>
</div>
</body>
</html>
<?php $conn->close(); ?>