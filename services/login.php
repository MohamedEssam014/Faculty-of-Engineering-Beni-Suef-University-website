<?php
session_start();
$error_message = '';

// --- قم بتعيين بيانات اعتماد الأدمن هنا ---
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'BsuEng@2025'); // اختر كلمة مرور قوية
// ------------------------------------

// إذا كان المستخدم مسجلاً دخوله بالفعل، انقله إلى صفحة الأدمن
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: admin_certificates.php");
    exit;
}

// التحقق إذا تم إرسال النموذج
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        // نجاح! قم بتسجيل جلسة (Session)
        $_SESSION['is_admin'] = true;
        
        // إعادة توجيه إلى صفحة الأدمن
        header("Location: admin_certificates.php");
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
    <title>تسجيل دخول الأدمن | كلية الهندسة</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <img src="https://www2.0zz0.com/2025/10/08/14/393349642.png" alt="شعار كلية الهندسة">
                <h1>كلية الهندسة</h1>
            </div>
            <nav>
                 </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <section class="page-content" style="max-width: 500px; margin: 40px auto;">
                <h2>تسجيل دخول لوحة التحكم</h2>

                <?php if (!empty($error_message)): ?>
                    <div style="padding: 15px; background-color: #fff0f0; border: 1px solid #ffcccc; border-radius: 5px; text-align: center; margin-bottom: 20px; font-weight: bold;">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post" class="contact-form">
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
        اسم المستخدم: admin<br>
        كلمة المرور: BsuEng@2025
                        </small>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p>
        </div>
    </footer>

</body>
</html>
