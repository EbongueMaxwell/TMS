<?php
session_start();
include 'dbconn.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    echo "Unauthorized access.";
    exit();
}

$trainer_id = $_SESSION['user_id']; // Ensure the session stores the trainer's ID
$course_id = $_POST['course_id'] ?? null;

if ($course_id) {
    // Check if the trainer is already assigned to this course
    $stmt = $conn->prepare("SELECT * FROM teacher_courses WHERE trainer_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $trainer_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // If the course is not already assigned, insert it
        $insert_stmt = $conn->prepare("INSERT INTO teacher_courses (trainer_id, course_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $trainer_id, $course_id);
        $insert_stmt->execute();
        $insert_stmt->close();
        echo "Course assigned successfully!";
    } else {
        echo "Course already assigned.";
    }
} else {
    echo "Invalid course selection.";
}

$stmt->close();
$conn->close();
?>
