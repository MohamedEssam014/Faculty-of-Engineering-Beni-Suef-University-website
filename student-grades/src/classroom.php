<?php
include 'includes/conn.php';
include 'includes/functions.php';

if (!isLoggedIn() || !hasRole('teacher')) {
    redirect('index.php');
}

$teacher_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_classroom'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO classrooms (name, description, teacher_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $teacher_id]);
        $message = "تم إنشاء الفصل الدراسي بنجاح!";
        $message_type = "success";
    } else {
        $message = "اسم الفصل مطلوب.";
        $message_type = "danger";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $classroom_id = $_POST['classroom_id'];
    $student_id = $_POST['student_id'];
    
    if (!empty($classroom_id) && !empty($student_id)) {
        $check_stmt = $pdo->prepare("SELECT * FROM classroom_students WHERE classroom_id = ? AND student_id = ?");
        $check_stmt->execute([$classroom_id, $student_id]);
        
        if ($check_stmt->rowCount() == 0) {
            $stmt = $pdo->prepare("INSERT INTO classroom_students (classroom_id, student_id) VALUES (?, ?)");
            $stmt->execute([$classroom_id, $student_id]);
            $message = "تمت إضافة الطالب إلى الفصل بنجاح!";
            $message_type = "success";
        } else {
            $message = "الطالب موجود بالفعل في هذا الفصل!";
            $message_type = "danger";
        }
    } else {
        $message = "يرجى اختيار فصل وطالب.";
        $message_type = "danger";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'remove_student' && isset($_GET['classroom_id']) && isset($_GET['student_id'])) {
    $classroom_id = $_GET['classroom_id'];
    $student_id = $_GET['student_id'];
    
    $result = removeStudentFromClassroom($pdo, $classroom_id, $student_id);
    if ($result === true) {
        $message = "تمت إزالة الطالب من الفصل بنجاح!";
        $message_type = "success";
    } else {
        $message = $result;
        $message_type = "danger";
    }
    header("Location: classroom.php?view_students=true&classroom_id=" . $classroom_id . "&msg=" . urlencode($message) . "&msg_type=" . $message_type);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_classroom' && isset($_GET['id'])) {
    $classroom_id = $_GET['id'];
    
    $check_stmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = ? AND teacher_id = ?");
    $check_stmt->execute([$classroom_id, $teacher_id]);
    
    if ($check_stmt->rowCount() > 0) {
        $result = deleteClassroom($pdo, $classroom_id);
        $message = ($result === true) ? "تم حذف الفصل الدراسي بنجاح!" : "حدث خطأ أثناء الحذف.";
        $message_type = ($result === true) ? "success" : "danger";
    } else {
        $message = "الفصل غير موجود أو ليس لديك صلاحية لحذفه!";
        $message_type = "danger";
    }
    header("Location: classroom.php?msg=" . urlencode($message) . "&msg_type=" . $message_type);
    exit;
}

if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['msg_type'];
}

$classrooms_stmt = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ? ORDER BY name");
$classrooms_stmt->execute([$teacher_id]);
$teacher_classrooms = $classrooms_stmt->fetchAll(PDO::FETCH_ASSOC);

$students_stmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'student' ORDER BY first_name, last_name");
$all_students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

