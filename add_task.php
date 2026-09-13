<?php
session_start();
require_once "student_planner_db.php";

// Redirect to login if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$studentId = $_SESSION['user_id'];
$showPreview = false;
$taskData = [];

// Automatically set overdue tasks (not marked completed) to 'Pending'
$today = date("Y-m-d");
$updateStmt = $conn->prepare("UPDATE tasks SET status = 'Pending' WHERE student_id = ? AND due_date < ? AND status != 'Completed'");
$updateStmt->bind_param("is", $studentId, $today);
$updateStmt->execute();
$updateStmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $category = trim($_POST['category']);

    // Insert task into database
    $stmt = $conn->prepare("INSERT INTO tasks (student_id, title, description, due_date, priority, status, category)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $studentId, $title, $description, $due_date, $priority, $status, $category);

    if ($stmt->execute()) {
        $showPreview = true; // Show task preview modal
        $taskData = [
            'title' => $title,
            'description' => $description,
            'due_date' => $due_date,
            'priority' => $priority,
            'status' => $status,
            'category' => $category
        ];
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Planner - Add Task</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }

        body {
            background-color: #0D0D0D;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding: 40px 20px;
        }

        .task-container {
            background: #fff;
            width: 100%;
            max-width: 600px;
            padding: 40px 30px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            margin-bottom: 40px; /* Add space between container and footer */
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #000;
            font-size: 26px;
            font-weight: bold;
        }

        .form-group { margin-bottom: 18px; }

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

        button:hover { background-color: #222; }

        .back-link {
            text-align: center;
            margin-top: 12px;
        }

        .back-link a {
            color: #000;
            text-decoration: underline;
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.75);
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

        .close-btn {
            margin-top: 20px;
            display: block;
            background-color: #000;
            color: #fff;
            padding: 10px;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
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

<!-- Task Form Container -->
<div class="task-container">
    <h2>Add Task</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="title">Task Title</label>
            <input type="text" name="title" id="title" required>
        </div>

        <div class="form-group">
            <label for="description">Task Description</label>
            <textarea name="description" id="description" rows="4" required></textarea>
        </div>

        <div class="form-group">
            <label for="due_date">Due Date</label>
            <input type="date" name="due_date" id="due_date" required>
        </div>

        <div class="form-group">
            <label for="category">Category/Tag</label>
            <input type="text" name="category" id="category" placeholder="e.g. Assignment, Exam, Personal">
        </div>

        <div class="form-group">
            <label for="priority">Priority</label>
            <select name="priority" id="priority" required>
                <option value="">Select Priority</option>
                <option>High</option>
                <option>Medium</option>
                <option>Low</option>
            </select>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" required>
                <option value="">Select Status</option>
                <option value="Pending">Pending</option>
                <option value="On-going">On-going</option>
                <option value="Completed">Completed</option>
            </select>
        </div>

        <button type="submit">Add Task</button>
    </form>

    <p class="back-link">
        <a href="dashboard.php">Back to Dashboard</a>
    </p>
</div>

<!-- Task Preview Modal -->
<?php if ($showPreview): ?>
<div class="modal" id="taskModal" style="display: flex;">
    <div class="modal-content">
        <h3>Task Added Successfully!</h3>
        <p><strong>Title:</strong> <?= htmlspecialchars($taskData['title']) ?></p>
        <p><strong>Description:</strong> <?= htmlspecialchars($taskData['description']) ?></p>
        <p><strong>Due Date:</strong> <?= htmlspecialchars($taskData['due_date']) ?></p>
        <p><strong>Priority:</strong> <?= htmlspecialchars($taskData['priority']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($taskData['status']) ?></p>
        <p><strong>Category:</strong> <?= htmlspecialchars($taskData['category']) ?></p>
        <div class="close-btn" onclick="redirectToDashboard()">OK</div>
    </div>
</div>

<script>
    // Redirect user to dashboard after modal confirmation
    function redirectToDashboard() {
        window.location.href = "dashboard.php";
    }
</script>
<?php endif; ?>

<!-- Footer -->
<footer>
    &copy; <?php echo date("Y"); ?> Hafizi (I22023364), INTI International University. All rights reserved.
</footer>

</body>
</html>
