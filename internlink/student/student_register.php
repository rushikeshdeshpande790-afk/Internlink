<?php
// connect to DB (reuse the same config you used for company)
include "../config.php";   // path: C:\xampp\htdocs\internlink\config.php

$message = "";

// run only when form submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // read values from form
    $first    = trim($_POST["first_name"] ?? "");
    $last     = trim($_POST["last_name"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $phone    = trim($_POST["phone"] ?? "");
    $branch   = trim($_POST["branch"] ?? "");
    $semester = trim($_POST["semester"] ?? "");
    $pass     = $_POST["password"] ?? "";
    $cpass    = $_POST["confirm_password"] ?? "";

    // basic validation
    if ($first === "" || $last === "" || $email === "" || $phone === "" ||
        $branch === "" || $semester === "" || $pass === "" || $cpass === "") {

        $message = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif ($pass !== $cpass) {

        $message = "Passwords do not match.";

    } else {
        // full name + hash password
        $full_name = $first . " " . $last;
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        // insert into students table
        // columns: id, full_name, email, password_hash, phone, branch, semester, created_at
        $stmt = $conn->prepare(
            "INSERT INTO students (full_name, email, password_hash, phone, branch, semester)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            $message = "SQL error: " . $conn->error;
        } else {
            $stmt->bind_param("ssssss", $full_name, $email, $hash, $phone, $branch, $semester);

            try {
                if ($stmt->execute()) {
                    // success → alert + go to login page
                    echo "<script>
                            alert('Student account created successfully! Please login.');
                            window.location = 'student_login.html';
                          </script>";
                    exit;
                } else {
                    // duplicate email (unique key)
                    if ($conn->errno === 1062) {
                        $message = "An account with this email already exists.";
                    } else {
                        $message = "Failed to create account. Please try again.";
                    }
                }
            } catch (mysqli_sql_exception $e) {
                $message = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>InternLink — Student Register</title>
</head>
<body>
<?php if ($message !== ""): ?>
    <p style="color:red; font-family: Arial; font-size:14px;">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

<!-- simple fallback view if someone opens student_register.php directly -->
<p>Go back to the <a href="student_register.html">student registration form</a>.</p>
</body>
</html>
