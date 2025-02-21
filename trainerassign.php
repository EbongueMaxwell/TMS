<?php
session_start();
include 'dbconn.php'; // Your database connection file
include 'trainerheader.php'; // Include the trainer header file

// Fetch the current trainer's username from the database or session
$username = '';
if (isset($_SESSION['user_id'])) {
    $stmtUser = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmtUser->bind_param("i", $_SESSION['user_id']);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    if ($row = $resultUser->fetch_assoc()) {
        $username = htmlspecialchars($row['username']); // Sanitize output
    }
    $stmtUser->close();
}

// Fetch assignments submitted by trainees
$assignments = [];
$stmtAssignments = $conn->prepare("
    SELECT a.id, a.file_path, a.submission_date, c.title AS course_title, u.username 
    FROM assignments a 
    JOIN courses c ON a.course_id = c.id 
    JOIN users u ON a.trainee_id = u.id 
    ORDER BY a.submission_date DESC
");
$stmtAssignments->execute();
$resultAssignments = $stmtAssignments->get_result();
while ($assignment = $resultAssignments->fetch_assoc()) {
    $assignments[] = $assignment; // Add each assignment to the assignments array
}
$stmtAssignments->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitted Assignments</title>
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: whitesmoke;
            margin: 0;
           
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .content {
            padding: 20px;
            margin-left: 0;
            transition: margin-left 0.3s;
            background-color: whitesmoke;
        }
        .assignment-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .assignment-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .assignment-card p {
            margin: 5px 0;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="content">
    <h1>Submitted Assignments</h1>
    <div class="assignment-container">
        <?php if (!empty($assignments)): ?>
            <?php foreach ($assignments as $assignment): ?>
                <div class="assignment-card">
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_title']); ?></p>
                    <p><strong>Trainee:</strong> <?php echo htmlspecialchars($assignment['username']); ?></p>
                    <p><strong>Submission Date:</strong> <?php echo htmlspecialchars($assignment['submission_date']); ?></p>
                    <p>
                        <strong>Assignment File:</strong> 
                        <a href="<?php echo htmlspecialchars($assignment['file_path']); ?>" target="_blank" class="btn">Download</a>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No assignments have been submitted yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

<?php
include 'footer.php';
?>