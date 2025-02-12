<?php
session_start();
include 'dbconn.php'; // Your database connection code

// Redirect if not logged in as a trainer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? null;

// Fetch courses and their corresponding objectives
$stmt = $conn->prepare("
    SELECT c.id, c.title, c.description, co.objectives, co.content_objectives, co.documents 
    FROM courses c
    LEFT JOIN course_objectives co ON c.id = co.course_id
    JOIN teacher_courses tc ON c.id = tc.course_id
    WHERE tc.teacher_username = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result(); // Get result set
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <link rel="stylesheet" href="styles.css"> <!-- Link to your main CSS file -->
    <style>
        /* Basic styles for mycourses page */
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
            position: relative;
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

        .search-icon, .user-icon, .menu-icon {
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
            background-color: #343a40; /* Dark background */
            color: white; /* White text */
            transition: left 0.3s;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 15px;
        }

        .sidebar h2 {
            color: #ffffff; /* Title color */
            margin: 0 0 20px 0; /* Spacing below title */
            font-size: 24px; /* Title size */
        }

        .sidebar a {
            display: block;
            padding: 10px 15px;
            color: #ffffff; /* White text */
            text-decoration: none;
            border-radius: 4px;
            margin: 5px 0;
            transition: background-color 0.3s, color 0.3s; /* Transition for hover effect */
        }

        .sidebar a:hover {
            background-color: #495057; /* Darker background on hover */
            color: #ffffff; /* Keep text white on hover */
        }

        .content {
            padding: 20px;
            margin-left: 220px; /* Adjusted for sidebar width */
        }

        .course-card {
            border: 1px solid #ccc;
            border-radius: 8px;
            margin: 10px 0;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .course-title {
            font-size: 20px;
            font-weight: bold;
        }

        .course-description, .objectives {
            font-size: 14px;
            margin: 5px 0;
        }

        .documents {
            margin-top: 10px;
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

        function executeSearch() {
            const searchBar = document.getElementById('search-bar');
            const searchValue = searchBar.value.trim();
            if (searchValue) {
                window.location.href = 'search_results.php?q=' + encodeURIComponent(searchValue);
            } else {
                alert("Please enter a search term.");
            }
        }
    </script>
</head>
<body>

    <div class="header">
        <span class="menu-icon" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </span>
        <h2>Calea Portal</h2>
        <input type="text" id="search-bar" class="search-bar" placeholder="Search..." onkeypress="if(event.key === 'Enter') executeSearch()">
        <span class="search-icon" onclick="executeSearch()"><i class="fas fa-search"></i></span>
        <span class="user-icon" onclick="toggleUserTooltip()">
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
        <h1>My Courses</h1>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($course = $result->fetch_assoc()): ?>
            <div class="course-card">
                <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
                <div class="course-description"><?php echo htmlspecialchars($course['description']); ?></div>
                
                <div class="objectives">
                    <strong>Objectives:</strong>
                    <p><?php echo nl2br(htmlspecialchars($course['objectives'])); ?></p>
                </div>

                <div class="objectives">
                    <strong>Content Objectives:</strong>
                    <p><?php echo nl2br(htmlspecialchars($course['content_objectives'])); ?></p>
                </div>

                <div class="documents">
                    <strong>Uploaded Documents:</strong>
                    <?php
                    $documents = json_decode($course['documents'], true);
                    if (!empty($documents)): ?>
                        <?php foreach ($documents as $document): ?>
                            <a href="<?php echo htmlspecialchars($document); ?>" class="document-link" target="_blank">
                                <?php echo htmlspecialchars(basename($document)); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No documents uploaded.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No courses found.</p>
        <?php endif; ?>

        <?php
        $stmt->close(); // Close the statement
        $conn->close(); // Close the database connection
        ?>
    </div>
</body>
</html>