<?php
session_start();
include "../config.php";

if (!isset($_SESSION["company_id"])) {
    header("Location: company_login.php");
    exit;
}

$companyId   = $_SESSION["company_id"];
$companyName = $_SESSION["company_name"] ?? "Company";

/* Fetch applicants for this company's internships */
$sql = "
    SELECT 
        applications.id,
        applications.student_id,
        applications.status,
        applications.applied_at,
        internships.title
    FROM applications
    JOIN internships ON internships.id = applications.internship_id
    WHERE internships.company_id = ?
    ORDER BY applications.applied_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $companyId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Applicants | InternLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { margin:0; background:#f5f7fb; display:flex; font-family:'Poppins',sans-serif; }
        .sidebar { width:240px; height:100vh; background:#1E293B; padding:25px; color:white; position:fixed; left:0; top:0; }
        .sidebar h2 { margin-bottom:10px; font-size:22px; font-weight:600; text-align:center; }
        .signed { font-size:13px; text-align:center; margin:5px 0 4px; color:#CBD5F5; }
        .company-name { text-align:center; font-size:15px; font-weight:600; margin-bottom:20px; }
        .sidebar a { display:block; padding:12px 14px; margin:10px 0; border-radius:8px; text-decoration:none; color:#E2E8F0; font-size:15px; transition:.3s; }
        .sidebar a:hover, .sidebar a.active { background:#3B82F6; color:white; }
        .sidebar a.logout { background:#EF4444; margin-top:20px; text-align:center; }
        .content { margin-left:260px; padding:30px; width:calc(100% - 260px); }
        .topbar { background:white; padding:15px 25px; border-radius:12px; margin-bottom:25px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 3px 10px rgba(0,0,0,0.08); }
        h1 { font-size:24px; font-weight:600; }
        .card { background:white; padding:18px 20px; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.08); }
        table { width:100%; border-collapse:collapse; margin-top:10px; font-size:13px; }
        th, td { padding:10px 12px; border-bottom:1px solid #edf0fa; }
        th { background:#f0f4ff; color:#4f8cff; font-weight:600; text-align:left; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>InternLink</h2>
    <div class="signed">Signed in as</div>
    <div class="company-name"><?= htmlspecialchars($companyName) ?></div>

    <a href="company_dashboard.php">🏠 Dashboard</a>
    <a href="company_internship.php">📢 Internship Listings</a>
    <a href="company_applicant.php" class="active">👤 Applicants</a>
    <a href="company_settings.php">⚙️ Settings</a>
    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<div class="content">
    <div class="topbar">
        <h1>Applicants</h1>
        <span style="font-size:13px;font-weight:600;">Today: <?= date("d M Y"); ?></span>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>Internship Title</th>
                <th>Student ID</th>
                <th>Status</th>
                <th>Applied At</th>
            </tr>
            <?php if ($result->num_rows === 0): ?>
                <tr><td colspan="4">No applicants yet.</td></tr>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["title"]) ?></td>
                        <td><?= htmlspecialchars($row["student_id"]) ?></td>
                        <td><?= htmlspecialchars($row["status"]) ?></td>
                        <td><?= htmlspecialchars($row["applied_at"]) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
