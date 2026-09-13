<?php
session_start();
require_once "student_planner_db.php";

$studentName = "";
$showConfirmationPopup = false;
$showPasswordForm = false;
$showSuccessPopup = false;
$error = "";

// Handle POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Step 1: Check student ID
    if (isset($_POST["check_id"])) {
        $studentId = trim($_POST["student_id"]);
        $stmt = $conn->prepare("SELECT name FROM users WHERE student_id = ?");
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_SESSION["reset_id"] = $studentId;
            $_SESSION["reset_name"] = $row['name'];
            $studentName = $row['name'];
            $showConfirmationPopup = true;
        } else {
            $error = "Student ID not found.";
        }
    }
    // Step 2: Confirm name
    elseif (isset($_POST["confirm_name"])) {
        if ($_POST["confirm_name"] === "yes") {
            $showPasswordForm = true;
        } else {
            session_unset();
        }
    }
    // Step 3: Reset password
    elseif (isset($_POST["reset_password"])) {
        $newPassword = trim($_POST["new_password"]);
        $confirmPassword = trim($_POST["confirm_password"]);

        if ($newPassword !== $confirmPassword) {
            $error = "Passwords do not match.";
            $showPasswordForm = true;
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $studentId = $_SESSION["reset_id"];
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE student_id = ?");
            $stmt->bind_param("ss", $hashedPassword, $studentId);
            if ($stmt->execute()) {
                session_unset();
                $showSuccessPopup = true;
            } else {
                $error = "Failed to update password.";
                $showPasswordForm = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #0D0D0D;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .card {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(255, 255, 255, 0.1);
            width: 340px;
            text-align: center;
        }

        h2 {
            font-size: 24px;
            margin-bottom: 18px;
            color: #000;
            font-weight: bold;
        }

        .input-container {
            margin-bottom: 16px;
        }

        .input-container input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            background: #f9f9f9;
        }

        .input-container input:focus {
            border-color: black;
        }

        button {
            width: 70%;
            padding: 10px;
            background-color: black;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background-color: #222;
        }

        .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 12px;
        }
		
		.modal-content form button {
			width: 100px;
			margin: 0 5px;
		}
		.modal-content p {
			margin-bottom: 15px;
		}
		.back-link {
			display: block;
			margin-top: 12px;
			text-align: center;
			color: #000;
			font-weight: 500;
			text-decoration: none;
			transition: color 0.2s;
		}

		.back-link:hover {
			color: #555;
			text-decoration: underline;
		}
		
		p {
            margin-top: 10px;
            font-size: 13px;
        }

        p a {
            color: black;
            text-decoration: none;
            font-weight: bold;
        }

        p a:hover {
            text-decoration: underline;
        }

       
    </style>
</head>
<body>

<div class="card">
    <h2>Forgot Password</h2>

    <?php if (!empty($error)) echo "<div class='error-message'>$error</div>"; ?>

    <!-- Step 1: Student ID input -->
    <?php if (!$showConfirmationPopup && !$showPasswordForm && !$showSuccessPopup): ?>
        <form method="POST">
            <div class="input-container">
                <input type="text" name="student_id" placeholder="Enter Student ID" required />
            </div>
            <button type="submit" name="check_id">Verify</button>
			<p><a href="login.php" class="back-link">Back to Login</a></p>
        </form>
    <?php endif; ?>

    <!-- Step 2: Name confirmation -->
    <?php if ($showConfirmationPopup): ?>
        <div class="modal">
            <div class="modal-content">
                <p>Is your name <strong><?= $_SESSION["reset_name"] ?></strong>?</p>
                <form method="POST">
                    <button type="submit" name="confirm_name" value="yes">Yes</button>
                    <button type="submit" name="confirm_name" value="no">No</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Step 3: Reset password -->
    <?php if ($showPasswordForm): ?>
        <form method="POST">
            <div class="input-container">
                <input type="password" name="new_password" placeholder="Enter New Password" required />
            </div>
            <div class="input-container">
                <input type="password" name="confirm_password" placeholder="Re-confirm Password" required />
            </div>
            <button type="submit" name="reset_password">Reset Password</button>
            <p><a href="login.php" class="back-link">Back to Login</a></p>
        </form>
    <?php endif; ?>

    <!-- Final success popup -->
    <?php if ($showSuccessPopup): ?>
        <div class="modal">
            <div class="modal-content">
                <p>Password reset successful!</p>
                <a href="Login.php"><button>Go to Login</button></a>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
