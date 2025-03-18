<?php
session_start(); // Add session_start() if admin access is restricted
include 'db_connection.php';

// Check if admin is logged in (optional, add if needed)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}


// Get the student ID from the URL
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch the student data from the database
$sql = "SELECT * FROM applications WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Define the uploads directory
$uploads_dir = "uploads/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        .details-container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            backdrop-filter: blur(10px);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h2 {
            color: #2c3e50;
            font-weight: 700;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .section-title {
            color: #3498db;
            font-weight: 600;
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        .detail-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .detail-item {
            flex: 1;
            min-width: 300px;
            padding: 10px;
        }
        .detail-label {
            font-weight: 600;
            color: #2c3e50;
            margin-right: 10px;
        }
        .detail-value {
            color: #7f8c8d;
        }
        .btn-back, .btn-download {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            font-weight: 600;
            margin: 5px;
        }
        .btn-download {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
        }
        .btn-back:hover, .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
            text-decoration: none;
        }
        .no-data {
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .details-container {
                padding: 20px;
            }
            .header h2 {
                font-size: 24px;
            }
            .detail-item {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="details-container">
        <div class="header">
            <h2>Student Profile</h2>
        </div>
        
        <?php if ($student): ?>
            <!-- Personal Information -->
            <div class="section">
                <div class="section-title">Personal Information</div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">ID:</span> <span class="detail-value"><?php echo $student['id']; ?></span></div>
                    <div class="detail-item"><span class="detail-label">Full Name:</span> <span class="detail-value"><?php echo htmlspecialchars($student['full_name']); ?></span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">Date of Birth:</span> <span class="detail-value"><?php echo $student['dob']; ?></span></div>
                    <div class="detail-item"><span class="detail-label">Gender:</span> <span class="detail-value"><?php echo $student['gender']; ?></span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">Blood Group:</span> <span class="detail-value"><?php echo htmlspecialchars($student['blood_group'] ?? 'N/A'); ?></span></div>
                    <div class="detail-item"><span class="detail-label">Religion:</span> <span class="detail-value"><?php echo htmlspecialchars($student['religion'] ?? 'N/A'); ?></span></div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="section">
                <div class="section-title">Contact Information</div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">Email:</span> <span class="detail-value"><?php echo htmlspecialchars($student['email']); ?></span></div>
                    <div class="detail-item"><span class="detail-label">Phone:</span> <span class="detail-value"><?php echo htmlspecialchars($student['phone']); ?></span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">Permanent Address:</span> <span class="detail-value"><?php echo htmlspecialchars($student['permanent_address']); ?></span></div>
                    <div class="detail-item"><span class="detail-label">Correspondence Address:</span> <span class="detail-value"><?php echo htmlspecialchars($student['correspondence_address'] ?? 'N/A'); ?></span></div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="section">
                <div class="section-title">Academic Information</div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">School:</span> <span class="detail-value"><?php echo htmlspecialchars($student['school']); ?></span></div>
                    <div class="detail-item"><span class="detail-label">Year of Passing:</span> <span class="detail-value"><?php echo htmlspecialchars($student['year_passing']); ?></span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">Percentage:</span> <span class="detail-value"><?php echo htmlspecialchars($student['percentage']); ?></span></div>
                    <div class="detail-item"><span class="detail-label">Previous Qualification:</span> <span class="detail-value"><?php echo htmlspecialchars($student['previous_qualification']); ?></span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">Group (12th):</span> <span class="detail-value"><?php echo htmlspecialchars($student['group_12th'] ?? 'N/A'); ?></span></div>
                    <div class="detail-item"><span class="detail-label">Marks:</span> <span class="detail-value"><?php echo htmlspecialchars($student['marks']); ?></span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">Subjects Applied:</span> <span class="detail-value"><?php echo htmlspecialchars($student['subjects_applied']); ?></span></div>
                    <div class="detail-item"><span class="detail-label">Admission Year:</span> <span class="detail-value"><?php echo htmlspecialchars($student['admission_year'] ?? 'N/A'); ?></span></div>
                </div>
            </div>

            <!-- Guardian Information -->
            <div class="section">
                <div class="section-title">Guardian Information</div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">Father's Name:</span> <span class="detail-value"><?php echo htmlspecialchars($student['fathers_name'] ?? 'N/A'); ?></span></div>
                    <div class="detail-item"><span class="detail-label">Father's Occupation:</span> <span class="detail-value"><?php echo htmlspecialchars($student['fathers_occupation'] ?? 'N/A'); ?></span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">Father's Contact:</span> <span class="detail-value"><?php echo htmlspecialchars($student['fathers_contact'] ?? 'N/A'); ?></span></div>
                    <div class="detail-item"><span class="detail-label">Mother's Name:</span> <span class="detail-value"><?php echo htmlspecialchars($student['mothers_name'] ?? 'N/A'); ?></span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">Mother's Occupation:</span> <span class="detail-value"><?php echo htmlspecialchars($student['mothers_occupation'] ?? 'N/A'); ?></span></div>
                    <div class="detail-item"><span class="detail-label">Mother's Contact:</span> <span class="detail-value"><?php echo htmlspecialchars($student['mothers_contact'] ?? 'N/A'); ?></span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-item"><span class="detail-label">Guardian's Name:</span> <span class="detail-value"><?php echo htmlspecialchars($student['guardian_name'] ?? 'N/A'); ?></span></div>
                    <div class="detail-item"><span class="detail-label">Guardian's Contact:</span> <span class="detail-value"><?php echo htmlspecialchars($student['guardian_contact'] ?? 'N/A'); ?></span></div>
                </div>
            </div>

            <!-- Documents Section -->
            <div class="section">
                <div class="section-title">Uploaded Documents</div>
                <div class="detail-row">
                    <?php
                    $documentColumns = [
                        'photo' => 'Photo',
                        'signature' => 'Signature',
                        'marksheet_10' => '10th Marksheet',
                        'marksheet_12' => '12th Marksheet',
                        'aadhaar' => 'Aadhaar Card',
                        'birth_certificate' => 'Birth Certificate',
                        'caste_certificate' => 'Caste Certificate',
                        'income_certificate' => 'Income Certificate'
                    ];
                    $hasDocuments = false;
                    foreach ($documentColumns as $column => $label) {
                        if (!empty($student[$column])) {
                            $docPath = $uploads_dir . $student[$column];
                            if (file_exists($docPath)) {
                                $hasDocuments = true;
                                echo "<div class='detail-item'>";
                                echo "<span class='detail-label'>{$label}:</span><br>";
                                echo "<a href='download.php?file=" . urlencode($student[$column]) . "' class='btn-download'>Download {$label}</a>";
                                echo "</div>";
                            } else {
                                echo "<div class='detail-item'>";
                                echo "<span class='detail-label'>{$label}:</span> <span class='detail-value'>File not found</span>";
                                echo "</div>";
                            }
                        }
                    }
                    if (!$hasDocuments) {
                        echo "<div class='detail-item'><span class='detail-value'>No documents uploaded</span></div>";
                    }
                    ?>
                </div>
            </div>

        <?php else: ?>
            <p class="no-data">No student found with the given ID.</p>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="admin.php" class="btn-back">Back to Admissions</a>
            <?php if ($student): ?>
                <a href="download_all.php?id=<?php echo $student_id; ?>" class="btn-download">Download All</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>