<?php
session_start();
include 'dbconn.php'; // Assuming your connection code is in this file

// Fetch username from the users table based on user ID
$username = 'Guest'; // Default to 'Guest'
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id']; // Assuming user ID is stored in the session
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $username = $user['username']; // Set the username from the database
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainee Dashboard</title>
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
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
            margin: 0 15px; /* Ensure consistent spacing */
        }

        .welcome-message {
            margin-left: auto; /* Align to the right */
            font-size: 16px; /* Font size */
            font-weight: bold; /* Bold text */
        }

        .search-bar {
            display: block; /* Always visible */
            background-color: white;
            border: 1px solid #ccc;
            padding: 10px; /* Increased padding for height */
            border-radius: 22px;
            width: 400px; /* Adjust width as needed */
            margin-left: 15px; /* Space between search bar and icons */
            font-size: 16px; /* Increase font size */
        }

        .search-icon {
            cursor: pointer;
            font-size: 36px; /* Increased font size for the search icon */
            margin-left: 15px; /* Space between icon and search bar */
            line-height: 55px; /* Center vertically */
        }

        .user-icon {
            cursor: pointer;
            font-size: 36px; /* Increased font size for user icon */
            position: relative;
            margin-left: 20px; /* Space between icons */
        }

        .username-tooltip {
            display: none;
            position: absolute;
            right: 0;
            top: 100%; /* Position below the icon */
            background-color: white;
            border: 1px solid #ccc;
            padding: 5px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .user-icon:hover .username-tooltip {
            display: block; /* Show on hover */
        }

        .sidebar {
            height: 100%;
            width: 200px;
            position: fixed;
            left: -300px; /* Initially hidden */
            background-color: #f9f9f9;
            transition: left 0.3s;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 15px; /* Add padding around sidebar items */
        }

        .sidebar a {
            display: block; /* Make links block elements */
            padding: 10px 15px; /* Add padding for links */
            color: #333; /* Text color */
            text-decoration: none; /* Remove underline */
            border-radius: 4px; /* Rounded corners */
            margin: 5px 0; /* Spacing between links */
            transition: background-color 0.3s; /* Smooth background color transition */
        }

        .sidebar a:hover {
            background-color: #007bff; /* Highlight on hover */
            color: white; /* Change text color on hover */
        }

        .content {
            padding: 20px;
            transition: margin-left 0.3s;
            margin-left: 0; /* Initial margin when sidebar is hidden */
        }

        .show-sidebar .sidebar {
            left: 0; /* Show the sidebar */
        }

        .show-sidebar .content {
            margin-left: 200px; /* Shift content to the right when sidebar is shown */
        }
    </style>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const body = document.body;
            if (sidebar.style.left === '0px') {
                sidebar.style.left = '-300px'; // Hide sidebar
                body.classList.remove('show-sidebar');
            } else {
                sidebar.style.left = '0px'; // Show sidebar
                body.classList.add('show-sidebar');
            }
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
            <i class="fas fa-bars"></i> <!-- Menu icon -->
        </span>
        <h2>Calea Portal</h2><!-- Header title -->
        <div class="welcome-message">Welcome, <?php echo htmlspecialchars($username); ?>!</div> <!-- Welcome message -->
        <input type="text" id="search-bar" class="search-bar" placeholder="Search..." onkeypress="if(event.key === 'Enter') executeSearch()">
        <span class="search-icon" onclick="executeSearch()"><i class="fas fa-search"></i></span> <!-- Search icon -->
        <span class="user-icon">
            <i class="fas fa-user"></i> <!-- User icon -->
            <div class="username-tooltip"><?php echo htmlspecialchars($username); ?></div> <!-- Username tooltip -->
        </span>
    </div>

    <div id="sidebar" class="sidebar">
        <a href="my-courses.php">My Courses</a>
        <a href="attendance.php">Track Attendance</a>
        <a href="reports.php">View Reports</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="content">
        <h1>Trainee Dashboard</h1>
        <p>Welcome to the trainee dashboard. Use the menu to navigate.</p>
    </div>

</body>
</html>