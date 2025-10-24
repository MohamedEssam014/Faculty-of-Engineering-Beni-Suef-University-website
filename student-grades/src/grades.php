<?php
include 'includes/conn.php';
include 'includes/functions.php';

if (!isLoggedIn() || !hasRole('teacher')) {
    redirect('index.php');
}

$teacher_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grade'])) {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $classroom_id = $_POST['classroom_id'];
    $grade = $_POST['grade'];
    $grade_type = $_POST['grade_type'];
    $remarks = trim($_POST['remarks']);

    if (!empty($student_id) && !empty($subject_id) && !empty($classroom_id) && is_numeric($grade) && $grade >= 0 && $grade <= 100) {
        $stmt = $pdo->prepare("INSERT INTO grades (student_id, subject_id, classroom_id, teacher_id, grade, grade_type, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $subject_id, $classroom_id, $teacher_id, $grade, $grade_type, $remarks]);
        $message = "تمت إضافة الدرجة بنجاح!";
        $message_type = "success";
    } else {
         $message = "بيانات غير صالحة. يرجى التحقق من المدخلات.";
         $message_type = "danger";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_grade' && isset($_GET['id'])) {
    $grade_id = $_GET['id'];

    $result = deleteGrade($pdo, $grade_id, $teacher_id);
    if ($result === true) {
        $message = "تم حذف الدرجة بنجاح!";
        $message_type = "success";
    } else {
        $message = $result;
        $message_type = "danger";
    }
    header("Location: grades.php?msg=" . urlencode($message) . "&msg_type=" . $message_type);
    exit;
}
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['msg_type'];
}

$classrooms_stmt = $pdo->prepare("SELECT id, name FROM classrooms WHERE teacher_id = ? ORDER BY name");
$classrooms_stmt->execute([$teacher_id]);
$teacher_classrooms = $classrooms_stmt->fetchAll(PDO::FETCH_ASSOC);

$subjects_stmt = $pdo->query("SELECT id, name FROM subjects ORDER BY name");
$subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

$students_stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name
    FROM users u
    JOIN classroom_students cs ON u.id = cs.student_id
    JOIN classrooms c ON cs.classroom_id = c.id
    WHERE c.teacher_id = ? AND u.role = 'student'
    ORDER BY u.first_name, u.last_name
