<?php
// Start the session
session_start();

// Include database connection
require_once "student_planner_db.php";

// Redirect to login page if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Retrieve logged-in student's ID from the session
$studentId = $_SESSION['user_id'];

// Get the selected sort option from the query string (default is 'due_date')
$sortOption = $_GET['sort'] ?? 'due_date';

// Determine the SQL ORDER BY clause based on selected sorting option
switch ($sortOption) {
    case 'priority_high':
        // Custom order: High > Medium > Low
        $orderBy = "FIELD(priority, 'High', 'Medium', 'Low')";
        break;
    case 'priority_low':
        // Custom order: Low > Medium > High
        $orderBy = "FIELD(priority, 'Low', 'Medium', 'High')";
        break;
    default:
        // Default sort by due date ascending
        $orderBy = "due_date ASC";
        break;
}

// Fetch all tasks for the logged-in student with applied sorting
$stmt = $conn->prepare("SELECT * FROM tasks WHERE student_id = ? ORDER BY $orderBy");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Tasks Only</title>
    <style>
        /* Base styles */
        body {
            background-color: #0D0D0D;
            padding: 40px 20px;
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
        }

        /* Main container card */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            color: #000;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        /* Header */
        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }

        /* Table styles */
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

        /* Category tag styles */
        .category-tag {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 13px;
            color: #fff;
        }

        /* Predefined category colors */
        .Assignment { background-color: #007bff; }
        .Exam { background-color: #dc3545; }
        .Personal { background-color: #6f42c1; }
        .Project { background-color: #28a745; }
        .Uncategorized, .Default { background-color: #555; }

        /* No task message */
        .no-task {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 16px;
        }

        /* Back to dashboard link */
        .back-link {
            margin-top: 25px;
            text-align: center;
        }

        .back-link a {
            color: #000;
            text-decoration: underline;
        }

        /* Sort/filter form */
        .filter-form {
            text-align: center;
            margin-bottom: 30px;
        }

        .filter-form select {
            padding: 10px 16px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
            color: #333;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .filter-form select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.3);
        }

        .filter-form button {
            padding: 10px 18px;
            font-size: 14px;
            margin-left: 10px;
            background-color: #000;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .filter-form button:hover {
            background-color: #333;
        }

        /* Footer */
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
    <h2>Your Tasks (View Only)</h2>

    <!-- Sorting/filter form -->
    <form method="get" class="filter-form">
        <select name="sort">
            <option value="due_date" <?= $sortOption === 'due_date' ? 'selected' : '' ?>>Sort by Due Date</option>
            <option value="priority_high" <?= $sortOption === 'priority_high' ? 'selected' : '' ?>>Priority (High to Low)</option>
            <option value="priority_low" <?= $sortOption === 'priority_low' ? 'selected' : '' ?>>Priority (Low to High)</option>
        </select>
        <button type="submit">Apply</button>
    </form>

    <!-- Task table -->
    <?php if (count($tasks) > 0): ?>
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
                <?php foreach ($tasks as $task): ?>
                    <?php
                        // Normalize category string and prepare class
                        $rawCategory = $task['category'] ?: 'Uncategorized';
                        $normalizedCategory = ucfirst(strtolower(trim($rawCategory)));
                        $categoryClass = preg_replace('/[^a-zA-Z0-9]/', '', $normalizedCategory) ?: 'Default';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($task['title']) ?></td>
                        <td><?= htmlspecialchars($task['due_date']) ?></td>
                        <td>
                            <?php
                                // Automatically mark as "Pending" if due date is today or earlier and not completed
                                $currentDate = date('Y-m-d');
                                if ($task['status'] !== 'Complete' && $task['due_date'] <= $currentDate) {
                                    echo 'Pending';
                                } else {
                                    echo htmlspecialchars($task['status']);
                                }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($task['priority']) ?></td>
                        <td>
                            <span class="category-tag <?= $categoryClass ?>">
                                <?= htmlspecialchars($normalizedCategory) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <!-- If no tasks -->
        <div class="no-task">No tasks found.</div>
    <?php endif; ?>

    <!-- Back link -->
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
