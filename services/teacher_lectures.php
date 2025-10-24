<?php
session_start();

if (!isset($_SESSION['is_lecture_manager']) || $_SESSION['is_lecture_manager'] !== true) {
    header("Location: lecture_login.php");
    exit;
}

include '../student-grades/src/includes/conn.php';
include '../student-grades/src/includes/functions.php';

$teacher_id = $_SESSION['lecture_teacher_id'];
$teacher_name = $_SESSION['lecture_teacher_name'] ?? "مدرس";

$message = '';
$message_type = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete_lecture' && isset($_GET['id'])) {
    $lecture_id = $_GET['id'];
    
    $get_file_stmt = $pdo->prepare("SELECT file_path FROM lectures WHERE id = ? AND teacher_id = ?");
    $get_file_stmt->execute([$lecture_id, $teacher_id]);
    $lecture_file = $get_file_stmt->fetch(PDO::FETCH_ASSOC);

    if ($lecture_file) {
        try {
            $pdo->beginTransaction();

            $delete_stmt = $pdo->prepare("DELETE FROM lectures WHERE id = ? AND teacher_id = ?");
            $delete_stmt->execute([$lecture_id, $teacher_id]);

            if (file_exists($lecture_file['file_path'])) {
                unlink($lecture_file['file_path']);
            }

            $pdo->commit();
            $message = "تم حذف المحاضرة بنجاح.";
            $message_type = "success";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "خطأ أثناء حذف المحاضرة.";
            $message_type = "danger";
            error_log("Lecture Deletion Error: " . $e->getMessage());
        }
    } else {
        $message = "المحاضرة غير موجودة أو ليس لديك صلاحية لحذفها.";
        $message_type = "danger";
    }
    
    $_SESSION['temp_message'] = $message;
    $_SESSION['temp_message_type'] = $message_type;
    header("Location: teacher_lectures.php");
    exit;
}

if (isset($_SESSION['temp_message'])) {
    $message = $_SESSION['temp_message'];
    $message_type = $_SESSION['temp_message_type'];
    unset($_SESSION['temp_message']);
    unset($_SESSION['temp_message_type']);
}

if (isset($_SESSION['edit_success'])) {
    $message = $_SESSION['edit_success'];
    $message_type = 'success';
    unset($_SESSION['edit_success']);
}

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$levels = $pdo->query("SELECT * FROM academic_levels ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["lecture_file"])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');
    $subject_id = $_POST['subject_id'];
    $department_id = $_POST['department_id'];
    $level_id = $_POST['level_id'];
    $file = $_FILES["lecture_file"];

    $target_dir = "uploads/lectures/";
    $original_filename = basename($file["name"]);
    $safe_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $original_filename);
    $file_extension = strtolower(pathinfo($safe_filename, PATHINFO_EXTENSION));
    $unique_prefix = time() . '_' . uniqid();
    $target_file_name = $unique_prefix . '_' . $safe_filename;
    $target_file_path = $target_dir . $target_file_name;
    $file_type = $file["type"];
    $file_size = $file["size"];

    $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar'];
    
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $message = "خطأ أثناء رفع الملف (رمز الخطأ: " . $file["error"] . ")";
        $message_type = "danger";
    } elseif (!in_array($file_extension, $allowed_extensions)) {
        $message = "خطأ: نوع الملف غير مسموح به. المسموح به: " . implode(', ', $allowed_extensions);
        $message_type = "danger";
    } elseif ($file_size > 50 * 1024 * 1024) { // 50MB Limit
        $message = "خطأ: حجم الملف يتجاوز الحد المسموح به (50MB).";
        $message_type = "danger";
    } elseif (empty($title) || empty($subject_id) || empty($department_id) || empty($level_id)) {
         $message = "خطأ: يرجى ملء جميع الحقول المطلوبة.";
         $message_type = "danger";
    } else {
        if (move_uploaded_file($file["tmp_name"], $target_file_path)) {
            try {
                $sql = "INSERT INTO lectures (title, description, file_path, file_type, file_size, teacher_id, subject_id, department_id, level_id)
                        VALUES (:title, :description, :file_path, :file_type, :file_size, :teacher_id, :subject_id, :department_id, :level_id)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':file_path' => $target_file_path,
                    ':file_type' => $file_extension, // Store extension instead of MIME type
                    ':file_size' => $file_size,
                    ':teacher_id' => $teacher_id,
                    ':subject_id' => $subject_id,
                    ':department_id' => $department_id,
                    ':level_id' => $level_id
                ]);
                $message = "تم رفع المحاضرة '" . htmlspecialchars($title) . "' بنجاح.";
                $message_type = "success";
            } catch (PDOException $e) {
                unlink($target_file_path);
                $message = "خطأ في قاعدة البيانات.";
                $message_type = "danger";
                error_log("DB Error inserting lecture: " . $e->getMessage());
            }
        } else {
            $message = "خطأ: لم يتم نقل الملف إلى المجلد الصحيح. تحقق من صلاحيات المجلد.";
            $message_type = "danger";
            error_log("File Move Error: Failed to move " . $file["tmp_name"] . " to " . $target_file_path);
        }
    }
}

