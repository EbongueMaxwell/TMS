<?php
session_start();
include 'dbconn.php'; // Your database connection file
include 'trainerheader.php'; // Include the trainer header file

// Redirect if not logged in as a trainer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

// Fetch username from the users table
$username = '';
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id']; // Assuming user ID is stored in the session
    $stmtUser = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmtUser->bind_param("i", $userId);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    if ($resultUser->num_rows > 0) {
        $user = $resultUser->fetch_assoc();
        $username = htmlspecialchars($user['username']); // Sanitize output
    }
    $stmtUser->close();
}

// Fetch all available courses
$stmt = $conn->prepare("SELECT * FROM courses");
$stmt->execute();
$courses = $stmt->get_result();

// Fetch all users
$stmtUsers = $conn->prepare("SELECT id, username, role FROM users");
$stmtUsers->execute();
$users = $stmtUsers->get_result();

// Handle course selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $courseId = $_POST['course_id'];

    // Check if the trainer already teaches this course
    $stmtCheck = $conn->prepare("SELECT * FROM teacher_courses WHERE course_id = ? AND teacher_username = ?");
    $stmtCheck->bind_param("is", $courseId, $username);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows === 0) {
        // Assign course to trainer
        $stmtInsert = $conn->prepare("INSERT INTO teacher_courses (course_id, teacher_username) VALUES (?, ?)");
        $stmtInsert->bind_param("is", $courseId, $username);
        if ($stmtInsert->execute()) {
            header("Location: mycourse.php?course_id=" . $courseId);
            exit();
        } else {
            $error = "Error assigning course.";
        }
        $stmtInsert->close();
    } else {
        $error = "You are already assigned to this course.";
    }

    $stmtCheck->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard</title>
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <style>
        /* General Styling */
        html, body {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
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
            height: 200px;
            width: calc(30% - 20px);
            transition: transform 0.2s;
            cursor: pointer;
        }

        .course-card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .course-image {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }

        .course-title {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }

        .course-description {
            font-size: 12px;
            text-align: center;
            margin: 5px 0;
            flex-grow: 1;
        }

        .course-duration {
            font-size: 12px;
            margin: 5px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Welcome To The Dashboard</h1>

    <div class="content">
        <h1>Select Courses to Teach</h1>
        <div class="course-container">
            <?php if ($courses->num_rows > 0): ?>
                <?php while ($course = $courses->fetch_assoc()): ?>
                <div class="course-card" onclick="selectCourse(<?php echo $course['id']; ?>)">
                    <img src="<?php echo htmlspecialchars($course['image_url']); ?>" alt="Course Image" class="course-image">
                    <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
                    <div class="course-description"><?php echo htmlspecialchars($course['description']); ?></div>
                    <div class="course-duration">Duration: <?php echo htmlspecialchars($course['duration']); ?> weeks</div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div>No courses found.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function selectCourse(courseId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'course_id';
            input.value = courseId;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>

</body>
</html>

<?php
include 'footer.php';
?>