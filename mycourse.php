<?php
session_start();
include 'dbconn.php'; // Your database connection code

// Redirect if not logged in as a trainer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? null;

// Check if course_id is set in the URL
if (isset($_GET['course_id'])) {
    $courseId = $_GET['course_id'];

    // Fetch the specific course and its corresponding objectives
    $stmt = $conn->prepare("
        SELECT c.id, c.title, c.description, co.objectives, co.content_objectives, co.documents 
        FROM courses c
        LEFT JOIN course_objectives co ON c.id = co.course_id
        WHERE c.id = ? AND EXISTS (
            SELECT 1 FROM teacher_courses tc WHERE tc.course_id = c.id AND tc.teacher_username = ?
        )
    ");
    $stmt->bind_param("is", $courseId, $username);
    $stmt->execute();
    $result = $stmt->get_result(); // Get result set

    // Check if the course was found
    if ($result->num_rows > 0) {
        $course = $result->fetch_assoc(); // Fetch course data
    } else {
        // Redirect to trainer dashboard if no course is found
        header("Location: trainerdash.php");
        exit();
    }

    // If course is found, handle form submission for objectives
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
        $courseId = $_POST['course_id'];
        $objectives = $_POST['objectives'];
        $contentObjectives = $_POST['content_objectives'];

        // Handle file upload
        $documents = [];
        if (!empty($_FILES['documents']['name'][0])) {
            $uploadsDir = 'uploads/';
            foreach ($_FILES['documents']['name'] as $key => $name) {
                $tmpName = $_FILES['documents']['tmp_name'][$key];
                $filePath = $uploadsDir . basename($name);
                if (move_uploaded_file($tmpName, $filePath)) {
                    $documents[] = $filePath; // Store the file path
                }
            }
        }

        // Insert into course_objectives table
        $stmtInsert = $conn->prepare("INSERT INTO course_objectives (course_id, objectives, content_objectives, documents) VALUES (?, ?, ?, ?)");
        $stmtInsert->bind_param("isss", $courseId, $objectives, $contentObjectives, json_encode($documents));
        $stmtInsert->execute();

        // Redirect after submission
        header("Location: mycourse.php?course_id=" . $courseId);
        exit();
    }
} else {
    // Redirect to trainer dashboard if no course_id is provided
    header("Location: trainerdash.php");
    exit();
}
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
        /* Basic styles for my courses page */
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

        .course-description {
            font-size: 14px;
            margin: 5px 0;
        }

        .objectives {
            margin: 10px 0;
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

        /* Objectives Form Styles */
        #objectivesForm {
            background-color: #f8f9fa;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        #objectivesForm h2 {
            margin-top: 0;
            color: #007bff;
        }

        #objectivesForm label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }

        #objectivesForm textarea,
        #objectivesForm input[type="file"] {
            width: 100%;
            padding: 1px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        #objectivesForm button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
        }

        #objectivesForm button:hover {
            background-color: #0056b3;
            transform: scale(1.05);
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
        <h1>My Course</h1>

        <?php if (isset($course)): ?>
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

            <!-- Objectives Form -->
            <div id="objectivesForm">
                <h2>Add Course Objectives</h2>
                <form method="POST" action="mycourse.php" enctype="multipart/form-data">
                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
                    <label for="course-objectives">Objectives:</label>
                    <textarea id="course-objectives" name="objectives" rows="4" required></textarea>
                    
                    <label for="content-objectives">Content Objectives:</label>
                    <textarea id="content-objectives" name="content_objectives" rows="4" required></textarea>
                    
                    <label for="documents">Upload Documents:</label>
                    <input type="file" id="documents" name="documents[]" multiple>
                    
                    <button type="submit">Submit Objectives</button>
                </form>
            </div>
        <?php else: ?>
            <p>No course found.</p>
        <?php endif; ?>

        <?php
        $stmt->close(); // Close the statement
        $conn->close(); // Close the database connection
        ?>
    </div>
</body>
</html>