$lectures_stmt = $pdo->prepare("
    SELECT l.*, s.name as subject_name, d.name as department_name, al.name as level_name
    FROM lectures l
    JOIN subjects s ON l.subject_id = s.id
    JOIN departments d ON l.department_id = d.id
    JOIN academic_levels al ON l.level_id = al.id
    WHERE l.teacher_id = ?
    ORDER BY l.upload_date DESC
");
$lectures_stmt->execute([$teacher_id]);
$uploaded_lectures = $lectures_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>إدارة المحاضرات</title>
    
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
                         fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                         slideInDown: {
                            '0%': { opacity: '0', transform: 'translateY(-20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.6s ease-out forwards',
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
        .form-group input[type="text"], .form-group input[type="file"], .form-group textarea, .form-group select {
            width: 100%; padding: 0.75rem 1rem; border: 1px solid #D1D5DB;
            border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #fff;
        }
        .form-group input[type="file"] {
             padding: 0.6rem; cursor: pointer;
        }
        .form-group select {
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22currentColor%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: left 0.5rem center;
            background-size: 1.5em 1.5em;
            padding-left: 2.5rem;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
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
        
        .data-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden;
        }
        .data-table th, .data-table td {
            border-bottom: 1px solid #ddd; padding: 12px 15px;
            text-align: right; white-space: nowrap;
        }
        .data-table th {
            background-color: #f9fafb; font-weight: 700; color: #374151;
            font-size: 0.875rem; text-transform: uppercase;
        }
        .data-table tr:last-child td { border-bottom: 0; }
        .data-table tr:hover { background-color: #f3f4f6; }
        .data-table .actions a {
            display: inline-block; margin: 0 3px; padding: 5px 8px; font-size: 13px;
            text-decoration: none; border: 1px solid; border-radius: 4px;
            transition: all 0.2s ease;
        }
        .data-table .actions .btn-view { border-color: #3b82f6; color: #3b82f6; }
        .data-table .actions .btn-view:hover { background-color: #3b82f6; color: white; }
        .data-table .actions .btn-edit { border-color: #f59e0b; color: #f59e0b; }
        .data-table .actions .btn-edit:hover { background-color: #f59e0b; color: white; }
        .data-table .actions .btn-delete { border-color: #ef4444; color: #ef4444; }
        .data-table .actions .btn-delete:hover { background-color: #ef4444; color: white; }
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
                            <li><a href="certificates.php">البحث عن الشهادات</a></li>
                            <li><a href="lectures.php" class="bg-bsu_light_gray text-bsu_dark_blue font-semibold">المحاضرات الدراسية</a></li>
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
                <li><a href="certificates.php" class="block py-2 pl-6 pr-3 rounded hover:bg-bsu_light_gray">البحث عن الشهادات</a></li>
                <li><a href="lectures.php" class="block py-2 pl-6 pr-3 rounded bg-bsu_light_gray">المحاضرات الدراسية</a></li>
                <li><a href="../contact.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">اتصل بنا</a></li>
            </ul>
        </div>
    </header>

    <main class="py-12 md:py-16">
        <div class="container mx-auto px-4">
            <section class="page-content max-w-6xl mx-auto bg-white p-6 md:p-8 rounded-lg shadow-md animate-fade-in-up">
                <div class="flex flex-col md:flex-row justify-between md:items-center mb-6 gap-4">
                    <h2 class="text-2xl md:text-3xl font-bold text-bsu_dark_blue flex items-center">
                        <i class="fas fa-upload mr-3 text-bsu_medium_blue"></i> إدارة ورفع المحاضرات
                    </h2>
                    <span class="text-sm text-gray-600">مرحباً، <?php echo htmlspecialchars($teacher_name); ?>! 
                        [<a href="lecture_logout.php" class="text-red-600 hover:underline">تسجيل الخروج</a>]
                    </span>
                </div>

                <?php if ($message): ?>
                    <div class="p-4 mb-5 rounded-md text-center font-semibold animate-slide-in-down <?php echo $message_type === 'success' ? 'bg-success_bg border border-success_border text-success_text' : 'bg-danger_bg border border-danger_border text-danger_text'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="contact-form bg-bsu_light_gray border border-gray-200 p-5 rounded-lg mb-8">
                     <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 flex items-center">
                        <i class="fas fa-plus-circle mr-2 text-bsu_medium_blue"></i> رفع محاضرة جديدة
                    </h3>
                     <form action="teacher_lectures.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">عنوان المحاضرة/الملف</label>
                            <input type="text" id="title" name="title" required placeholder="مثال: المحاضرة الأولى - مقدمة">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label for="subject_id">المادة الدراسية</label>
                                <select id="subject_id" name="subject_id" required>
                                    <option value="">-- اختر المادة --</option>
                                    <?php foreach($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']) . " (" . htmlspecialchars($subject['code']) . ")"; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="department_id">القسم</label>
                                <select id="department_id" name="department_id" required>
                                    <option value="">-- اختر القسم --</option>
                                    <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="level_id">الفرقة الدراسية</label>
                                <select id="level_id" name="level_id" required>
                                    <option value="">-- اختر الفرقة --</option>
                                     <?php foreach($levels as $level): ?>
                                    <option value="<?php echo $level['id']; ?>"><?php echo htmlspecialchars($level['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description">وصف (اختياري)</label>
                            <textarea id="description" name="description" rows="3" placeholder="ملاحظات بسيطة حول ملف المحاضرة..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="lecture_file">ملف المحاضرة (PDF, PPTX, DOCX, ZIP)</label>
                            <input type="file" id="lecture_file" name="lecture_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.rar" required>
                        </div>
                        <button type="submit" class="submit-btn text-lg">
                            <i class="fas fa-upload mr-2"></i> رفع الملف
                        </button>
                     </form>
                </div>

                <div>
                    <h3 class="text-xl md:text-2xl font-bold text-bsu_dark_blue mb-4 flex items-center">
                        <i class="fas fa-list-ul mr-3 text-bsu_medium_blue"></i> المحاضرات التي قمت برفعها
                    </h3>
                    <?php if (empty($uploaded_lectures)): ?>
                        <div class="p-4 text-center text-gray-600 bg-bsu_light_gray border border-gray-200 rounded-md">
                            <i class="fas fa-info-circle mr-2"></i> لم تقم برفع أي محاضرات بعد.
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;" class="bg-white rounded-lg shadow">
                            <table class="data-table min-w-full">
                                <thead>
                                    <tr>
                                        <th>العنوان</th>
                                        <th>المادة</th>
                                        <th>القسم</th>
                                        <th>الفرقة</th>
                                        <th>نوع الملف</th>
                                        <th>تاريخ الرفع</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <?php foreach($uploaded_lectures as $lecture): ?>
                                    <tr>
                                        <td class="font-medium text-gray-900"><?php echo htmlspecialchars($lecture['title']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['department_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['level_name']); ?></td>
                                        <td><span class="inline-block bg-gray-200 text-gray-700 text-xs font-semibold px-2 py-1 rounded-full uppercase"><?php echo htmlspecialchars($lecture['file_type']) ?: 'ملف'; ?></span></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($lecture['upload_date'])); ?></td>
                                        <td class="actions">
                                            <a href="<?php echo htmlspecialchars($lecture['file_path']); ?>" target="_blank" rel="noopener noreferrer" class="btn-view">
                                                <i class="fas fa-eye"></i> عرض
                                            </a>
                                            <a href="edit_lecture.php?id=<?php echo $lecture['id']; ?>" class="btn-edit">
                                                <i class="fas fa-edit"></i> تعديل
                                            </a>
                                            <a href="?action=delete_lecture&id=<?php echo $lecture['id']; ?>"
                                               onclick="return confirm('هل أنت متأكد من حذف هذه المحاضرة؟ سيتم حذف الملف نهائياً.')"
                                               class="btn-delete">
                                                <i class="fas fa-trash"></i> حذف
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
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