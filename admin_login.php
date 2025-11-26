<?php
session_start();

// IMPORTANT: correct path to config.php
require_once __DIR__ . '/../config.php';  // this fixes the include error

$error_message = "";

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $error_message = "Please enter email and password.";
    } else {
        // 🔴 CHANGE table/column names here if your table is called `admins` instead of `admin`
        $sql = "SELECT id, email, password FROM admin WHERE email = ? AND password = ? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL error: " . $conn->error);
        }

        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // login ok
            $_SESSION["admin_id"]    = $row["id"];
            $_SESSION["admin_email"] = $row["email"];

            header("Location: admin_dashboard.php"); // in same /admin folder
            exit;
        } else {
            $error_message = "Invalid admin email or password.";
        }
    }
}
?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Login | InternLink</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    body { font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  </style>
</head>

<body class="bg-slate-950 text-slate-50 flex items-center justify-center min-h-screen">

  <div class="bg-slate-900/80 border border-slate-800 rounded-2xl shadow-xl w-full max-w-md p-8">
    <!-- Logo -->
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 rounded-xl bg-violet-500 flex items-center justify-center text-white font-bold">
        IL
      </div>
      <div>
        <div class="text-xs uppercase tracking-wide text-slate-400">INTERNLINK</div>
        <div class="text-sm font-semibold text-slate-100">Admin Panel</div>
      </div>
    </div>

    <h1 class="text-xl font-semibold mb-1">Sign in</h1>
    <p class="text-sm text-slate-400 mb-6">Sign in as InternLink team member.</p>

    <?php if (!empty($error_message)) : ?>
      <div class="mb-4 rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-200">
        <?= htmlspecialchars($error_message) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-xs font-medium text-slate-300 mb-1">Email</label>
        <input
          type="email"
          name="email"
          required
          class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2.5 text-sm text-slate-100
                 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
          placeholder="abc@gmail.com"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-300 mb-1">Password</label>
        <input
          type="password"
          name="password"
          required
          class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2.5 text-sm text-slate-100
                 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
          placeholder="Enter password"
        />
      </div>

      <button
        type="submit"
        class="w-full mt-2 rounded-full bg-violet-500 hover:bg-violet-600 text-sm font-semibold py-2.5
               shadow-lg shadow-violet-500/30 transition">
        Login
      </button>
    </form>

    <div class="mt-6 text-center">
      <a href="../index.html"
         class="text-xs text-slate-400 hover:text-slate-200 underline underline-offset-2">
        ← Back to Home
      </a>
    </div>
  </div>

</body>
</html>
