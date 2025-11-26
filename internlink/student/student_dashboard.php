<?php
session_start();
include "../config.php";

// 1. LOGIN CHECK ----------------------------------------------------------
if (!isset($_SESSION["student_id"])) {
    header("Location: student_login.php");
    exit;
}

$student_id = $_SESSION["student_id"];

// 2. LOAD STUDENT INFO ----------------------------------------------------
$stuStmt = $conn->prepare(
    "SELECT full_name, branch, semester 
     FROM students 
     WHERE id = ?"
);
$stuStmt->bind_param("i", $student_id);
$stuStmt->execute();
$stuRes = $stuStmt->get_result();
$stu = $stuRes->fetch_assoc();

$studentName   = $stu["full_name"] ?? "Student";
$studentBranch = $stu["branch"] ?? "";
$studentSem    = $stu["semester"] ?? "";

// 3. LOAD JOINED INTERNSHIP IDS (for JOINED badge) -----------------------
$joinedIds = [];
$ji = $conn->prepare(
    "SELECT internship_id FROM student_internships WHERE student_id = ?"
);
$ji->bind_param("i", $student_id);
$ji->execute();
$jiRes = $ji->get_result();
while ($row = $jiRes->fetch_assoc()) {
    $joinedIds[] = (int)$row["internship_id"];
}

// 4. RECOMMENDED INTERNSHIPS (based on branch in title) ------------------
$recommended = [];
if ($studentBranch !== "") {
    $like = "%" . $studentBranch . "%";
    $recStmt = $conn->prepare(
        "SELECT i.*, c.company_name
         FROM internships i
         JOIN companies c ON i.company_id = c.id
         WHERE i.title LIKE ?
         ORDER BY i.created_at DESC
         LIMIT 4"
    );
    $recStmt->bind_param("s", $like);
    $recStmt->execute();
    $recRes = $recStmt->get_result();
    while ($row = $recRes->fetch_assoc()) {
        $recommended[] = $row;
    }
}

// 5. ALL INTERNSHIPS (for "All recent internships" section) --------------
$internships = [];
$listStmt = $conn->prepare(
    "SELECT i.*, c.company_name
     FROM internships i
     JOIN companies c ON i.company_id = c.id
     ORDER BY i.created_at DESC
     LIMIT 20"
);
$listStmt->execute();
$listRes = $listStmt->get_result();
while ($row = $listRes->fetch_assoc()) {
    $internships[] = $row;
}

