<?php
session_start();
require_once "student_planner_db.php";

// Redirect to login page if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$studentId = $_SESSION['user_id'];
$taskId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$successUpdate = false;

// Fetch the task data based on task ID and student ID
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND student_id = ?");
$stmt->bind_param("ii", $taskId, $studentId);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();
$stmt->close();

// Show error if task not found
if (!$task) {
    echo "Task not found.";
    exit();
}

// Handle form submission to update the task
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $category = trim($_POST['category']);

    // Update query
    $updateStmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, due_date = ?, priority = ?, status = ?, category = ? WHERE id = ? AND student_id = ?");
    $updateStmt->bind_param("ssssssii", $title, $description, $due_date, $priority, $status, $category, $taskId, $studentId);

    if ($updateStmt->execute()) {
        $successUpdate = true;
        // Update local variable to reflect the changes
        $task = [
            'title' => $title,
            'description' => $description,
            'due_date' => $due_date,
            'priority' => $priority,
            'status' => $status,
            'category' => $category
        ];
    }

    $updateStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Task</title>
    <style>
        /* Basic reset and layout styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: #0D0D0D;
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .task-container {
            background: #fff;
            width: 100%;
            max-width: 600px;
            padding: 40px 30px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            margin-bottom: 60px; /* Add margin to prevent overlap with footer */
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #000;
            font-size: 26px;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #333;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: #f9f9f9;
            transition: 0.2s ease;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #000;
            background-color: #fff;
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #000;
            color: #fff;
            font-weight: bold;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #222;
        }

        .back-link {
            text-align: center;
            margin-top: 12px;
        }

        .back-link a {
            color: #000;
            text-decoration: underline;
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.75);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            text-align: left;
            font-size: 16px;
            color: #000;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }

        .modal-content h3 {
            margin-bottom: 15px;
            color: green;
        }

        /* Footer styling */
        footer {
            text-align: center;
            color: #888;
            font-size: 14px;
            padding: 20px 0;
            width: 100%;
            border-top: 1px solid #333;
        }
    </style>
</head>
<body>

<!-- Task update form container -->
<div class="task-container">
    <h2>Update Task</h2>
    <form method="POST">
        <div class="form-group">
            <label for="title">Task Title</label>
            <input type="text" name="title" id="title" value="<?= htmlspecialchars($task['title']) ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Task Description</label>
            <textarea name="description" id="description" rows="4" required><?= htmlspecialchars($task['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="due_date">Due Date</label>
            <input type="date" name="due_date" id="due_date" value="<?= htmlspecialchars($task['due_date']) ?>" required>
        </div>

        <div class="form-group">
            <label for="category">Category/Tag</label>
            <input type="text" name="category" id="category" value="<?= htmlspecialchars($task['category']) ?>">
        </div>

        <div class="form-group">
            <label for="priority">Priority</label>
            <select name="priority" id="priority" required>
                <option value="">Select Priority</option>
                <option <?= $task['priority'] === 'High' ? 'selected' : '' ?>>High</option>
                <option <?= $task['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                <option <?= $task['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
            </select>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" required>
                <option value="">Select Status</option>
                <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="On-going" <?= $task['status'] === 'On-going' ? 'selected' : '' ?>>On-going</option>
                <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>

        <button type="submit">Update Task</button>
    </form>

    <p class="back-link">
        <a href="edit_task.php">Back to Task Editor</a>
    </p>
</div>

<!-- Success modal on update -->
<?php if ($successUpdate): ?>
<div class="modal" id="successModal" style="display: flex;">
    <div class="modal-content">
        <h3>Task Updated Successfully!</h3>
        <p><strong>Title:</strong> <?= htmlspecialchars($task['title']) ?></p>
        <p><strong>Description:</strong> <?= htmlspecialchars($task['description']) ?></p>
        <p><strong>Due Date:</strong> <?= htmlspecialchars($task['due_date']) ?></p>
        <p><strong>Priority:</strong> <?= htmlspecialchars($task['priority']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($task['status']) ?></p>
        <p><strong>Category:</strong> <?= htmlspecialchars($task['category']) ?></p>
    </div>
</div>

<script>
    // Auto-redirect after showing success modal
    setTimeout(function () {
        window.location.href = "edit_task.php";
    }, 3000);
</script>
<?php endif; ?>

<!-- Footer -->
<footer>
    &copy; <?php echo date("Y"); ?> Hafizi (I22023364), INTI International University. All rights reserved.
</footer>

</body>
</html>
