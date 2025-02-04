<?php
session_start();
include 'dbconn.php'; // Assuming your connection code is in this file

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields.";
    } else {
        // Query the user
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        if (!$stmt) {
            $_SESSION['error'] = "Database error. Please try again later.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Regenerate session ID
                    session_regenerate_id(true);
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];

                    // Redirect based on user role
                    header("Location: " . ($_SESSION['role'] === 'admin' ? "admindash.php" : "trainerdash.php"));
                    exit();
                } else {
                    $_SESSION['error'] = "Invalid password.";
                }
            } else {
                $_SESSION['error'] = "User not found.";
            }

            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
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
        input[type="text"], input[type="password"] {
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
        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>WELCOME BACK TO CALEA</h1>
        <?php
        // Display error messages
        if (isset($_SESSION['error'])) {
            echo "<div class='error'>{$_SESSION['error']}</div>";
            unset($_SESSION['error']); // Clear the error after displaying
        }
        ?>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">

            <label for="password">Password:</label>
            <input type="password" name="password" placeholder="Password" required>

            <button type="submit">Login</button>
            <p>Don't have an account? | <a href="index.php">Register</a></p>
        </form>
    </div>
</body>
</html>