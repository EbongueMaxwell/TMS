<?php
session_start();
include 'dbconn.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle attendance marking (simplified)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['user_id'];
    $courseId = $_POST['course_id'];
    $date = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO attendance (user_id, course_id, date) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $userId, $courseId, $date);
    $stmt->execute();
    $stmt->close();
}

// Retrieve attendance records (modify as needed)
$attendanceRecords = $conn->query("SELECT * FROM attendance");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Tracking</title>
</head>
<body>
    <h1>Attendance Tracking</h1>

    <form method="POST" action="">
        <label for="user_id">User ID:</label>
        <input type="number" name="user_id" required>
        <label for="course_id">Course ID:</label>
        <input type="number" name="course_id" required>
        <button type="submit">Mark Attendance</button>
    </form>

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
</body>
</html>