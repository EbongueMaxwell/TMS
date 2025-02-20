<?php
session_start();
include 'dbconn.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $courseId = 1; // Adjust according to your course logic
    $date = date('Y-m-d');

    // Check for valid course ID
    $courseCheckStmt = $conn->prepare("SELECT COUNT(*) FROM courses WHERE id = ?");
    $courseCheckStmt->bind_param("i", $courseId);
    $courseCheckStmt->execute();
    $courseCheckStmt->bind_result($courseExists);
    $courseCheckStmt->fetch();
    $courseCheckStmt->close();

    if ($courseExists > 0) {
        // Log attendance on logout
        $attendanceStmt = $conn->prepare("INSERT INTO attendance (user_id, course_id, date) VALUES (?, ?, ?)");
        $attendanceStmt->bind_param("iis", $userId, $courseId, $date);
        $attendanceStmt->execute();
        $attendanceStmt->close();
    } else {
        // Handle the case where the course does not exist
        error_log("Course ID $courseId does not exist.");
    }

    // Clear session
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit();
}
?>