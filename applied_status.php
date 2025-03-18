<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$application = null;

// Fetch the user's application details
$sql = "SELECT full_name, subjects_applied, status FROM applications WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $application = $result->fetch_assoc();
} else {
    // If no application exists (unlikely due to index.php check), redirect back
    header("Location: index.php");
    exit;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status - Sankar Polytechnic College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 50px;
        }
        .status-container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .btn {
            background-color: #2575fc;
            color: white;
            padding: 7px 15px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease-in-out;
        }
        .btn:hover {
            background-color: #1e60d8;
            box-shadow: 0 4px 10px rgba(37, 117, 252, 0.4);
            color: white;
            text-decoration: none;
        }
        .status-approved { color: #27ae60; }
        .status-rejected { color: #c0392b; }
        .status-pending { color: #f39c12; }
    </style>
</head>
<body>
    <div class="status-container">
        <h2>Application Status</h2>
        <p>You have already applied.</p>
        <?php if ($application): ?>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($application['full_name']); ?></p>
            <p><strong>Course Applied:</strong> <?php echo htmlspecialchars($application['subjects_applied']); ?></p>
            <p><strong>Status:</strong> 
                <span class="status-<?php echo strtolower($application['status']); ?>">
                    <?php echo $application['status']; ?>
                </span>
            </p>
        <?php endif; ?>
        <a href="index.php" class="btn mt-3">Back to Home</a>
    </div>
</body>
</html>
<?php $conn->close(); ?>