<?php
// Start session to handle user login session data
session_start();

// Include database connection file
include 'student_planner_db.php';

// Initialize error message variable
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form input
    $student_id = trim($_POST['student_id']);
    $password = trim($_POST['password']);

    // Check if inputs are not empty
    if (!empty($student_id) && !empty($password)) {
        // Prepare SQL query to fetch user based on student ID
        $stmt = $conn->prepare("SELECT id, student_id, name, email, phone, program, year_of_study, age, gender, address, password FROM users WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $stmt->store_result();

        // Check if a matching user is found
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $db_student_id, $name, $email, $phone, $program, $year_of_study, $age, $gender, $address, $db_password);
            $stmt->fetch();

            // Verify entered password with hashed password from database
            if (password_verify($password, $db_password)) {
                // Set session variables
                $_SESSION['user_id'] = $id;
                $_SESSION['student_id'] = $db_student_id;
                $_SESSION['student_name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['phone'] = $phone;
                $_SESSION['program'] = $program;
                $_SESSION['year_of_study'] = $year_of_study;
                $_SESSION['age'] = $age;
                $_SESSION['gender'] = $gender;
                $_SESSION['address'] = $address;

                // Redirect to dashboard with a success alert
                echo "<script>
                    alert('Login successful! Welcome, $name.');
                    window.location.href = 'dashboard.php';
                </script>";
                exit();
            } else {
                $error = "Invalid student ID or password.";
            }
        } else {
            $error = "Invalid student ID or password.";
        }

        $stmt->close();
    } else {
        $error = "Please fill in both fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Planner - Login</title>
    <style>
        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #0D0D0D;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .login-container {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(255, 255, 255, 0.1);
            width: 340px;
            text-align: center;
            margin-bottom: 20px;
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

        footer {
            text-align: center;
            color: #888;
            font-size: 14px;
            padding: 10px 0;
            width: 100%;
            border-top: 1px solid #333;
            background-color: #0D0D0D;
        }
    </style>
</head>
<body>
    <main>
        <!-- Login Form Container -->
        <div class="login-container">
            <h2>Login</h2>

            <!-- Display error message if any -->
            <?php if (!empty($error)) echo "<p class='error-message'>$error</p>"; ?>

            <!-- Login Form -->
            <form method="POST" action="">
                <div class="input-container">
                    <input type="text" name="student_id" placeholder="Student ID" required>
                </div>
                <div class="input-container">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit">Continue</button>
            </form>

            <!-- Links -->
            <p><a href="forgot_password.php">Forgot Password?</a></p>
            <p>Don't have an account? <a href="register.php">Sign up</a></p>
        </div>
    </main>

    <!-- Footer Section -->
    <footer>
        &copy; <?php echo date("Y"); ?> Hafizi (I22023364), INTI International University. All rights reserved.
    </footer>
</body>
</html>
