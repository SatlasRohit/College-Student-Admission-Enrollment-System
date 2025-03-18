<?php
session_start();
include 'db_connection.php';

// Check if the user wants to log out
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Check if the user is logged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$has_applied = false;
$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';

// Redirect admins to admin.php (safety check)
if ($logged_in && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header("Location: admin.php");
    exit;
}

if ($logged_in) {
    $user_id = $_SESSION['user_id'];

    // Check if the user has already applied
    $sql = "SELECT COUNT(*) FROM applications WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $has_applied = $count > 0;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sankar Polytechnic College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; color: #333; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 20px; background-color: white; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        .header h1 { font-size: 24px; font-weight: bold; color: #4169E1; margin: 0; }
        .header-buttons { display: flex; align-items: center; gap: 10px; }
        .btn { background-color: #2575fc; color: white; padding: 7px 15px; font-size: 16px; font-weight: bold; border: none; border-radius: 8px; text-decoration: none; transition: all 0.3s ease-in-out; }
        .btn:hover { background-color: #1e60d8; box-shadow: 0 4px 10px rgba(37, 117, 252, 0.4); color: white; text-decoration: none; }
        .greeting { font-size: 16px; color: #333; margin-right: 10px; }
        .big-image { width: 100%; max-height: 500px; object-fit: cover; }
        .details { background-color: white; padding: 20px; text-align: center; }
        .courses { background-color: white; padding: 20px; }
        .courses h3 { color: #2575fc; }
        .course-item { cursor: pointer; }
        .contact-info { background-color: white; padding: 20px; }
        .apply-btn { margin-top: 15px; }
        .footer { background-color: #f8f9fa; text-align: center; padding: 10px; position: relative; bottom: 0; width: 100%; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sankar Polytechnic College (Autonomous)</h1>
        <div class="header-buttons">
            <?php if ($logged_in): ?>
                <span class="greeting">Hi, <?php echo htmlspecialchars($username); ?></span>
                <a href="profile.php" class="btn">View Profile</a>
                <a href="?action=logout" class="btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn">Login</a>
                <a href="admission_register.php" class="btn">Register Now</a>
            <?php endif; ?>
        </div>
    </div>

    <img src="background.jpeg" alt="Sankar Polytechnic College Campus" class="big-image">

    <div>
        <div class="details">
            <h2>Welcome to Sankar Polytechnic College Admission</h2>
            <?php if ($logged_in): ?>
                <p>Welcome back, <strong><?php echo htmlspecialchars($username); ?></strong>! Explore your opportunities at Sankar Polytechnic.</p>
            <?php else: ?>
                <p>Sankar Polytechnic College is a renowned institution dedicated to providing quality education 
                in the field of engineering and technology. With state-of-the-art facilities, experienced faculty, 
                and a strong commitment to academic excellence, we empower students to achieve their career goals 
                and contribute to society.</p>
            <?php endif; ?>
            <?php if ($logged_in && $has_applied): ?>
                <div class="alert alert-info mt-3">You’ve already applied! Check your application status <a href="applied_status.php">here</a>.</div>
            <?php endif; ?>
        </div>
        <div class="courses">
            <h3>Courses Offered</h3>
            <ul>
                <li class="course-item" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Learn cutting-edge programming and system design;">Computer Engineering</li>
                <li class="course-item" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Master electrical systems and electronics;">Electrical and Electronics Engineering (EEE)</li>
                <li class="course-item" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Explore communication technologies and circuits;">Electronics and Communication Engineering (ECE)</li>
                <li class="course-item" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Design and build infrastructure projects;">Civil Engineering</li>
                <li class="course-item" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Develop skills in machinery and manufacturing;">Mechanical Engineering</li>
            </ul>
            <center>
            <?php if ($logged_in): ?>
                <a href="<?php echo $has_applied ? 'applied_status.php' : 'apply.php'; ?>" class="btn apply-btn">Apply Now</a>
            <?php endif; ?>
            </center>
        </div>
    </div>

    <div class="contact-info">
        <h3>Contact Us</h3>
        <p>📍 Address: Sankar Polytechnic College, Tirunelveli, Tamil Nadu</p>
        <p>📞 Phone: +91 98765 43210</p>
        <p>📧 Email: info@sankarpolytechnic.edu.in</p>
    </div>

    <div class="footer">
        <p>© <?php echo date('Y'); ?> Sankar Polytechnic College | Current Date: <?php echo date('F j, Y'); ?></p>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function(){
            $('[data-toggle="popover"]').popover();
        });
    </script>
</body>
</html>
<?php if (isset($conn)) $conn->close(); ?>