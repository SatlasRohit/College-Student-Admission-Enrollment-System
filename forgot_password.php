<?php
session_start();
include 'db_connection.php';
require 'vendor/autoload.php';

$errorMessage = '';
$successMessage = '';

$client = new Google_Client();
$client->setAuthConfig('client_secret.json');
$client->addScope(Google_Service_Gmail::GMAIL_SEND);
$client->setRedirectUri('http://localhost/stuadm/google-auth-callback.php');
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

if (isset($_GET['code']) && !isset($_SESSION['access_token'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            $errorMessage = 'Google authentication failed: ' . $token['error'];
        } else {
            $_SESSION['access_token'] = $token;
            header('Location: forgot_password.php');
            exit;
        }
    } catch (Exception $e) {
        $errorMessage = 'Google authentication failed: ' . $e->getMessage();
    }
}

if (!isset($_SESSION['access_token'])) {
    $authUrl = $client->createAuthUrl();
    echo "<div class='text-center mt-5'><p class='lead text-white'>Please authenticate with Google to proceed.</p>";
    echo "<a href='$authUrl' class='btn btn-light btn-lg animate__animated animate__pulse'>Authenticate with Google</a></div>";
    exit;
} else {
    $client->setAccessToken($_SESSION['access_token']);
    if ($client->isAccessTokenExpired()) {
        unset($_SESSION['access_token']);
        header('Location: forgot_password.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $errorMessage = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $token = bin2hex(random_bytes(32));
            $updateSql = "UPDATE users SET reset_token = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 2 HOUR) WHERE email = ?";
            $updateStmt = $conn->prepare($updateSql);
            if (!$updateStmt) {
                die("SQL error: " . $conn->error);
            }
            $updateStmt->bind_param('ss', $token, $email);

            if ($updateStmt->execute()) {
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/stuadm/reset_password.php?token=" . urlencode($token);                $service = new Google_Service_Gmail($client);
                $senderEmail = 'satlasrohit7@gmail.com';

                $rawMessage = "From: $senderEmail\r\nTo: $email\r\nSubject: Password Reset Request\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\nHello,\n\nClick the link below to reset your password:\n$resetLink\n\nThis link expires in 2 hours. Ignore if you didn’t request this.\n\nRegards,\nCollege Admission Team";
                $encodedMessage = strtr(base64_encode($rawMessage), '+/', '-_');
                $message = new Google_Service_Gmail_Message();
                $message->setRaw($encodedMessage);

                try {
                    $service->users_messages->send('me', $message);
                    $successMessage = 'A password reset link has been sent to your email.';
                } catch (Exception $e) {
                    $errorMessage = 'Failed to send email: ' . $e->getMessage() . " Use this link instead: <a href='$resetLink' class='alert-link'>$resetLink</a>";
                }
            } else {
                $errorMessage = 'Failed to generate reset token. Please try again.';
            }
        } else {
            $errorMessage = 'No account found with that email.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - College Admission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            animation: fadeInUp 0.8s ease-out;
        }
        h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            border-color: #6e8efb;
            box-shadow: 0 0 5px rgba(110, 142, 251, 0.5);
        }
        .btn-primary {
            background: linear-gradient(90deg, #6e8efb, #a777e3);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(110, 142, 251, 0.4);
        }
        .alert {
            border-radius: 10px;
            animation: fadeIn 0.5s ease;
        }
        .back-link {
            color: #6e8efb;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #a777e3;
            text-decoration: underline;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Forgot Your Password?</h2>
    <p class="text-center text-muted mb-4">Enter your email to receive a reset link.</p>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger animate__animated animate__shakeX"><?php echo $errorMessage; ?></div>
    <?php endif; ?>
    <?php if ($successMessage): ?>
        <div class="alert alert-success animate__animated animate__fadeIn"><?php echo $successMessage; ?></div>
    <?php else: ?>
        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="your@email.com">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
        </form>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="login.php" class="back-link">Back to Login</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>