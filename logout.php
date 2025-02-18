<?php
session_start();
include 'dbconn.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $courseId = 1; // Adjust according to your course logic
    $date = date('Y-m-d');

    // Log attendance on logout
    $attendanceStmt = $conn->prepare("INSERT INTO attendance (user_id, course_id, date) VALUES (?, ?, ?)");
    $attendanceStmt->bind_param("iis", $userId, $courseId, $date);
    $attendanceStmt->execute();
    $attendanceStmt->close();

    // Clear session
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}
?>