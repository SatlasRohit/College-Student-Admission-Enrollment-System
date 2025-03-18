<?php
session_start();
require 'db_connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit;
}


// Ensure uploads directory exists
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

// Function to handle file uploads (unchanged)
function uploadFile($fileInputName, $studentId) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileType = strtolower(pathinfo($_FILES[$fileInputName]["name"], PATHINFO_EXTENSION));
    $allowedTypes = array("jpg", "jpeg", "png", "pdf");
    $filename = "student_{$studentId}_{$fileInputName}.{$fileType}";
    $targetFile = $targetDir . $filename;

    if ($_FILES[$fileInputName]["error"] !== UPLOAD_ERR_OK) {
        return "Error: Failed to upload " . $_FILES[$fileInputName]["name"];
    }

    if (!empty($_FILES[$fileInputName]["name"])) {
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $targetFile)) {
                return $filename;
            } else {
                return "Error: Could not move file " . $_FILES[$fileInputName]["name"];
            }
        } else {
            return "Error: Invalid file type " . $_FILES[$fileInputName]["name"];
        }
    }
    return null;
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $full_name = $_POST['full_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'] ?? NULL;
    $religion = $_POST['religion'] ?? NULL;
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $permanent_address = $_POST['permanent_address'];
    $correspondence_address = $_POST['correspondence_address'] ?? NULL;
    $school = $_POST['school'];
    $year_passing = $_POST['year_passing'];
    $percentage = $_POST['percentage'];
    $subjects_taken = $conn->real_escape_string($_POST['subjects_applied']);
    $previous_qualification = $_POST['previous_qualification_radio'];
    $group_12th = $_POST['group'] ?? NULL;
    $marks = $_POST['marks'];
    $subjects_applied = $_POST['subjects_applied'];
    $course = $_POST['course'] ?? NULL;
    $branch = $_POST['branch'] ?? NULL;
    $fathers_name = $_POST['fathers_name'] ?? NULL;
    $fathers_occupation = $_POST['fathers_occupation'] ?? NULL;
    $fathers_contact = $_POST['fathers_contact'] ?? NULL;
    $mothers_name = $_POST['mothers_name'] ?? NULL;
    $mothers_occupation = $_POST['mothers_occupation'] ?? NULL;
    $mothers_contact = $_POST['mothers_contact'] ?? NULL;
    $guardian_name = $_POST['guardian_name'] ?? NULL;
    $guardian_contact = $_POST['guardian_contact'] ?? NULL;

    // Determine admission year
    $admission_year = '1st Year'; // Default
    if ($previous_qualification === '12th') {
        if (in_array($group_12th, ['Maths-Computer', 'Maths-Biology'])) {
            $admission_year = '2nd Year (Lateral)';
        }
    }

    // Insert basic data including user_id and admission_year
    $sql = "INSERT INTO applications (
        user_id, full_name, dob, gender, blood_group, religion, email, phone, permanent_address, 
        correspondence_address, school, year_passing, percentage, subjects_taken, 
        previous_qualification, group_12th, marks, subjects_applied,
        fathers_name, fathers_occupation, fathers_contact, mothers_name, mothers_occupation, 
        mothers_contact, guardian_name, guardian_contact, admission_year
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL prepare failed: " . $conn->error);
        }

        $stmt->bind_param("issssssssssssssssssssssssss",
            $user_id, $full_name, $dob, $gender, $blood_group, $religion, $email, $phone, 
            $permanent_address, $correspondence_address, $school, $year_passing, $percentage, 
            $subjects_taken, $previous_qualification, $group_12th, $marks, $subjects_applied, 
            $fathers_name, $fathers_occupation, $fathers_contact, 
            $mothers_name, $mothers_occupation, $mothers_contact, $guardian_name, 
            $guardian_contact, $admission_year);

        if (!$stmt->execute()) {
            throw new Exception("SQL execution failed: " . $stmt->error);
        }

        // Get the last inserted ID
        $student_id = $conn->insert_id;

        // Upload files with student ID in filename
        $photo = uploadFile("photo", $student_id);
        $signature = uploadFile("signature", $student_id);
        $marksheet_10 = uploadFile("marksheet10", $student_id);
        $marksheet_12 = uploadFile("marksheet12", $student_id);
        $aadhaar = uploadFile("aadhaar", $student_id);
        $birth_certificate = uploadFile("birthCertificate", $student_id);
        $caste_certificate = uploadFile("casteCertificate", $student_id);
        $income_certificate = uploadFile("incomeCertificate", $student_id);

        // Update the record with file paths
        $update_sql = "UPDATE applications SET 
            photo = ?, signature = ?, marksheet_10 = ?, marksheet_12 = ?, 
            aadhaar = ?, birth_certificate = ?, caste_certificate = ?, income_certificate = ?
            WHERE id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception("Update SQL prepare failed: " . $conn->error);
        }
        
        $update_stmt->bind_param("ssssssssi",
            $photo, $signature, $marksheet_10, $marksheet_12,
            $aadhaar, $birth_certificate, $caste_certificate, $income_certificate, 
            $student_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Update SQL execution failed: " . $update_stmt->error);
        }

        echo "<script>window.location.href='applied_status.php';</script>";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
