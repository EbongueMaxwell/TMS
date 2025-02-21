<?php
include 'dbconn.php'; // Your database connection file
$username = 'Guest'; // Default username
$user_id = null; // Initialize user_id

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id']; // Get user ID from session
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $username = $user['username']; // Set username from database
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calea Portal</title>
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: Helvetica;
            display: flex;
            flex-direction: column;
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

        .welcome-message {
            margin-left: auto;
            font-size: 16px;
            font-weight: bold;
        }

        .search-bar {
            background-color: white;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 22px;
            width: 400px;
            margin-left: 15px;
            font-size: 16px;
        }

        .search-icon, .user-icon {
            cursor: pointer;
            font-size: 36px;
            margin-left: 15px;
            line-height: 55px;
        }

        .username-tooltip {
            display: none;
            position: absolute;
            top: 40px;
            left: 1300px;
            background-color: #333;
            color: #fff;
            padding: 3px;
            border-radius: 5px;
            z-index: 1000;
            font-size: 14px;
            white-space: nowrap;
        }

        .user-icon:hover .username-tooltip {
            display: block;
        }

        .sidebar {
            visibility: hidden; /* Initially hidden */
            opacity: 0; /* Fade effect */
            flex-direction: column; /* Stack links vertically */
            background-color: #B2E0D5;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: visibility 0s, opacity 0.3s ease; /* Smooth transition */
        }

        .sidebar.visible {
            visibility: visible; /* Make visible */
            opacity: 1; /* Fully opaque */
        }

        .sidebar a {
            margin: 5px 0; /* Space between links */
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #007bff;
            color: white;
        }

        .content {
            flex-grow: 1; /* Allow content area to grow */
            padding: 15px;
            background-color: #f1f1f1; /* Optional background for content area */
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

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('visible');
        }
    </script>
</head>
<body>

    <div class="header">
        <span class="menu-icon" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </span>
        <h2>Calea Portal</h2>
        <div class="welcome-message">Welcome, <?php echo htmlspecialchars($username); ?>!</div>
        <input type="text" id="search-bar" class="search-bar" placeholder="Search..." onkeypress="if(event.key === 'Enter') executeSearch()">
        <span class="search-icon" onclick="executeSearch()"><i class="fas fa-search"></i></span>
        <span class="user-icon">
            <i class="fas fa-user"></i>
            <div class="username-tooltip"><?php echo htmlspecialchars($username); ?></div>
        </span>
    </div>

    <div class="sidebar">
    <!----------<h2>Trainer Menu</h2>---------->
        <a href="trainerdash.php">Dashboard</a>
        <a href="mycourse.php">My Courses</a>
        <a href="trainerassign.php">Assignments</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="content">
        <!-- Page-specific content goes here -->
       

</body>
</html>