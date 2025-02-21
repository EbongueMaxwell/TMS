<?php
session_start();
include 'dbconn.php';
include 'trainerheader.php';

// Redirect if not logged in as a trainer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? '';

// Fetch courses assigned to this trainer
$stmt = $conn->prepare("
    SELECT c.id, c.title, c.description, c.duration, c.image
    FROM courses c
    JOIN teacher_courses tc ON c.id = tc.course_id
    WHERE tc.teacher_username = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close(); // Close after fetching data

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $courseId = $_POST['course_id'];
    $courseObjectives = $_POST['course_objectives'];
    $contentObjectives = $_POST['content_objectives'];
    $fileDestination = null;

    // Handle file upload if provided
    if (isset($_FILES['course_file']) && $_FILES['course_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['course_file']['name'];
        $fileTmpName = $_FILES['course_file']['tmp_name'];
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileNewName = uniqid('', true) . '.' . $fileExt;
        $fileDestination = 'uploads/' . $fileNewName;
        move_uploaded_file($fileTmpName, $fileDestination);
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

        // Append new file if provided
        if ($fileDestination) {
            $updatedFiles = $existingFiles ? $existingFiles . ',' . $fileDestination : $fileDestination;
        } else {
            $updatedFiles = $existingFiles; // No new file, keep existing
        }

        // Update existing record
        $updateStmt = $conn->prepare("UPDATE course_objectives SET course_objectives = ?, content_objectives = ?, file = ? WHERE course_id = ?");
        $updateStmt->bind_param("sssi", $updatedCourseObjectives, $updatedContentObjectives, $updatedFiles, $courseId);
        $success = $updateStmt->execute();
        $updateStmt->close(); // Close update statement
    } else {
        // Insert new record
        $insertStmt = $conn->prepare("INSERT INTO course_objectives (course_id, course_objectives, content_objectives, file) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("isss", $courseId, $courseObjectives, $contentObjectives, $fileDestination);
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
    <title>My Courses</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        /* General Styling */
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        
        .content {
            padding: 20px;
            background-color: whitesmoke;
        }

        /* Course Cards */
        .course-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            background-color: whitesmoke;
        }

        .course-card {
            border: 1px solid #ccc;
            border-radius: 8px;
            margin: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            height: 400px;
            width: calc(30% - 20px);
            transition: transform 0.2s;
            cursor: pointer;
            background: #fff;
        }

        .course-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .course-card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .course-info {
            padding: 10px;
            text-align: center;
        }

        .course-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .course-description {
            font-size: 12px;
            margin-bottom: 5px;
            color: #666;
        }

        .course-duration {
            font-size: 12px;
            color: #888;
        }

        /* Button Styling */
        .view-btn, .update-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        .view-btn:hover, .update-btn:hover {
            background-color: #0056b3;
        }

        /* Update Form Styling */
        .update-form-container {
            margin-top: 20px;
        }

        .update-form-container h2 {
            margin-bottom: 10px;
        }

        .update-form-container textarea {
            width: 100%;
            min-height: 100px;
            margin-bottom: 10px;
        }

        .save-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
        }

        .save-btn:hover {
            background-color: #218838;
        }
    </style>
    <script>
        function showUpdateForm(courseId) {
            // Prepare the form for the clicked course
            const formContent = ` 
                <form action="mycourse.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="course_id" value="${courseId}">
                    <label>Course Objectives:</label>
                    <textarea name="course_objectives" required></textarea>

                    <label>Content Objectives:</label>
                    <textarea name="content_objectives" required></textarea>

                    <label>Upload file:</label>
                    <input type="file" name="course_file">

                    <button type="submit" class="save-btn">Save Changes</button>
                </form>
            `;

            // Show the form
            document.getElementById("update-form-content").innerHTML = formContent;
            document.getElementById("update-form-container").style.display = "block";
        }
    </script>
</head>
<body>

    <div class="content">
        <h1>My Assigned Courses</h1>
        <div class="course-container">
            <?php if ($courses->num_rows > 0): ?>
                <?php while ($course = $courses->fetch_assoc()): ?>
                    <div class="course-card">
                        <img src="<?php echo !empty($course['image']) ? htmlspecialchars($course['image']) : 'default_course.png'; ?>" alt="Course Image">
                        <div class="course-info">
                            <h2 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h2>
                            <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
                            <p class="course-duration">Duration: <?php echo htmlspecialchars($course['duration']); ?> weeks</p>
                        </div>

                        <!-- View and Update Buttons -->
                        <button class="view-btn" onclick="window.location.href='view_course.php?course_id=<?php echo $course['id']; ?>'">View</button>
                        <button class="update-btn" onclick="showUpdateForm(<?php echo $course['id']; ?>)">Update Course</button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No assigned courses found.</p>
            <?php endif; ?>
        </div>

        <!-- Update Form Container -->
        <div class="update-form-container" id="update-form-container" style="display:none;">
            <h2>Update Course</h2>
            <div id="update-form-content"></div>
        </div>
    </div>

</body>
</html>

<?php
include 'footer.php';
?>