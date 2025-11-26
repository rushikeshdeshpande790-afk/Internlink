<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit;
}

$adminName = $_SESSION["admin_name"] ?? "Admin";

// counts
$studentsCount = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()["c"];
$companiesCount = $conn->query("SELECT COUNT(*) AS c FROM companies")->fetch_assoc()["c"];
$internshipsCount = $conn->query("SELECT COUNT(*) AS c FROM internships")->fetch_assoc()["c"];

// latest 5 students & companies
$students = $conn->query("SELECT id, full_name, email, branch, semester, created_at FROM students ORDER BY created_at DESC LIMIT 5");
$companies = $conn->query("SELECT id, company_name, email, created_at FROM companies ORDER BY created_at DESC LIMIT 5");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>InternLink — Admin Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../internlink.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100"
      style="font-family:'Inter',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">

  <header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-violet-600 text-sm font-bold">
          IL
        </span>
        <div>
          <div class="text-sm font-semibold">InternLink Admin</div>
          <div class="text-xs text-slate-400">Managing students & companies</div>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-slate-300">Hello, <?php echo htmlspecialchars($adminName); ?></span>
        <a href="../index.html" class="text-xs text-slate-400 hover:underline">Back to Site</a>
      </div>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 py-8 space-y-8">
    <!-- Stats -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
        <div class="text-xs text-slate-400 mb-1">Students</div>
        <div class="text-2xl font-semibold"><?php echo $studentsCount; ?></div>
      </div>
      <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
        <div class="text-xs text-slate-400 mb-1">Companies</div>
        <div class="text-2xl font-semibold"><?php echo $companiesCount; ?></div>
      </div>
      <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
        <div class="text-xs text-slate-400 mb-1">Internships</div>
        <div class="text-2xl font-semibold"><?php echo $internshipsCount; ?></div>
      </div>
    </section>

    <!-- Latest students & companies -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
        <h2 class="text-sm font-semibold mb-3">Latest Students</h2>
        <table class="w-full text-xs text-left">
          <thead class="text-slate-400 border-b border-slate-800">
          <tr>
            <th class="py-1">Name</th>
            <th class="py-1">Branch</th>
            <th class="py-1">Sem</th>
          </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
          <?php while ($s = $students->fetch_assoc()): ?>
            <tr>
              <td class="py-1"><?php echo htmlspecialchars($s["full_name"]); ?></td>
              <td class="py-1"><?php echo htmlspecialchars($s["branch"]); ?></td>
              <td class="py-1"><?php echo htmlspecialchars($s["semester"]); ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4">
        <h2 class="text-sm font-semibold mb-3">Latest Companies</h2>
        <table class="w-full text-xs text-left">
          <thead class="text-slate-400 border-b border-slate-800">
          <tr>
            <th class="py-1">Company</th>
            <th class="py-1">Email</th>
          </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
          <?php while ($c = $companies->fetch_assoc()): ?>
            <tr>
              <td class="py-1"><?php echo htmlspecialchars($c["company_name"]); ?></td>
              <td class="py-1"><?php echo htmlspecialchars($c["email"]); ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
