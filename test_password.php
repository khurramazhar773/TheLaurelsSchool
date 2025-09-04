<?php
// Test Password Hash for Admin Login
echo "<h2>Password Hash Test</h2>";

$password = 'admin123';
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "<p><strong>Testing password:</strong> $password</p>";
echo "<p><strong>Hash:</strong> $hash</p>";

// Test if the hash works
if (password_verify($password, $hash)) {
    echo "<p style='color: green;'>✅ Password hash is working correctly!</p>";
} else {
    echo "<p style='color: red;'>❌ Password hash is NOT working!</p>";
}

// Generate a new hash for comparison
$new_hash = password_hash($password, PASSWORD_DEFAULT);
echo "<p><strong>New hash generated:</strong> $new_hash</p>";

// Test the new hash
if (password_verify($password, $new_hash)) {
    echo "<p style='color: green;'>✅ New hash is working correctly!</p>";
} else {
    echo "<p style='color: red;'>❌ New hash is NOT working!</p>";
}

echo "<hr>";
echo "<h3>Use this working hash in your database:</h3>";
echo "<code>$new_hash</code>";

echo "<hr>";
echo "<h3>SQL to update admin password:</h3>";
echo "<pre>";
echo "UPDATE users SET password = '$new_hash' WHERE email = 'admin@laurelsschool.com';";
echo "</pre>";
?> 