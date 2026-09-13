<?php
session_start();
require_once "student_planner_db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$studentId = $_SESSION['user_id'];

// Automatically update overdue tasks to 'Pending' if not completed or still On-going
$currentDate = date('Y-m-d');
$updateStmt = $conn->prepare("UPDATE tasks SET status = 'Pending' 
    WHERE student_id = ? 
    AND due_date < ? 
    AND (status IS NULL OR status = '' OR status = 'On-going')");
$updateStmt->bind_param("is", $studentId, $currentDate);
$updateStmt->execute();
$updateStmt->close();

// Handle task completion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_task_id'])) {
    $taskId = $_POST['complete_task_id'];

    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $taskId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    $stmt->close();

    if ($task) {
        $completedStatus = "Completed";
        $stmt = $conn->prepare("INSERT INTO archived_tasks (student_id, title, description, due_date, priority, status, category)
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "issssss",
            $studentId,
            $task['title'],
            $task['description'],
            $task['due_date'],
            $task['priority'],
            $completedStatus,
            $task['category']
        );
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND student_id = ?");
        $stmt->bind_param("ii", $taskId, $studentId);
        $stmt->execute();
        $stmt->close();

        $_SESSION['completed'] = true;
        header("Location: task_status.php");
        exit();
    }
}

// Load tasks
$stmt = $conn->prepare("SELECT * FROM tasks WHERE student_id = ? ORDER BY due_date ASC");
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
    <title>View Tasks</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body {
            background-color: #0D0D0D;
            padding: 40px 20px;
            min-height: 100vh;
            color: #fff;
        }
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

        .mark-complete-btn {
            padding: 6px 12px;
            font-size: 13px;
            background-color: #000;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .mark-complete-btn:hover {
            background-color: #222;
        }

        .back-link {
            margin-top: 25px;
            text-align: center;
        }
        .back-link a {
            color: #000;
            text-decoration: underline;
        }
        .no-task {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 16px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            color: #000;
            max-width: 400px;
        }
        .modal-content p {
            margin-bottom: 20px;
        }
        .modal-content button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            margin: 0 10px;
            cursor: pointer;
        }
        .confirm-btn {
            background-color: #28a745;
            color: #fff;
        }
        .cancel-btn {
            background-color: #dc3545;
            color: #fff;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 15px;
        }

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
    <h2>Your Tasks</h2>

    <?php if (isset($_SESSION['completed'])): ?>
        <div class="success-message">Task marked as complete successfully.</div>
        <?php unset($_SESSION['completed']); ?>
    <?php endif; ?>

    <?php if (count($tasks) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Category</th>
                    <th>Mark as Complete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <?php
                        $rawCategory = $task['category'] ?: 'Uncategorized';
                        $normalizedCategory = ucfirst(strtolower(trim($rawCategory)));
                        $categoryClass = preg_replace('/[^a-zA-Z0-9]/', '', $normalizedCategory) ?: 'Default';

                        // Determine correct status display
                        $status = $task['status'];
                        $dueDate = $task['due_date'];

                        if ($status !== 'Completed' && $dueDate <= date('Y-m-d')) {
                            $status = 'Pending';
                        } elseif (!$status || trim($status) === '') {
                            $status = 'Pending';
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($task['title']) ?></td>
                        <td><?= htmlspecialchars($task['due_date']) ?></td>
                        <td><?= htmlspecialchars($status) ?></td>
                        <td><?= htmlspecialchars($task['priority']) ?></td>
                        <td>
                            <span class="category-tag <?= $categoryClass ?>">
                                <?= htmlspecialchars($normalizedCategory) ?>
                            </span>
                        </td>
                        <td>
                            <button class="mark-complete-btn" onclick="confirmComplete(<?= $task['id'] ?>)">Mark as Complete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-task">No tasks found.</div>
    <?php endif; ?>

    <div class="back-link">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</div>

<footer>
    &copy; <?= date("Y") ?> Hafizi (I22023364), INTI International University. All rights reserved.
</footer>

<div class="modal" id="completeModal">
    <div class="modal-content">
        <p>Are you sure you want to mark this task as completed?</p>
        <form method="POST" id="completeForm">
            <input type="hidden" name="complete_task_id" id="completeTaskId">
            <button type="submit" class="confirm-btn">Yes</button>
            <button type="button" class="cancel-btn" onclick="closeModal()">No</button>
        </form>
    </div>
</div>

<script>
    function confirmComplete(taskId) {
        document.getElementById('completeTaskId').value = taskId;
        document.getElementById('completeModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('completeModal').style.display = 'none';
    }
</script>

</body>
</html>
