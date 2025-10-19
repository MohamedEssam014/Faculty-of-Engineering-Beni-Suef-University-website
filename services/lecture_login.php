<?php
session_start();
$error_message = '';

// --- بيانات اعتماد الدخول لرفع المحاضرات ---
define('LECTURE_USERNAME', 'teacher'); // يمكن تغييره
define('LECTURE_PASSWORD', 'LecturePass123'); // اختر كلمة مرور قوية!
define('TEACHER_USER_ID', 2); // !! هام: ضع هنا الـ ID الخاص بحساب المدرس من جدول users
// ------------------------------------

// إذا كان مسجلاً دخوله بالفعل، انقله لصفحة المحاضرات
if (isset($_SESSION['is_lecture_manager']) && $_SESSION['is_lecture_manager'] === true) {
    header("Location: teacher_lectures.php");
    exit;
}

// معالجة محاولة الدخول
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === LECTURE_USERNAME && $password === LECTURE_PASSWORD) {
        // نجاح! تسجيل الجلسة وتخزين ID المدرس
        $_SESSION['is_lecture_manager'] = true;
        $_SESSION['lecture_teacher_id'] = TEACHER_USER_ID; // تخزين ID المدرس الثابت
        $_SESSION['lecture_teacher_name'] = "مدرس المحاضرات"; // اسم افتراضي

        header("Location: teacher_lectures.php");
        exit;
    } else {
        $error_message = "خطأ في اسم المستخدم أو كلمة المرور.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول - إدارة المحاضرات</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo"><img src="https://www2.0zz0.com/2025/10/08/14/393349642.png" alt="شعار كلية الهندسة"><h1>كلية الهندسة</h1></div>
            <nav></nav> </div>
    </header>

    <main>
        <div class="container">
            <section class="page-content" style="max-width: 500px; margin: 40px auto;">
                <h2>تسجيل الدخول - إدارة المحاضرات</h2>

                <?php if (!empty($error_message)): ?>
                    <div style="padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 5px; text-align: center; margin-bottom: 20px; font-weight: bold;">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form action="lecture_login.php" method="post" class="contact-form">
                    <div class="form-group">
                        <label for="username">اسم المستخدم</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">كلمة المرور</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="submit-btn">تسجيل الدخول</button>
                    <div style="text-align: center; margin-top: 15px; font-size: 14px; color: #555;">
                        <small>
                        بيانات الدخول (للتجربة):<br>
        اسم المستخدم: teacher<br>
        كلمة المرور: LecturePass123
                        </small>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <footer><div class="container"><p>&copy; 2025 كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p></div></footer>

</body>
</html>
