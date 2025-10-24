<?php
include 'includes/conn.php';
include 'includes/functions.php';
requireRole('admin');

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        
        if (empty($username) || empty($password) || empty($role) || empty($first_name) || empty($last_name)) {
             $message = "يرجى ملء جميع الحقول المطلوبة.";
             $message_type = "danger";
        } elseif (strlen($password) < 6) {
            $message = "يجب أن تكون كلمة المرور 6 أحرف على الأقل!";
            $message_type = "danger";
        } else {
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check_stmt->execute([$username]);
            
            if ($check_stmt->rowCount() > 0) {
                $message = "اسم المستخدم موجود بالفعل!";
                $message_type = "danger";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $password_hash, $email, $role, $first_name, $last_name]);
                $message = "تمت إضافة المستخدم بنجاح!";
                $message_type = "success";
            }
        }
    }
    
    if (isset($_POST['add_subject'])) {
        $name = trim($_POST['name']);
        $code = trim($_POST['code']);
        $description = trim($_POST['description']);
        
        if (empty($name) || empty($code)) {
             $message = "يرجى ملء اسم المادة ورمزها.";
             $message_type = "danger";
        } else {
            $check_stmt = $pdo->prepare("SELECT id FROM subjects WHERE code = ?");
            $check_stmt->execute([$code]);
            
            if ($check_stmt->rowCount() > 0) {
                $message = "رمز المادة موجود بالفعل!";
                $message_type = "danger";
            } else {
                $stmt = $pdo->prepare("INSERT INTO subjects (name, code, description) VALUES (?, ?, ?)");
                $stmt->execute([$name, $code, $description]);
                $message = "تمت إضافة المادة بنجاح!";
                $message_type = "success";
            }
        }
    }
    
    if (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        
        if (empty($username) || empty($role) || empty($first_name) || empty($last_name)) {
             $message = "يرجى ملء جميع الحقول المطلوبة.";
             $message_type = "danger";
        } else {
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check_stmt->execute([$username, $user_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $message = "اسم المستخدم موجود بالفعل!";
                $message_type = "danger";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $first_name, $last_name, $email, $role, $user_id]);
                $message = "تم تحديث بيانات المستخدم بنجاح!";
                $message_type = "success";
            }
        }
    }
    
    if (isset($_POST['update_password'])) {
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $message = "كلمتا المرور غير متطابقتين!";
            $message_type = "danger";
        } elseif (strlen($new_password) < 6) {
            $message = "يجب أن تكون كلمة المرور 6 أحرف على الأقل!";
            $message_type = "danger";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            $message = "تم تحديث كلمة المرور بنجاح!";
            $message_type = "success";
        }
    }
    
    if (isset($_POST['update_subject'])) {
        $subject_id = $_POST['subject_id'];
        $name = trim($_POST['name']);
        $code = trim($_POST['code']);
        $description = trim($_POST['description']);
        
         if (empty($name) || empty($code)) {
             $message = "يرجى ملء اسم المادة ورمزها.";
             $message_type = "danger";
        } else {
            $check_stmt = $pdo->prepare("SELECT id FROM subjects WHERE code = ? AND id != ?");
            $check_stmt->execute([$code, $subject_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $message = "رمز المادة موجود بالفعل!";
                $message_type = "danger";
            } else {
                $stmt = $pdo->prepare("UPDATE subjects SET name = ?, code = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $code, $description, $subject_id]);
                $message = "تم تحديث المادة بنجاح!";
                $message_type = "success";
            }
        }
    }
    
    if ($message) {
         header("Location: admin.php?msg=" . urlencode($message) . "&msg_type=" . $message_type);
         exit;
    }
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    switch ($action) {
        case 'delete_user':
            $result = deleteUser($pdo, $id);
            $message = ($result === true) ? "تم حذف المستخدم بنجاح!" : $result;
            $message_type = ($result === true) ? "success" : "danger";
            break;
            
        case 'delete_subject':
            $result = deleteSubject($pdo, $id);
            $message = ($result === true) ? "تم حذف المادة بنجاح!" : $result;
            $message_type = ($result === true) ? "success" : "danger";
            break;
            
        case 'delete_classroom':
            $result = deleteClassroom($pdo, $id);
            $message = ($result === true) ? "تم حذف الفصل الدراسي بنجاح!" : $result;
            $message_type = ($result === true) ? "success" : "danger";
            break;
    }
    
    if ($message) {
         header("Location: admin.php?msg=" . urlencode($message) . "&msg_type=" . $message_type);
         exit;
    }
}

if (isset($_GET['msg']) && isset($_GET['msg_type'])) {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['msg_type'];
}


$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$total_classrooms = $pdo->query("SELECT COUNT(*) FROM classrooms")->fetchColumn();
$total_subjects = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();

$users = $pdo->query("SELECT * FROM users ORDER BY role, first_name")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$classrooms = $pdo->query("SELECT c.*, u.first_name, u.last_name
                           FROM classrooms c
                           LEFT JOIN users u ON c.teacher_id = u.id
                           ORDER BY c.name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>لوحة تحكم الأدمن - نظام الدرجات</title>

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
                        info_border: '#b8daff',
                        info_text: '#004085',
                    },
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    },
                     keyframes: {
                         slideInDown: {
                            '0%': { opacity: '0', transform: 'translateY(-20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0'},
                            '100%': { opacity: '1'},
                        }
                    },
                    animation: {
                        'slide-in-down': 'slideInDown 0.5s ease-out forwards',
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
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
        
        .admin-tabs {
            display: flex; flex-wrap: wrap;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
        }
        .admin-tabs button {
            background-color: transparent;
            border: none;
            padding: 12px 18px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: #555;
            transition: color 0.3s, border-bottom 0.3s;
            border-bottom: 3px solid transparent;
            margin-left: 5px;
        }
        .admin-tabs button.active {
            color: #1e40af;
            border-bottom-color: #1e40af;
        }
        .admin-tabs button:hover {
            color: #3b82f6;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.5s ease-out; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.07);
            text-align: center;
            border: 1px solid #e5e7eb;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
             transform: translateY(-5px);
             box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .stat-card i {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #3b82f6;
        }
        .stat-card h4 {
            font-size: 2.25em;
            margin-bottom: 5px;
            color: #111827;
        }
        .stat-card p {
            margin: 0;
            color: #4b5563;
            font-weight: 600;
        }

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
        .data-table .actions a, .data-table .actions button {
            display: inline-flex;
            align-items: center;
            margin: 2px;
            padding: 5px 10px;
            font-size: 13px;
            text-decoration: none;
            border: 1px solid;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
         .data-table .actions .btn-edit { border-color: #3b82f6; color: #3b82f6; }
         .data-table .actions .btn-edit:hover { background-color: #3b82f6; color: white; }
         .data-table .actions .btn-password { border-color: #f59e0b; color: #f59e0b; }
         .data-table .actions .btn-password:hover { background-color: #f59e0b; color: white; }
         .data-table .actions .btn-delete { border-color: #ef4444; color: #ef4444; }
         .data-table .actions .btn-delete:hover { background-color: #ef4444; color: white; }
         .data-table .actions .btn-info { border-color: #17a2b8; color: #17a2b8; }
         .data-table .actions .btn-info:hover { background-color: #17a2b8; color: white; }

        .modal {
            display: none; position: fixed; z-index: 1050;
            left: 0; top: 0; width: 100%; height: 100%;
            overflow: auto; background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 8% auto;
            padding: 0;
            border: 0;
            width: 90%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: slideInDown 0.4s ease-out;
        }
        .modal-header {
            padding: 1rem 1.5rem;
            background: #1e40af;
            color: white;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h5 { margin: 0; font-size: 1.25rem; font-weight: 700; }
        .modal-body { padding: 1.5rem; }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background-color: #f9fafb;
            border-radius: 0 0 8px 8px;
        }
        .btn-close {
            background: none; border: none; font-size: 1.75rem;
            color: white; opacity: 0.8; cursor: pointer;
            transition: opacity 0.2s ease;
        }
         .btn-close:hover { opacity: 1; }

         .modal-footer .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            border: none;
            transition: background-color 0.3s ease;
         }
         .btn-secondary { background-color: #6c757d; color: white; }
         .btn-primary { background-color: #1e40af; color: white; }
         .btn-primary:hover { background-color: #3b82f6;}
         .btn-secondary:hover { background-color: #5a6268;}

         .password-toggle { cursor: pointer; color: #666; }
         .password-toggle:hover { color: #1e40af; }
         
         .form-group { margin-bottom: 1.25rem; }
         .form-group label {
            display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;
         }
         .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="password"], .form-group select {
            width: 100%; padding: 0.75rem 1rem; border: 1px solid #D1D5DB;
            border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
             -webkit-appearance: none; -moz-appearance: none; appearance: none;
             background-color: #fff;
         }
         .form-group select {
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22currentColor%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: left 0.5rem center;
            background-size: 1.5em 1.5em;
            padding-left: 2.5rem;
        }
         .form-group input:focus, .form-group select:focus {
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
                        <i class="fas fa-shield-alt mr-3 text-bsu_medium_blue"></i> لوحة تحكم الأدمن (نظام الدرجات)
                    </h2>
                    <span class="text-sm text-gray-600">مرحباً، <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 
                        [<a href="logout.php" class="text-red-600 hover:underline">تسجيل الخروج</a>]
                    </span>
                </div>

                <?php if ($message): ?>
                    <div class="p-4 mb-5 rounded-md text-center font-semibold animate-slide-in-down <?php echo $message_type === 'success' ? 'bg-success_bg border border-success_border text-success_text' : 'bg-danger_bg border border-danger_border text-danger_text'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="admin-tabs">
                    <button class="tab-button active" onclick="openTab(event, 'dashboard')">
                        <i class="fas fa-tachometer-alt mr-2"></i> لوحة المعلومات
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'users')">
                        <i class="fas fa-users mr-2"></i> إدارة المستخدمين
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'subjects')">
                        <i class="fas fa-book mr-2"></i> إدارة المواد
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'classrooms')">
                        <i class="fas fa-chalkboard mr-2"></i> إدارة الفصول
                    </button>
                </div>

                <div id="dashboard" class="tab-content active">
                    <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 mt-4">نظرة عامة</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <i class="fas fa-user-graduate"></i>
                            <h4><?php echo $total_students; ?></h4>
                            <p>إجمالي الطلاب</p>
                        </div>
                         <div class="stat-card">
                             <i class="fas fa-chalkboard-teacher"></i>
                             <h4><?php echo $total_teachers; ?></h4>
                            <p>إجمالي المدرسين</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-chalkboard"></i>
                            <h4><?php echo $total_classrooms; ?></h4>
                            <p>الفصول الدراسية</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-book"></i>
                            <h4><?php echo $total_subjects; ?></h4>
                            <p>المواد الدراسية</p>
                        </div>
                    </div>
                </div>

                <div id="users" class="tab-content">
                     <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 mt-4">إدارة المستخدمين</h3>
                    <button class="submit-btn" style="width: auto; margin-bottom: 20px; background-color: #28a745; padding: 0.6rem 1.2rem;" onclick="openModal('addUserModal')">
                        <i class="fas fa-plus mr-2"></i> إضافة مستخدم جديد
                    </button>

                     <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="data-table min-w-full">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>الاسم</th>
                                    <th>اسم المستخدم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الدور</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <?php foreach($users as $user): ?>
                                <tr>
                                    <td class="text-gray-500"><?php echo $user['id']; ?></td>
                                    <td class="font-medium text-gray-900"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="inline-block px-2 py-1 text-xs font-semibold rounded-full <?php 
                                        switch($user['role']) {
                                            case 'admin': echo 'bg-red-100 text-red-700'; break;
                                            case 'teacher': echo 'bg-green-100 text-green-700'; break;
                                            case 'student': echo 'bg-blue-100 text-blue-700'; break;
                                            default: echo 'bg-gray-100 text-gray-700';
                                        }
                                    ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                                    <td class="actions">
                                        <button class="btn-edit" onclick="openEditUserModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>', '<?php echo htmlspecialchars(addslashes($user['first_name'])); ?>', '<?php echo htmlspecialchars(addslashes($user['last_name'])); ?>', '<?php echo htmlspecialchars(addslashes($user['email'])); ?>', '<?php echo $user['role']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-password" onclick="openChangePasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <a href="?action=delete_user&id=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟ لا يمكن التراجع عن هذا الإجراء.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="subjects" class="tab-content">
                    <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 mt-4">إدارة المواد الدراسية</h3>
                     <button class="submit-btn" style="width: auto; margin-bottom: 20px; background-color: #28a745; padding: 0.6rem 1.2rem;" onclick="openModal('addSubjectModal')">
                        <i class="fas fa-plus mr-2"></i> إضافة مادة جديدة
                    </button>

                    <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="data-table min-w-full">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>اسم المادة</th>
                                    <th>الرمز</th>
                                    <th>الوصف</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <?php foreach($subjects as $subject): ?>
                                <tr>
                                    <td class="text-gray-500"><?php echo $subject['id']; ?></td>
                                    <td class="font-medium text-gray-900"><?php echo htmlspecialchars($subject['name']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['description']); ?></td>
                                    <td class="actions">
                                         <button class="btn-edit" onclick="openEditSubjectModal(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars(addslashes($subject['name'])); ?>', '<?php echo htmlspecialchars(addslashes($subject['code'])); ?>', '<?php echo htmlspecialchars(addslashes($subject['description'])); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?action=delete_subject&id=<?php echo $subject['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذه المادة؟ سيؤدي هذا أيضًا إلى حذف جميع الدرجات المرتبطة بها.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                 <div id="classrooms" class="tab-content">
                    <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 mt-4">إدارة الفصول الدراسية</h3>
                    <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="data-table min-w-full">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>اسم الفصل</th>
                                    <th>الوصف</th>
                                    <th>المدرس المسؤول</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                             <tbody class="bg-white">
                                <?php foreach($classrooms as $classroom): ?>
                                <tr>
                                    <td class="text-gray-500"><?php echo $classroom['id']; ?></td>
                                    <td class="font-medium text-gray-900"><?php echo htmlspecialchars($classroom['name']); ?></td>
                                    <td><?php echo htmlspecialchars($classroom['description']); ?></td>
                                    <td><?php echo htmlspecialchars($classroom['first_name'] . ' ' . $classroom['last_name']); ?></td>
                                    <td class="actions">
                                         <button class="btn-edit"> <i class="fas fa-edit"></i></button>
                                         <button class="btn-info"> <i class="fas fa-users"></i></button>
                                        <a href="?action=delete_classroom&id=<?php echo $classroom['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا الفصل؟ سيؤدي هذا أيضًا إلى حذف جميع الدرجات وارتباطات الطلاب به.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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

    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5>إضافة مستخدم جديد</h5>
                <button type="button" class="btn-close" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body contact-form">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>الاسم الأول</label>
                            <input type="text" name="first_name" required>
                        </div>
                         <div class="form-group">
                            <label>الاسم الأخير</label>
                            <input type="text" name="last_name" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>اسم المستخدم</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>الدور</label>
                            <select name="role" required>
                                <option value="" disabled selected>اختر الدور</option>
                                <option value="admin">أدمن</option>
                                <option value="teacher">مدرس</option>
                                <option value="student">طالب</option>
                            </select>
                        </div>
                    </div>
                     <div class="form-group">
                        <label>البريد الإلكتروني (اختياري)</label>
                        <input type="email" name="email" placeholder="example@domain.com">
                    </div>
                     <div class="form-group">
                        <label>كلمة المرور</label>
                         <div style="position: relative;">
                            <input type="password" name="password" id="add_user_password" required>
                            <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);">
                                 <i class="fas fa-eye password-toggle" id="add_user_password_icon" onclick="togglePassword('add_user_password', 'add_user_password_icon')"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">إلغاء</button>
                    <button type="submit" name="add_user" class="btn btn-primary">إضافة المستخدم</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5>تعديل بيانات المستخدم</h5>
                <button type="button" class="btn-close" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body contact-form">
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>الاسم الأول</label>
                            <input type="text" name="first_name" id="edit_first_name" required>
                        </div>
                         <div class="form-group">
                            <label>الاسم الأخير</label>
                            <input type="text" name="last_name" id="edit_last_name" required>
                        </div>
                    </div>
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>اسم المستخدم</label>
                            <input type="text" name="username" id="edit_username" required>
                        </div>
                        <div class="form-group">
                            <label>الدور</label>
                            <select name="role" id="edit_role" required>
                                <option value="admin">أدمن</option>
                                <option value="teacher">مدرس</option>
                                <option value="student">طالب</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" id="edit_email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">إلغاء</button>
                    <button type="submit" name="update_user" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>

    <div id="changePasswordModal" class="modal">
         <div class="modal-content">
            <div class="modal-header">
                <h5>تغيير كلمة المرور</h5>
                 <button type="button" class="btn-close" onclick="closeModal('changePasswordModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="password_user_id">
                <div class="modal-body contact-form">
                    <div class="form-group">
                        <label>اسم المستخدم</label>
                        <input type="text" class="form-control" id="password_username" readonly style="background-color: #e9ecef; color: #6c757d;">
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور الجديدة</label>
                        <div style="position: relative;">
                            <input type="password" name="new_password" id="change_password" required>
                             <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);">
                                <i class="fas fa-eye password-toggle" id="change_password_icon" onclick="togglePassword('change_password', 'change_password_icon')"></i>
                            </span>
                        </div>
                         <small style="font-size: 12px; color: #666;">يجب أن تكون 6 أحرف على الأقل.</small>
                    </div>
                    <div class="form-group">
                        <label>تأكيد كلمة المرور</label>
                         <div style="position: relative;">
                            <input type="password" name="confirm_password" id="confirm_password" required>
                            <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);">
                                <i class="fas fa-eye password-toggle" id="confirm_password_icon" onclick="togglePassword('confirm_password', 'confirm_password_icon')"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('changePasswordModal')">إلغاء</button>
                    <button type="submit" name="update_password" class="btn btn-primary">تغيير كلمة المرور</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addSubjectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5>إضافة مادة جديدة</h5>
                <button type="button" class="btn-close" onclick="closeModal('addSubjectModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body contact-form">
                     <div class="form-group">
                        <label>اسم المادة</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>رمز المادة</label>
                        <input type="text" name="code" required>
                    </div>
                    <div class="form-group">
                        <label>الوصف (اختياري)</label>
                        <input type="text" name="description">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addSubjectModal')">إلغاء</button>
                    <button type="submit" name="add_subject" class="btn btn-primary">إضافة المادة</button>
                </div>
            </form>
        </div>
    </div>


    <div id="editSubjectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5>تعديل بيانات المادة</h5>
                <button type="button" class="btn-close" onclick="closeModal('editSubjectModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="subject_id" id="edit_subject_id">
                <div class="modal-body contact-form">
                    <div class="form-group">
                        <label>اسم المادة</label>
                        <input type="text" name="name" id="edit_subject_name" required>
                    </div>
                    <div class="form-group">
                        <label>رمز المادة</label>
                        <input type="text" name="code" id="edit_subject_code" required>
                    </div>
                     <div class="form-group">
                        <label>الوصف</label>
                        <input type="text" name="description" id="edit_subject_description">
                    </div>
                </div>
                 <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editSubjectModal')">إلغاء</button>
                    <button type="submit" name="update_subject" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../script.js"></script>
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();

        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            var selectedTab = document.getElementById(tabName);
            if(selectedTab) {
                selectedTab.style.display = "block";
                selectedTab.classList.add("active");
            }
            if(evt) {
                evt.currentTarget.classList.add("active");
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            var dashboardTab = document.getElementById('dashboard');
            if(dashboardTab) {
                dashboardTab.style.display = 'block';
            } else {
                 var firstTab = document.querySelector('.tab-content');
                 if(firstTab) {
                     firstTab.style.display = 'block';
                     firstTab.classList.add('active');
                 }
            }
             var firstTabButton = document.querySelector('.tab-button');
             if(firstTabButton) {
                 firstTabButton.classList.add('active');
             }
        });

        function openModal(modalId) {
            var modal = document.getElementById(modalId);
            if(modal) modal.style.display = "block";
        }
        function closeModal(modalId) {
            var modal = document.getElementById(modalId);
            if(modal) modal.style.display = "none";
        }
        
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = "none";
                }
            }
        }

        function openEditUserModal(id, username, firstName, lastName, email, role) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_first_name').value = firstName;
            document.getElementById('edit_last_name').value = lastName;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            openModal('editUserModal');
        }

        function openChangePasswordModal(id, username) {
            document.getElementById('password_user_id').value = id;
            document.getElementById('password_username').value = username;
            document.getElementById('change_password').value = '';
            document.getElementById('confirm_password').value = '';
            openModal('changePasswordModal');
        }

         function openEditSubjectModal(id, name, code, description) {
            document.getElementById('edit_subject_id').value = id;
            document.getElementById('edit_subject_name').value = name;
            document.getElementById('edit_subject_code').value = code;
            document.getElementById('edit_subject_description').value = description;
            openModal('editSubjectModal');
        }

         function togglePassword(passwordFieldId, iconId) {
            var passwordField = document.getElementById(passwordFieldId);
            var icon = document.getElementById(iconId);
            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordField.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>

</body>
</html>