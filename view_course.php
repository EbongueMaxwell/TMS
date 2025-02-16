<?php
session_start();
include 'dbconn.php'; // Your database connection file

// Redirect if not logged in as a trainer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? '';

// Check if course_id is set in the URL
if (isset($_GET['course_id'])) {
    $courseId = $_GET['course_id'];

    // Fetch course details
    $stmt = $conn->prepare("
        SELECT c.id, c.title, c.description, c.duration, c.image, co.course_objectives, co.content_objectives, co.file
        FROM courses c
        LEFT JOIN course_objectives co ON c.id = co.course_id
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    // Redirect to my course page if no course_id is provided
    header("Location: mycourse.php");
    exit();
}

// Handle form submission for file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $courseId = $_POST['course_id'];
    $courseObjectives = $_POST['course_objectives'];
    $contentObjectives = $_POST['content_objectives'];
    $fileDestinations = [];

    // Handle multiple file uploads
    if (isset($_FILES['course_files'])) {
        foreach ($_FILES['course_files']['name'] as $key => $fileName) {
            if ($_FILES['course_files']['error'][$key] === UPLOAD_ERR_OK) {
                $fileTmpName = $_FILES['course_files']['tmp_name'][$key];
                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                $fileNewName = uniqid('', true) . '.' . $fileExt;
                $fileDestination = 'uploads/' . $fileNewName;
                move_uploaded_file($fileTmpName, $fileDestination);
                $fileDestinations[] = $fileDestination; // Collect paths
            }
        }
    }

    // Check if course objectives already exist
    $checkStmt = $conn->prepare("SELECT * FROM course_objectives WHERE course_id = ?");
    $checkStmt->bind_param("i", $courseId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $checkStmt->close(); // Close check statement

    if ($result->num_rows > 0) {
        // Fetch existing objectives and files
        $existingObjectives = $result->fetch_assoc();
        $existingCourseObjectives = $existingObjectives['course_objectives'];
        $existingContentObjectives = $existingObjectives['content_objectives'];
        $existingFiles = $existingObjectives['file']; // Assuming this field holds existing files

        // Append new objectives
        $updatedCourseObjectives = $existingCourseObjectives . "\n" . $courseObjectives;
        $updatedContentObjectives = $existingContentObjectives . "\n" . $contentObjectives;

        // Append new files if provided
        $newFiles = !empty($fileDestinations) ? implode(',', $fileDestinations) : '';
        $updatedFiles = $existingFiles ? $existingFiles . ',' . $newFiles : $newFiles;

        // Update existing record
        $updateStmt = $conn->prepare("UPDATE course_objectives SET course_objectives = ?, content_objectives = ?, file = ? WHERE course_id = ?");
        $updateStmt->bind_param("sssi", $updatedCourseObjectives, $updatedContentObjectives, $updatedFiles, $courseId);
        $success = $updateStmt->execute();
        $updateStmt->close(); // Close update statement
    } else {
        // Insert new record
        $insertStmt = $conn->prepare("INSERT INTO course_objectives (course_id, course_objectives, content_objectives, file) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("isss", $courseId, $courseObjectives, $contentObjectives, implode(',', $fileDestinations));
        $success = $insertStmt->execute();
        $insertStmt->close(); // Close insert statement
    }

    if ($success) {
        echo "<script>alert('Course objectives updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating course objectives.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Course Details</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        /* General Styling */
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header {
            height: 55px;
            background-color: #007bff;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header h2 {
            margin: 0 15px;
        }

        .search-bar {
            background-color: white;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 22px;
            width: 400px;
            margin-left: auto;
            font-size: 16px;
        }

        .search-icon, .user-icon {
            cursor: pointer;
            font-size: 36px;
            margin-left: 20px;
            line-height: 55px;
        }

        .sidebar {
            height: 100%;
            width: 200px;
            position: fixed;
            left: -300px; /* Initially hidden */
            background-color: #343a40;
            color: white;
            transition: left 0.3s;
            z-index: 1000;
            padding: 15px;
        }

        .sidebar h2 {
            color: #ffffff;
            margin: 0 0 20px;
            font-size: 24px;
        }

        .sidebar a {
            display: block;
            padding: 10px 15px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px 0;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #495057;
        }

        .content {
            padding: 20px;
            margin-left: 220px; /* Adjusted for sidebar width */
        }

        /* Course Details Styling */
        .course-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .course-description {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .course-duration {
            font-size: 14px;
            margin-bottom: 10px;
            color: #888;
        }

        .objectives h3 {
            margin-top: 20px;
        }

        .document-link {
            color: #007bff;
            text-decoration: none;
        }

        .document-link:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.style.left = sidebar.style.left === '0px' ? '-300px' : '0px'; // Toggle sidebar
        }
    </script>
</head>
<body>

    <div class="header">
        <span class="menu-icon" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </span>
        <h2>Calea Portal</h2>
        <input type="text" id="search-bar" class="search-bar" placeholder="Search...">
        <span class="search-icon"><i class="fas fa-search"></i></span>
        <span class="user-icon">
            <i class="fas fa-user"></i>
            <div class="username-tooltip"><?php echo htmlspecialchars($username); ?></div>
        </span>
    </div>

    <div class="sidebar">
        <h2>Trainer Menu</h2>
        <a href="trainerdash.php">Dashboard</a>
        <a href="mycourse.php">My Courses</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="content">
        <h1 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h1>
        <p class="course-description"><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
        <p class="course-duration">Duration: <?php echo htmlspecialchars($course['duration']); ?> weeks</p>

        <div class="objectives">
            <h3>Course Objectives:</h3>
            <p><?php echo nl2br(htmlspecialchars($course['course_objectives'])); ?></p>
        </div>

        <div class="objectives">
            <h3>Content Objectives:</h3>
            <p><?php echo nl2br(htmlspecialchars($course['content_objectives'])); ?></p>
        </div>

        <div class="objectives">
            <h3>Uploaded Documents:</h3>
            <?php if (!empty($course['file'])): ?>
                <?php $files = explode(',', $course['file']); ?>
                <ul>
                    <?php foreach ($files as $file): ?>
                        <li type="1"><a href="<?php echo htmlspecialchars(trim($file)); ?>" class="document-link" target="_blank">Download Document</a></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No documents uploaded for this course.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>