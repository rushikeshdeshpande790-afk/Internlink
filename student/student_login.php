<?php
// student/student_login.php
session_start();
require_once "../config.php";  // DB connection from root

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter both email and password.";
    } else {
        // find student by email
        $stmt = $conn->prepare(
            "SELECT id, full_name, email, password_hash, branch, semester
             FROM students
             WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if ($student && password_verify($password, $student["password_hash"])) {
            // login OK → store in session
            $_SESSION["student_id"]       = $student["id"];
            $_SESSION["student_name"]     = $student["full_name"];
            $_SESSION["student_branch"]   = $student["branch"];
            $_SESSION["student_semester"] = $student["semester"];

            // redirect to student dashboard
            header("Location: student_dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>InternLink — Student Login</title>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Global styles (btn-anim etc.) - go up one folder -->
    <link rel="stylesheet" href="../internlink.css">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">

</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4"
      style="font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">

    <div class="w-full max-w-md bg-slate-900/80 border border-slate-800 rounded-2xl shadow-xl p-8">

        <!-- Logo / title -->
        <div class="flex items-center justify-center gap-3 mb-6">
            <div class="bg-violet-500 text-white rounded-lg p-2">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none">
                    <path d="M10 14a3 3 0 01-4.24 0l-1.5-1.5a3 3 0 114.24-4.24L10 9.76"
                          stroke="currentColor" stroke-width="1.5"
                          stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M14 10a3 3 0 014.24 0l1.5 1.5a3 3 0 11-4.24 4.24L14 14.24"
                          stroke="currentColor" stroke-width="1.5"
                          stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <span class="text-violet-400 font-bold text-2xl">InternLink</span>
        </div>

        <h2 class="text-xl font-semibold text-center mb-2">Student Login</h2>
        <p class="text-center text-slate-400 mb-6 text-sm">
            Sign in to access your personalized internship dashboard.
        </p>

        <?php if ($error !== ""): ?>
            <div class="mb-4 rounded-xl border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-300">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Email</label>
                <input
                    type="email"
                    name="email"
                    required
                    placeholder="you@example.com"
                    class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700
                           text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    placeholder="Enter your password"
                    class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700
                           text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                />
            </div>

            <button
                type="submit"
                class="mt-2 w-full bg-violet-500 hover:bg-violet-600 text-white font-semibold py-3 rounded-full
                       shadow-lg shadow-violet-500/30 transition btn-anim">
                Login
            </button>
        </form>

        <div class="mt-6 text-sm text-slate-400 flex flex-col gap-2 items-center">
            <p>
                Don’t have an account?
                <a href="student_register.html" class="text-violet-400 hover:underline">
                    Create one
                </a>
            </p>
            <p>
                ← <a href="/internlink/index.html" class="text-slate-300 hover:underline">
                    Back to Home
                </a>
            </p>
        </div>
    </div>

</body>
</html>
