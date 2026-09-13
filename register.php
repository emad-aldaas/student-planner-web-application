<?php
session_start();
include 'student_planner_db.php';

$showExistModal = false;

// Check for error session (duplicate student ID or email)
if (isset($_SESSION['error'])) {
    if ($_SESSION['error'] === "Student ID or email already exists.") {
        $showExistModal = true;
    }
    unset($_SESSION['error']);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $age = trim($_POST['age']);
    $student_id = trim($_POST['student_id']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $program = trim($_POST['program']);
    $year = trim($_POST['year']);
    $gender = trim($_POST['gender']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!empty($name) && !empty($student_id) && !empty($email) && !empty($password)) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
            $stmt->bind_param("ss", $student_id, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $_SESSION['error'] = "Student ID or email already exists.";
                $stmt->close();
                header("Location: register.php");
                exit();
            } else {
                $stmt->close();
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users 
                    (name, age, student_id, email, phone, program, year_of_study, gender, address, password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sissssssss", $name, $age, $student_id, $email, $phone, $program, $year, $gender, $address, $hashed_password);

                if ($stmt->execute()) {
                    // Redirect to login page after successful registration
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Something went wrong. Please try again.";
                }
                $stmt->close();
            }
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Planner - Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: #0D0D0D;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 40px 20px;
        }

        .register-container {
            background: #fff;
            width: 100%;
            max-width: 600px;
            padding: 40px 30px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            margin: 0 auto;
            margin-bottom: 40px; /* Prevents overlap with footer */
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

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: red;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }

        /* Footer Styling */
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

<!-- Show Modal if duplicate student ID or email -->
<?php if ($showExistModal): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('existsModal');
        modal.style.display = 'flex';
    });
</script>
<?php endif; ?>

<!-- Registration Form Container -->
<div class="register-container">
    <h2>Register</h2>
    <form method="POST" action="">
        <div class="form-group"><input type="text" name="name" placeholder="Full Name" required></div>
        <div class="form-group"><input type="number" name="age" placeholder="Age" required></div>
        <div class="form-group"><input type="text" name="student_id" id="student_id" placeholder="Student ID" required></div>
        <div class="form-group"><input type="email" name="email" placeholder="Email Address" required></div>
        <div class="form-group"><input type="password" name="password" placeholder="Password" required></div>
        <div class="form-group"><input type="password" name="confirm_password" placeholder="Re-enter Password" required></div>
        <div class="form-group"><input type="text" name="program" placeholder="Program of Study"></div>
        <div class="form-group"><input type="text" name="year" placeholder="Year of Study"></div>
        <div class="form-group"><input type="text" name="phone" placeholder="Phone Number"></div>
        <div class="form-group">
            <select name="gender">
                <option value="">Select Gender</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
            </select>
        </div>
        <div class="form-group"><textarea name="address" placeholder="Address" rows="2"></textarea></div>
        <button type="submit">Register</button>
        <p style="text-align:center; margin-top: 12px;">
            <a href="login.php" style="color: #000; text-decoration: underline;">Back to Login</a>
        </p>
    </form>
</div>

<!-- Modal for Duplicate Entry -->
<div id="existsModal" class="modal">
    <div class="modal-content">
        Student ID or email already exists.<br><br>
        <a href="login.php">Go to Login</a>
    </div>
</div>

<script>
// Close modal on click
document.getElementById("existsModal").addEventListener("click", function () {
    this.style.display = 'none';
});
</script>

<!-- Footer -->
<footer>
    &copy; <?php echo date("Y"); ?> Hafizi (I22023364), INTI International University. All rights reserved.
</footer>

</body>
</html>
