<?php
session_start();
include "../config.php";

// only logged in students
if (!isset($_SESSION["student_id"])) {
    header("Location: student_login.php");
    exit;
}

$student_id = $_SESSION["student_id"];

// internship id from form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $internship_id = intval($_POST["internship_id"] ?? 0);

    if ($internship_id <= 0) {
        header("Location: student_dashboard.php?msg=invalid");
        exit;
    }

    // insert join record
    $stmt = $conn->prepare(
        "INSERT INTO student_internships (student_id, internship_id) VALUES (?, ?)"
    );
    $stmt->bind_param("ii", $student_id, $internship_id);

    try {
        $stmt->execute();
        header("Location: student_dashboard.php?msg=joined");
    } catch (mysqli_sql_exception $e) {
        // 1062 => already joined (duplicate unique key)
        if ($conn->errno === 1062) {
            header("Location: student_dashboard.php?msg=already");
        } else {
            header("Location: student_dashboard.php?msg=error");
        }
    }
    exit;
}

// if accessed directly
header("Location: student_dashboard.php");
exit;
