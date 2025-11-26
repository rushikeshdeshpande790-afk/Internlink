<?php
session_start();
include "../config.php";

if (!isset($_SESSION["company_id"])) {
    header("Location: company_login.php");
    exit;
}

$companyId   = $_SESSION["company_id"];
$companyName = $_SESSION["company_name"] ?? "Company";
$message = "";

/* Handle open/close actions */
if (isset($_GET["action"], $_GET["id"])) {
    $id     = (int) $_GET["id"];
    $action = $_GET["action"] === "close" ? "Closed" : "Open";

    $sql = "UPDATE internships SET status = ? WHERE id = ? AND company_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $action, $id, $companyId);
    $stmt->execute();
    $message = "Internship status updated.";
}

/* Fetch internships */
$sql = "SELECT id, title, location, mode, duration, stipend, status, created_at
        FROM internships
        WHERE company_id = ?
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $companyId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Internship Listings | InternLink</title>
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
        .badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; }
        .badge-open { background:rgba(34,197,94,0.14); color:#16a34a; }
        .badge-closed { background:rgba(239,68,68,0.14); color:#dc2626; }
        .btn-small { padding:5px 10px; border-radius:6px; border:none; cursor:pointer; font-size:11px; text-decoration:none; }
        .btn-close { background:#F97316; color:white; }
        .btn-open { background:#22C55E; color:white; }
        .message { margin-bottom:12px; padding:8px 10px; border-radius:8px; background:#DBEAFE; color:#1D4ED8; font-size:13px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>InternLink</h2>
    <div class="signed">Signed in as</div>
    <div class="company-name"><?= htmlspecialchars($companyName) ?></div>

    <a href="company_dashboard.php">🏠 Dashboard</a>
    <a href="company_internships.php" class="active">📢 Internship Listings</a>
    <a href="company_applicants.php">👤 Applicants</a>
    <a href="company_settings.php">⚙️ Settings</a>
    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<div class="content">
    <div class="topbar">
        <h1>Internship Listings</h1>
        <span style="font-size:13px;font-weight:600;">Today: <?= date("d M Y"); ?></span>
    </div>

    <div class="card">
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <table>
            <tr>
                <th>Title</th>
                <th>Location</th>
                <th>Mode</th>
                <th>Duration</th>
                <th>Stipend</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php if ($result->num_rows === 0): ?>
                <tr><td colspan="7">No internships posted yet.</td></tr>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["title"]) ?></td>
                        <td><?= htmlspecialchars($row["location"]) ?></td>
                        <td><?= htmlspecialchars($row["mode"]) ?></td>
                        <td><?= htmlspecialchars($row["duration"]) ?></td>
                        <td><?= htmlspecialchars($row["stipend"]) ?></td>
                        <td>
                            <span class="badge <?= $row["status"] === 'Open' ? 'badge-open' : 'badge-closed' ?>">
                                <?= htmlspecialchars($row["status"]) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row["status"] === "Open"): ?>
                                <a class="btn-small btn-close" href="?action=close&id=<?= $row['id'] ?>">Close</a>
                            <?php else: ?>
                                <a class="btn-small btn-open" href="?action=open&id=<?= $row['id'] ?>">Reopen</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
