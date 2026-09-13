<?php
session_start();
require_once "student_planner_db.php";

// Redirect user to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$studentName = $_SESSION['student_name'] ?? '';

// Handle task archiving if 'archive' parameter is set
if (isset($_GET['archive'])) {
    $taskId = intval($_GET['archive']);

    // Fetch the task details to archive
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $taskId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();

    if ($task) {
        // Archive task by inserting it into 'archived_tasks' table with status 'Deleted'
        $status = "Deleted";
        $insertStmt = $conn->prepare("INSERT INTO archived_tasks (title, due_date, priority, category, student_id, status) VALUES (?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("ssssss", $task['title'], $task['due_date'], $task['priority'], $task['category'], $userId, $status);
        $insertStmt->execute();

        // Remove task from original 'tasks' table
        $deleteStmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $deleteStmt->bind_param("i", $taskId);
        $deleteStmt->execute();

        // Redirect with archive success flag
        header("Location: edit_task.php?archived=1");
        exit();
    }
}

// Fetch all tasks for the current logged-in student
$sql = "SELECT id, title, due_date, priority, category FROM tasks WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
?>

<!-- The rest of the file remains unchanged from your original version -->
<!-- No further code changes were made below this line -->

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Task</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    /* Dark-themed body style */
    body {
      background: linear-gradient(145deg, #0a0a0a, #1a1a1a);
      color: white;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
    }

    /* Header styling */
    header {
      background-color: #111;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 24px;
      border-bottom: 1px solid #333;
    }

    header h1 {
      font-size: 22px;
      font-weight: bold;
      color: #fff;
    }

    .top-right {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .top-right span {
      font-size: 18px;
      font-weight: bold;
    }

    .top-right a {
      color: white;
      text-decoration: none;
      background-color: #222;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 14px;
      transition: background 0.3s ease;
    }

    .top-right a:hover {
      background-color: #333;
    }

    /* Container card for task list */
    .container {
      max-width: 700px;
      margin: 60px auto;
      padding: 32px 24px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
      color: black;
    }

    h2 {
      font-size: 24px;
      margin-bottom: 24px;
      padding-bottom: 10px;
      text-align: center;
    }

    .task-list {
      list-style: none;
      padding: 0;
    }

    .task-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px;
      margin-bottom: 12px;
      background: #f3f3f3;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .task-details {
      flex: 1;
    }

    .task-details strong {
      font-size: 16px;
    }

    /* Styling for action buttons */
    .task-actions a {
      text-decoration: none;
      background: #111;
      color: white;
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
      margin-left: 8px;
    }

    .task-actions a:hover {
      background-color: #333;
    }

    .delete-btn {
      background-color: #b30000;
    }

    /* Footer styling */
    footer {
      text-align: center;
      margin-top: 40px;
      padding: 20px;
      color: #aaa;
      font-size: 14px;
    }

    /* Modal overlay styling */
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

    /* Modal box content */
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

    /* Additional footer style to align with rest of design */
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
  <header>
    <h1>Edit Task</h1>
    <div class="top-right">
      <!-- Display logged in student's name -->
      <span><?php echo htmlspecialchars($studentName); ?></span>
      <a href="dashboard.php">Dashboard</a>
      <a href="logout.php">Logout</a>
    </div>
  </header>

  <div class="container">
    <h2>Your Tasks</h2>
    <!-- Display list of tasks or a message if none -->
    <?php if (count($tasks) > 0): ?>
      <ul class="task-list">
        <?php foreach ($tasks as $task): ?>
          <li class="task-item">
            <div class="task-details">
              <!-- Task info display -->
              <strong><?php echo htmlspecialchars($task['title']); ?></strong><br>
              Due: <?php echo htmlspecialchars($task['due_date']); ?> |
              Priority: <?php echo htmlspecialchars($task['priority']); ?> |
              Category: <?php echo htmlspecialchars($task['category']); ?>
            </div>
            <div class="task-actions">
              <!-- Edit and Delete actions -->
              <a href="#" onclick="confirmEdit(<?php echo $task['id']; ?>)">Edit</a>
              <a href="#" class="delete-btn" onclick="confirmArchive(<?php echo $task['id']; ?>)">Delete</a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>No tasks available to edit.</p>
    <?php endif; ?>
  </div>

  <footer>
    &copy; <?php echo date("Y"); ?> Hafizi (I22023364), INTI International University. All rights reserved.
  </footer>

  <!-- Archive Confirmation Modal -->
  <div id="archiveModal" class="modal">
    <div class="modal-content">
      <p>Are you sure you want to delete this task? It will be moved to the archive.</p>
      <button class="confirm-btn" id="confirmArchiveBtn">Yes</button>
      <button class="cancel-btn" onclick="cancelModal('archiveModal')">No</button>
    </div>
  </div>

  <!-- Archive Success Modal (shows after archive is done) -->
  <?php if (isset($_GET['archived']) && $_GET['archived'] == 1): ?>
    <div id="successModal" class="modal" style="display:flex;">
      <div class="modal-content">
        <p>Task archived successfully.</p>
        <button class="confirm-btn" onclick="closeModal('successModal')">OK</button>
      </div>
    </div>
  <?php endif; ?>

  <!-- Edit Confirmation Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <p>Do you want to edit this task?</p>
      <button class="confirm-btn" id="confirmEditBtn">Yes</button>
      <button class="cancel-btn" onclick="cancelModal('editModal')">No</button>
    </div>
  </div>

  <script>
    let taskToArchive = null;
    let taskToEdit = null;

    // Show archive confirmation modal
    function confirmArchive(taskId) {
      taskToArchive = taskId;
      document.getElementById('archiveModal').style.display = 'flex';
    }

    // On confirm archive, redirect with archive ID
    document.getElementById('confirmArchiveBtn').addEventListener('click', function () {
      if (taskToArchive !== null) {
        window.location.href = "edit_task.php?archive=" + taskToArchive;
      }
    });

    // Cancel and hide modal
    function cancelModal(id) {
      document.getElementById(id).style.display = 'none';
    }

    // Close success modal and remove archived param from URL
    function closeModal(id) {
      document.getElementById(id).style.display = 'none';
      const url = new URL(window.location);
      url.searchParams.delete('archived');
      window.history.replaceState({}, document.title, url.toString());
    }

    // Show edit confirmation modal
    function confirmEdit(taskId) {
      taskToEdit = taskId;
      document.getElementById('editModal').style.display = 'flex';
    }

    // On confirm edit, redirect to update task page
    document.getElementById('confirmEditBtn').addEventListener('click', function () {
      if (taskToEdit !== null) {
        window.location.href = "update_task.php?id=" + taskToEdit;
      }
    });
  </script>
</body>
</html>
