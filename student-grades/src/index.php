<?php
// --- (1) بدء الجلسة والاتصال ---
include 'includes/conn.php';
include 'includes/functions.php';

// --- (2) التحقق إذا كان مسجلاً دخوله ---
if (isLoggedIn()) {
    redirect(getDashboardUrl());
}

// --- (3) معالجة محاولة تسجيل الدخول ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        
        redirect(getDashboardUrl());
    } else {
        $error = "اسم المستخدم أو كلمة المرور غير صحيحة!";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام درجات الطلاب</title>
    
    <link rel="stylesheet" href="../../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

    <header>
        <div class="container">
            <div class="logo">
                <img src="https://www2.0zz0.com/2025/10/08/14/393349642.png" alt="شعار كلية الهندسة جامعة بني سويف">
                <h1>كلية الهندسة</h1>
            </div>
            <nav>
                <ul>
                    <li class="dropdown">
                        <a href="index.html">الرئيسية</a>
                        <ul class="dropdown-content">
                            <li><a href="dean-word.html">كلمة عميد الكلية</a></li>
                            <li><a href="vision-mission.html">الرؤية والرسالة والأهداف</a></li>
                            <li><a href="regulations.html">اللائحة الداخلية</a></li>
                            <li><a href="laws-regulations.html">القوانين واللوائح</a></li>
                        </ul>
                    </li>
                    <li><a href="about.html">عن الكلية</a></li>
                    <li class="dropdown">
                        <a href="#">الأقسام العلمية</a>
                        <ul class="dropdown-content">
                            <li><a href="civil-engineering.html">قسم الهندسة المدنية</a></li>
                            <li><a href="architectural-engineering.html">قسم الهندسة المعمارية</a></li>
                            <li><a href="electrical-engineering.html">قسم الهندسة الكهربية</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="#">البرامج الدراسية</a>
                        <ul class="dropdown-content">
                           <li><a href="structural-engineering.html">برنامج الهندسة الإنشائية</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="#">الطلاب</a>
                        <ul class="dropdown-content">
                            <li><a href="http://www.email.bsu.edu.eg/_BSU_Std.aspx" target="_blank">الحصول على البريد الإلكتروني</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=284" target="_blank">منتدى الطلاب</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=286" target="_blank">المدن الجامعية</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=277" target="_blank">رعاية الشباب</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=53" target="_blank">إدارة الوافدين</a></li>
                            <li><a href="http://www.results.bsu.edu.eg/" target="_blank">نتائج الكليات</a></li>
                            <li><a href="http://www.bsu.edu.eg/Sector_Home.aspx?cat_id=283" target="_blank">التربية العسكرية</a></li>
                            <li><a href="http://www.payment.bsu.edu.eg/services/" target="_blank">خدمات الدفع الإلكتروني</a></li>
                        </ul>
                    </li>
                    
                    <li class="dropdown">
                    <a href="#">الخدمات</a>
                    <ul class="dropdown-content">
                        <li><a href="services/certificates.php">البحث عن الشهادات</a></li>
                        <li><a href="services/lectures.php">المحاضرات الدراسية</a></li>
                    </ul>
                </li>
                    <li><a href="student-grades/src/index.php">درجات الطلاب</a></li>
                    <li><a href="contact.html">اتصل بنا</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <section class="page-content" style="max-width: 500px; margin: 40px auto;">
                <h2>نظام إدارة درجات الطلاب</h2>
                <p style="text-align: center; margin-bottom: 25px;">قم بتسجيل الدخول إلى حسابك (أدمن / دكتور / طالب)</p>

                <?php if (isset($error)): ?>
                    <div style="padding: 15px; background-color: #fff0f0; border: 1px solid #ffcccc; border-radius: 5px; text-align: center; margin-bottom: 20px; font-weight: bold;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="contact-form">
                    <div class="form-group">
                        <label for="username" class="form-label">اسم المستخدم</label>
                        <input type="text" id="username" class="form-control" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">كلمة المرور</label>
                        <input type="password" id="password" class="form-control" name="password" required>
                    </div>
                    <button type="submit" class="submit-btn">
                        تسجيل الدخول
                    </button>
                </form>
                
                <div style="text-align: center; margin-top: 20px; font-size: 14px; color: #555; line-height: 1.8;">
                    <small>
                        <strong>حسابات تجريبية:</strong><br>
                        أدمن: admin / admin123<br>
                        دكتور: teacher / teacher123<br>
                        طالب: student / student123
                    </small>
                </div>

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
