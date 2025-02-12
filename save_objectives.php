<?php
session_start();
include 'dbconn.php'; // Your database connection code

// Check if the user is logged in as a trainer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve data from the form
    $courseId = $_POST['course_id'];
    $objectives = $_POST['objectives'];
    $contentObjectives = $_POST['content_objectives'];

    // Handle file uploads
    $uploadedFiles = [];
    if (!empty($_FILES['documents']['name'][0])) {
        foreach ($_FILES['documents']['name'] as $key => $name) {
            $tmp_name = $_FILES['documents']['tmp_name'][$key];
            $uploadDir = 'uploads/'; // Ensure this directory exists and is writable
            $filePath = $uploadDir . basename($name);
            if (move_uploaded_file($tmp_name, $filePath)) {
                $uploadedFiles[] = $filePath; // Store the path of uploaded files
            } else {
                echo "Failed to upload file: " . htmlspecialchars($name);
            }
        }
    }

    // Prepare and execute the insertion into the database for course objectives
    $stmt = $conn->prepare("INSERT INTO course_objectives (course_id, objectives, content_objectives, documents) VALUES (?, ?, ?, ?)");
    $documentsJson = json_encode($uploadedFiles); // Convert documents array to JSON
    $stmt->bind_param("isss", $courseId, $objectives, $contentObjectives, $documentsJson);

    if ($stmt->execute()) {
        // Redirect to mycourse.php after successful insertion
        header("Location: mycourse.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close(); // Close the statement
    $conn->close(); // Close the database connection
    exit();
}
?>