$classroom_students = [];
$current_classroom_name = '';
if (isset($_GET['view_students']) && isset($_GET['classroom_id'])) {
    $classroom_id = $_GET['classroom_id'];
    $classroom_students = getClassroomStudents($pdo, $classroom_id);
    
    $name_stmt = $pdo->prepare("SELECT name FROM classrooms WHERE id = ? AND teacher_id = ?");
    $name_stmt->execute([$classroom_id, $teacher_id]);
    $current_classroom_name = $name_stmt->fetchColumn();
    if(!$current_classroom_name) {
         header("Location: classroom.php");
         exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>إدارة الفصول الدراسية - نظام الدرجات</title>

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
        .form-group input[type="text"], .form-group textarea, .form-group select {
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

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        .data-table th, .data-table td {
            border-bottom: 1px solid #ddd;
            padding: 12px 15px;
            text-align: right;
            white-space: nowrap;
        }
        .data-table th {
            background-color: #f9fafb;
            font-weight: 700;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        .data-table tr:last-child td { border-bottom: 0; }
        .data-table tbody tr:hover { background-color: #f3f4f6; }
        .data-table .actions a {
            display: inline-flex; align-items: center;
            margin: 0 3px; padding: 5px 8px; font-size: 13px; text-decoration: none;
            border: 1px solid; border-radius: 4px; cursor: pointer;
            transition: all 0.2s ease;
        }
        .data-table .actions .btn-view { border-color: #17a2b8; color: #17a2b8; }
        .data-table .actions .btn-view:hover { background-color: #17a2b8; color: white; }
        .data-table .actions .btn-delete { border-color: #ef4444; color: #ef4444; }
        .data-table .actions .btn-delete:hover { background-color: #ef4444; color: white; }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
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
            <section class="page-content max-w-6xl mx-auto bg-white p-6 md:p-8 rounded-lg shadow-md animate-fade-in-up">

                <?php if ($message): ?>
                    <div class="p-4 mb-5 rounded-md text-center font-semibold animate-slide-in-down <?php echo $message_type === 'success' ? 'bg-success_bg border border-success_border text-success_text' : 'bg-danger_bg border border-danger_border text-danger_text'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['view_students']) && $current_classroom_name): ?>
                    
                    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
                        <h2 class="text-2xl md:text-3xl font-bold text-bsu_dark_blue flex items-center">
                            <i class="fas fa-users mr-3 text-bsu_medium_blue"></i> طلاب فصل: <?php echo htmlspecialchars($current_classroom_name); ?>
                        </h2>
                        <a href="classroom.php" class="inline-flex items-center text-sm font-medium px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors duration-200">
                            <i class="fas fa-arrow-right ml-2"></i> العودة إلى كل الفصول
                        </a>
                    </div>
                    <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="data-table min-w-full">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>اسم الطالب</th>
                                    <th>اسم المستخدم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                 <?php if (empty($classroom_students)): ?>
                                    <tr><td colspan="5" class="text-center text-gray-500 py-4">لا يوجد طلاب في هذا الفصل بعد.</td></tr>
                                <?php else: ?>
                                    <?php foreach($classroom_students as $student): ?>
                                    <tr>
                                        <td class="text-gray-500"><?php echo $student['id']; ?></td>
                                        <td class="font-medium text-gray-900"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td class="actions">
                                            <a href="?action=remove_student&classroom_id=<?php echo $_GET['classroom_id']; ?>&student_id=<?php echo $student['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من إزالة هذا الطالب من الفصل؟')">
                                                <i class="fas fa-user-minus mr-1"></i> إزالة
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>
                    
                    <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
                        <h2 class="text-2xl md:text-3xl font-bold text-bsu_dark_blue">
                            <i class="fas fa-chalkboard mr-3 text-bsu_medium_blue"></i> إدارة فصولي الدراسية
                        </h2>
                         <a href="teacher.php" class="inline-flex items-center text-sm font-medium px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors duration-200">
                            <i class="fas fa-arrow-right ml-2"></i> العودة للوحة التحكم
                        </a>
                    </div>
                    <div class="grid-container">
                        <div class="contact-form bg-bsu_light_gray border border-gray-200 p-5 rounded-lg">
                            <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 flex items-center">
                                <i class="fas fa-plus-circle mr-2 text-bsu_medium_blue"></i> إنشاء فصل دراسي جديد
                            </h3>
                            <form method="POST">
                                <div class="form-group">
                                    <label for="create_name">اسم الفصل</label>
                                    <input type="text" id="create_name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="create_desc">الوصف (اختياري)</label>
                                    <textarea id="create_desc" name="description" rows="3"></textarea>
                                </div>
                                <button type="submit" name="create_classroom" class="submit-btn">
                                    <i class="fas fa-save mr-2"></i> إنشاء الفصل
                                </button>
                            </form>
                        </div>

                        <div class="contact-form bg-bsu_light_gray border border-gray-200 p-5 rounded-lg">
                            <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 flex items-center">
                                <i class="fas fa-user-plus mr-2 text-bsu_medium_blue"></i> إضافة طالب إلى فصل
                            </h3>
                            <form method="POST">
                                <div class="form-group">
                                    <label for="add_classroom_id">اختر الفصل الدراسي</label>
                                    <select id="add_classroom_id" name="classroom_id" required>
                                        <option value="" disabled selected>-- اختر --</option>
                                        <?php foreach($teacher_classrooms as $classroom): ?>
                                        <option value="<?php echo $classroom['id']; ?>"><?php echo htmlspecialchars($classroom['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="add_student_id">اختر الطالب</label>
                                    <select id="add_student_id" name="student_id" required>
                                        <option value="" disabled selected>-- اختر --</option>
                                        <?php foreach($all_students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="add_student" class="submit-btn" style="background-color: #28a745; hover:background-color: #218838;">
                                    <i class="fas fa-user-plus mr-2"></i> إضافة الطالب
                                </button>
                            </form>
                        </div>
                    </div>

                    <h3 class="text-xl md:text-2xl font-bold text-bsu_dark_blue mb-4 mt-8">
                        <i class="fas fa-list-ul mr-3 text-bsu_medium_blue"></i> فصولي الحالية
                    </h3>
                    <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="data-table min-w-full">
                            <thead>
                                <tr>
                                    <th>اسم الفصل</th>
                                    <th>الوصف</th>
                                    <th>عدد الطلاب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <?php if (empty($teacher_classrooms)): ?>
                                    <tr><td colspan="4" class="text-center text-gray-500 py-4">لم تقم بإنشاء أي فصول بعد.</td></tr>
                                <?php else: ?>
                                    <?php foreach($teacher_classrooms as $classroom):
                                        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM classroom_students WHERE classroom_id = ?");
                                        $count_stmt->execute([$classroom['id']]);
                                        $student_count = $count_stmt->fetchColumn();
                                    ?>
                                    <tr>
                                        <td class="font-medium text-gray-900"><?php echo htmlspecialchars($classroom['name']); ?></td>
                                        <td><?php echo htmlspecialchars($classroom['description']); ?></td>
                                        <td><span class="inline-block px-3 py-1 text-sm font-semibold rounded-full bg-bsu_light_gray text-bsu_dark_blue"><?php echo $student_count; ?> طالب</span></td>
                                        <td class="actions">
                                            <a href="?view_students=true&classroom_id=<?php echo $classroom['id']; ?>" class="btn-view">
                                                <i class="fas fa-users mr-1"></i> عرض الطلاب
                                            </a>
                                            <a href="?action=delete_classroom&id=<?php echo $classroom['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا الفصل؟ سيتم حذف كل ما يتعلق به.')">
                                                <i class="fas fa-trash mr-1"></i> حذف
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
    </script>
</body>
</html>