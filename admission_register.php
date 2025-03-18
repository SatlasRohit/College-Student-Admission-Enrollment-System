<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "college";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $error_message = "All fields are required.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $check_email_query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_email_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "Email is already registered.";
        } else {
            $insert_query = "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                $success_message = "Registration successful! Redirecting to login page...";
                echo "<script>setTimeout(() => window.location.href='login.php', 3000);</script>";
            } else {
                $error_message = "Error registering user. Please try again.";
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | College Admission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f7fc;
            font-family: 'Arial', sans-serif;
        }
        .register-container {
            margin: 50px auto;
            max-width: 500px;
            padding: 30px;
            background-color: #007bff;
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .register-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group label {
            color: white;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px;
        }
        .btn-primary {
            background-color: #0056b3;
            border: none;
            border-radius: 5px;
            padding: 10px;
            font-size: 16px;
            width: 100%;
            transition: 0.3s;
        }
        .btn-primary:hover {
            background-color: #003f7f;
            transform: translateY(-3px);
        }
        .btn-secondary {
            background-color: #6c757d;
            border: none;
            border-radius: 5px;
            padding: 10px;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
            transition: 0.3s;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-3px);
        }
        .form-icon {
            position: absolute;
            left: 10px;
            top: 60%;
            color: #0056b3;
        }
        .form-control-icon {
            padding-left: 40px;
            border: 1px solid #ced4da;
        }
    </style>
    <script>
        function validateForm() {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!name || !email || !password) {
                alert("All fields are required!");
                return false;
            }

            const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
            if (!email.match(emailPattern)) {
                alert("Please enter a valid email address.");
                return false;
            }

            if (password.length < 6) {
                alert("Password must be at least 6 characters long.");
                return false;
            }

            return true;
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h2>Register</h2>
            <?php if (isset($success_message)) { ?>
                <div class="alert alert-success text-center"><?php echo $success_message; ?></div>
            <?php } ?>
            <?php if (isset($error_message)) { ?>
                <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
            <?php } ?>
            <form method="POST" action="admission_register.php" onsubmit="return validateForm()">
                <div class="form-group position-relative">
                    <label for="name">Full Name</label>
                    <i class="fas fa-user form-icon"></i>
                    <input type="text" class="form-control form-control-icon" id="name" name="name" required>
                </div>
                <div class="form-group position-relative">
                    <label for="email">Email Address</label>
                    <i class="fas fa-envelope form-icon"></i>
                    <input type="email" class="form-control form-control-icon" id="email" name="email" required>
                </div>
                <div class="form-group position-relative">
                    <label for="password">Password</label>
                    <i class="fas fa-lock form-icon"></i>
                    <input type="password" class="form-control form-control-icon" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary mt-4">Register</button>
            </form>
            <a href="login.php" class="btn btn-secondary">Login</a>
        </div>
    </div>
</body>
</html>
