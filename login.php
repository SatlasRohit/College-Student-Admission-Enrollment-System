<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "college";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error_message = "Both email and password are required.";
    } else {
        $sql = "SELECT id, full_name, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if (password_verify($password, $row['password'])) {
                // Store user info in session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['full_name'];
                $_SESSION['logged_in'] = true;
                $_SESSION['role'] = $row['role']; // Store role for debugging

                // Debug: Log session data
                error_log("Login successful: user_id=" . $row['id'] . ", role=" . $row['role']);

                // Check if user is an admin
                if (isset($row['role']) && $row['role'] === 'admin') {
                    $_SESSION['is_admin'] = true;
                    header("Location: admin.php");
                } else {
                    $_SESSION['is_admin'] = false;
                    header("Location: index.php");
                }
                exit();
            } else {
                $error_message = "Incorrect password.";
            }
        } else {
            $error_message = "No account found with that email.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | College Admission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7fa; font-family: 'Segoe UI', sans-serif; }
        .login-container { margin-top: 100px; background: white; border-radius: 15px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); padding: 40px; max-width: 500px; }
        h2 { color: #333; font-weight: 600; margin-bottom: 20px; }
        .form-control { border-radius: 8px; border: 1px solid #ced4da; padding: 10px; }
        .btn-primary { background-color: #007bff; border: none; border-radius: 8px; padding: 12px; width: 100%; margin-bottom: 15px; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary, .btn-info { border-radius: 8px; padding: 12px; width: 100%; margin-bottom: 15px; }
        .alert { border-radius: 8px; margin-bottom: 20px; }
        .text-center a { display: block; color: #007bff; text-decoration: none; margin-bottom: 20px; }
        .text-center a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 login-container">
            <h2>Login</h2>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group text-center">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
                <a href="admission_register.php" class="btn btn-secondary">Register</a>
                <a href="index.php" class="btn btn-info">Home Page</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>