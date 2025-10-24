<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../student-grades/src/includes/conn.php';

$message = '';
$message_type = ''; // 'success' or 'danger'

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["certificate_file"])) {
    
    $national_id = trim($_POST['national_id']);
    $student_name = trim($_POST['student_name']);
    $file = $_FILES["certificate_file"];

    // Validation
    if (empty($national_id) || empty($student_name)) {
        $message = "خطأ: يرجى ملء جميع الحقول.";
        $message_type = "danger";
    } elseif (!ctype_digit($national_id) || strlen($national_id) != 14) {
         $message = "خطأ: الرقم القومي يجب أن يتكون من 14 رقمًا.";
         $message_type = "danger";
    } else {
        $target_dir = "uploads/certificates/";
        $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $target_file_name = $national_id . '.' . $file_extension;
        $target_file_path = $target_dir . $target_file_name;

        if ($file_extension != "pdf") {
            $message = "خطأ: مسموح فقط بملفات PDF.";
            $message_type = "danger";
        } elseif ($file["error"] !== UPLOAD_ERR_OK) {
             $message = "خطأ أثناء رفع الملف. رمز الخطأ: " . $file["error"];
             $message_type = "danger";
        } elseif ($file["size"] > 50 * 1024 * 1024) { // 50MB Limit
             $message = "خطأ: حجم الملف يتجاوز الحد المسموح به (50MB).";
             $message_type = "danger";
        } else {
            if (move_uploaded_file($file["tmp_name"], $target_file_path)) {
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
                    $message_type = "success";
                    
                } catch (PDOException $e) {
                    unlink($target_file_path); // Delete file if DB insert fails
                    $message = "خطأ في قاعدة البيانات. لم يتم حفظ البيانات.";
                    $message_type = "danger";
                    // error_log("DB Error: " . $e->getMessage()); // Log for admin
                }
            } else {
                $message = "خطأ: حدثت مشكلة أثناء نقل الملف. تحقق من صلاحيات المجلد.";
                $message_type = "danger";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow"> 
    <title>رفع شهادات التخرج | لوحة التحكم</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bsu_dark_blue: '#1e40af',
                        bsu_light_gray: '#f3f4f6',
                        bsu_medium_blue: '#3b82f6',
                        bsu_gold: '#FFC107',
                        success_bg: '#d4edda',
                        success_border: '#c3e6cb',
                        success_text: '#155724',
                        danger_bg: '#f8d7da',
                        danger_border: '#f5c6cb',
                        danger_text: '#721c24',
                    },
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    },
                    keyframes: {
                         slideInDown: {
                            '0%': { opacity: '0', transform: 'translateY(-20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    },
                    animation: {
                        'slide-in-down': 'slideInDown 0.5s ease-out forwards',
                    }
                },
            },
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            font-family: 'cairo', sans-serif;
            scroll-behavior: smooth;
        }
        nav ul li .dropdown-content {
            display: none; position: absolute; background: white;
            min-width: 200px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px; padding: 0.5rem 0; z-index: 50;
            opacity: 0; transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        nav ul li:hover > .dropdown-content { display: block; opacity: 1; transform: translateY(0); }
        .dropdown-content li a {
            display: block; padding: 10px 15px; color: #374151;
            transition: background-color 0.2s ease, padding-right 0.2s ease, color 0.2s ease; white-space: nowrap;
        }
        .dropdown-content li a:hover {
            background-color: #f3f4f6; padding-right: 20px; color: #1e40af;
        }
        header { border-bottom: 3px solid #FFC107; }
        #mobile-menu a:hover { background-color: #e5e7eb; }
        
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;
        }
        .form-group input[type="text"], .form-group input[type="file"] {
            width: 100%; padding: 0.75rem 1rem; border: 1px solid #D1D5DB;
            border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-group input[type="file"] {
             padding: 0.6rem;
             cursor: pointer;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none; border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .submit-btn {
            display: inline-flex; justify-content: center; align-items: center;
            width: 100%; padding: 0.75rem 1.5rem; background-color: #1e40af;
            color: white; font-weight: 700; border-radius: 0.375rem; border: none;
            cursor: pointer; transition: background-color 0.3s ease;
        }
        .submit-btn:hover { background-color: #3b82f6; }

    </style>
</head>
<body class="bg-bsu_light_gray text-gray-800">

    <header id="main-header" class="bg-white shadow sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="logo">
                <a href="../index.html" class="flex items-center space-x-3 rtl:space-x-reverse group">
                    <img src="https://www2.0zz0.com/2025/10/08/14/393349642.png" alt="شعار كلية الهندسة جامعة بني سويف" class="w-12 h-12 rounded-full shadow transition-transform duration-300 group-hover:scale-110">
                    <h1 class="text-xl md:text-2xl font-bold text-bsu_dark_blue group-hover:text-bsu_medium_blue transition-colors duration-200">كلية الهندسة</h1>
                </a>
            </div>

            <nav class="hidden md:flex">
                <ul class="flex space-x-5 rtl:space-x-reverse items-center">
                    <li class="relative group">
                        <a href="../index.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الرئيسية</a>
                        <ul class="dropdown-content">
                            <li><a href="../dean-word.html">كلمة عميد الكلية</a></li>
                            <li><a href="../vision-mission.html">الرؤية والرسالة والأهداف</a></li>
                            <li><a href="../regulations.html">اللائحة الداخلية</a></li>
                            <li><a href="../laws-regulations.html">القوانين واللوائح</a></li>
                        </ul>
                    </li>
                    <li><a href="../about.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">عن الكلية</a></li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الأقسام العلمية</a>
                        <ul class="dropdown-content">
                            <li><a href="../civil-engineering.html">قسم الهندسة المدنية</a></li>
                            <li><a href="../architectural-engineering.html">قسم الهندسة المعمارية</a></li>
                            <li><a href="../electrical-engineering.html">قسم الهندسة الكهربية</a></li>
                        </ul>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">البرامج الدراسية</a>
                        <ul class="dropdown-content">
                            <li><a href="../structural-engineering.html">برنامج الهندسة الإنشائية</a></li>
                        </ul>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الطلاب</a>
                        <ul class="dropdown-content w-56">
                            <li><a href="http://www.email.bsu.edu.eg/_BSU_Std.aspx" target="_blank" rel="noopener noreferrer">الحصول على البريد الإلكتروني</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=284" target="_blank" rel="noopener noreferrer">منتدى الطلاب</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=286" target="_blank" rel="noopener noreferrer">المدن الجامعية</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=277" target="_blank" rel="noopener noreferrer">رعاية الشباب</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=53" target="_blank" rel="noopener noreferrer">إدارة الوافدين</a></li>
                            <li><a href="http://www.results.bsu.edu.eg/" target="_blank" rel="noopener noreferrer">نتائج الكليات</a></li>
                            <li><a href="http://www.bsu.edu.eg/Sector_Home.aspx?cat_id=283" target="_blank" rel="noopener noreferrer">التربية العسكرية</a></li>
                            <li><a href="http://www.payment.bsu.edu.eg/services/" target="_blank" rel="noopener noreferrer">خدمات الدفع الإلكتروني</a></li>
                        </ul>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-bsu_dark_blue font-semibold transition-colors duration-200">الخدمات</a>
                        <ul class="dropdown-content">
                            <li><a href="certificates.php" class="bg-bsu_light_gray text-bsu_dark_blue font-semibold">البحث عن الشهادات</a></li>
                            <li><a href="lectures.php">المحاضرات الدراسية</a></li>
                        </ul>
                    </li>
                    <li><a href="../student-grades/src/index.php" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">درجات الطلاب</a></li>
                    <li><a href="../contact.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">اتصل بنا</a></li>
                </ul>
            </nav>
            <button class="md:hidden text-bsu_dark_blue text-2xl" id="menu-toggle" aria-controls="mobile-menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="md:hidden bg-white shadow-lg absolute w-full top-full right-0 z-40 hidden" id="mobile-menu">
            <ul class="px-4 py-2 space-y-1">
                 <li><a href="../index.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">الرئيسية</a></li>
                <li><a href="../about.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">عن الكلية</a></li>
                <li><a href="#" class="block py-2 px-3 rounded bg-bsu_light_gray font-semibold text-bsu_dark_blue">الخدمات</a></li>
                <li><a href="certificates.php" class="block py-2 pl-6 pr-3 rounded bg-bsu_light_gray">البحث عن الشهادات</a></li>
                <li><a href="lectures.php" class="block py-2 pl-6 pr-3 rounded hover:bg-bsu_light_gray">المحاضرات الدراسية</a></li>
                <li><a href="../contact.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">اتصل بنا</a></li>
            </ul>
        </div>
    </header>

    <main class="py-12 md:py-16">
        <div class="container mx-auto px-4">
            <section class="max-w-xl mx-auto bg-white p-6 md:p-8 rounded-lg shadow-md animate-fade-in-up">
                <h2 class="text-2xl md:text-3xl font-bold text-bsu_dark_blue mb-2 text-center">لوحة تحكم رفع الشهادات</h2>
                <p class="text-center text-gray-600 mb-4">مرحباً بك. قم برفع ملفات PDF الخاصة بالخريجين.</p>

                <div class="mb-4 text-left">
                     <a href="logout.php" class="inline-flex items-center text-sm font-medium px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200">
                        <i class="fas fa-sign-out-alt ml-2"></i> تسجيل الخروج
                    </a>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="p-4 mb-4 rounded-md text-center font-semibold animate-slide-in-down <?php echo $message_type === 'success' ? 'bg-success_bg border border-success_border text-success_text' : 'bg-danger_bg border border-danger_border text-danger_text'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="admin_certificates.php" method="post" enctype="multipart/form-data" class="contact-form border-t border-gray-200 pt-6">
                    <div class="form-group">
                        <label for="national_id">الرقم القومي (14 رقم)</label>
                        <input type="text" id="national_id" name="national_id" maxlength="14" required 
                               pattern="\d{14}" title="يجب إدخال 14 رقم" inputmode="numeric"
                               class="text-lg text-center tracking-widest">
                    </div>
                    <div class="form-group">
                        <label for="student_name">اسم الطالب (كما في الشهادة)</label>
                        <input type="text" id="student_name" name="student_name" required placeholder="الاسم رباعي">
                    </div>
                    <div class="form-group">
                        <label for="certificate_file">ملف الشهادة (PDF فقط)</label>
                        <input type="file" id="certificate_file" name="certificate_file" accept=".pdf" required>
                    </div>
                    <button type="submit" class="submit-btn text-lg">
                        <i class="fas fa-upload ml-2"></i> رفع الشهادة
                    </button>
                </form>
            </section>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <span id="currentYear"></span> كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>