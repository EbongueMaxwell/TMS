<?php
session_start();
include 'dbconn.php';

// Redirect if not logged in as a trainer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header("Location: login.php");
    exit();
}

// Fetch trainer-specific data (e.g., courses)
$username = $_SESSION['username'] ?? '';
$stmt = $conn->prepare("SELECT * FROM courses WHERE trainer_username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$courses = $stmt->get_result(); // Get result set

// Handle form submission for selecting courses
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_courses'])) {
    $selectedCourses = $_POST['courses'] ?? []; // Get selected course IDs
    // Process selected courses (e.g., save to database or perform an action)
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
            width: 100%;
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
            left: -200px; /* Initially hidden */
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
            margin: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            height: 200px; /* Set a fixed height for the card */
            transition: transform 0.2s; /* Transition for hover effect */
        }

        .course-card:hover {
            transform: scale(1.05); /* Scale up on hover */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Enhance shadow on hover */
        }

        .course-image {
            width: 100%; /* Full width */
            height: 120px; /* Fixed height for the image */
            object-fit: cover; /* Cover the area while maintaining aspect ratio */
            border-radius: 8px 8px 0 0; /* Rounded corners only at the top */
        }

        .course-title {
            font-size: 16px; /* Adjusted size */
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

        .actions {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-top: 5px;
        }

        .actions input[type="checkbox"] {
            cursor: pointer;
            width: 20px; /* Adjust size as needed */
            height: 20px; /* Adjust size as needed */
            margin: 0; /* Remove default margin */
            display: inline-block; /* Ensure it behaves like a block */
        }

        /* Responsive design */
        @media (min-width: 600px) {
            .course-container {
                display: flex;
                flex-wrap: wrap;
            }

            .course-card {
                width: calc(33.333% - 20px); /* Three cards per row, adjust as needed */
            }
        }

        @media (max-width: 600px) {
            .course-card {
                width: calc(100% - 20px); /* Full width on small screens */
            }
        }
    </style>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.style.left = sidebar.style.left === '0px' ? '-200px' : '0px'; // Toggle sidebar
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
    <div class="container">
        <div class="sidebar">
            <h2>Trainer Menu</h2>
            <a href="trainerdash.php">Dashboard</a>
            <a href="create-course.php">Create Course</a>
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
                                <input type="checkbox" id="course-<?php echo $course['id']; ?>" name="courses[]" value="<?php echo $course['id']; ?>"> 
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div>No courses found.</div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="select_courses">Submit Selected Courses</button>
            </form>
        </div>
    </div>

    <?php
    $stmt->close(); // Close the statement
    $conn->close(); // Close the database connection
    ?>
</body>
</html>