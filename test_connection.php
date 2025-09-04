<?php
// Test Database Connection for The Laurels School LMS
echo "<h2>The Laurels School LMS - Database Connection Test</h2>";

try {
    // Test database connection
    $pdo = new PDO(
        "mysql:host=localhost;dbname=the_laurels_school;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check if tables exist
    $tables = ['users', 'students', 'activity_logs'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' does not exist</p>";
        }
    }
    
    // Check if admin user exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetch()['count'];
    
    if ($adminCount > 0) {
        echo "<p style='color: green;'>✅ Admin user exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Admin user does not exist</p>";
    }
    
    // Show table structure
    echo "<h3>Students Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE students");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Please run the database/setup.sql script first!</strong></p>";
}
?>

<hr>
<h3>Next Steps:</h3>
<ol>
    <li>If connection failed, run: <code>database/setup.sql</code></li>
    <li>If successful, go to: <a href="index.php">Login Page</a></li>
    <li>Login with: admin@laurelsschool.com / admin123</li>
</ol> 