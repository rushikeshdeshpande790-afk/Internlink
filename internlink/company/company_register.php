<?php
include "../config.php";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name  = trim($_POST["company_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $pass  = $_POST["password"] ?? "";
    $cpass = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" || $pass === "" || $cpass === "") {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email.";
    } elseif ($pass !== $cpass) {
        $message = "Passwords do not match.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO companies (company_name, email, password_hash) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $name, $email, $hash);

        if ($stmt->execute()) {
            echo "<script>
                    alert('Company account created successfully!');
                    window.location='company_login.php';
                  </script>";
            exit;
        } else {
            if ($conn->errno === 1062) {
                $message = "This email is already registered.";
            } else {
                $message = "Something went wrong. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Company Account | InternLink</title>
    <link rel="stylesheet" href="../global.css">
</head>
<body>

<div class="page-center">
    <div class="card form-card">

        <h2 class="form-title">Create Company Account</h2>
        <p class="form-subtitle">Register your company on InternLink</p>

        <?php if ($message !== ""): ?>
            <p class="form-error"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" class="form-grid">

            <div class="form-group">
                <label>Company Name</label>
                <input type="text" name="company_name" placeholder="Enter company name" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="company@example.com" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Create password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Re-enter password" required>
            </div>

            <button class="btn full-btn">Create Account</button>
        </form>

        <p class="form-footer">
            Already have an account?
            <a href="company_login.php" class="link">Login</a>
        </p>
    </div>
</div>

</body>
</html>
