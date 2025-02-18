<?php
include 'traineeheader.php'; // Include the header and sidebar
include 'dbconn.php'; // Your database connection file

// Fetch all available courses
$courses = [];
$stmtCourses = $conn->prepare("SELECT * FROM courses");
$stmtCourses->execute();
$resultCourses = $stmtCourses->get_result();
while ($course = $resultCourses->fetch_assoc()) {
    $courses[] = $course; // Add each course to the courses array
}
$stmtCourses->close();
?>

<div class="content">
    <h1>Trainee Dashboard</h1>
    <p>Welcome to the trainee dashboard.<br> Here you have the opportunity to learn and gain new skills.<br>
    Take advantage of the best free learning available on this platform to upgrade your knowledge.</p>

    <h1>Note</h1>
    <p>Note: If you register for a course, make sure to terminate it before registering for a new course.</p>

    <h2>Available Courses</h2>
    <div class="course-container">
        <?php if (!empty($courses)): ?>
            <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <img src="<?php echo htmlspecialchars($course['image_url']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?> Image"> <!-- Course image -->
                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                    <p><?php echo htmlspecialchars($course['description']); ?></p>
                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($course['duration']); ?> weeks</p>
                    <a href="?course_id=<?php echo $course['id']; ?>" class="btn">Enroll</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No courses available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<style>
    /* CSS styles for course cards and layout */
    .content {
        padding: 20px;
        background-color: whitesmoke;
        transition: margin-left 0.3s;
    }

    .course-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px; /* Space between course cards */
    }

    .course-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin: 10px 0;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        max-width: 300px; /* Set a maximum width for the card */
        text-align: center; /* Center text */
    }

    .course-card img {
        max-width: 100%; /* Responsive image */
        height: auto; /* Maintain aspect ratio */
        border-radius: 4px; /* Slightly rounded corners */
    }

    .course-card h3 {
        margin: 10px 0 5px; /* Spacing */
        font-size: 18px;
    }

    .course-card p {
        margin: 5px 0;
        color: #555;
    }

    .btn {
        background-color: #007bff; /* Button color */
        color: white; /* Button text color */
        padding: 10px;
        width: 70px;
        text-align: center;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s;
    }

    .btn:hover {
        background-color: #0056b3; /* Darker shade on hover */
    }
</style>

<?php
// Enrollment logic
if (isset($_GET['course_id']) && isset($_SESSION['user_id'])) {
    $courseId = $_GET['course_id'];
    $userId = $_SESSION['user_id'];

    // Check if the trainee is already enrolled in another course
    $stmt = $conn->prepare("SELECT c.duration, e.enrollment_date FROM enrollments e 
                             JOIN courses c ON e.course_id = c.id 
                             WHERE e.trainee_id = ? 
                             ORDER BY e.enrollment_date DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $enrollment = $result->fetch_assoc();
        $duration = $enrollment['duration'];
        $enrollmentDate = new DateTime($enrollment['enrollment_date']);
        $endDate = clone $enrollmentDate;
        $endDate->modify("+$duration weeks");

        $now = new DateTime();
        if ($now < $endDate) {
            echo "<script>alert('You cannot enroll in another course until " . $endDate->format('Y-m-d') . ".');</script>";
        } else {
            // Proceed with enrollment
            $stmtEnroll = $conn->prepare("INSERT INTO enrollments (trainee_id, course_id) VALUES (?, ?)");
            $stmtEnroll->bind_param("ii", $userId, $courseId);
            if ($stmtEnroll->execute()) {
                header("Location: traineecourse.php"); // Redirect to My Courses
                exit();
            } else {
                echo "<script>alert('Error enrolling in the course.');</script>";
            }
            $stmtEnroll->close();
        }
    } else {
        // No current enrollments, proceed with enrollment
        $stmtEnroll = $conn->prepare("INSERT INTO enrollments (trainee_id, course_id) VALUES (?, ?)");
        $stmtEnroll->bind_param("ii", $userId, $courseId);
        if ($stmtEnroll->execute()) {
            header("Location: traineecourse.php"); // Redirect to My Courses
            exit();
        } else {
            echo "<script>alert('Error enrolling in the course.');</script>";
        }
        $stmtEnroll->close();
    }
}
?>