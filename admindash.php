<?php
session_start();
include 'dbconn.php'; // Your database connection file
include 'adminheader.php'; // Include the header and sidebar

// Fetch enrolled courses and their objectives
$enrolledCourses = [];
$stmtEnrollments = $conn->prepare("
    SELECT c.*, co.course_objectives, co.content_objectives, co.file 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    LEFT JOIN course_objectives co ON c.id = co.course_id 
    WHERE e.trainee_id = ?
");
$stmtEnrollments->bind_param("i", $_SESSION['user_id']); // Assuming user ID is stored in the session
$stmtEnrollments->execute();
$resultEnrollments = $stmtEnrollments->get_result();
while ($course = $resultEnrollments->fetch_assoc()) {
    $courseId = $course['id'];
    if (!isset($enrolledCourses[$courseId])) {
        $enrolledCourses[$courseId] = [
            'course' => $course,
            'objectives' => []
        ];
    }
    // Collect course and content objectives if they exist
    if (!empty($course['course_objectives'])) {
        $enrolledCourses[$courseId]['objectives'][] = $course['course_objectives'];
    }
    if (!empty($course['content_objectives'])) {
        $enrolledCourses[$courseId]['objectives'][] = $course['content_objectives'];
    }
}
$stmtEnrollments->close();

// Fetch all users
$stmtUsers = $conn->prepare("SELECT username, role FROM users");
$stmtUsers->execute();
$users = $stmtUsers->get_result();

// Function to send notification
function sendNotification($conn, $userId, $action) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $action);
    $stmt->execute();
    $stmt->close();
}

// Check if a course detail is toggled
if (isset($_POST['courseId'])) {
    $courseId = $_POST['courseId'];
    $userId = $_SESSION['user_id'];
    sendNotification($conn, $userId, "Toggled details for course ID: $courseId");
    // Optionally you can return a response
    echo json_encode(['status' => 'success']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .content {
            padding: 20px;
            margin-left: 0;
            transition: margin-left 0.3s;
            background-color: whitesmoke;
        }

        .course-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px; /* Space between course cards */
        }

        .course-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 25px;
            margin: 10px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center; /* Center text */
            cursor: pointer; /* Indicate clickable */
            width: 300px; /* Fixed width for cards */
        }

        .course-card h3 {
            margin: 10px 0 5px; /* Spacing */
            font-size: 18px;
        }

        .course-card p {
            margin: 5px 0;
            color: #555;
        }

        .course-details {
            display: none; /* Initially hide details */
            margin: 10px 0;
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 5px;
        }

        /* User Table */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .user-table th, .user-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .user-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
    <script>
        function executeSearch() {
            const searchBar = document.getElementById('search-bar');
            const searchValue = searchBar.value.trim();
            if (searchValue) {
                window.location.href = 'search_results.php?q=' + encodeURIComponent(searchValue);
            } else {
                alert("Please enter a search term.");
            }
        }

        function toggleDetails(courseId) {
            const details = document.getElementById('details-' + courseId);
            if (details.style.display === 'none' || details.style.display === '') {
                details.style.display = 'block'; // Show details
                // Send notification to admin
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'courseId=' + courseId
                });
            } else {
                details.style.display = 'none'; // Hide details
            }
        }
    </script>
</head>
<body>

    <div class="content">
        <h1>Admin Dashboard</h1>
        <?php if (!empty($enrolledCourses)): ?>
            <div class="course-container">
                <?php foreach ($enrolledCourses as $courseId => $courseData): ?>
                    <div class="course-card" onclick="toggleDetails(<?php echo $courseId; ?>)">
                        <h3><?php echo htmlspecialchars($courseData['course']['title']); ?></h3>
                        <p><?php echo htmlspecialchars($courseData['course']['description']); ?></p>
                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($courseData['course']['duration']); ?> weeks</p>
                        <p><strong>Status:</strong> Enrolled</p>
                        <?php if (!empty($courseData['course']['file'])): ?>
                            <p>
                                <strong>Document:</strong>
                                <a href="<?php echo htmlspecialchars($courseData['course']['file']); ?>" target="_blank">Download</a>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="course-details" id="details-<?php echo $courseId; ?>">
                        <p><strong>Credits:</strong> <?php echo htmlspecialchars($courseData['course']['credits']); ?></p>
                        <p><strong>Course Objectives:</strong></p>
                        <ul>
                            <?php foreach ($courseData['objectives'] as $objective): ?>
                                <li><?php echo htmlspecialchars($objective); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Welcome to the Dashboard.</p>
        <?php endif; ?>

        <h1>All Users</h1>
        <table class="user-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users->num_rows > 0): ?>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $stmtUsers->close();
    $conn->close();
    ?>
</body>
</html>

<?php
include 'footer.php';
?>