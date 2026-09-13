<?php
// Start session
session_start();

// Include database connection file
include 'student_planner_db.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['student_id'])) {
    $_SESSION['redirect_after_login'] = 'edit_user.php';
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$success = false;
$error = "";

// Fetch user data from database
$stmt = $conn->prepare("SELECT name, age, student_id, email, phone, program, year_of_study, gender, address FROM users WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete_confirm'])) {
    $name = trim($_POST['name']);
    $age = trim($_POST['age']);
    $phone = trim($_POST['phone']);
    $program = trim($_POST['program']);
    $year = trim($_POST['year']);
    $gender = trim($_POST['gender']);
    $address = trim($_POST['address']);

    // Ensure required fields are not empty
    if (!empty($name) && !empty($age)) {
        // Update user data
        $stmt = $conn->prepare("UPDATE users SET name=?, age=?, phone=?, program=?, year_of_study=?, gender=?, address=? WHERE student_id=?");
        $stmt->bind_param("sissssss", $name, $age, $phone, $program, $year, $gender, $address, $student_id);

        if ($stmt->execute()) {
            // Update session data and user array
            $_SESSION['name'] = $name;
            $success = true;
            $user['name'] = $name;
            $user['age'] = $age;
            $user['phone'] = $phone;
            $user['program'] = $program;
            $user['year_of_study'] = $year;
            $user['gender'] = $gender;
            $user['address'] = $address;
        } else {
            $error = "Update failed. Please try again.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle account deletion
if (isset($_POST['delete_confirm']) && $_POST['delete_confirm'] === 'yes') {
    $delStmt = $conn->prepare("DELETE FROM users WHERE student_id = ?");
    $delStmt->bind_param("s", $student_id);
    if ($delStmt->execute()) {
        session_destroy();
        echo "<script>
            alert('Your account has been deleted.');
            window.location.href = 'dashboard.php';
        </script>";
        exit();
    } else {
        $error = "Failed to delete your account. Please try again.";
    }
    $delStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <style>
        /* General Reset and Font */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }

        html, body { height: 100%; }

        /* Page background and layout */
        body {
            background-color: #0D0D0D;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px 80px 20px;
        }

        /* Form container styling */
        .register-container {
            background: #fff;
            width: 100%;
            max-width: 600px;
            padding: 40px 30px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #000;
            font-size: 26px;
            font-weight: bold;
        }

        /* Form input styling */
        .form-group { margin-bottom: 18px; }

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

        /* Submit button */
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

        /* Delete button */
        .delete-btn {
            width: 60%;
            padding: 10px 24px;
            background-color: #cc0000;
            display: block;
            margin: 16px auto 0 auto;
            font-size: 13.5px;
            font-weight: bold;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .delete-btn:hover {
            background-color: #990000;
        }

        /* Modal styling */
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
            padding: 35px 30px;
            border-radius: 14px;
            text-align: center;
            color: #000;
            max-width: 400px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.4);
        }

        .modal-content p {
            margin-bottom: 25px;
            font-size: 16px;
        }

        .modal-content form {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .modal-content button {
            padding: 8px 16px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .confirm-btn {
            background-color: #28a745;
            color: #fff;
        }

        .confirm-btn:hover {
            background-color: #218838;
        }

        .cancel-btn {
            background-color: #dc3545;
            color: #fff;
        }

        .cancel-btn:hover {
            background-color: #c82333;
        }

        /* Error message styling */
        .error-msg {
            text-align: center;
            margin-bottom: 10px;
            color: red;
            font-weight: bold;
        }

        /* Footer styling */
        footer {
            text-align: center;
            color: #888;
            font-size: 14px;
            padding: 20px 0;
            width: 100%;
            border-top: 1px solid #333;
            background-color: #0D0D0D;
        }
    </style>
</head>
<body>

<!-- Show success modal if update was successful -->
<?php if ($success): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('successModal');
        modal.style.display = 'flex';
    });
</script>
<?php endif; ?>

<main>
    <div class="register-container">
        <h2>Edit Profile</h2>

        <!-- Display error message if any -->
        <?php if ($error): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <!-- Profile Edit Form -->
        <form method="POST" action="">
            <div class="form-group"><input type="text" name="name" placeholder="Full Name" value="<?= htmlspecialchars($user['name']) ?>" required></div>
            <div class="form-group"><input type="number" name="age" placeholder="Age" value="<?= htmlspecialchars($user['age']) ?>" required></div>
            <div class="form-group"><input type="text" value="<?= htmlspecialchars($user['student_id']) ?>" disabled></div>
            <div class="form-group"><input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
            <div class="form-group"><input type="text" name="program" placeholder="Program of Study" value="<?= htmlspecialchars($user['program']) ?>"></div>
            <div class="form-group"><input type="text" name="year" placeholder="Year of Study" value="<?= htmlspecialchars($user['year_of_study']) ?>"></div>
            <div class="form-group"><input type="text" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($user['phone']) ?>"></div>
            <div class="form-group">
                <select name="gender">
                    <option value="">Select Gender</option>
                    <option <?= $user['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                    <option <?= $user['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                    <option <?= $user['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="form-group"><textarea name="address" placeholder="Address" rows="2"><?= htmlspecialchars($user['address']) ?></textarea></div>

            <!-- Submit and delete buttons -->
            <button type="submit">Update</button>
            <button type="button" class="delete-btn" onclick="showDeleteModal()">Delete My Account</button>
            <p style="text-align:center; margin-top: 12px;">
                <a href="dashboard.php" style="color: #000; text-decoration: underline;">Back to Dashboard</a>
            </p>
        </form>
    </div>
</main>

<!-- Success modal for update confirmation -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <p>Profile updated successfully!</p>
        <a href="dashboard.php"
           style="background-color: #28a745; color: #fff; padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block; transition: background-color 0.3s ease;"
           onmouseover="this.style.backgroundColor='#218838'"
           onmouseout="this.style.backgroundColor='#28a745'">
           Go to Dashboard
        </a>
    </div>
</div>

<!-- Delete account confirmation modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <p>Are you sure you want to delete your account?</p>
        <form method="POST" action="">
            <input type="hidden" name="delete_confirm" value="yes">
            <button type="submit" class="confirm-btn">Yes</button>
            <button type="button" class="cancel-btn" onclick="hideDeleteModal()">No</button>
        </form>
    </div>
</div>

<!-- Modal handling scripts -->
<script>
// Close success modal on click
document.getElementById("successModal").addEventListener("click", function () {
    this.style.display = 'none';
});

// Show delete confirmation modal
function showDeleteModal() {
    document.getElementById('deleteModal').style.display = 'flex';
}

// Hide delete confirmation modal
function hideDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
</script>

<!-- Footer -->
<footer>
    &copy; <?php echo date("Y"); ?> Hafizi (I22023364), INTI International University. All rights reserved.
</footer>

</body>
</html>
