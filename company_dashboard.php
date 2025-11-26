<?php
session_start();
include "../config.php";  // DB connection

// Show errors while developing (optional)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// If company not logged in, redirect
if (!isset($_SESSION["company_id"])) {
    header("Location: company_login.php");
    exit;
}

$companyId   = $_SESSION["company_id"];
$companyName = $_SESSION["company_name"] ?? "Company";
$message = "";

/* ──────────────────────────────
   0) HANDLE DELETE INTERNSHIP
   ────────────────────────────── */
if (isset($_GET["delete_id"])) {
    $deleteId = (int) $_GET["delete_id"];

    $delSql = "DELETE FROM internships WHERE id = ? AND company_id = ?";
    $delStmt = $conn->prepare($delSql);
    if ($delStmt) {
        $delStmt->bind_param("ii", $deleteId, $companyId);
        if ($delStmt->execute()) {
            $message = "Internship deleted successfully.";
        } else {
            $message = "Error deleting internship: " . $delStmt->error;
        }
    } else {
        $message = "SQL ERROR (DELETE): " . $conn->error;
    }
}

/* ──────────────────────────────
   1) HANDLE NEW INTERNSHIP FORM
   ────────────────────────────── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "create") {
    $title    = trim($_POST["title"] ?? "");
    $duration = trim($_POST["duration"] ?? "");
    $stipend  = trim($_POST["stipend"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $mode     = trim($_POST["mode"] ?? "");
    $apply    = trim($_POST["apply_link"] ?? "");

    if ($title === "") {
        $message = "Please enter a title for the internship.";
    } else {
        $sql = "INSERT INTO internships 
                (company_id, title, location, mode, duration, stipend, apply_link, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Open')";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL ERROR (INSERT): " . $conn->error);
        }

        $stmt->bind_param("issssss", $companyId, $title, $location, $mode, $duration, $stipend, $apply);

        if ($stmt->execute()) {
            $message = "Internship created successfully.";
        } else {
            $message = "Error while creating internship: " . $stmt->error;
        }
    }
}

/* ──────────────────────────────
   2) FETCH STATS
   ────────────────────────────── */
$statsSql = "SELECT 
                COUNT(*) AS total_count,
                SUM(status = 'Open')   AS open_count,
                SUM(status = 'Closed') AS closed_count
             FROM internships
             WHERE company_id = ?";
$statsStmt = $conn->prepare($statsSql);

if (!$statsStmt) {
    die("SQL ERROR (STATS): " . $conn->error);
}

