<?php
// seed_admin.php – run ONCE to create initial admin

// go up one folder and include your DB connection
include "../config.php";

// admin details you want
$name  = "InternLink Admin";
$email = "admininternlink@gmail.com";
$plainPassword = "admin@123";

// create secure hash using PHP
$hash = password($plainPassword);

// prepare insert
$sql = "INSERT INTO admins (name, email, password) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("sss", $name, $email, $plainPassword);

if ($stmt->execute()) {
    echo "✅ Admin user inserted successfully!<br>";
    echo "Email: $email<br>";
    echo "Password: $plainPassword<br>";
   
} else {
    echo "❌ Insert failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