// 6. JOINED INTERNSHIPS (data for "Your joined internships") -------------
$myJoined = [];
$js = $conn->prepare(
    "SELECT i.*, c.company_name, si.joined_at
     FROM student_internships si
     JOIN internships i ON si.internship_id = i.id
     JOIN companies c ON i.company_id = c.id
     WHERE si.student_id = ?
     ORDER BY si.joined_at DESC"
);
$js->bind_param("i", $student_id);
$js->execute();
$jsRes = $js->get_result();
while ($row = $jsRes->fetch_assoc()) {
    $myJoined[] = $row;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>InternLink — Student Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .card {
      background: #0f172a;
      border-radius: 18px;
      border: 1px solid rgba(148,163,184,0.35);
      box-shadow:
        0 24px 60px rgba(15,23,42,0.8),
        0 0 0 1px rgba(15,23,42,0.8);
    }
  </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-50">

<!-- TOP BAR -------------------------------------------------------------->
<header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur sticky top-0 z-20">
  <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="bg-violet-600 text-white rounded-xl w-9 h-9 flex items-center justify-center font-bold">
        IL
      </div>
      <div>
        <p class="text-sm font-semibold">InternLink</p>
        <p class="text-xs text-slate-400">Student Dashboard</p>
      </div>
    </div>
    <div class="flex items-center gap-4">
      <div class="hidden sm:block text-right">
        <p class="text-sm font-semibold">
          <?php echo htmlspecialchars($studentName); ?>
        </p>
        <p class="text-xs text-slate-400">
          <?php echo htmlspecialchars($studentBranch ?: "Branch"); ?> ·
          <?php echo htmlspecialchars($studentSem ?: "Sem"); ?>
        </p>
      </div>
      <a href="student_logout.php"
         class="px-3 py-1.5 rounded-full bg-slate-800 hover:bg-slate-700 text-xs">
        Logout
      </a>
    </div>
  </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-6 space-y-6">

  <!-- GREETING + PROFILE CARDS ------------------------------------------>
  <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="card p-5 md:col-span-2">
      <p class="text-xs text-slate-400 mb-1">Welcome back,</p>
      <h1 class="text-2xl sm:text-3xl font-semibold mb-2">
        <?php echo htmlspecialchars($studentName); ?> 👋
      </h1>
      <p class="text-sm text-slate-300">
        Explore internships posted by companies on InternLink. You can check recommendations
        based on your branch and apply directly.
      </p>
    </div>

    <div class="card p-5 flex flex-col justify-between">
      <div>
        <p class="text-xs text-slate-400 mb-1">Profile</p>
        <p class="text-sm font-medium">
          Branch:
          <span class="text-slate-100">
            <?php echo htmlspecialchars($studentBranch ?: "Not set"); ?>
          </span>
        </p>
        <p class="text-sm">
          Semester:
          <span class="text-slate-100">
            <?php echo htmlspecialchars($studentSem ?: "Not set"); ?>
          </span>
        </p>
      </div>
      <a href="../index.html"
         class="mt-3 inline-flex items-center justify-center px-3 py-1.5 rounded-full bg-violet-600 text-xs font-semibold hover:bg-violet-500">
        Back to Home
      </a>
    </div>
  </section>

  <!-- RECOMMENDED INTERNSHIPS ------------------------------------------->
  <section class="space-y-3">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">Recommended internships</h2>
      <span class="text-xs text-slate-400">
        Based on your branch: <?php echo htmlspecialchars($studentBranch ?: "General"); ?>
      </span>
    </div>

    <?php if (count($recommended) === 0): ?>
      <p class="text-sm text-slate-400">
        No branch-specific recommendations yet. Check all open internships below.
      </p>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($recommended as $rec): ?>
          <article class="card p-4 flex flex-col gap-2">
            <div class="flex items-center justify-between gap-2">
              <div>
                <h3 class="text-sm font-semibold text-violet-300">
                  <?php echo htmlspecialchars($rec["title"]); ?>
                </h3>
                <p class="text-xs text-slate-400">
                  <?php echo htmlspecialchars($rec["company_name"]); ?>
                </p>
              </div>
              <span class="text-[10px] px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/40">
                Recommended
              </span>
            </div>

            <p class="text-xs text-slate-300">
              <?php echo htmlspecialchars($rec["location"]); ?> ·
              <?php echo htmlspecialchars($rec["mode"]); ?> ·
              <?php echo htmlspecialchars($rec["duration"]); ?>
            </p>

            <p class="text-xs text-slate-300">
              Stipend: ₹<?php echo htmlspecialchars($rec["stipend"]); ?>
            </p>

            <div class="mt-3 flex justify-end">
              <?php if (!empty($rec["apply_link"])): ?>
                <a href="<?php echo htmlspecialchars($rec["apply_link"]); ?>" target="_blank"
                   class="inline-flex items-center text-xs px-3 py-1.5 rounded-full bg-violet-600 hover:bg-violet-500">
                  Apply
                </a>
              <?php else: ?>
                <span class="text-[11px] text-slate-500">Apply link not provided</span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- YOUR JOINED INTERNSHIPS ------------------------------------------->
  <section class="space-y-3 mt-6">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">Your joined internships</h2>
      <span class="text-xs text-slate-400">
        You have joined <?php echo count($myJoined); ?> internship(s)
      </span>
    </div>

    <?php if (count($myJoined) === 0): ?>
      <p class="text-sm text-slate-400">
        You haven’t joined any internships yet. Use the <strong>Join</strong> button in the list below.
      </p>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($myJoined as $j): ?>
          <article class="card p-4 flex flex-col gap-2">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-semibold text-violet-300">
                  <?php echo htmlspecialchars($j["title"]); ?>
                </h3>
                <p class="text-xs text-slate-400">
                  <?php echo htmlspecialchars($j["company_name"]); ?>
                </p>
              </div>
              <span class="text-[10px] px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/40">
                Joined
              </span>
            </div>

            <p class="text-xs text-slate-300">
              <?php echo htmlspecialchars($j["location"]); ?> ·
              <?php echo htmlspecialchars($j["mode"]); ?> ·
              <?php echo htmlspecialchars($j["duration"]); ?>
            </p>

            <p class="text-xs text-slate-400">
              Joined on:
              <?php echo htmlspecialchars(
                  date("d M Y", strtotime($j["joined_at"]))
              ); ?>
            </p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- ALL RECENT INTERNSHIPS -------------------------------------------->
  <section class="space-y-3 mt-6">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">All recent internships</h2>
      <span class="text-xs text-slate-400">Latest opportunities from all companies</span>
    </div>

    <?php if (count($internships) === 0): ?>
      <p class="text-sm text-slate-400">
        No internships posted yet.
      </p>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($internships as $job): ?>
          <article class="card p-4 flex flex-col gap-2">
            <div>
              <h3 class="text-sm font-semibold text-violet-300">
                <?php echo htmlspecialchars($job["title"]); ?>
              </h3>
              <p class="text-xs text-slate-400">
                <?php echo htmlspecialchars($job["company_name"]); ?>
              </p>
            </div>

            <p class="text-xs text-slate-300">
              <?php echo htmlspecialchars($job["location"]); ?> ·
              <?php echo htmlspecialchars($job["mode"]); ?> ·
              <?php echo htmlspecialchars($job["duration"]); ?>
            </p>

            <p class="text-xs text-slate-300">
              Stipend: ₹<?php echo htmlspecialchars($job["stipend"]); ?>
            </p>

            <div class="mt-3 flex justify-between items-center text-[11px] text-slate-400">
              <span>
                Posted:
                <?php echo htmlspecialchars(
                    date("d M Y", strtotime($job["created_at"]))
                ); ?>
              </span>

              <div class="flex gap-2 items-center">
                <?php if (!empty($job["apply_link"])): ?>
                  <a href="<?php echo htmlspecialchars($job["apply_link"]); ?>" target="_blank"
                     class="inline-flex items-center text-xs px-3 py-1.5 rounded-full bg-slate-800 hover:bg-slate-700">
                    View / Apply
                  </a>
                <?php endif; ?>

                <?php if (in_array((int)$job["id"], $joinedIds)): ?>
                  <span class="inline-flex items-center text-xs px-3 py-1.5 rounded-full bg-emerald-600/10 text-emerald-300 border border-emerald-500/50">
                    Joined
                  </span>
                <?php else: ?>
                  <form method="POST" action="join_internship.php" class="inline">
                    <input type="hidden" name="internship_id" value="<?php echo (int)$job["id"]; ?>">
                    <button type="submit"
                      class="inline-flex items-center text-xs px-3 py-1.5 rounded-full bg-violet-600 hover:bg-violet-500">
                      Join
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</main>
</body>
</html>
