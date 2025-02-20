<?php
session_start();
ob_start(); // Start output buffering
include 'dbconn.php';

// Ensure the admin header loads properly at the top
include 'adminheader.php'; 

// Restrict access to admins only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Retrieve attendance records
$attendanceRecords = $conn->query("SELECT * FROM attendance");

// Retrieve actions performed by trainees
$actionsRecords = $conn->query("SELECT * FROM actions ORDER BY timestamp DESC");

// Retrieve trainees and their enrolled courses
$traineeCourses = $conn->query("
    SELECT u.username AS trainee, c.title AS course 
    FROM enrollments e 
    JOIN users u ON e.trainee_id = u.id 
    JOIN courses c ON e.course_id = c.id 
    WHERE u.role = 'trainee'
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporting</title>
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8ff;
            margin: 0;
            padding: 0;
        }
        .content {
            padding: 20px;
            max-width: 1200px;
            margin: auto;
        }
        h1 {
            color: #007bff;
            text-align: center;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background: white;
        }
        th, td {
            border: 1px solid #007bff;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>

    <div class="content">
        <h1>Reportings</h1>

        <h2>Attendance Records</h2>
        <table>
            <tr>
                <th>User ID</th>
                <th>Course ID</th>
                <th>Date</th>
            </tr>
            <?php while ($record = $attendanceRecords->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($record['user_id']); ?></td>
                <td><?php echo htmlspecialchars($record['course_id']); ?></td>
                <td><?php echo htmlspecialchars($record['date']); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <h2>Actions by Trainees</h2>
        <table>
            <tr>
                <th>User ID</th>
                <th>Action</th>
                <th>Timestamp</th>
            </tr>
            <?php while ($action = $actionsRecords->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($action['user_id']); ?></td>
                <td><?php echo htmlspecialchars($action['action']); ?></td>
                <td><?php echo htmlspecialchars($action['timestamp']); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <h2>Trainees and Their Enrolled Courses</h2>
        <table>
            <tr>
                <th>Trainee</th>
                <th>Course</th>
            </tr>
            <?php while ($traineeCourse = $traineeCourses->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($traineeCourse['trainee']); ?></td>
                <td><?php echo htmlspecialchars($traineeCourse['course']); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

</body>
</html>

<?php
ob_end_flush(); // Flush the output buffer
?>