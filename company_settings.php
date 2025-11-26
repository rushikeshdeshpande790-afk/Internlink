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

/* Fetch current data */
$sql = "SELECT company_name, email FROM companies WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $companyId);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();

/* Handle update */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newName  = trim($_POST["company_name"] ?? "");
    $newEmail = trim($_POST["email"] ?? "");

    if ($newName === "" || $newEmail === "") {
        $message = "Name and email cannot be empty.";
    } else {
        $up = $conn->prepare("UPDATE companies SET company_name = ?, email = ? WHERE id = ?");
        $up->bind_param("ssi", $newName, $newEmail, $companyId);
        if ($up->execute()) {
            $message = "Profile updated.";
            $_SESSION["company_name"] = $newName;
            $current["company_name"]  = $newName;
            $current["email"]         = $newEmail;
        } else {
            $message = "Error updating profile.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings | InternLink</title>
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
        .card { background:white; padding:18px 20px; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.08); max-width:450px; }
        label { font-size:13px; font-weight:600; display:block; margin-top:10px; }
        input { width:100%; padding:8px 10px; border-radius:8px; border:1px solid #d4d7e5; margin-top:4px; font-size:13px; }
        .btn { margin-top:15px; padding:10px 18px; border:none; border-radius:8px; background:#3B82F6; color:white; cursor:pointer; font-size:14px; }
        .message { margin-bottom:12px; padding:8px 10px; border-radius:8px; background:#DBEAFE; color:#1D4ED8; font-size:13px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>InternLink</h2>
    <div class="signed">Signed in as</div>
    <div class="company-name"><?= htmlspecialchars($companyName) ?></div>

    <a href="company_dashboard.php">🏠 Dashboard</a>
    <a href="company_internships.php">📢 Internship Listings</a>
    <a href="company_applicants.php">👤 Applicants</a>
    <a href="company_settings.php" class="active">⚙️ Settings</a>
    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<div class="content">
    <div class="topbar">
        <h1>Settings</h1>
        <span style="font-size:13px;font-weight:600;">Today: <?= date("d M Y"); ?></span>
    </div>

    <div class="card">
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Company Name</label>
            <input type="text" name="company_name" value="<?= htmlspecialchars($current["company_name"] ?? "") ?>">

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($current["email"] ?? "") ?>">

            <button class="btn" type="submit">Save Changes</button>
        </form>
    </div>
</div>
</body>
</html>
