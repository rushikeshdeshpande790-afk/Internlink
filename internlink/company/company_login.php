<?php
// Show errors while developing (optional but helpful)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include "../config.php";

$message = "";

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $pass  = trim($_POST["password"] ?? "");

    if ($email === "" || $pass === "") {
        $message = "Please enter email and password.";
    } else {
        // Fetch company with this email
        $sql = "SELECT id, company_name, password_hash FROM companies WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            // If prepare fails, show error
            $message = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Verify password
                if (password_verify($pass, $row["password_hash"])) {
                    // ✅ Login success — set session and redirect
                    $_SESSION["company_id"]   = $row["id"];
                    $_SESSION["company_name"] = $row["company_name"];

                    // IMPORTANT: no echo/HTML before this header
                    header("Location: company_dashboard.php");
                    exit;
                } else {
                    $message = "Incorrect password.";
                }
            } else {
                $message = "No account found with this email.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Login | InternLink</title>
    <link rel="stylesheet" href="../global.css">
    <style>
        body {
            background: #f5f7fb;
            font-family: 'Poppins', system-ui, sans-serif;
        }
        .page-center {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: white;
            border-radius: 18px;
            padding: 26px 26px 22px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 12px 40px rgba(15,23,42,0.18);
        }
        .form-title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
        }
        .form-subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 18px;
            font-size: 13px;
        }
        .form-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 12px;
        }
        .form-group {
            margin-top: 10px;
            font-size: 13px;
        }
        .form-group label {
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
        }
        .form-group input {
            width: 100%;
            padding: 10px 11px;
            border-radius: 10px;
            border: 1px solid #d4d7e5;
            font-size: 13px;
        }
        .btn {
            margin-top: 16px;
            width: 100%;
            background: #4f8cff;
            color: white;
            padding: 10px 0;
            border-radius: 999px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:hover {
            background: #3a6ed1;
        }
        .form-footer {
            text-align: center;
            margin-top: 14px;
            font-size: 13px;
            color: #6b7280;
        }
        .form-footer a {
            color: #4f8cff;
            font-weight: 600;
            text-decoration: none;
        }
        .form-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="page-center">
    <div class="card">
        <h2 class="form-title">Company Login</h2>
        <p class="form-subtitle">Access your InternLink company dashboard</p>

        <?php if ($message !== ""): ?>
            <div class="form-error">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="company@example.com" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <p class="form-footer">
            New company?
            <a href="company_register.php">Create an account</a>
        </p>
    </div>
</div>

</body>
</html>
