<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>footer</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }

        .content {
            flex: 1;
        }

        .footer {
            background-color: #343a40;
            flex: 1;
            color: white;
            text-align: center;
            padding: 20px 0;
            width: 100%;
        }

        .footer p {
            margin: 5px 0;
        }

        .footer a {
            color: #007bff; /* Link color */
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline; /* Underline on hover */
        }
    </style>
</head>
<body>
    <div class="content">
        <!-- Page content goes here -->
    </div>

    <div class="footer">
        <p>&copy; <?php echo date("Y"); ?> Calea Portal. All rights reserved.</p>
        <p>
            <a href="privacy.php">Privacy Policy</a> | 
            <a href="terms.php">Terms of Service</a>
        </p>
    </div>
</body>
</html>
