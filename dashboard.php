<?php
// Start the session
session_start();

// Include database connection file
require_once "student_planner_db.php";

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);

// Get the student name from session if logged in
$studentName = $loggedIn ? $_SESSION['student_name'] : null;

// Initialize reminders array and modal flag
$reminders = [];
$showReminderModal = false;

if ($loggedIn) {
    // Get logged-in user ID
    $userId = $_SESSION['user_id'];

    // Prepare SQL to fetch upcoming tasks that are not completed
    $query = "SELECT title, due_date, priority, category FROM tasks 
              WHERE student_id = ? 
              AND status != 'Completed' 
              AND due_date >= CURDATE() 
              ORDER BY due_date ASC";

    // Execute prepared statement
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $reminders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Show modal only once per session if there are reminders
    if (count($reminders) > 0 && !isset($_SESSION['reminder_modal_shown'])) {
        $_SESSION['reminder_modal_shown'] = true;
        $showReminderModal = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Planner Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Inline styles for dashboard UI -->
  <style>
    /* General body and layout styling */
    body {
      background: linear-gradient(145deg, #0a0a0a, #1a1a1a);
      color: white;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
    }

    /* Header and top ribbon */
    header {
      background-color: #111;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 24px;
      border-bottom: 1px solid #333;
      box-shadow: 0 2px 4px rgba(0,0,0,0.3);
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

    .top-right a.username-link,
    .top-right a {
      font-size: 14px;
      font-weight: bold;
      color: white;
      text-decoration: none;
      background-color: #222;
      padding: 6px 12px;
      border-radius: 6px;
      transition: background 0.3s ease;
    }

    .top-right a.username-link:hover,
    .top-right a:hover {
      background-color: #333;
    }

    /* Container for main task menu */
    .container {
      max-width: 580px;
      margin: 60px auto;
      padding: 32px 24px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
      color: black;
    }

    .task-container-wrapper {
      position: relative;
    }

    /* Notification button style */
    .notification-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      padding: 8px 16px;
      background-color: #111;
      border: 1px solid #444;
      border-radius: 8px;
      color: #fff;
      font-weight: bold;
      text-decoration: none;
      font-size: 14px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.4);
      transition: all 0.3s ease;
    }

    .notification-btn:hover {
      background-color: #222;
      transform: scale(1.05);
    }

    h2 {
      font-size: 24px;
      margin-bottom: 24px;
      padding-bottom: 10px;
      text-align: left;
    }

    /* Grid layout for task buttons */
    .task-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
      margin-top: 10px;
    }

    /* Task button styles */
    .task-button {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 24px 16px;
      background: linear-gradient(to right, #111, #1f1f1f);
      border: 1px solid #444;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 600;
      text-decoration: none;
      color: #fff;
      transition: all 0.3s ease;
      height: 80px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.4);
    }

    .task-button:hover {
      transform: scale(1.05);
      background: linear-gradient(to right, #222, #333);
      box-shadow: 0 6px 20px rgba(255, 255, 255, 0.08);
    }

    /* Archived task button section */
    .archived-wrapper {
      margin-top: 30px;
      text-align: center;
    }

    .archived-button {
      display: inline-block;
      padding: 12px 20px;
      background: linear-gradient(to right, #1a1a1a, #2a2a2a);
      border: 1px solid #444;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 600;
      color: white;
      text-decoration: none;
      transition: all 0.3s ease;
      box-shadow: 0 3px 12px rgba(0, 0, 0, 0.5);
    }

    .archived-button:hover {
      background: linear-gradient(to right, #2d2d2d, #3d3d3d);
      transform: scale(1.04);
      box-shadow: 0 6px 20px rgba(255, 255, 255, 0.07);
    }

    /* Footer style */
    footer {
      text-align: center;
      margin-top: 40px;
      padding: 20px;
      color: #aaa;
      font-size: 14px;
    }

    /* Reminder modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.8);
    }

    .modal-content {
      background-color: #1e1e1e;
      margin: 10% auto;
      padding: 30px;
      border-radius: 14px;
      width: 85%;
      max-width: 600px;
      color: #f1f1f1;
      box-shadow: 0 8px 24px rgba(255,255,255,0.1);
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
    }

    .close:hover {
      color: #fff;
    }

    .modal-content h3 {
      margin-top: 0;
      font-size: 22px;
      border-bottom: 1px solid #444;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }

    .modal-reminder-item {
      border-bottom: 1px solid #333;
      padding: 10px 0;
      line-height: 1.6;
    }

    .modal-reminder-item:last-child {
      border-bottom: none;
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
  <!-- Header with login/logout and greeting -->
  <header>
    <h1>Student Planner</h1>
    <div class="top-right">
      <?php if ($loggedIn): ?>
        <a href="edit_user.php" class="username-link">Hello, <?php echo htmlspecialchars($studentName); ?></a>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- Main container for dashboard -->
  <div class="container task-container-wrapper">
    <a href="notifications.php" class="notification-btn">Notification</a>
    <h2>Task Manager</h2>
    <div class="task-grid">
      <!-- Conditional redirect based on login status -->
      <a href="<?php echo $loggedIn ? 'add_task.php' : 'login.php'; ?>" class="task-button">Add Task</a>
      <a href="<?php echo $loggedIn ? 'edit_task.php' : 'login.php'; ?>" class="task-button">Edit Task</a>
      <a href="<?php echo $loggedIn ? 'view_tasks.php' : 'login.php'; ?>" class="task-button">View Task</a>
      <a href="<?php echo $loggedIn ? 'task_status.php' : 'login.php'; ?>" class="task-button">Task Status</a>
    </div>

    <!-- Link to archived tasks -->
    <div class="archived-wrapper">
      <a href="<?php echo $loggedIn ? 'archived_task.php' : 'login.php'; ?>" class="archived-button">View Archived Task</a>
    </div>
  </div>

  <!-- Modal popup for task reminders -->
  <?php if ($showReminderModal): ?>
    <div id="reminderModal" class="modal">
      <div class="modal-content">
        <span class="close" id="closeReminderModal">&times;</span>
        <h3>Upcoming Task Reminders</h3>
        <?php foreach ($reminders as $reminder): ?>
          <div class="modal-reminder-item">
            <strong><?php echo htmlspecialchars($reminder['title']); ?></strong><br>
            Due: <?php echo htmlspecialchars($reminder['due_date']); ?><br>
            Priority: <?php echo htmlspecialchars($reminder['priority']); ?><br>
            Category: <?php echo htmlspecialchars($reminder['category']); ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Footer with copyright -->
  <footer>
    &copy; <?php echo date("Y"); ?> Hafizi (I22023364), INTI International University. All rights reserved.
  </footer>

  <!-- JavaScript for modal behavior -->
  <script>
    // Show reminder modal on page load
    <?php if ($showReminderModal): ?>
      window.onload = function () {
        document.getElementById('reminderModal').style.display = 'block';
      };
    <?php endif; ?>

    // Close modal on X click
    document.getElementById('closeReminderModal')?.addEventListener('click', function () {
      document.getElementById('reminderModal').style.display = 'none';
    });
  </script>
</body>
</html>
