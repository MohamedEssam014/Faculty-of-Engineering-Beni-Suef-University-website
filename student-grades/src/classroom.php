<?php
include 'includes/conn.php';
include 'includes/functions.php';

// التأكد من أن المستخدم هو مدرس
if (!isLoggedIn() || !hasRole('teacher')) {
    redirect('index.php');
}

$teacher_id = $_SESSION['user_id'];
$message = '';
$message_type = ''; // success or danger

// --- (الكود الخاص بمعالجة البيانات يبقى كما هو) ---
// Handle classroom creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_classroom'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    
    $stmt = $pdo->prepare("INSERT INTO classrooms (name, description, teacher_id) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $teacher_id]);
    $message = "تم إنشاء الفصل الدراسي بنجاح!";
    $message_type = "success";
}

// Handle student addition to classroom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $classroom_id = $_POST['classroom_id'];
    $student_id = $_POST['student_id'];
    
    // Check if student is already in classroom
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
}

// Handle student removal from classroom
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
    // Redirect to avoid re-action on refresh
    header("Location: classroom.php?view_students=true&classroom_id=" . $classroom_id . "&msg=" . urlencode($message) . "&msg_type=" . $message_type);
    exit;
}

// Handle classroom deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_classroom' && isset($_GET['id'])) {
    $classroom_id = $_GET['id'];
    
    // Verify the classroom belongs to the teacher
    $check_stmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = ? AND teacher_id = ?");
    $check_stmt->execute([$classroom_id, $teacher_id]);
    
    if ($check_stmt->rowCount() > 0) {
        $result = deleteClassroom($pdo, $classroom_id);
        if ($result === true) {
            $message = "تم حذف الفصل الدراسي بنجاح!";
            $message_type = "success";
        } else {
            $message = $result;
            $message_type = "danger";
        }
    } else {
        $message = "الفصل غير موجود أو ليس لديك صلاحية لحذفه!";
        $message_type = "danger";
    }
     // Redirect to avoid re-action on refresh
    header("Location: classroom.php?msg=" . urlencode($message) . "&msg_type=" . $message_type);
    exit;
}

// Get message from URL
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['msg_type'];
}

// --- (الكود الخاص بجلب البيانات يبقى كما هو) ---
$classrooms_stmt = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ? ORDER BY name");
$classrooms_stmt->execute([$teacher_id]);
$teacher_classrooms = $classrooms_stmt->fetchAll();

$students_stmt = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY first_name, last_name");
$all_students = $students_stmt->fetchAll();

