<?php
session_start();
include 'dbconn.php'; // Your database connection file
include 'traineeheader.php'; // Include the header and sidebar

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch current user's data
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch enrolled courses
$stmtCourses = $conn->prepare("
    SELECT c.title 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    WHERE e.trainee_id = ?
");
if ($stmtCourses === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
$stmtCourses->bind_param("i", $userId);
$stmtCourses->execute();
$courses = $stmtCourses->get_result();
$stmtCourses->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile</title>
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css"> <!-- Link Font Awesome -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 100px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .profile-info {
            margin-bottom: 30px;
        }
        .profile-info label {
            font-weight: bold;
        }
        .profile-info p {
            margin: 5px 0;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .courses {
            margin-top: 20px;
        }
        .courses h2 {
            text-align: center;
            color: #333;
        }
        .courses ul {
            list-style-type: none;
            padding: 0;
        }
        .courses li {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
   
    <i class="fas fa-user" style="display: block; margin: 20px auto; font-size:80px; color: #007bff; text-align: center;"></i> <!-- User icon with inline CSS -->

    <h1>Manage Profile</h1>
        <div class="profile-info">
            <label>Username:</label>
            <p><?php echo htmlspecialchars($user['username']); ?></p>
        </div>
        <div class="profile-info">
            <label>Role:</label>
            <p><?php echo htmlspecialchars($user['role']); ?></p>
        </div>
        <div class="courses">
            <h2>Enrolled Courses</h2>
            <ul>
                <?php while ($course = $courses->fetch_assoc()): ?>
                <li><?php echo htmlspecialchars($course['title']); ?></li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>

<?php
include 'footer.php';
?>