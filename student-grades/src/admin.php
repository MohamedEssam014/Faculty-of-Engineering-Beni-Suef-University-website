<?php
include 'includes/conn.php';
include 'includes/functions.php';
requireRole('admin'); // التأكد من أن المستخدم هو أدمن

$message = '';
$message_type = ''; // success or danger

// --- معالجة إرسال النماذج (إضافة/تعديل/حذف) ---
// (الكود الخاص بمعالجة POST و GET يبقى كما هو - لم يتم تغييره)
// ... (Your existing PHP code for handling POST/GET requests) ...
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'];
        $role = $_POST['role'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];

        // Check if username already exists
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->execute([$username]);

        if ($check_stmt->rowCount() > 0) {
            $message = "اسم المستخدم موجود بالفعل!";
            $message_type = "danger";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $email, $role, $first_name, $last_name]);
            $message = "تمت إضافة المستخدم بنجاح!";
            $message_type = "success";
        }
    }

    if (isset($_POST['add_subject'])) {
        $name = $_POST['name'];
        $code = $_POST['code'];
        $description = $_POST['description'];

        // Check if subject code already exists
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

    if (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $username = $_POST['username'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $role = $_POST['role'];

        // Check if username already exists (excluding current user)
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
        $name = $_POST['name'];
        $code = $_POST['code'];
        $description = $_POST['description'];

        // Check if subject code already exists (excluding current subject)
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
// Handle delete actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];

    switch ($action) {
        case 'delete_user':
            $result = deleteUser($pdo, $id);
            if ($result === true) {
                $message = "تم حذف المستخدم بنجاح!";
                $message_type = "success";
            } else {
                $message = $result; // Will contain error message
                $message_type = "danger";
            }
            break;

        case 'delete_subject':
             $result = deleteSubject($pdo, $id);
            if ($result === true) {
                $message = "تم حذف المادة بنجاح!";
                $message_type = "success";
            } else {
                $message = $result; // Will contain error message
                $message_type = "danger";
            }
            break;

        case 'delete_classroom':
             $result = deleteClassroom($pdo, $id);
            if ($result === true) {
                $message = "تم حذف الفصل الدراسي بنجاح!";
                $message_type = "success";
            } else {
                $message = $result; // Will contain error message
                $message_type = "danger";
            }
            break;
    }
    // Redirect back to admin page without GET parameters to avoid re-deletion on refresh
    if ($message) {
         header("Location: admin.php?msg=" . urlencode($message) . "&msg_type=" . $message_type);
         exit;
    }
}

// Get message from URL parameters if redirected after deletion
if (isset($_GET['msg']) && isset($_GET['msg_type'])) {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['msg_type'];
}


// --- جلب البيانات للإحصائيات والجداول ---
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$total_classrooms = $pdo->query("SELECT COUNT(*) FROM classrooms")->fetchColumn();
$total_subjects = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();

$users = $pdo->query("SELECT * FROM users ORDER BY role, first_name")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
$classrooms = $pdo->query("SELECT c.*, u.first_name, u.last_name
                           FROM classrooms c
                           LEFT JOIN users u ON c.teacher_id = u.id
                           ORDER BY c.name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الأدمن - نظام الدرجات</title>

    <link rel="stylesheet" href="../../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* استايلات التبويبات */
        .admin-tabs {
            display: flex;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            background-color: #f9f9f9;
            padding: 5px;
            border-radius: 8px 8px 0 0;
        }
        .admin-tabs button {
            background-color: transparent;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #555;
            transition: color 0.3s, border-bottom 0.3s;
            border-bottom: 3px solid transparent;
            margin-left: 5px; /* مسافة بين الأزرار */
        }
        .admin-tabs button.active {
            color: #00447C; /* لون الكلية الرئيسي */
            border-bottom-color: #00447C;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* استايلات بطاقات الإحصائيات */
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
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            text-align: center;
        }
        .stat-card i {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #005A9E; /* لون الكلية الثانوي */
        }
        .stat-card h4 {
            font-size: 2em;
            margin-bottom: 5px;
            color: #333;
        }
        .stat-card p {
            margin: 0;
            color: #666;
            font-weight: bold;
        }

        /* استايلات الجداول */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: right;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }
        .data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tr:hover { background-color: #f1f1f1; }
        .data-table .actions a, .data-table .actions button {
            margin: 0 3px;
            padding: 5px 8px;
            font-size: 13px;
            text-decoration: none;
            border: 1px solid;
            border-radius: 4px;
            cursor: pointer;
        }
         .data-table .actions .btn-edit { border-color: #007bff; color: #007bff; background: transparent;}
         .data-table .actions .btn-password { border-color: #ffc107; color: #ffc107; background: transparent;}
         .data-table .actions .btn-delete { border-color: #dc3545; color: #dc3545; background: transparent;}
         .data-table .actions a:hover, .data-table .actions button:hover { opacity: 0.7; }

        /* استايلات النوافذ المنبثقة (Modal) */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1050; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; /* 10% from the top and centered */
            padding: 0;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 15px 20px;
            background: #00447C; /* لون الكلية */
            color: white;
            border-bottom: 1px solid #dee2e6;
             border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h5 { margin: 0; font-size: 1.25rem;}
        .modal-body { padding: 20px; }
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end; /* Align buttons to the right (since RTL) */
            gap: 10px;
        }
        .btn-close {
            background: none; border: none; font-size: 1.5rem; color: white; opacity: 0.7; cursor: pointer;
        }
         .btn-close:hover { opacity: 1; }

         /* استخدام نفس ستايل الأزرار الرئيسي */
         .modal-footer .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
         }
         .btn-secondary { background-color: #6c757d; color: white; border: none;}
         .btn-primary { background-color: #00447C; color: white; border: none;}
         .btn-primary:hover { background-color: #005A9E;}
         .btn-secondary:hover { background-color: #5a6268;}


        /* أيقونة العين لكلمة المرور */
        .password-toggle { cursor: pointer; color: #666; }
        .password-toggle:hover { color: #00447C; }

    </style>
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
            <section class="page-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>لوحة تحكم الأدمن (نظام الدرجات)</h2>
                    <span>مرحباً، <?php echo htmlspecialchars($_SESSION['first_name']); ?>! [<a href="logout.php" style="color: red; text-decoration: none;">تسجيل الخروج</a>]</span>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $message_type === 'success' ? 'message-success' : 'message-danger'; ?>" style="padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; background-color: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; border: 1px solid <?php echo $message_type === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>


                <div class="admin-tabs">
                    <button class="tab-button active" onclick="openTab(event, 'dashboard')">
                        <i class="fas fa-tachometer-alt me-2"></i> لوحة المعلومات
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'users')">
                        <i class="fas fa-users me-2"></i> إدارة المستخدمين
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'subjects')">
                        <i class="fas fa-book me-2"></i> إدارة المواد
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'classrooms')">
                        <i class="fas fa-chalkboard me-2"></i> إدارة الفصول
                    </button>
                </div>

                <div id="dashboard" class="tab-content active">
                    <h3>نظرة عامة</h3>
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
                     <h3>إدارة المستخدمين</h3>
                    <button class="submit-btn" style="width: auto; margin-bottom: 20px;" onclick="openModal('addUserModal')">إضافة مستخدم جديد</button>

                     <table class="data-table">
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
                        <tbody>
                            <?php foreach($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td class="actions">
                                    <button class="btn-edit" onclick="openEditUserModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>', '<?php echo htmlspecialchars(addslashes($user['first_name'])); ?>', '<?php echo htmlspecialchars(addslashes($user['last_name'])); ?>', '<?php echo htmlspecialchars(addslashes($user['email'])); ?>', '<?php echo $user['role']; ?>')">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    <button class="btn-password" onclick="openChangePasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')">
                                        <i class="fas fa-key"></i> كلمة المرور
                                    </button>
                                    <a href="?action=delete_user&id=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟ لا يمكن التراجع عن هذا الإجراء.')">
                                        <i class="fas fa-trash"></i> حذف
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="subjects" class="tab-content">
                    <h3>إدارة المواد الدراسية</h3>
                     <button class="submit-btn" style="width: auto; margin-bottom: 20px;" onclick="openModal('addSubjectModal')">إضافة مادة جديدة</button>

                    <table class="data-table">
                         <thead>
                            <tr>
                                <th>ID</th>
                                <th>اسم المادة</th>
                                <th>الرمز</th>
                                <th>الوصف</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($subjects as $subject): ?>
                            <tr>
                                <td><?php echo $subject['id']; ?></td>
                                <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                <td><?php echo htmlspecialchars($subject['description']); ?></td>
                                <td class="actions">
                                     <button class="btn-edit" onclick="openEditSubjectModal(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars(addslashes($subject['name'])); ?>', '<?php echo htmlspecialchars(addslashes($subject['code'])); ?>', '<?php echo htmlspecialchars(addslashes($subject['description'])); ?>')">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    <a href="?action=delete_subject&id=<?php echo $subject['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذه المادة؟ سيؤدي هذا أيضًا إلى حذف جميع الدرجات المرتبطة بها.')">
                                        <i class="fas fa-trash"></i> حذف
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                 <div id="classrooms" class="tab-content">
                    <h3>إدارة الفصول الدراسية</h3>
                    <table class="data-table">
                         <thead>
                            <tr>
                                <th>ID</th>
                                <th>اسم الفصل</th>
                                <th>الوصف</th>
                                <th>المدرس المسؤول</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                         <tbody>
                            <?php foreach($classrooms as $classroom): ?>
                            <tr>
                                <td><?php echo $classroom['id']; ?></td>
                                <td><?php echo htmlspecialchars($classroom['name']); ?></td>
                                <td><?php echo htmlspecialchars($classroom['description']); ?></td>
                                <td><?php echo htmlspecialchars($classroom['first_name'] . ' ' . $classroom['last_name']); ?></td>
                                <td class="actions">
                                    <button class="btn-edit"> <i class="fas fa-edit"></i> تعديل </button>
                                     <button class="btn-info" style="border-color: #17a2b8; color: #17a2b8;"> <i class="fas fa-users"></i> عرض الطلاب </button>
                                    <a href="?action=delete_classroom&id=<?php echo $classroom['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا الفصل؟ سيؤدي هذا أيضًا إلى حذف جميع الدرجات وارتباطات الطلاب به.')">
                                        <i class="fas fa-trash"></i> حذف
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p>
        </div>
    </footer>


     <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5>إضافة مستخدم جديد</h5>
                <button type="button" class="btn-close" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body contact-form" style="box-shadow: none; padding-top: 10px;">
                    <div class="form-group">
                        <label>الاسم الأول</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                     <div class="form-group">
                        <label>الاسم الأخير</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label>اسم المستخدم</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>البريد الإلكتروني (اختياري)</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                     <div class="form-group">
                        <label>الدور</label>
                        <select class="form-control" name="role" required>
                            <option value="">اختر الدور</option>
                            <option value="admin">أدمن</option>
                            <option value="teacher">مدرس</option>
                            <option value="student">طالب</option>
                        </select>
                    </div>
                     <div class="form-group">
                        <label>كلمة المرور</label>
                         <div style="position: relative;">
                            <input type="password" class="form-control" name="password" id="add_user_password" required>
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
                <div class="modal-body contact-form" style="box-shadow: none; padding-top: 10px;">
                     <div class="form-group">
                        <label>الاسم الأول</label>
                        <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                    </div>
                     <div class="form-group">
                        <label>الاسم الأخير</label>
                        <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                    </div>
                     <div class="form-group">
                        <label>اسم المستخدم</label>
                        <input type="text" class="form-control" name="username" id="edit_username" required>
                    </div>
                    <div class="form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" class="form-control" name="email" id="edit_email">
                    </div>
                    <div class="form-group">
                        <label>الدور</label>
                        <select class="form-control" name="role" id="edit_role" required>
                            <option value="admin">أدمن</option>
                            <option value="teacher">مدرس</option>
                            <option value="student">طالب</option>
                        </select>
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
                <div class="modal-body contact-form" style="box-shadow: none; padding-top: 10px;">
                    <div class="form-group">
                        <label>اسم المستخدم</label>
                        <input type="text" class="form-control" id="password_username" readonly style="background-color: #e9ecef;">
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور الجديدة</label>
                        <div style="position: relative;">
                            <input type="password" class="form-control" name="new_password" id="change_password" required>
                             <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);">
                                <i class="fas fa-eye password-toggle" id="change_password_icon" onclick="togglePassword('change_password', 'change_password_icon')"></i>
                            </span>
                        </div>
                         <small style="font-size: 12px; color: #666;">يجب أن تكون 6 أحرف على الأقل.</small>
                    </div>
                    <div class="form-group">
                        <label>تأكيد كلمة المرور</label>
                         <div style="position: relative;">
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
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
                <div class="modal-body contact-form" style="box-shadow: none; padding-top: 10px;">
                     <div class="form-group">
                        <label>اسم المادة</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>رمز المادة</label>
                        <input type="text" class="form-control" name="code" required>
                    </div>
                    <div class="form-group">
                        <label>الوصف (اختياري)</label>
                        <input type="text" class="form-control" name="description">
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
                <div class="modal-body contact-form" style="box-shadow: none; padding-top: 10px;">
                    <div class="form-group">
                        <label>اسم المادة</label>
                        <input type="text" class="form-control" name="name" id="edit_subject_name" required>
                    </div>
                    <div class="form-group">
                        <label>رمز المادة</label>
                        <input type="text" class="form-control" name="code" id="edit_subject_code" required>
                    </div>
                     <div class="form-group">
                        <label>الوصف</label>
                        <input type="text" class="form-control" name="description" id="edit_subject_description">
                    </div>
                </div>
                 <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editSubjectModal')">إلغاء</button>
                    <button type="submit" name="update_subject" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        // --- وظائف فتح وإغلاق الـ Modals ---
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }
        // إغلاق الـ Modal عند الضغط خارج المحتوى
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = "none";
                }
            }
        }

        // --- وظيفة فتح التبويبات ---
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
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        // إظهار التبويب الأول (لوحة المعلومات) افتراضياً عند تحميل الصفحة
         document.addEventListener('DOMContentLoaded', function() {
             document.getElementById('dashboard').style.display = 'block';
         });


        // --- وظائف ملء بيانات الـ Modals للتعديل ---
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
             // Reset password fields
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


         // --- وظيفة إظهار/إخفاء كلمة المرور ---
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
