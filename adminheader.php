<?php
session_start();
include 'dbconn.php'; // Your database connection file

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
    <title>Calea Portal</title>
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: Helvetica;
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
            height: 100%;
            width: 140px;
            position: fixed;
            left: -300px;
            background-color: #f9f9f9;
            transition: left 0.3s;
            padding: 15px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar a {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px 0;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #007bff;
            color: white;
        }

        .show-sidebar .content {
            margin-left: 180px;
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
            const sidebar = document.getElementById('sidebar');
            const body = document.body;
            if (sidebar.style.left === '0px') {
                sidebar.style.left = '-300px';
                body.classList.remove('show-sidebar');
            } else {
                sidebar.style.left = '0px';
                body.classList.add('show-sidebar');
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
        <div class="welcome-message">Welcome, <?php echo htmlspecialchars($username); ?>!</div>
        <input type="text" id="search-bar" class="search-bar" placeholder="Search..." onkeypress="if(event.key === 'Enter') executeSearch()">
        <span class="search-icon" onclick="executeSearch()"><i class="fas fa-search"></i></span>
        <span class="user-icon">
            <i class="fas fa-user"></i>
            <div class="username-tooltip"><?php echo htmlspecialchars($username); ?></div>
        </span>
    </div>

    <div id="sidebar" class="sidebar">
        <a href="admindash.php">Dashboard</a>
        <a href="manage-courses.php">My Courses</a>
        <a href="reports.php">View Reports</a>
        <a href="logout.php">Logout</a>
    </div>

</body>
</html>
