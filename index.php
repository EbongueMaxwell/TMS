<?php
// Database connection (assuming dbconn.php contains the connection logic)
include 'dbconn.php';

session_start(); // Start session for storing messages

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

        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, qualifications, expertise) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            $_SESSION['error'] = "Error preparing statement: " . $conn->error;
        } else {
            // Bind parameters based on role
            if ($role === 'trainer') {
                $stmt->bind_param("sssss", $username, $hashed_password, $role, $qualifications, $expertise);
            } else {
                $stmt->bind_param("ssss", $username, $hashed_password, $role, null); // No qualifications or expertise for non-trainers
            }

            // Execute the statement
            if ($stmt->execute()) {
                $_SESSION['success'] = "Registration successful!";
                header("Location: login.php"); // Redirect after successful registration
                exit();
            } else {
                $_SESSION['error'] = "Error: " . $stmt->error;
            }
            $stmt->close();
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
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"], select {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #218838;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
        @media (max-width: 500px) {
            .container {
                width: 90%; /* Make container responsive */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>WELCOME TO CALEA</h1>
        <?php
        // Display error or success messages
        if (isset($_SESSION['error'])) {
            echo "<div class='error'>{$_SESSION['error']}</div>";
            unset($_SESSION['error']); // Clear error after displaying
        }
        if (isset($_SESSION['success'])) {
            echo "<div class='success'>{$_SESSION['success']}</div>";
            unset($_SESSION['success']); // Clear success after displaying
        }
        ?>
        <form method="POST">
            <h2>Enter Your Info</h2>
            <label for="username">Username:</label>
            <input type="text" name="username" placeholder="Username" required>

            <label for="password">Password:</label>
            <input type="password" name="password" placeholder="Password" required>

            <label for="role">Choose Your Status:</label>
            <select name="role" required>
                <option value="trainee">Trainee</option>
                <option value="trainer">Trainer</option>
                <option value="admin">Admin</option>
            </select>

            <div id="trainer-fields" style="display:none;">
                <label for="qualifications">Qualifications:</label>
                <input type="text" name="qualifications" placeholder="Qualifications" required>

                <label for="expertise">Areas of Expertise:</label>
                <input type="text" name="expertise" placeholder="Areas of Expertise" required>
            </div>

            <button type="submit">Register</button>
            <p>Have an account? | <a href="login.php">Login</a></p>
        </form>
    </div>

    <script>
        // Show/Hide trainer-specific fields based on role selection
        document.getElementById('role').addEventListener('change', function() {
            const trainerFields = document.getElementById('trainer-fields');
            if (this.value === 'trainer') {
                trainerFields.style.display = 'block';
            } else {
                trainerFields.style.display = 'none';
            }
        });
    </script>
</body>
</html>