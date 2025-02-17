<?php
// Database connection (assuming dbconn.php contains the connection logic)
include 'dbconn.php';

session_start(); // Start session for storing messages

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $qualifications = isset($_POST['qualifications']) ? trim($_POST['qualifications']) : null;
    $expertise = isset($_POST['expertise']) ? trim($_POST['expertise']) : null;

    // Validate username
    if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $_SESSION['error'] = "Invalid username. Must be 3-20 characters long and contain only letters, numbers, or underscores.";
    } elseif (empty($password) || strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
    } elseif ($role === 'trainer' && (empty($qualifications) || empty($expertise))) {
        $_SESSION['error'] = "Qualifications and areas of expertise are required for trainers.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if the username already exists in the users table
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Username already taken. Please choose another.";
        } else {
            // Insert into users table for all roles
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, qualifications, expertise) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                $_SESSION['error'] = "Error preparing statement: " . $conn->error;
            } else {
                if ($role === 'trainer') {
                    $stmt->bind_param("sssss", $username, $hashed_password, $role, $qualifications, $expertise);
                } else {
                    // For trainees and admins, set qualifications and expertise to NULL
                    $qualifications_param = null; // or use "" for an empty string
                    $expertise_param = null; // or use "" for an empty string
                    $stmt->bind_param("sssss", $username, $hashed_password, $role, $qualifications_param, $expertise_param);
                }
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Registration successful!";
                    session_regenerate_id(true);
                    // Redirect to login page for all users
                    header("Location: login.php"); 
                    exit();
                } else {
                    $_SESSION['error'] = "Error executing statement: " . $stmt->error;
                    // Debugging: Print the error
                    error_log("Error: " . $stmt->error);
                }
            }
        }
    }
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 { text-align: center; color: #333; }
        form { display: flex; flex-direction: column; }
        label { margin-bottom: 8px; font-weight: bold; }
        input[type="text"], input[type="password"], select {
            padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px;
        }
        button {
            padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
        }
        button:hover { background-color: #218838; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
        @media (max-width: 500px) {
            .container { width: 90%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>WELCOME TO CALEA</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='error'>{$_SESSION['error']}</div>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo "<div class='success'>{$_SESSION['success']}</div>";
            unset($_SESSION['success']);
        }
        ?>
        <form method="POST">
            <h2>Enter Your Info</h2>
            <label for="username">Username:</label>
            <input type="text" name="username" placeholder="Username" required>

            <label for="password">Password:</label>
            <input type="password" name="password" placeholder="Password" required>

            <label for="role">Choose Your Status:</label>
            <select name="role" id="role" required>
                <option value="trainee">Trainee</option>
                <option value="trainer">Trainer</option>
                <option value="admin">Admin</option>
            </select>

            <div id="trainer-fields" style="display:none;">
                <label for="qualifications">Qualifications:</label>
                <input type="text" name="qualifications" placeholder="Qualifications">

                <label for="expertise">Areas of Expertise:</label>
                <input type="text" name="expertise" placeholder="Areas of Expertise" >
            </div>

            <button type="submit">Register</button>
            <p>Have an account? | <a href="login.php">Login</a></p>
        </form>
    </div>

    <script>
        document.getElementById('role').addEventListener('change', function() {
            const trainerFields = document.getElementById('trainer-fields');
            trainerFields.style.display = this.value === 'trainer' ? 'block' : 'none';
        });
    </script>
</body>
</html>