$classroom_students = [];
$current_classroom_name = '';
if (isset($_GET['view_students']) && isset($_GET['classroom_id'])) {
    $classroom_id = $_GET['classroom_id'];
    $classroom_students = getClassroomStudents($pdo, $classroom_id);
    // Get current classroom name for the title
    $name_stmt = $pdo->prepare("SELECT name FROM classrooms WHERE id = ? AND teacher_id = ?");
    $name_stmt->execute([$classroom_id, $teacher_id]);
    $current_classroom_name = $name_stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الفصول الدراسية - نظام الدرجات</title>

    <link rel="stylesheet" href="../../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* استايلات مشابهة لصفحة الأدمن */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd; padding: 12px; text-align: right;
        }
        .data-table th { background-color: #f2f2f2; font-weight: bold; color: #333; }
        .data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tr:hover { background-color: #f1f1f1; }
        .data-table .actions a {
            margin: 0 3px; padding: 5px 8px; font-size: 13px; text-decoration: none;
            border: 1px solid; border-radius: 4px; cursor: pointer;
        }
        .data-table .actions .btn-view { border-color: #17a2b8; color: #17a2b8; }
        .data-table .actions .btn-delete { border-color: #dc3545; color: #dc3545; }
        .data-table .actions a:hover { opacity: 0.7; }
    </style>
</head>
<body>

    <!-- ======================= Header Section ======================= -->
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

    <!-- ======================= Main Content Section ======================= -->
    <main>
        <div class="container">
            <section class="page-content">

                <?php if ($message): ?>
                    <div class="message" style="padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; background-color: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; border: 1px solid <?php echo $message_type === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['view_students']) && $current_classroom_name): ?>
                    <!-- عرض الطلاب في فصل معين -->
                    <h2><i class="fas fa-users"></i> طلاب فصل: <?php echo htmlspecialchars($current_classroom_name); ?></h2>
                    <a href="classroom.php" class="submit-btn" style="display:inline-block; width: auto; margin-bottom: 20px; background-color:#6c757d;">
                        <i class="fas fa-arrow-right"></i> العودة إلى كل الفصول
                    </a>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>اسم الطالب</th>
                                <th>اسم المستخدم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php if (empty($classroom_students)): ?>
                                <tr><td colspan="5" style="text-align: center;">لا يوجد طلاب في هذا الفصل بعد.</td></tr>
                            <?php else: ?>
                                <?php foreach($classroom_students as $student): ?>
                                <tr>
                                    <td><?php echo $student['id']; ?></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td class="actions">
                                        <a href="?action=remove_student&classroom_id=<?php echo $_GET['classroom_id']; ?>&student_id=<?php echo $student['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من إزالة هذا الطالب من الفصل؟')">
                                            <i class="fas fa-user-minus"></i> إزالة
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                <?php else: ?>
                    <!-- العرض الرئيسي لإدارة الفصول -->
                    <h2><i class="fas fa-chalkboard"></i> إدارة فصولي الدراسية</h2>
                    <div class="grid-container">
                        <!-- نموذج إنشاء فصل جديد -->
                        <div class="contact-form">
                            <h3><i class="fas fa-plus-circle"></i> إنشاء فصل دراسي جديد</h3>
                            <form method="POST">
                                <div class="form-group">
                                    <label>اسم الفصل</label>
                                    <input type="text" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label>الوصف (اختياري)</label>
                                    <textarea name="description" rows="3"></textarea>
                                </div>
                                <button type="submit" name="create_classroom" class="submit-btn">
                                    <i class="fas fa-save"></i> إنشاء الفصل
                                </button>
                            </form>
                        </div>

                        <!-- نموذج إضافة طالب لفصل -->
                        <div class="contact-form">
                            <h3><i class="fas fa-user-plus"></i> إضافة طالب إلى فصل</h3>
                            <form method="POST">
                                <div class="form-group">
                                    <label>اختر الفصل الدراسي</label>
                                    <select name="classroom_id" required>
                                        <option value="">-- اختر --</option>
                                        <?php foreach($teacher_classrooms as $classroom): ?>
                                        <option value="<?php echo $classroom['id']; ?>"><?php echo htmlspecialchars($classroom['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>اختر الطالب</label>
                                    <select name="student_id" required>
                                        <option value="">-- اختر --</option>
                                        <?php foreach($all_students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="add_student" class="submit-btn" style="background-color: #28a745;">
                                    <i class="fas fa-user-plus"></i> إضافة الطالب
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- جدول الفصول الحالية -->
                    <h3><i class="fas fa-list-ul"></i> فصولي الحالية</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>اسم الفصل</th>
                                <th>الوصف</th>
                                <th>عدد الطلاب</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php foreach($teacher_classrooms as $classroom):
                                // حساب عدد الطلاب في كل فصل
                                $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM classroom_students WHERE classroom_id = ?");
                                $count_stmt->execute([$classroom['id']]);
                                $student_count = $count_stmt->fetchColumn();
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($classroom['name']); ?></td>
                                <td><?php echo htmlspecialchars($classroom['description']); ?></td>
                                <td><?php echo $student_count; ?></td>
                                <td class="actions">
                                    <a href="?view_students=true&classroom_id=<?php echo $classroom['id']; ?>" class="btn-view">
                                        <i class="fas fa-users"></i> عرض الطلاب
                                    </a>
                                    <a href="?action=delete_classroom&id=<?php echo $classroom['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا الفصل؟ سيتم حذف كل ما يتعلق به.')">
                                        <i class="fas fa-trash"></i> حذف
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- ======================= Footer Section ======================= -->
    <footer>
        <div class="container">
            <p>&copy; 2025 كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p>
        </div>
    </footer>

</body>
</html>
