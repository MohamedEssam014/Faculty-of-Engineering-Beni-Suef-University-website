<?php
session_start();

// إلغاء تعيين متغيرات جلسة المحاضرات فقط
unset($_SESSION['is_lecture_manager']);
unset($_SESSION['lecture_teacher_id']);
unset($_SESSION['lecture_teacher_name']);

// إعادة التوجيه إلى صفحة تسجيل دخول المحاضرات
header("Location: lecture_login.php");
exit;
?>
