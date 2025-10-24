<?php
// التحقق إذا لم تكن هناك جلسة نشطة، قم ببدء واحدة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'db';
$dbname = 'student_grades_db';
$username = 'bsu_user';
$password = 'bsu_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage());
    die("Database connection error.");
}
?>
