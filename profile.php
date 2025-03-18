<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = $error_message = "";
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === 'true';

// Fetch current user data
$sql = "SELECT full_name, email, username, bio, location, website, profile_picture, join_date FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $website = trim($_POST['website'] ?? '');

    $profile_picture = $user['profile_picture'] ?? 'default_profile.jpg';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $absolute_upload_dir = __DIR__ . '/' . $upload_dir;
        if (!is_dir($absolute_upload_dir)) {
            mkdir($absolute_upload_dir, 0777, true);
            chmod($absolute_upload_dir, 0777);
        }

        $file_ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_exts)) {
            $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        } else {
            $file_name = $user_id . '_' . time() . '.' . $file_ext;
            $profile_picture = $upload_dir . $file_name;
            $upload_path = $absolute_upload_dir . $file_name;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                if ($user['profile_picture'] && $user['profile_picture'] !== 'default_profile.jpg' && file_exists(__DIR__ . '/' . $user['profile_picture'])) {
                    unlink(__DIR__ . '/' . $user['profile_picture']);
                }
            } else {
                $error_message = "Failed to upload profile picture. Error code: " . $_FILES['profile_picture']['error'];
                $profile_picture = $user['profile_picture'] ?? 'default_profile.jpg';
            }
        }
    }

    if (empty($full_name) || empty($email)) {
        $error_message = "Full name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        $error_message = "Invalid website URL.";
    } else {
        $sql = "UPDATE users SET full_name = ?, email = ?, username = ?, bio = ?, location = ?, website = ?, profile_picture = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $full_name, $email, $username, $bio, $location, $website, $profile_picture, $user_id);

        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            $edit_mode = false;
            // Refresh user data
            $sql = "SELECT full_name, email, username, bio, location, website, profile_picture, join_date FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $profile_picture = $user['profile_picture'] ?? 'default_profile.jpg';
        } else {
            $error_message = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch application status
$sql = "SELECT status FROM applications WHERE user_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->num_rows > 0 ? $result->fetch_assoc() : null;
$stmt->close();

$profile_picture_url = '/' . ($user['profile_picture'] ?? 'default_profile.jpg') . '?t=' . time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .profile-card { background: white; border-radius: 15px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); padding: 20px; margin-top: 30px; position: relative; }
        .profile-img-link { 
            width: 120px; 
            height: 120px; 
            border: 2px solid #007bff; 
            display: block; 
            text-align: center; 
            line-height: 116px; 
            color: #007bff; 
            text-decoration: none; 
            background: #f8f9fa; 
            margin-bottom: 15px;
        }
        .profile-img-link:hover { background: #e9ecef; text-decoration: none; color: #0056b3; }
        .profile-img { width: 120px; height: 120px; object-fit: cover; border: 3px solid #007bff; }
        .edit-btn { position: absolute; top: 15px; right: 15px; background-color: #007bff; color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; transition: background-color 0.3s; }
        .edit-btn:hover { background-color: #0056b3; color: white; }
        .detail-label { font-weight: bold; color: #555; }
        .detail-value { color: #333; margin-bottom: 10px; }
        .form-control { border-radius: 8px; border: 1px solid #ced4da; width: 100%; }
        .btn-primary { background-color: #007bff; border: none; border-radius: 8px; padding: 10px 20px; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { border-radius: 8px; padding: 10px 20px; }
        h2 { color: #333; font-weight: 600; }
        .alert { border-radius: 8px; }
        .modal-img { max-width: 100%; max-height: 80vh; margin: auto; display: block; }
        .status-badge { font-size: 14px; padding: 5px 10px; border-radius: 12px; }
        .status-pending { background-color: #ffc107; color: #fff; }
        .status-approved { background-color: #28a745; color: #fff; }
        .status-rejected { background-color: #dc3545; color: #fff; }
        .image-upload-area { 
            width: 120px; 
            height: 120px; 
            border: 3px dashed #007bff; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            background: #f8f9fa; 
            margin: 10px auto; 
            position: relative; 
            overflow: hidden; 
        }
        .image-upload-area img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            border-radius: 50%;
            display: block; 
        }
        .image-upload-area span { 
            color: #007bff; 
            font-weight: bold; 
            position: absolute;
            background: rgba(255, 255, 255, 0.7);
            padding: 2px 5px;
            border-radius: 3px;
        }
        .image-upload-area:hover { background: #e9ecef; }
        #profile_picture_input { display: none; }
        .editable-field { 
            border: none; 
            background: transparent; 
            color: #333; 
            width: 100%; 
            padding: 0;
            margin: 0;
        }
        .editable-field[readonly] {
            border: none;
            background: transparent;
            cursor: default;
        }
        .editable-field:focus { 
            outline: none; 
            border: 1px solid #007bff; 
            padding: 5px; 
            background: white;
        }
        textarea.editable-field {
            resize: none;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-card">
            <h2>Your Profile</h2>
            <a href="?edit=true" class="edit-btn" title="Edit Profile"><i class="bi bi-pencil"></i></a>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" id="profile_form">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <?php if ($edit_mode): ?>
                            <div class="image-upload-area" onclick="document.getElementById('profile_picture_input').click();">
                                <img id="upload_preview" src="<?php echo htmlspecialchars($profile_picture_url); ?>" alt="Preview">
                                <span id="upload_text">Change Picture</span>
                            </div>
                            <input type="file" id="profile_picture_input" name="profile_picture" accept="image/*">
                            <small class="text-muted mt-2 d-block">Current: <?php echo htmlspecialchars(basename($user['profile_picture'] ?? 'default_profile.jpg')); ?></small>
                        <?php else: ?>
                            <a href="#" class="profile-img-link" data-toggle="modal" data-target="#imageModal">
                                <?php echo $user['profile_picture'] ? 'View Profile Picture' : 'No Picture Set'; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-9">
                        <div class="detail-label">Name:</div>
                        <div class="detail-value">
                            <input type="text" name="full_name" class="editable-field" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                   <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                        </div>
                        <div class="detail-label">Email:</div>
                        <div class="detail-value">
                            <input type="email" name="email" class="editable-field" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                        </div>
                        <div class="detail-label">Username:</div>
                        <div class="detail-value">
                            @<input type="text" name="username" class="editable-field" 
                                   value="<?php echo htmlspecialchars($user['username'] ?? 'Not set'); ?>" 
                                   <?php echo $edit_mode ? '' : 'readonly'; ?>>
                        </div>
                        <div class="detail-label">Bio:</div>
                        <div class="detail-value">
                            <textarea name="bio" class="editable-field" rows="3" 
                                      <?php echo $edit_mode ? '' : 'readonly'; ?>><?php echo htmlspecialchars($user['bio'] ?? 'No bio set'); ?></textarea>
                        </div>
                        <div class="detail-label">Location:</div>
                        <div class="detail-value">
                            <input type="text" name="location" class="editable-field" 
                                   value="<?php echo htmlspecialchars($user['location'] ?? 'Not specified'); ?>" 
                                   <?php echo $edit_mode ? '' : 'readonly'; ?>>
                        </div>
                        <div class="detail-label">Website:</div>
                        <div class="detail-value">
                            <input type="url" name="website" class="editable-field" 
                                   value="<?php echo htmlspecialchars($user['website'] ?? 'Not set'); ?>" 
                                   <?php echo $edit_mode ? '' : 'readonly'; ?>>
                        </div>
                        <div class="detail-label">Joined:</div>
                        <div class="detail-value"><?php echo $user['join_date'] ? date('F Y', strtotime($user['join_date'])) : 'Unknown'; ?></div>
                        <div class="detail-label">Application Status:</div>
                        <div class="detail-value">
                            <?php if ($application): ?>
                                <span class="status-badge <?php
                                    switch ($application['status']) {
                                        case 'Pending': echo 'status-pending'; break;
                                        case 'Approved': echo 'status-approved'; break;
                                        case 'Rejected': echo 'status-rejected'; break;
                                        default: echo 'status-pending';
                                    }
                                ?>">
                                    <?php echo htmlspecialchars($application['status']); ?>
                                </span>
                            <?php else: ?>
                                Not Applied
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($edit_mode): ?>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="profile.php" class="btn btn-secondary">Cancel</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="text-center mt-3">
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Profile Picture</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <img src="<?php echo htmlspecialchars($profile_picture_url); ?>" alt="Full-Screen Profile Picture" class="modal-img" id="modal_img_preview">
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('profile_picture_input')?.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('upload_preview');
                    preview.src = e.target.result;
                    document.getElementById('modal_img_preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>