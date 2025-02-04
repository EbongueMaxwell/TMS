<?php
session_start();
include 'dbconn.php';

// Redirect if not admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Create course
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $credits = intval($_POST['credits']);
    $duration = intval($_POST['duration']);

    // Handle file upload
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/";
        $imageName = basename($_FILES['image']['name']);
        $imagePath = $targetDir . uniqid() . '_' . $imageName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            $errorMessage = "Error uploading image. Please try again.";
        }
    }

    // Basic validation
    if (!empty($title) && !empty($description) && $credits > 0 && $duration > 0) {
        $stmt = $conn->prepare("INSERT INTO courses (title, description, credits, duration, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $title, $description, $credits, $duration, $imagePath);
        if ($stmt->execute()) {
            $successMessage = "Course created successfully.";
        } else {
            $errorMessage = "Error creating course. Please try again.";
        }
        $stmt->close();
    } else {
        $errorMessage = "Please fill in all fields correctly.";
    }
}

// Read courses
$courses = $conn->query("SELECT * FROM courses");

// Update course
if (isset($_GET['edit'])) {
    $courseId = intval($_GET['edit']);
    $course = $conn->query("SELECT * FROM courses WHERE id = $courseId")->fetch_assoc();
}

// Update form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $courseId = intval($_POST['course_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $credits = intval($_POST['credits']);
    $duration = intval($_POST['duration']);

    // Handle file upload for update
    $imagePath = $course['image_url']; // Preserve existing image
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/";
        $imageName = basename($_FILES['image']['name']);
        $imagePath = $targetDir . uniqid() . '_' . $imageName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            $errorMessage = "Error uploading image. Please try again.";
        } else {
            // Delete the old image if a new one is uploaded
            if (file_exists($course['image_url'])) {
                unlink($course['image_url']);
            }
        }
    }

    // Basic validation
    if (!empty($title) && !empty($description) && $credits > 0 && $duration > 0) {
        $stmt = $conn->prepare("UPDATE courses SET title=?, description=?, credits=?, duration=?, image_url=? WHERE id=?");
        $stmt->bind_param("ssissi", $title, $description, $credits, $duration, $imagePath, $courseId);
        if ($stmt->execute()) {
            header("Location: manage-courses.php");
            exit();
        } else {
            $errorMessage = "Error updating course. Please try again.";
        }
        $stmt->close();
    } else {
        $errorMessage = "Please fill in all fields correctly.";
    }
}

// Delete course
if (isset($_GET['delete'])) {
    $courseId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $courseId);
    if ($stmt->execute()) {
        header("Location: manage-courses.php");
        exit();
    } else {
        $errorMessage = "Error deleting course. Please try again.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
    <link rel="stylesheet" href="fontawesome-free-6.4.0-web/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8ff;
            margin: 20px;
        }
        h1 {
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #007bff;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            border: 1px solid;
            border-radius: 5px;
        }
        .success {
            border-color: green;
            color: green;
            background-color: #e6ffed;
        }
        .error {
            border-color: red;
            color: red;
            background-color: #ffe6e6;
        }
        form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            color: #333;
        }
        input[type="text"], textarea, input[type="number"], input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        .actions a {
            margin-right: 10px;
            color: #007bff;
            text-decoration: none;
        }
        .actions a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Manage Courses</h1>

    <?php if (isset($successMessage)): ?>
        <div class="message success"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    <?php if (isset($errorMessage)): ?>
        <div class="message error"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <form method="POST" action="manage-courses.php" enctype="multipart/form-data">
        <input type="hidden" name="course_id" value="<?php echo isset($course) ? $course['id'] : ''; ?>">
        <label for="title">Course Title:</label>
        <input type="text" name="title" value="<?php echo isset($course) ? htmlspecialchars($course['title']) : ''; ?>" required>
        <label for="description">Description:</label>
        <textarea name="description" required><?php echo isset($course) ? htmlspecialchars($course['description']) : ''; ?></textarea>
        <label for="credits">Credits:</label>
        <input type="number" name="credits" value="<?php echo isset($course) ? htmlspecialchars($course['credits']) : ''; ?>" required min="1">
        <label for="duration">Duration (weeks):</label>
        <input type="number" name="duration" value="<?php echo isset($course) ? htmlspecialchars($course['duration']) : ''; ?>" required min="1">
        <label for="image">Course Image:</label>
        <input type="file" name="image" accept="image/*">
        <?php if (isset($course) && $course['image_url']): ?>
            <img src="<?php echo htmlspecialchars($course['image_url']); ?>" alt="Course Image" style="max-width: 100px; margin-top: 10px;">
        <?php endif; ?>
        <button type="submit" name="<?php echo isset($course) ? 'update' : 'create'; ?>">
            <?php echo isset($course) ? 'Update Course' : 'Create Course'; ?>
        </button>
    </form>

    <h2>Course List</h2>
    <table>
        <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Credits</th>
            <th>Duration (weeks)</th>
            <th>Image</th>
            <th>Actions</th>
        </tr>
        <?php while ($course = $courses->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($course['title']); ?></td>
            <td><?php echo htmlspecialchars($course['description']); ?></td>
            <td><?php echo htmlspecialchars($course['credits']); ?></td>
            <td><?php echo htmlspecialchars($course['duration']); ?></td>
            <td>
                <?php if ($course['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($course['image_url']); ?>" alt="Course Image" style="max-width: 100px;">
                <?php endif; ?>
            </td>
            <td class="actions">
                <a href="?edit=<?php echo $course['id']; ?>">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="?delete=<?php echo $course['id']; ?>" onclick="return confirm('Are you sure you want to delete this course?');">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>