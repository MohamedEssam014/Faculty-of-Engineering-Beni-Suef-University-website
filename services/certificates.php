<?php
require_once '../student-grades/src/includes/conn.php';
$result = null;
$error_message = '';

// التحقق إذا تم إرسال النموذج
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['national_id'])) {
    
    $national_id = $_POST['national_id'];
    
    if (empty($national_id)) {
        $error_message = "الرجاء إدخال الرقم القومي.";
    } else {
        try {
            $sql = "SELECT * FROM certificates WHERE national_id = :national_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['national_id' => $national_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // --- (هذا هو التعديل) ---
            if ($result) {
                // وجدنا سجل في قاعدة البيانات، الآن نتأكد أن الملف موجود فعلياً
                if (!file_exists($result['file_path'])) {
                    // السجل موجود ولكن الملف محذوف من السيرفر
                    $error_message = "لم يتم العثور على شهادة بهذا الرقم القومي. يرجى مراجعة إدارة الكلية.";
                    $result = null; // نلغي النتيجة حتى لا يظهر صندوق التحميل
                }
                // إذا كان الملف موجوداً، سيكمل الكود كالمعتاد ويعرض النتيجة
            } else {
                // السجل غير موجود أصلاً في قاعدة البيانات
                $error_message = "لم يتم العثور على شهادة بهذا الرقم القومي. يرجى مراجعة إدارة الكلية.";
            }
            // --- (نهاية التعديل) ---
            
        } catch (PDOException $e) {
            $error_message = "خطأ في الاتصال بقاعدة البيانات.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البحث عن شهادات التخرج</title>
    <link rel="stylesheet" href="../style.css">
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
            <section class="page-content" style="max-width: 700px; margin: 20px auto;">
                <h2>خدمة البحث عن شهادات التخرج</h2>
                <div style="margin-bottom: 20px; text-align: left;">
                    <a href="admin_certificates.php" class="submit-btn" style="display: inline-block; width: auto; font-size: 14px; padding: 8px 15px; background-color: #5a6268;">
                        <i class="fas fa-cog"></i> الذهاب لإدارة الشهادات (للأدمن)
                    </a>
                </div>
                <p>أدخل الرقم القومي الخاص بك للبحث عن شهادتك وتحميلها.</p>

                <form action="certificates.php" method="post" class="contact-form">
                    <div class="form-group">
                        <label for="national_id">الرقم القومي (14 رقم)</label>
                        <input type="text" id="national_id" name="national_id" maxlength="14" required>
                    </div>
                    <button type="submit" class="submit-btn">بحث</button>
                </form>

                <?php if ($result): ?>
                    <div style="margin-top: 30px; padding: 20px; background-color: #e6f7ff; border: 1px solid #b3e0ff; border-radius: 5px;">
                        <h3>تم العثور على شهادتك</h3>
                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($result['student_name']); ?></p>
                        <p><strong>الرقم القومي:</strong> <?php echo htmlspecialchars($result['national_id']); ?></p>
                        <a href="<?php echo htmlspecialchars($result['file_path']); ?>" class="button-link" target="_blank" style="background-color: #007bff; color: #fff;">
                            تحميل الشهادة (PDF)
                        </a>
                    </div>
                <?php elseif (!empty($error_message)): ?>
                    <div style="margin-top: 30px; padding: 15px; background-color: #fff0f0; border: 1px solid #ffcccc; border-radius: 5px; text-align: center; font-weight: bold;">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

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
