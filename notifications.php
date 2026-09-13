<?php
session_start();
require_once "student_planner_db.php";

// Redirect user to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$studentId = $_SESSION['user_id'];

// Prepare SQL to get active (not completed, not archived) tasks for the logged-in student
$sql = "SELECT title, due_date, category, priority 
        FROM tasks 
        WHERE student_id = ? AND status != 'Completed' AND archived = 0 
        ORDER BY due_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();

$activeTasks = [];

// Fetch results into an array
while ($row = $result->fetch_assoc()) {
    $activeTasks[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Active Task Notifications</title>
  <style>
    /* Global styles */
    * {
      box-sizing: border-box;
    }

    html, body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      background: #121212;
      color: #fff;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      flex-direction: column;
    }

    /* Remove top padding to move content higher */
    body {
      padding-top: 0px;
    }

    /* Card container */
    .card {
      background: #1e1e1e;
      padding: 15px;
      border-radius: 12px;
      box-shadow: 0 0 12px rgba(255,255,255,0.1);
      max-width: 800px;
      width: 50%;
      margin: auto;
    }

    /* Header for card */
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      border-bottom: 1px solid #333;
      padding-bottom: 10px;
    }

    .card-header h2 {
      margin: 0;
      font-size: 20px;
    }

    /* Back button style */
    .back-button {
      background-color: #333;
      color: #fff;
      padding: 8px 14px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
      transition: background-color 0.3s;
    }

    .back-button:hover {
      background-color: #444;
    }

    /* Individual task box */
    .task {
      padding: 12px;
      border: 1px solid #333;
      border-radius: 8px;
      margin-bottom: 12px;
      background: #292929;
    }

    /* Task title */
    .task-title {
      font-size: 16px;
      font-weight: bold;
    }

    /* Task meta details (due date, category, priority) */
    .meta {
      font-size: 14px;
      color: #ccc;
    }

    /* Footer style */
    footer {
      text-align: center;
      margin-top: auto;
      color: #888;
      font-size: 14px;
      padding: 12px 0;
      border-top: 1px solid #333;
    }
  </style>
</head>
<body>
  <!-- Task notification container -->
  <div class="card">
    <div class="card-header">
      <h2>Active Tasks</h2>
      <a href="dashboard.php" class="back-button">Back</a>
    </div>

    <!-- Display active tasks or message if none -->
    <?php if (count($activeTasks) > 0): ?>
      <?php foreach ($activeTasks as $task): ?>
        <div class="task">
          <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
          <div class="meta">
            Due: <?php echo htmlspecialchars($task['due_date']); ?> |
            Category: <?php echo htmlspecialchars($task['category']); ?> |
            Priority: <?php echo htmlspecialchars($task['priority']); ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No active tasks found.</p>
    <?php endif; ?>
  </div>

  <!-- Footer -->
  <footer>
    &copy; <?php echo date("Y"); ?> Hafizi (I22023364), INTI International University. All rights reserved.
  </footer>
</body>
</html>