$statsStmt->bind_param("i", $companyId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$totalCount  = $stats["total_count"]  ?? 0;
$openCount   = $stats["open_count"]   ?? 0;
$closedCount = $stats["closed_count"] ?? 0;

/* ──────────────────────────────
   3) FETCH INTERNSHIPS LIST
   ────────────────────────────── */
$listSql = "SELECT id, title, location, mode, duration, stipend, status, created_at
            FROM internships
            WHERE company_id = ?
            ORDER BY created_at DESC";

$listStmt = $conn->prepare($listSql);

if (!$listStmt) {
    die("SQL ERROR (LIST): " . $conn->error);
}

$listStmt->bind_param("i", $companyId);
$listStmt->execute();
$internships = $listStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Dashboard | InternLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            margin: 0;
            background: #f5f7fb;
            display: flex;
            font-family: 'Poppins', sans-serif;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            height: 100vh;
            background: #1E293B;
            padding: 25px;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar h2 {
            margin-bottom: 10px;
            font-size: 22px;
            font-weight: 600;
            text-align: center;
        }

        .sidebar .signed {
            font-size: 13px;
            text-align: center;
            margin-top: 5px;
            margin-bottom: 4px;
            color: #CBD5F5;
        }

        .sidebar .company-name {
            text-align: center;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: block;
            padding: 12px 14px;
            margin: 10px 0;
            border-radius: 8px;
            text-decoration: none;
            color: #E2E8F0;
            font-size: 15px;
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #3B82F6;
            color: white;
        }

        .sidebar a.logout {
            background: #EF4444;
            color: white;
            margin-top: 20px;
            text-align: center;
        }

        /* Main content */
        .content {
            margin-left: 260px;
            padding: 30px;
            width: calc(100% - 260px);
        }

        .topbar {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .topbar h1 {
            font-size: 24px;
            font-weight: 600;
        }

        /* Stats */
        .stats-container {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .stat-card {
            flex: 1;
            min-width: 180px;
            background: white;
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .stat-card h3 {
            font-size: 13px;
            font-weight: 600;
            color: #3B82F6;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-card p {
            font-size: 26px;
            margin-top: 6px;
            font-weight: bold;
        }

        /* Layout */
        .two-column {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.4fr);
            gap: 20px;
            margin-top: 25px;
        }

        .card {
            background: white;
            padding: 18px 20px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .card h3 {
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        /* Form */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 18px;
            margin-top: 10px;
        }

        .form-grid .full {
            grid-column: 1 / span 2;
        }

        .form-grid label {
            font-size: 13px;
            font-weight: 600;
        }

        .form-grid input {
            width: 100%;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #d4d7e5;
            margin-top: 5px;
            font-size: 13px;
        }

        .btn-primary {
            display: inline-block;
            background: #3B82F6;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
            margin-top: 15px;
        }

        .btn-primary:hover {
            background: #2456c6;
        }

        .message {
            margin-top: 12px;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 13px;
            background: #DBEAFE;
            color: #1D4ED8;
        }

        /* Table */
        table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        table th, table td {
            padding: 10px 12px;
            border-bottom: 1px solid #edf0fa;
            font-size: 13px;
        }

        table th {
            background: #f0f4ff;
            color: #4f8cff;
            font-weight: 600;
            text-align: left;
        }

        table tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
        }

        .badge-open {
            background: rgba(34,197,94,0.14);
            color: #16a34a;
        }

        .badge-closed {
            background: rgba(239,68,68,0.14);
            color: #dc2626;
        }

        .empty-text {
            font-size: 13px;
            color: #8a8fa3;
            margin-top: 8px;
        }

        .btn-delete {
            padding: 5px 10px;
            border-radius: 6px;
            border: none;
            font-size: 11px;
            background: #EF4444;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>InternLink</h2>
        <div class="signed">Signed in as</div>
        <div class="company-name"><?= htmlspecialchars($companyName) ?></div>

        <a href="company_dashboard.php" class="active">🏠 Dashboard</a>
        <a href="company_internship.php">📢 Internship Listings</a>
        <a href="company_applicant.php">👤 Applicants</a>
        <a href="company_settings.php">⚙️ Settings</a>
        <a href="logout.php" class="logout">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">

        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h1>Company Dashboard</h1>
            </div>
            <p style="font-weight:600; font-size:13px;">Today: <?= date("d M Y"); ?></p>
        </div>

        <!-- Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Internships</h3>
                <p><?= $totalCount ?></p>
            </div>
            <div class="stat-card">
                <h3>Open Positions</h3>
                <p><?= $openCount ?></p>
            </div>
            <div class="stat-card">
                <h3>Closed Positions</h3>
                <p><?= $closedCount ?></p>
            </div>
        </div>

        <!-- Message -->
        <?php if ($message !== ""): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Form + Table -->
        <div class="two-column">

            <!-- Post Internship Form -->
            <div class="card">
                <h3>Post a new internship</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create">

                    <div class="form-grid">
                        <div>
                            <label>Title *</label>
                            <input type="text" name="title" placeholder="e.g. Web Development Intern" required>
                        </div>

                        <div>
                            <label>Duration</label>
                            <input type="text" name="duration" placeholder="e.g. 3 months">
                        </div>

                        <div>
                            <label>Stipend</label>
                            <input type="text" name="stipend" placeholder="e.g. ₹8000 / month">
                        </div>

                        <div>
                            <label>Location</label>
                            <input type="text" name="location" placeholder="e.g. Mumbai / Remote">
                        </div>

                        <div>
                            <label>Mode</label>
                            <input type="text" name="mode" placeholder="Remote / On-site / Hybrid">
                        </div>

                        <div class="full">
                            <label>Apply Link (optional)</label>
                            <input type="url" name="apply_link" placeholder="https://your-careers-page...">
                        </div>
                    </div>

                    <button class="btn-primary" type="submit">Create Internship</button>
                </form>
            </div>

            <!-- Internship List -->
            <div class="card">
                <h3>Your internships</h3>

                <?php if ($internships->num_rows === 0): ?>
                    <p class="empty-text">No internships found. Add your first internship using the form on the left.</p>
                <?php else: ?>
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

                        <?php while ($row = $internships->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row["title"]) ?></td>
                                <td><?= htmlspecialchars($row["location"] ?: "-") ?></td>
                                <td><?= htmlspecialchars($row["mode"] ?: "-") ?></td>
                                <td><?= htmlspecialchars($row["duration"] ?: "-") ?></td>
                                <td><?= htmlspecialchars($row["stipend"] ?: "-") ?></td>
                                <td>
                                    <span class="badge <?= $row["status"] === 'Open' ? 'badge-open' : 'badge-closed' ?>">
                                        <?= htmlspecialchars($row["status"]) ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="btn-delete"
                                       href="company_dashboard.php?delete_id=<?= $row['id'] ?>"
                                       onclick="return confirm('Are you sure you want to delete this internship?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>
</body>
</html>
