<?php
// Start the session to access user login data
session_start();

// Include the database connection
require_once "student_planner_db.php";

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Retrieve the logged-in student's ID from session
$studentId = $_SESSION['user_id'];

// Prepare SQL query to fetch archived tasks for the current student
$stmt = $conn->prepare("SELECT * FROM archived_tasks WHERE student_id = ? ORDER BY due_date ASC");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$archivedTasks = $result->fetch_all(MYSQLI_ASSOC); // Fetch all archived tasks as an associative array
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archived Tasks</title>
    <style>
        /* Global Reset and Font */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: #0D0D0D;
            padding: 40px 20px;
            min-height: 100vh;
            color: #fff;
        }

        /* Main Container Styling */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            color: #000;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: #000;
            color: #fff;
        }

        tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        /* Category Tag Styles */
        .category-tag {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 13px;
            color: #fff;
        }

        .Assignment { background-color: #007bff; }
        .Exam { background-color: #dc3545; }
        .Personal { background-color: #6f42c1; }
        .Project { background-color: #28a745; }
        .Uncategorized, .Default { background-color: #555; }

        /* Back Link Styling */
        .back-link {
            margin-top: 25px;
            text-align: center;
        }

        .back-link a {
            color: #000;
            text-decoration: underline;
        }

        /* Message when no archived tasks found */
        .no-task {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 16px;
        }

        /* Footer styling */
        footer {
            text-align: center;
            margin-top: 40px;
            color: #888;
            font-size: 14px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Archived Tasks</h2>

    <?php if (count($archivedTasks) > 0): ?>
        <!-- Task Table -->
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Category</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($archivedTasks as $task): ?>
                    <?php
                        // Handle category formatting and fallback
                        $rawCategory = $task['category'] ?: 'Uncategorized';
                        $normalizedCategory = ucfirst(strtolower(trim($rawCategory))); // Capitalize and normalize
                        $categoryClass = preg_replace('/[^a-zA-Z0-9]/', '', $normalizedCategory) ?: 'Default'; // Generate safe CSS class
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($task['title']) ?></td>
                        <td><?= htmlspecialchars($task['due_date']) ?></td>
                        <td><?= htmlspecialchars($task['status']) ?></td>
                        <td><?= htmlspecialchars($task['priority']) ?></td>
                        <td>
                            <!-- Category Tag with Dynamic Class -->
                            <span class="category-tag <?= $categoryClass ?>">
                                <?= htmlspecialchars($normalizedCategory) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <!-- No Tasks Message -->
        <div class="no-task">No archived tasks found.</div>
    <?php endif; ?>

    <!-- Back to Dashboard Link -->
    <div class="back-link">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</div>

<!-- Footer -->
<footer>
    &copy; <?php echo date("Y"); ?> Hafizi (I22023364), INTI International University. All rights reserved.
</footer>

</body>
</html>
