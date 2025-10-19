<?php
// --- (1) بدء الجلسة (يجب أن يكون أول سطر) ---
session_start();

// --- (2) "الحارس" للتحقق من تسجيل الدخول ---
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

// --- (3) الكود الأصلي للصفحة (رفع الملفات وقاعدة البيانات) ---

// تضمين ملف الاتصال بقاعدة البيانات
// المسار النسبي هام جداً
require_once '../student-grades/src/includes/conn.php';

$message = '';

// التحقق إذا تم إرسال النموذج
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["certificate_file"])) {
    
    $national_id = $_POST['national_id'];
    $student_name = $_POST['student_name'];
    $file = $_FILES["certificate_file"];

    // مجلد حفظ الملفات
    $target_dir = "uploads/certificates/";
    // إنشاء اسم فريد للملف باستخدام الرقم القومي
    $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $target_file_name = $national_id . '.' . $file_extension;
    $target_file_path = $target_dir . $target_file_name;

    // التأكد أن الملف هو PDF
    if (strtolower($file_extension) != "pdf") {
        $message = "خطأ: مسموح فقط بملفات PDF.";
    } else {
        // محاولة رفع الملف
        if (move_uploaded_file($file["tmp_name"], $target_file_path)) {
            
            // إذا نجح الرفع، قم بإدخال البيانات في قاعدة البيانات
            try {
                $sql = "INSERT INTO certificates (national_id, student_name, file_path) 
                        VALUES (:national_id, :student_name, :file_path)
                        ON DUPLICATE KEY UPDATE 
                        student_name = :student_name, file_path = :file_path";
                        
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'national_id' => $national_id,
                    'student_name' => $student_name,
                    'file_path' => $target_file_path
                ]);
                
                $message = "تم رفع شهادة الطالب: " . htmlspecialchars($student_name) . " بنجاح.";
                
            } catch (PDOException $e) {
                $message = "خطأ في قاعدة البيانات: " . $e->getMessage();
            }
            
        } else {
            $message = "خطأ: حدثت مشكلة أثناء رفع الملف.";
        }
    }
}
// --- (4) نهاية كتلة الـ PHP (قبل بداية الـ HTML) ---
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع شهادات التخرج | لوحة التحكم</title>
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
                <h2>لوحة تحكم رفع الشهادات</h2>
                <a href="logout.php" style="display: block; text-align: center; margin: -10px 0 20px 0; color: red; font-weight: bold;">[ تسجيل الخروج ]</a>
                <p style="color:red; font-weight:bold; text-align:center;">تحذير: هذه الصفحة يجب أن تكون محمية بنظام تسجيل دخول للأدمن فقط.</p>
                
                <?php if (!empty($message)): ?>
                    <div style="padding: 15px; background-color: #f0f0f0; border: 1px solid #ccc; border-radius: 5px; text-align: center; margin-bottom: 20px; font-weight: bold;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form action="admin_certificates.php" method="post" enctype="multipart/form-data" class="contact-form">
                    <div class="form-group">
                        <label for="national_id">الرقم القومي (14 رقم)</label>
                        <input type="text" id="national_id" name="national_id" maxlength="14" required>
                    </div>
                    <div class="form-group">
                        <label for="student_name">اسم الطالب (كما في الشهادة)</label>
                        <input type="text" id="student_name" name="student_name" required>
                    </div>
                    <div class="form-group">
                        <label for="certificate_file">ملف الشهادة (PDF فقط)</label>
                        <input type="file" id="certificate_file" name="certificate_file" accept=".pdf" required>
                    </div>
                    <button type="submit" class="submit-btn">رفع الشهادة</button>
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