");
$students_stmt->execute([$teacher_id]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

$grades_stmt = $pdo->prepare("
    SELECT g.*, u.first_name, u.last_name, s.name as subject_name, c.name as classroom_name
    FROM grades g
    JOIN users u ON g.student_id = u.id
    JOIN subjects s ON g.subject_id = s.id
    JOIN classrooms c ON g.classroom_id = c.id
    WHERE g.teacher_id = ?
    ORDER BY g.graded_at DESC
");
$grades_stmt->execute([$teacher_id]);
$grades = $grades_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_grades = count($grades);
$average_grade = 0;
if ($total_grades > 0) {
    $sum = 0;
    foreach ($grades as $grade_item) {
        if (is_numeric($grade_item['grade'])) {
             $sum += $grade_item['grade'];
        }
    }
    $average_grade = $sum / $total_grades;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>إدارة الدرجات - نظام الدرجات</title>

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
        .form-group input[type="text"], .form-group input[type="number"], .form-group textarea, .form-group select {
            width: 100%; padding: 0.75rem 1rem; border: 1px solid #D1D5DB;
            border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #fff;
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
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .stat-card-small {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            border: 1px solid #eee;
        }
         .stat-card-small h3 { font-size: 1.8em; margin-bottom: 5px; color: #1e40af; }
         .stat-card-small small { color: #555; font-weight: 600; }

         .grading-scale-table {
             width: 100%;
             font-size: 14px;
             border-collapse: collapse;
         }
         .grading-scale-table td { padding: 6px; border: 1px solid #eee; text-align: center; }
         .grading-scale-table tr:nth-child(even) { background-color: #f9fafb; }
         .grading-scale-table span.badge { font-size: 0.9em; padding: 3px 8px; border-radius: 4px; color: white; min-width: 25px; display: inline-block; }
        
        .bg-success { background-color: #28a745; }
        .bg-info { background-color: #17a2b8; }
        .bg-warning { background-color: #ffc107; color: #333 !important; }
        .bg-danger { background-color: #dc3545; }
        .bg-dark { background-color: #343a40; }
        .bg-secondary { background-color: #6c757d; }

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
        .data-table tbody tr:hover { background-color: #f3f4f6; }
        .data-table .actions a {
            display: inline-flex; align-items: center;
            margin: 0 3px; padding: 5px 8px; font-size: 13px; text-decoration: none;
            border: 1px solid #ef4444; color: #ef4444; border-radius: 4px;
            transition: all 0.2s ease;
        }
         .data-table .actions a:hover { background-color: #ef4444; color: white; }
    </style>
</head>
<body class="bg-bsu_light_gray text-gray-800">

    <header id="main-header" class="bg-white shadow sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="logo">
                <a href="../../index.html" class="flex items-center space-x-3 rtl:space-x-reverse group">
                    <img src="https://www2.0zz0.com/2025/10/08/14/393349642.png" alt="شعار كلية الهندسة جامعة بني سويف" class="w-12 h-12 rounded-full shadow transition-transform duration-300 group-hover:scale-110">
                    <h1 class="text-xl md:text-2xl font-bold text-bsu_dark_blue group-hover:text-bsu_medium_blue transition-colors duration-200">كلية الهندسة</h1>
                </a>
            </div>

            <nav class="hidden md:flex">
                <ul class="flex space-x-5 rtl:space-x-reverse items-center">
                    <li class="relative group">
                        <a href="../../index.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الرئيسية</a>
                        <ul class="dropdown-content">
                            <li><a href="../../dean-word.html">كلمة عميد الكلية</a></li>
                            <li><a href="../../vision-mission.html">الرؤية والرسالة والأهداف</a></li>
                            <li><a href="../../regulations.html">اللائحة الداخلية</a></li>
                            <li><a href="../../laws-regulations.html">القوانين واللوائح</a></li>
                        </ul>
                    </li>
                    <li><a href="../../about.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">عن الكلية</a></li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الأقسام العلمية</a>
                        <ul class="dropdown-content">
                            <li><a href="../../civil-engineering.html">قسم الهندسة المدنية</a></li>
                            <li><a href="../../architectural-engineering.html">قسم الهندسة المعمارية</a></li>
                            <li><a href="../../electrical-engineering.html">قسم الهندسة الكهربية</a></li>
                        </ul>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">البرامج الدراسية</a>
                        <ul class="dropdown-content">
                            <li><a href="../../structural-engineering.html">برنامج الهندسة الإنشائية</a></li>
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
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الخدمات</a>
                        <ul class="dropdown-content">
                            <li><a href="../../services/certificates.php">البحث عن الشهادات</a></li>
                            <li><a href="../../services/lectures.php">المحاضرات الدراسية</a></li>
                        </ul>
                    </li>
                    <li><a href="index.php" class="text-bsu_dark_blue font-semibold transition-colors duration-200">درجات الطلاب</a></li>
                    <li><a href="../../contact.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">اتصل بنا</a></li>
                </ul>
            </nav>
            <button class="md:hidden text-bsu_dark_blue text-2xl" id="menu-toggle" aria-controls="mobile-menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="md:hidden bg-white shadow-lg absolute w-full top-full right-0 z-40 hidden" id="mobile-menu">
            <ul class="px-4 py-2 space-y-1">
                 <li><a href="../../index.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">الرئيسية</a></li>
                <li><a href="../../about.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">عن الكلية</a></li>
                <li><a href="index.php" class="block py-2 px-3 rounded bg-bsu_light_gray font-semibold text-bsu_dark_blue">درجات الطلاب</a></li>
                <li><a href="../../contact.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">اتصل بنا</a></li>
            </ul>
        </div>
    </header>

    <main class="py-12 md:py-16">
        <div class="container mx-auto px-4">
            <section class="page-content max-w-7xl mx-auto bg-white p-6 md:p-8 rounded-lg shadow-md animate-fade-in-up">
                <div class="flex flex-col md:flex-row justify-between md:items-center mb-6 gap-4">
                    <h2 class="text-2xl md:text-3xl font-bold text-bsu_dark_blue flex items-center">
                        <i class="fas fa-edit mr-3 text-bsu_medium_blue"></i> إدارة الدرجات
                    </h2>
                    <span class="text-sm text-gray-600">مرحباً، <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 
                        [<a href="logout.php" class="text-red-600 hover:underline">تسجيل الخروج</a>]
                    </span>
                </div>
                 <a href="teacher.php" class="inline-flex items-center text-sm font-medium px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors duration-200 mb-6">
                    <i class="fas fa-arrow-right ml-2"></i> العودة للوحة التحكم
                </a>

                <?php if ($message): ?>
                    <div class="p-4 mb-5 rounded-md text-center font-semibold animate-slide-in-down <?php echo $message_type === 'success' ? 'bg-success_bg border border-success_border text-success_text' : 'bg-danger_bg border border-danger_border text-danger_text'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="grid-container">
                    <div class="contact-form bg-bsu_light_gray border border-gray-200 p-5 rounded-lg">
                        <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 flex items-center">
                            <i class="fas fa-plus-circle mr-2 text-bsu_medium_blue"></i> إضافة درجة جديدة
                        </h3>
                        <form method="POST">
                            <div class="form-group">
                                <label for="add_student_id">الطالب</label>
                                <select id="add_student_id" name="student_id" required>
                                    <option value="" disabled selected>-- اختر الطالب --</option>
                                    <?php foreach($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>" <?php echo (isset($_GET['student_id']) && $_GET['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label for="add_subject_id">المادة</label>
                                    <select id="add_subject_id" name="subject_id" required>
                                        <option value="" disabled selected>-- اختر المادة --</option>
                                        <?php foreach($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="add_classroom_id">الفصل الدراسي</label>
                                    <select id="add_classroom_id" name="classroom_id" required>
                                        <option value="" disabled selected>-- اختر الفصل --</option>
                                        <?php foreach($teacher_classrooms as $classroom): ?>
                                        <option value="<?php echo $classroom['id']; ?>"><?php echo htmlspecialchars($classroom['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label for="add_grade">الدرجة (من 100)</label>
                                    <input type="number" id="add_grade" name="grade" min="0" max="100" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label for="add_grade_type">نوع الدرجة</label>
                                    <select id="add_grade_type" name="grade_type" required>
                                        <option value="">-- اختر النوع --</option>
                                        <option value="quiz">اختبار قصير</option>
                                        <option value="assignment">واجب</option>
                                        <option value="exam">امتحان</option>
                                        <option value="project">مشروع</option>
                                        <option value="participation">مشاركة</option>
                                        <option value="other">أخرى</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="add_remarks">ملاحظات (اختياري)</label>
                                <textarea id="add_remarks" name="remarks" rows="2" placeholder="أي ملاحظات إضافية..."></textarea>
                            </div>
                            <button type="submit" name="add_grade" class="submit-btn text-lg">
                                <i class="fas fa-save mr-2"></i> إضافة الدرجة
                            </button>
                        </form>
                    </div>

                    <div>
                        <div class="contact-form bg-bsu_light_gray border border-gray-200 p-5 rounded-lg mb-6">
                            <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 flex items-center">
                                <i class="fas fa-chart-bar mr-2 text-bsu_medium_blue"></i> إحصائيات
                            </h3>
                            <div class="stats-grid">
                                <div class="stat-card-small">
                                    <h3><?php echo $total_grades; ?></h3>
                                    <small>إجمالي الدرجات</small>
                                </div>
                                <div class="stat-card-small">
                                     <h3 class="text-bsu_medium_blue"><?php echo number_format($average_grade, 1); ?></h3>
                                    <small>متوسط الدرجات</small>
                                </div>
                                <div class="stat-card-small">
                                    <h3><?php echo count($teacher_classrooms); ?></h3>
                                    <small>فصولي</small>
                                </div>
                            </div>
                        </div>

                         <div class="contact-form bg-bsu_light_gray border border-gray-200 p-5 rounded-lg">
                             <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 flex items-center">
                                <i class="fas fa-info-circle mr-2 text-bsu_medium_blue"></i> مقياس التقديرات
                            </h3>
                             <table class="grading-scale-table w-full">
                                 <tbody class="text-gray-700">
                                     <tr><td class="font-semibold">90-100</td><td><span class="badge bg-success">A</span></td><td>ممتاز</td></tr>
                                     <tr><td>80-89</td><td><span class="badge bg-info">B</span></td><td>جيد جداً</td></tr>
                                     <tr><td>70-79</td><td><span class="badge bg-warning text-black">C</span></td><td>جيد</td></tr>
                                     <tr><td>60-69</td><td><span class="badge bg-danger">D</span></td><td>مقبول</td></tr>
                                     <tr><td>أقل من 60</td><td><span class="badge bg-dark">F</span></td><td>راسب</td></tr>
                                 </tbody>
                             </table>
                         </div>
                    </div>
                </div>

                <div class="mt-10">
                    <h3 class="text-xl md:text-2xl font-bold text-bsu_dark_blue mb-4 flex items-center">
                        <i class="fas fa-list-ul mr-3 text-bsu_medium_blue"></i> الدرجات المرصودة مؤخراً
                    </h3>
                     <div style="overflow-x: auto;" class="bg-white rounded-lg shadow">
                        <table class="data-table min-w-full">
                            <thead>
                                <tr>
                                    <th>الطالب</th>
                                    <th>المادة</th>
                                    <th>الفصل</th>
                                    <th>الدرجة</th>
                                    <th>النوع</th>
                                    <th>الملاحظات</th>
                                    <th>التاريخ</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <?php if (empty($grades)): ?>
                                    <tr><td colspan="8" class="text-center text-gray-500 py-4">لم تقم برصد أي درجات بعد.</td></tr>
                                <?php else: ?>
                                    <?php foreach($grades as $grade_item):
                                        $grade_point = calculateGradePoint($grade_item['grade'] ?? 0);
                                        $grade_color_class = getGradeColor($grade_item['grade'] ?? 0, 'tailwind'); // Using a helper for tailwind classes
                                    ?>
                                    <tr>
                                        <td class="font-medium text-gray-900"><?php echo htmlspecialchars($grade_item['first_name'] . ' ' . $grade_item['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade_item['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade_item['classroom_name']); ?></td>
                                        <td>
                                             <span class="badge <?php echo $grade_color_class; ?>">
                                                <?php echo htmlspecialchars(is_numeric($grade_item['grade']) ? number_format($grade_item['grade'], 1) : 'N/A'); ?> (<?php echo $grade_point; ?>)
                                             </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white"><?php echo htmlspecialchars(ucfirst($grade_item['grade_type'])); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($grade_item['remarks']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($grade_item['graded_at'])); ?></td>
                                        <td class="actions">
                                            <a href="?action=delete_grade&id=<?php echo $grade_item['id']; ?>"
                                               onclick="return confirm('هل أنت متأكد من حذف هذه الدرجة؟')"
                                               class="text-red-600 hover:bg-red-600 hover:text-white border-red-600">
                                               <i class="fas fa-trash mr-1"></i> حذف
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <span id="currentYear"></span> كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p>
        </div>
    </footer>

    <script src="../../script.js"></script>
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
        
        // Helper function to get Tailwind grade colors (mimics PHP helper)
        function getGradeColor(grade, type = 'tailwind') {
            if (!isFinite(grade)) return 'bg-gray-400 text-white';
            if (grade >= 90) return 'bg-green-500 text-white';
            if (grade >= 80) return 'bg-blue-500 text-white';
            if (grade >= 70) return 'bg-yellow-500 text-black';
            if (grade >= 60) return 'bg-red-500 text-white';
            return 'bg-gray-800 text-white';
        }
        
        // Apply badge colors dynamically if needed (though PHP handles it on load)
        document.querySelectorAll('.badge[data-grade]').forEach(badge => {
            const grade = parseFloat(badge.dataset.grade);
            const colorClass = getGradeColor(grade, 'tailwind');
            badge.classList.add(...colorClass.split(' '));
        });
    </script>
</body>
</html>