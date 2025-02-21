<?php
session_start();
include 'dbconn.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle attendance marking
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $courseTitle = trim($_POST['course_title']);
    $date = date('Y-m-d');

    // Fetch user ID by username
    $userCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $userCheck->bind_param("s", $username);
    $userCheck->execute();
    $userCheck->bind_result($userId);
    $userCheck->fetch();
    $userCheck->close();

    // Fetch course ID by title
    $courseCheck = $conn->prepare("SELECT id FROM courses WHERE title = ?");
    $courseCheck->bind_param("s", $courseTitle);
    $courseCheck->execute();
    $courseCheck->bind_result($courseId);
    $courseCheck->fetch();
    $courseCheck->close();

    if ($userId && $courseId) {
        // Insert attendance record
        $stmt = $conn->prepare("INSERT INTO attendance (user_id, course_id, date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $userId, $courseId, $date);
        if ($stmt->execute()) {
            $message = "Attendance marked successfully.";
        } else {
            $message = "Error marking attendance.";
        }
        $stmt->close();
    } else {
        $message = "Invalid username or course title.";
    }
}

// Retrieve attendance records
$attendanceRecords = $conn->query("SELECT a.user_id, a.course_id, a.date, u.username, c.title 
                                    FROM attendance a 
                                    JOIN users u ON a.user_id = u.id 
                                    JOIN courses c ON a.course_id = c.id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Tracking</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #007bff;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            color: green;
        }
        .error {
            color: red;
        }
        form {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Attendance Tracking</h1>

    <?php if (!empty($message)): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" name="username" required>
        <label for="course_title">Course Title:</label>
        <input type="text" name="course_title" required>
        <button type="submit">Mark Attendance</button>
    </form>

    <h2>Attendance Records</h2>
    <table>
        <tr>
            <th>Username</th>
            <th>Course Title</th>
            <th>Date</th>
        </tr>
        <?php while ($record = $attendanceRecords->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($record['username']); ?></td>
            <td><?php echo htmlspecialchars($record['title']); ?></td>
            <td><?php echo htmlspecialchars($record['date']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php
include 'footer.php';
?>