<?php
session_start();
include 'dbconn.php'; // Your database connection code

// Redirect if not logged in as a trainer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? '';

// Handle form submission for selected course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $courseId = $_POST['course_id'];

    // Insert into teacher_courses table
    $stmt = $conn->prepare("INSERT INTO teacher_courses (course_id, teacher_username) VALUES (?, ?)");
    $stmt->bind_param("is", $courseId, $username);
    $stmt->execute();

    // Redirect to avoid resubmission
    header("Location: trainerdash.php");
    exit();
}

// Handle objectives submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['objectives'])) {
    echo '<pre>';
    print_r($_POST);
    print_r($_FILES);
    echo '</pre>';
    exit; // Stop execution to see the output

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
    $stmt = $conn->prepare("INSERT INTO course_objectives (course_id, objectives, content_objectives, documents) VALUES (?, ?, ?, ?)");
    $testCourseId = 1; // Example course ID
    $testObjectives = "Test objectives";
    $testContentObjectives = "Test content objectives";
    $testDocuments = json_encode([]);
    $stmt->bind_param("isss", $testCourseId, $testObjectives, $testContentObjectives, $testDocuments);
    $stmt->execute();
    // Redirect to avoid resubmission
    header("Location: trainerdash.php");
    exit();
}

// Fetch trainer-specific courses
$stmt = $conn->prepare("SELECT * FROM courses WHERE trainer_username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$courses = $stmt->get_result(); // Get result set
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
        /* Add your styles here */
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

        .course-container {
            display: flex; /* Use flexbox for horizontal alignment */
            flex-wrap: wrap; /* Allow wrapping to the next line if needed */
            justify-content: space-between; /* Space out the cards */
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
            height: 200px; /* Decreased height for a smaller card */
            width: calc(30% - 20px); /* Set width to 30% minus margin for three cards in a row */
            transition: transform 0.2s; /* Transition for hover effect */
        }

        .course-card:hover {
            transform: scale(1.05); /* Scale up on hover */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Enhance shadow on hover */
        }

        .course-image {
            width: 100%; /* Full width */
            height: 100px; /* Fixed height for the image */
            object-fit: cover; /* Cover the area while maintaining aspect ratio */
            border-radius: 8px 8px 0 0; /* Rounded corners only at the top */
        }

        .course-title {
            font-size: 14px; /* Adjusted size */
            font-weight: bold;
            margin: 5px 0;
        }

        .course-description {
            font-size: 12px; /* Adjusted size */
            text-align: center;
            margin: 5px 0;
            flex-grow: 1; /* Allow description to take available space */
        }

        .course-duration {
            font-size: 12px; /* Adjusted size */
            margin: 5px 0;
            color: #666; /* Lighter color */
        }

        .submit-button {
            background-color: #007bff; /* Primary button color */
            color: white; /* Button text color */
            border: none; /* No border */
            border-radius: 5px; /* Rounded corners */
            padding: 10px 20px; /* Padding for the button */
            cursor: pointer; /* Pointer cursor */
            margin-top: 20px; /* Spacing above the button */
            font-size: 16px; /* Font size for the button */
            transition: background-color 0.3s, transform 0.3s; /* Transition for hover effect */
        }

        .submit-button:hover {
            background-color: #0056b3; /* Darker shade on hover */
            transform: scale(1.05); /* Slight scale effect on hover */
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

        #objectivesForm .cancel-button {
            background-color: #dc3545;
            margin-top: 10px;
        }

        #objectivesForm .cancel-button:hover {
            background-color: #c82333;
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

        function openObjectivesForm() {
            const checkedCourse = document.querySelector('input[name="course_id"]:checked');
            if (!checkedCourse) {
                alert("Please select a course.");
                return;
            }
            document.getElementById("selected-course-id").value = checkedCourse.value; // Set the selected course ID
            document.getElementById("objectivesForm").style.display = "block"; // Show the objectives form
        }

        function closeObjectivesForm() {
            document.getElementById("objectivesForm").style.display = "none"; // Hide the objectives form
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

    <div class="container">
        <div class="sidebar">
            <h2>Trainer Menu</h2>
            <a href="trainerdash.php">Dashboard</a>
            <a href="mycourse.php">My Courses</a>
            <a href="logout.php">Logout</a>
        </div>

        <div class="content">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>

            <h2>Select Courses to Teach</h2>
            <form method="POST" action="trainerdash.php">
                <div class="course-container">
                    <?php if ($courses->num_rows > 0): ?>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                        <div class="course-card">
                            <img src="<?php echo htmlspecialchars($course['image_url']); ?>" alt="Course Image" class="course-image">
                            <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
                            <div class="course-description"><?php echo htmlspecialchars($course['description']); ?></div>
                            <div class="course-duration">Duration: <?php echo htmlspecialchars($course['duration']); ?> weeks</div>
                            <div class="actions">
                                <input type="radio" id="course-<?php echo $course['id']; ?>" name="course_id" value="<?php echo $course['id']; ?>"> 
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div>No courses found.</div>
                    <?php endif; ?>
                </div>
                <button type="button" class="submit-button" onclick="openObjectivesForm()">Submit Selected Course</button>
            </form>

            <!-- Objectives Form -->
            <div id="objectivesForm" style="display: none;">
                <h2>Course Objectives</h2>
                <form method="POST" action="trainerdash.php" enctype="multipart/form-data">
                    <input type="hidden" id="selected-course-id" name="course_id">
                    <label for="course-objectives">Objectives:</label>
                    <textarea id="course-objectives" name="objectives" rows="4" required></textarea>
                    
                    <label for="content-objectives">Content Objectives:</label>
                    <textarea id="content-objectives" name="content_objectives" rows="4" required></textarea>
                    
                    <label for="documents">Upload Documents:</label>
                    <input type="file" id="documents" name="documents[]" multiple required>
                    
                    <button type="submit">Submit</button>
                    <button type="button" class="cancel-button" onclick="closeObjectivesForm()">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <?php
    $stmt->close(); // Close the statement
    $conn->close(); // Close the database connection
    ?>
</body>
</html>