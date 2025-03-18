<?php
session_start();
include 'db_connection.php';
require 'vendor/autoload.php'; // Ensure this works after Composer setup
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}


// Handle actions (approve, reject, delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';
    $sql = "UPDATE applications SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin.php?status=updated");
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $sql = "DELETE FROM applications WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin.php?status=deleted");
    exit;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="applications_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Full Name', 'Email', 'Course', 'Status']);
    $sql = "SELECT id, full_name, email, subjects_applied, status FROM applications";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [$row['id'], $row['full_name'], $row['email'], $row['subjects_applied'], $row['status']]);
    }
    fclose($output);
    exit;
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    if (ob_get_length()) ob_end_clean();
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Full Name');
    $sheet->setCellValue('C1', 'Email');
    $sheet->setCellValue('D1', 'Course');
    $sheet->setCellValue('E1', 'Status');
    $sql = "SELECT id, full_name, email, subjects_applied, status FROM applications";
    $result = $conn->query($sql);
    $rowNum = 2;
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue("A$rowNum", $row['id']);
        $sheet->setCellValue("B$rowNum", $row['full_name']);
        $sheet->setCellValue("C$rowNum", $row['email']);
        $sheet->setCellValue("D$rowNum", $row['subjects_applied']);
        $sheet->setCellValue("E$rowNum", $row['status']);
        $rowNum++;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="applications_' . date('Y-m-d_H-i-s') . '.xlsx"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Fetch all applications, sorting so Rejected goes to the bottom
$sql = "SELECT id, full_name, email, subjects_applied, status 
        FROM applications 
        ORDER BY CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END, id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Enrollment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); font-family: 'Inter', sans-serif; min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .dashboard-container { background: rgba(255, 255, 255, 0.95); border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); padding: 40px; backdrop-filter: blur(10px); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { color: #2c3e50; font-weight: 700; font-size: 32px; }
        .search-form { margin-bottom: 20px; }
        .stats { background: white; border-radius: 12px; padding: 15px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); }
        .table-wrapper { overflow-x: auto; border-radius: 12px; background: white; }
        .table { margin-bottom: 0; }
        .table thead { background: linear-gradient(45deg, #3498db, #2980b9); color: white; }
        .table th, .table td { vertical-align: middle; text-align: center; }
        .table tbody tr:hover { background: #f1f5f9; cursor: pointer; }
        .btn-custom { padding: 8px 15px; border-radius: 20px; text-decoration: none; color: white; margin: 2px; display: inline-block; }
        .btn { margin: 10px 0px 0px -5px; position: relative; right: 8px; }
        .btn-approve { background: linear-gradient(45deg, #2ecc71, #27ae60); }
        .btn-reject { background: linear-gradient(45deg, #e74c3c, #c0392b); }
        .btn-edit { background: linear-gradient(45deg, #f39c12, #e67e22); }
        .btn-delete { background: linear-gradient(45deg, #95a5a6, #7f8c8d); }
        .btn-download { background: linear-gradient(45deg, #3498db, #2980b9); }
        .btn-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); color: white; text-decoration: none; }
        .status-message { color: green; font-weight: bold; text-align: center; }
        @media (max-width: 768px) { .dashboard-container { padding: 20px; } .header h2 { font-size: 24px; } }
    </style>
</head>
<body>
    <div class="container dashboard-container">
        <div class="header">
            <h2>Admin Dashboard - Student Enrollment</h2>
        </div>

        <!-- Search Bar -->
        <div class="search-form">
            <input type="text" id="searchInput" placeholder="Search by name, ID, or email" class="form-control" onkeyup="filterTable()">
            <a href="admin.php?export=csv" class="btn btn-success ml-2">Export to CSV</a>
            <a href="admin.php?export=xlsx" class="btn btn-success ml-2">Export to Excel</a>
        </div>

        <!-- Stats -->
        <div class="stats">
            <?php
            $total = $conn->query("SELECT COUNT(*) as count FROM applications")->fetch_assoc()['count'];
            $approved = $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'Approved'")->fetch_assoc()['count'];
            $rejected = $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'Rejected'")->fetch_assoc()['count'];
            $pending = $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'Pending'")->fetch_assoc()['count'];
            ?>
            <p>Total Applications: <?php echo $total; ?> | Approved: <?php echo $approved; ?> | Rejected: <?php echo $rejected; ?> | Pending: <?php echo $pending; ?></p>
        </div>

        <!-- Status Message -->
        <?php if (isset($_GET['status'])): ?>
            <p class="status-message">
                <?php echo ($_GET['status'] === 'updated') ? 'Application status updated successfully!' : 'Application deleted successfully!'; ?>
            </p>
        <?php endif; ?>

        <!-- Applications Table -->
        <div class="table-wrapper">
            <table class="table table-bordered table-hover" id="applicationsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>" . htmlspecialchars($row['full_name']) . "</td>
                                    <td>" . htmlspecialchars($row['email']) . "</td>
                                    <td>" . htmlspecialchars($row['subjects_applied']) . "</td>
                                    <td>{$row['status']}</td>
                                    <td>
                                        <a href='view_student.php?id={$row['id']}' class='btn-custom btn-download'>View</a>
                                        <a href='edit_student.php?id={$row['id']}' class='btn-custom btn-edit'>Edit</a>
                                        <a href='admin.php?action=approve&id={$row['id']}' class='btn-custom btn-approve' onclick='return confirm(\"Approve this application?\");'>Approve</a>
                                        <a href='admin.php?action=reject&id={$row['id']}' class='btn-custom btn-reject' onclick='return confirm(\"Reject this application?\");'>Reject</a>
                                        <a href='admin.php?delete_id={$row['id']}' class='btn-custom btn-delete' onclick='return confirm(\"Delete this application?\");'>Delete</a>
                                        <a href='download_all.php?id={$row['id']}' class='btn-custom btn-download'>Download All</a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>No applications found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Logout Link -->
        <div class="text-center mt-4">
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <!-- JavaScript for Dynamic Search -->
    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('applicationsTable');
            const rows = table.getElementsByTagName('tr');
            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let match = false;
                for (let j = 0; j < cells.length - 1; j++) {
                    const cellText = cells[j].textContent || cells[j].innerText;
                    if (cellText.toLowerCase().indexOf(filter) > -1) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>