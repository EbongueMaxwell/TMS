<?php
session_start();
include 'dbconn.php'; // Your database connection file

// Redirect if not logged in as a trainer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? '';

// Fetch all courses
$stmt = $conn->prepare("SELECT * FROM courses");
$stmt->execute();
$courses = $stmt->get_result();

// Handle form submission for inserting course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $courseId = $_POST['course_id'];

    // Insert into teacher_courses table
    $stmtInsert = $conn->prepare("INSERT INTO teacher_courses (course_id, teacher_username) VALUES (?, ?)");
    $stmtInsert->bind_param("is", $courseId, $username);
    $stmtInsert->execute();

    if ($stmtInsert->affected_rows > 0) {
        echo "<script>window.location.href='mycourse.php?course_id=" . $courseId . "';</script>";
        exit();
    } else {
        echo "<script>alert('Error selecting course.');</script>";
    }

    $stmtInsert->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard</title>
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <link rel="stylesheet" href="styles.css"> <!-- Link to your main CSS file -->
    <style>
        /* General Styling */
        html, body {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
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
            left: -300px;
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
            margin-left: 220px;
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
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.style.left = sidebar.style.left === '0px' ? '-300px' : '0px';
        }

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

    <?php
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>
