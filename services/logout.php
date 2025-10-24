<?php
session_start();

// إلغاء تعيين جميع متغيرات الجلسة
$_SESSION = array();

// تدمير الجلسة
session_destroy();

// إعادة التوجيه إلى صفحة تسجيل الدخول
header("Location: login.php");
exit;
?>