<!-- HTML remains unchanged, as provided -->

<!-- Your HTML code remains unchanged -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Your existing styles remain unchanged */
        body {
            background: linear-gradient(to bottom right, #6a11cb, #2575fc);
            font-family: 'Poppins', sans-serif;
            color: #333;
        }
        .form-container {
            margin-top: 50px;
            margin-bottom: 50px;
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1s ease-in-out;
        }
        .college-name {
            text-align: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
            margin-top: 20px;
            text-shadow: 1px 1px 6px rgba(0, 0, 0, 0.4);
        }
        .form-header {
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            color: #2575fc;
            margin-bottom: 30px;
        }
        .progress-bar-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .progress-bar {
            width: 33%;
            height: 8px;
            background: #2575fc;
            transition: width 0.4s ease-in-out;
        }
        .btn-primary, .next-btn, .back-btn {
            background: #2575fc;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            border-radius: 8px;
            transition: all 0.3s ease-in-out;
            position: relative;
            top: 10px;
        }
        .next-btn {
            float: right;
        }
        .btn-primary:hover, .next-btn:hover, .back-btn:hover {
            background: #1e60d8;
            box-shadow: 0 4px 10px rgba(37, 117, 252, 0.4);
        }
        .form-group label {
            font-weight: bold;
            font-size: 14px;
        }
        .custom-file-label::after {
            content: "Browse"; 
        }
        .custom-file-input {
            cursor: pointer;
        }
        .section {
            display: none;
        }
        .section.active {
            display: block;
        }
        .upload-section {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .upload-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 5px;
            width: 100%;
        }
        .upload-item label {
            width: 250px;
            font-weight: bold;
        }
        .custom-file {
            flex-grow: 1;
        }
        .custom-file-input {
            width: 100%;
        }
        .custom-file-label {
            width: 100%;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="college-name">Sankar Polytechnic College (Autonomous)</div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 form-container">
                <div class="form-header">Admission Form</div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
                <form id="admission-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data" onsubmit="return confirmSubmission()">
                    <!-- Personal Information Section -->
                    <div id="personal-info" class="section active">
                        <h4 class="text-primary">Personal Information</h4>
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Enter your full name" required>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" class="form-control" id="dob" name="dob" required>
                        </div>
                        <div class="form-group">
                            <label>Gender</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" value="Male" required>
                                <label class="form-check-label">Male</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" value="Female" required>
                                <label class="form-check-label">Female</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" value="Other" required>
                                <label class="form-check-label">Other</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="blood_group">Blood Group (Optional)</label>
                            <input type="text" class="form-control" id="blood_group" name="blood_group" placeholder="Enter your blood group">
                        </div>
                        <div class="form-group">
                            <label for="religion">Religion (Optional)</label>
                            <input type="text" class="form-control" id="religion" name="religion" placeholder="Enter your religion">
                        </div>
                        <button type="button" class="btn next-btn" onclick="nextSection('contact-info', 66)">Next</button>
                    </div>

                    <!-- Contact Information Section -->
                    <div id="contact-info" class="section">
                        <h4 class="text-primary">Contact Information</h4>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter your phone number" required>
                        </div>
                        <div class="form-group">
                            <label for="permanent_address">Permanent Address</label>
                            <textarea class="form-control" id="permanent_address" name="permanent_address" rows="3" placeholder="Enter your permanent address" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="correspondence_address">Correspondence Address</label>
                            <textarea class="form-control" id="correspondence_address" name="correspondence_address" rows="3" placeholder="Enter your correspondence address"></textarea>
                        </div>
                        <button type="button" class="btn back-btn" onclick="prevSection('personal-info', 33)">Back</button>
                        <button type="button" class="btn next-btn" onclick="nextSection('academic-info', 66)">Next</button>
                    </div>

                    <!-- Academic Information Section -->
                    <div id="academic-info" class="section">
                        <h4 class="text-primary">Academic Information</h4>
                        <div class="form-group">
                            <label for="school">School Name</label>
                            <input type="text" class="form-control" id="school" name="school" placeholder="Enter your school name" required>
                        </div>
                        <div class="form-group">
                            <label for="year_passing">Year of Passing</label>
                            <input type="text" class="form-control" id="year_passing" name="year_passing" placeholder="Enter your year of passing" required>
                        </div>
                        <div class="form-group">
                            <label for="percentage">Percentage/Grade</label>
                            <input type="text" class="form-control" id="percentage" name="percentage" placeholder="Enter your marks or CGPA" required>
                        </div>
                        <div class="form-group">
                            <label>Previous Qualification</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="previous_qualification_radio" value="10th" required>
                                <label class="form-check-label">10th</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="previous_qualification_radio" value="12th" required onclick="toggleQualificationGroup()">
                                <label class="form-check-label">12th</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="previous_qualification_radio" value="Other" required>
                                <label class="form-check-label">Other</label>
                            </div>
                        </div>
                        <div id="group-select" class="form-group" style="display: none;">
                            <label for="group">Select Group (Only for 12th)</label>
                            <select class="form-control" id="group" name="group">
                                <option value="" disabled selected>Select your 12th group</option>
                                <option value="Maths-Biology">Maths-Biology</option>
                                <option value="Maths-Computer">Maths-Computer</option>
                                <option value="Bio-computer">Bio-computer</option>
                                <option value="Pure-Science">Pure-Science</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="marks">Marks/Grades Obtained</label>
                            <input type="text" class="form-control" id="marks" name="marks" placeholder="Enter your marks or grades" required>
                        </div>
                        <div class="form-group">
                            <label for="subjects_applied">Course Applied For</label>
                            <select class="form-control" id="subjects_applied" name="subjects_applied" required>
                                <option value="" disabled selected>Select your preferred branch</option>
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="ECE">ECE</option>
                                <option value="EEE">EEE</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                            </select>
                        </div>
                        <button type="button" class="btn back-btn" onclick="prevSection('contact-info', 33)">Back</button>
                        <button type="button" class="btn next-btn" onclick="nextSection('parent-info', 100)">Next</button>
                    </div>

                    <!-- Parent/Guardian Information Section -->
                    <div id="parent-info" class="section">
                        <h4 class="text-primary">Parent/Guardian Information</h4>
                        <div class="form-group">
                            <label for="fathers_name">Father's Name</label>
                            <input type="text" class="form-control" id="fathers_name" name="fathers_name" placeholder="Enter your father's name">
                        </div>
                        <div class="form-group">
                            <label for="fathers_occupation">Father's Occupation</label>
                            <input type="text" class="form-control" id="fathers_occupation" name="fathers_occupation" placeholder="Enter your father's occupation">
                        </div>
                        <div class="form-group">
                            <label for="fathers_contact">Father's Contact Number</label>
                            <input type="text" class="form-control" id="fathers_contact" name="fathers_contact" placeholder="Enter your father's contact number">
                        </div>
                        <div class="form-group">
                            <label for="mothers_name">Mother's Name</label>
                            <input type="text" class="form-control" id="mothers_name" name="mothers_name" placeholder="Enter your mother's name">
                        </div>
                        <div class="form-group">
                            <label for="mothers_occupation">Mother's Occupation</label>
                            <input type="text" class="form-control" id="mothers_occupation" name="mothers_occupation" placeholder="Enter your mother's occupation">
                        </div>
                        <div class="form-group">
                            <label for="mothers_contact">Mother's Contact Number</label>
                            <input type="text" class="form-control" id="mothers_contact" name="mothers_contact" placeholder="Enter your mother's contact number">
                        </div>
                        <div class="form-group">
                            <label for="guardian_name">Guardian's Name</label>
                            <input type="text" class="form-control" id="guardian_name" name="guardian_name" placeholder="Enter your guardian's name">
                        </div>
                        <div class="form-group">
                            <label for="guardian_contact">Guardian's Contact Number</label>
                            <input type="text" class="form-control" id="guardian_contact" name="guardian_contact" placeholder="Enter your guardian's contact number">
                        </div>
                        <button type="button" class="btn back-btn" onclick="prevSection('academic-info', 66)">Back</button>
                        <button type="button" class="btn next-btn" onclick="nextSection('upload-info', 100)">Next</button>
                    </div>

                    <!-- Upload Information Section -->
                    <div id="upload-info" class="section">
                        <h4 class="text-primary">Upload Documents</h4>
                        <div class="upload-section">
                            <div class="upload-item">
                                <label for="photo">Upload Photo</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="photo" name="photo" required>
                                    <label class="custom-file-label" for="photo">Choose file</label>
                                </div>
                            </div>
                            <div class="upload-item">
                                <label for="signature">Upload Signature</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="signature" name="signature" required>
                                    <label class="custom-file-label" for="signature">Choose file</label>
                                </div>
                            </div>
                            <div class="upload-item">
                                <label for="marksheet10">Upload 10th Marksheet</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="marksheet10" name="marksheet10" required>
                                    <label class="custom-file-label" for="marksheet10">Choose file</label>
                                </div>
                            </div>
                            <div class="upload-item">
                                <label for="marksheet12">Upload 12th Marksheet</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="marksheet12" name="marksheet12">
                                    <label class="custom-file-label" for="marksheet12">Choose file</label>
                                </div>
                            </div>
                            <div class="upload-item">
                                <label for="aadhaar">Upload Aadhaar Card</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="aadhaar" name="aadhaar" required>
                                    <label class="custom-file-label" for="aadhaar">Choose file</label>
                                </div>
                            </div>
                            <div class="upload-item">
                                <label for="birthCertificate">Upload Birth Certificate</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="birthCertificate" name="birthCertificate" required>
                                    <label class="custom-file-label" for="birthCertificate">Choose file</label>
                                </div>
                            </div>
                            <div class="upload-item">
                                <label for="casteCertificate">Upload Caste Certificate</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="casteCertificate" name="casteCertificate">
                                    <label class="custom-file-label" for="casteCertificate">Choose file</label>
                                </div>
                            </div>
                            <div class="upload-item">
                                <label for="incomeCertificate">Upload Income Certificate</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="incomeCertificate" name="incomeCertificate">
                                    <label class="custom-file-label" for="incomeCertificate">Choose file</label>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn back-btn" onclick="prevSection('parent-info', 66)">Back</button>
                        <button type="submit" class="btn next-btn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function nextSection(sectionId, progress) {
        document.querySelector('.section.active').classList.remove('active');
        document.getElementById(sectionId).classList.add('active');
        document.getElementById('progress-bar').style.width = progress + '%';
    }

    function prevSection(sectionId, progress) {
        document.querySelector('.section.active').classList.remove('active');
        document.getElementById(sectionId).classList.add('active');
        document.getElementById('progress-bar').style.width = progress + '%';
    }

    function toggleQualificationGroup() {
        var selectedQualification = document.querySelector('input[name="previous_qualification_radio"]:checked').value;
        var groupSelect = document.getElementById('group-select');
        var groupInput = document.getElementById('group');

        if (selectedQualification === "12th") {
            groupSelect.style.display = 'block';
            groupInput.setAttribute("required", "true");
        } else {
            groupSelect.style.display = 'none';
            groupInput.removeAttribute("required");
        }
    }

    function confirmSubmission() {
        return confirm('Are you sure you want to submit the form?');
    }

    document.querySelectorAll('.custom-file-input').forEach(input => {
        input.addEventListener('change', function() {
            let fileName = this.files[0].name;
            this.nextElementSibling.innerHTML = fileName;
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        toggleQualificationGroup();
    });

    document.querySelectorAll('input[name="previous_qualification_radio"]').forEach(radio => {
        radio.addEventListener('change', toggleQualificationGroup);
    });
    </script>
</body>
</html>
<?php if (isset($conn)) $conn->close(); ?>