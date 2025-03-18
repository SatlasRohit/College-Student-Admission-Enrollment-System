<?php
session_start();
include 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}


// Initialize variables
$student = null;
$error_message = null;

// Handle form submission (POST request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $blood_group = $conn->real_escape_string($_POST['blood_group'] ?? '');
    $religion = $conn->real_escape_string($_POST['religion'] ?? '');
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $permanent_address = $conn->real_escape_string($_POST['permanent_address']);
    $correspondence_address = $conn->real_escape_string($_POST['correspondence_address'] ?? '');
    $school = $conn->real_escape_string($_POST['school']);
    $year_passing = $conn->real_escape_string($_POST['year_passing']);
    $percentage = $conn->real_escape_string($_POST['percentage']);
    $subjects_taken = $conn->real_escape_string($_POST['subjects_applied']);
    $previous_qualification = $conn->real_escape_string($_POST['previous_qualification_radio']);
    $group_12th = $conn->real_escape_string($_POST['group'] ?? '');
    $marks = $conn->real_escape_string($_POST['marks']);
    $subjects_applied = $conn->real_escape_string($_POST['subjects_applied']);
    $fathers_name = $conn->real_escape_string($_POST['fathers_name'] ?? '');
    $fathers_occupation = $conn->real_escape_string($_POST['fathers_occupation'] ?? '');
    $fathers_contact = $conn->real_escape_string($_POST['fathers_contact'] ?? '');
    $mothers_name = $conn->real_escape_string($_POST['mothers_name'] ?? '');
    $mothers_occupation = $conn->real_escape_string($_POST['mothers_occupation'] ?? '');
    $mothers_contact = $conn->real_escape_string($_POST['mothers_contact'] ?? '');
    $guardian_name = $conn->real_escape_string($_POST['guardian_name'] ?? '');
    $guardian_contact = $conn->real_escape_string($_POST['guardian_contact'] ?? '');
    $admission_year = $conn->real_escape_string($_POST['admission_year']);

    $sql = "UPDATE applications SET 
        full_name = ?, dob = ?, gender = ?, blood_group = ?, religion = ?, email = ?, phone = ?, 
        permanent_address = ?, correspondence_address = ?, school = ?, year_passing = ?, percentage = ?, 
        subjects_taken = ?, previous_qualification = ?, group_12th = ?, marks = ?, subjects_applied = ?, 
        fathers_name = ?, fathers_occupation = ?, fathers_contact = ?, 
        mothers_name = ?, mothers_occupation = ?, mothers_contact = ?, guardian_name = ?, 
        guardian_contact = ?, admission_year = ?
        WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssssssssssssssi",
        $full_name, $dob, $gender, $blood_group, $religion, $email, $phone, 
        $permanent_address, $correspondence_address, $school, $year_passing, $percentage, 
        $subjects_taken, $previous_qualification, $group_12th, $marks, $subjects_applied, 
        $fathers_name, $fathers_occupation, $fathers_contact, 
        $mothers_name, $mothers_occupation, $mothers_contact, $guardian_name, 
        $guardian_contact, $admission_year, $id);
    
    if ($stmt->execute()) {
        echo "<script>window.location.href='admin.php';</script>";
        exit;
    } else {
        $error_message = "Error updating record: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch student data (GET request)
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM applications WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    if (!$student) {
        $error_message = "No student found with ID $id.";
    }
} else {
    $error_message = "No student ID provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to bottom right, #6a11cb, #2575fc);
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }
        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            max-width: 800px;
            margin: 0 auto;
        }
        h2 {
            text-align: center;
            color: #2575fc;
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: bold;
        }
        .btn-primary {
            background: #2575fc;
            border: none;
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="form-container">
            <h2>Edit Student Details</h2>
            <?php if (isset($error_message)) { echo "<div class='alert alert-danger'>$error_message</div>"; } ?>
            
            <?php if ($student): ?>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                    
                    <!-- Personal Information -->
                    <h4>Personal Information</h4>
                    <div class="form-group">
                        <label>Full Name:</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth:</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($student['dob']); ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Gender:</label>
                        <select name="gender" class="form-control" required>
                            <option value="Male" <?php if ($student['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if ($student['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                            <option value="Other" <?php if ($student['gender'] == 'Other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Blood Group (Optional):</label>
                        <input type="text" name="blood_group" value="<?php echo htmlspecialchars($student['blood_group'] ?? ''); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Religion (Optional):</label>
                        <input type="text" name="religion" value="<?php echo htmlspecialchars($student['religion'] ?? ''); ?>" class="form-control">
                    </div>

                    <!-- Contact Information -->
                    <h4>Contact Information</h4>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone:</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Permanent Address:</label>
                        <textarea name="permanent_address" class="form-control" required><?php echo htmlspecialchars($student['permanent_address']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Correspondence Address (Optional):</label>
                        <textarea name="correspondence_address" class="form-control"><?php echo htmlspecialchars($student['correspondence_address'] ?? ''); ?></textarea>
                    </div>

                    <!-- Academic Information -->
                    <h4>Academic Information</h4>
                    <div class="form-group">
                        <label>School:</label>
                        <input type="text" name="school" value="<?php echo htmlspecialchars($student['school']); ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Year of Passing:</label>
                        <input type="text" name="year_passing" value="<?php echo htmlspecialchars($student['year_passing']); ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Percentage:</label>
                        <input type="text" name="percentage" value="<?php echo htmlspecialchars($student['percentage']); ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Previous Qualification:</label>
                        <select name="previous_qualification_radio" class="form-control" required>
                            <option value="10th" <?php if ($student['previous_qualification'] == '10th') echo 'selected'; ?>>10th</option>
                            <option value="12th" <?php if ($student['previous_qualification'] == '12th') echo 'selected'; ?>>12th</option>
                            <option value="Other" <?php if ($student['previous_qualification'] == 'Other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Group (12th, Optional):</label>
                        <select name="group" class="form-control">
                            <option value="" <?php if (empty($student['group_12th'])) echo 'selected'; ?>>None</option>
                            <option value="Maths-Biology" <?php if ($student['group_12th'] == 'Maths-Biology') echo 'selected'; ?>>Maths-Biology</option>
                            <option value="Maths-Computer" <?php if ($student['group_12th'] == 'Maths-Computer') echo 'selected'; ?>>Maths-Computer</option>
                            <option value="Bio-computer" <?php if ($student['group_12th'] == 'Bio-computer') echo 'selected'; ?>>Bio-computer</option>
                            <option value="Pure-Science" <?php if ($student['group_12th'] == 'Pure-Science') echo 'selected'; ?>>Pure-Science</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Marks:</label>
                        <input type="text" name="marks" value="<?php echo htmlspecialchars($student['marks']); ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Course Applied For:</label>
                        <select name="subjects_applied" class="form-control" required>
                            <option value="Computer Engineering" <?php if ($student['subjects_applied'] == 'Computer Engineering') echo 'selected'; ?>>Computer Engineering</option>
                            <option value="ECE" <?php if ($student['subjects_applied'] == 'ECE') echo 'selected'; ?>>ECE</option>
                            <option value="EEE" <?php if ($student['subjects_applied'] == 'EEE') echo 'selected'; ?>>EEE</option>
                            <option value="Civil Engineering" <?php if ($student['subjects_applied'] == 'Civil Engineering') echo 'selected'; ?>>Civil Engineering</option>
                            <option value="Mechanical Engineering" <?php if ($student['subjects_applied'] == 'Mechanical Engineering') echo 'selected'; ?>>Mechanical Engineering</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Admission Year:</label>
                        <input type="text" name="admission_year" value="<?php echo htmlspecialchars($student['admission_year'] ?? ''); ?>" class="form-control" required>
                    </div>

                    <!-- Guardian Information -->
                    <h4>Guardian Information</h4>
                    <div class="form-group">
                        <label>Father's Name (Optional):</label>
                        <input type="text" name="fathers_name" value="<?php echo htmlspecialchars($student['fathers_name'] ?? ''); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Father's Occupation (Optional):</label>
                        <input type="text" name="fathers_occupation" value="<?php echo htmlspecialchars($student['fathers_occupation'] ?? ''); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Father's Contact (Optional):</label>
                        <input type="text" name="fathers_contact" value="<?php echo htmlspecialchars($student['fathers_contact'] ?? ''); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Mother's Name (Optional):</label>
                        <input type="text" name="mothers_name" value="<?php echo htmlspecialchars($student['mothers_name'] ?? ''); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Mother's Occupation (Optional):</label>
                        <input type="text" name="mothers_occupation" value="<?php echo htmlspecialchars($student['mothers_occupation'] ?? ''); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Mother's Contact (Optional):</label>
                        <input type="text" name="mothers_contact" value="<?php echo htmlspecialchars($student['mothers_contact'] ?? ''); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Guardian's Name (Optional):</label>
                        <input type="text" name="guardian_name" value="<?php echo htmlspecialchars($student['guardian_name'] ?? ''); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Guardian's Contact (Optional):</label>
                        <input type="text" name="guardian_contact" value="<?php echo htmlspecialchars($student['guardian_contact'] ?? ''); ?>" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="admin.php" class="btn btn-secondary">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>