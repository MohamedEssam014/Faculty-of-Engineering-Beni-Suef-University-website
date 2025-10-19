<?php
session_start(); // ابدأ الجلسة أولاً

// --- التحقق الجديد ---
if (!isset($_SESSION['is_lecture_manager']) || $_SESSION['is_lecture_manager'] !== true) {
    header("Location: lecture_login.php");
    exit;
}

// --- تضمين ملفات قاعدة البيانات ---
include '../student-grades/src/includes/conn.php';
include '../student-grades/src/includes/functions.php';

// --- الحصول على بيانات المدرس من الجلسة الجديدة ---
$teacher_id = $_SESSION['lecture_teacher_id'];

$message = '';
$message_type = '';
$lecture = null;

// ... (باقي كود جلب بيانات المحاضرة ومعالجة التعديل يبقى كما هو) ...
?>

$teacher_id = $_SESSION['user_id'];
$message = '';
$message_type = '';
$lecture = null;

// --- Get Lecture ID from URL ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('teacher_lectures.php'); // Redirect if no ID is provided
}
$lecture_id = $_GET['id'];

// --- Fetch Lecture Details (Verify Ownership) ---
$stmt = $pdo->prepare("SELECT * FROM lectures WHERE id = ? AND teacher_id = ?");
$stmt->execute([$lecture_id, $teacher_id]);
$lecture = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lecture) {
    // Lecture not found or doesn't belong to the teacher
    $_SESSION['edit_error'] = "المحاضرة المطلوبة غير موجودة أو لا تملك صلاحية تعديلها.";
    redirect('teacher_lectures.php');
}

// --- Fetch data for dropdowns ---
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$levels = $pdo->query("SELECT * FROM academic_levels ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// --- Handle Form Submission (Update) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'] ?? '';
    $subject_id = $_POST['subject_id'];
    $department_id = $_POST['department_id'];
    $level_id = $_POST['level_id'];

    // Basic validation
    if (empty($title) || empty($subject_id) || empty($department_id) || empty($level_id)) {
        $message = "خطأ: يرجى ملء جميع الحقول المطلوبة.";
        $message_type = "danger";
    } else {
        try {
            $sql = "UPDATE lectures SET
                        title = :title,
                        description = :description,
                        subject_id = :subject_id,
                        department_id = :department_id,
                        level_id = :level_id
                    WHERE id = :id AND teacher_id = :teacher_id";

            $update_stmt = $pdo->prepare($sql);
            $update_stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':subject_id' => $subject_id,
                ':department_id' => $department_id,
                ':level_id' => $level_id,
                ':id' => $lecture_id,
                ':teacher_id' => $teacher_id
            ]);

            // Set session message and redirect back
            $_SESSION['edit_success'] = "تم تعديل بيانات المحاضرة بنجاح.";
            header("Location: teacher_lectures.php");
            exit;

        } catch (PDOException $e) {
            $message = "خطأ في قاعدة البيانات أثناء التحديث: " . $e->getMessage();
            $message_type = "danger";
            error_log("Lecture Update Error: " . $e->getMessage());
        }
    }
     // Re-fetch lecture details in case of error during update to show current state
     $stmt->execute([$lecture_id, $teacher_id]);
     $lecture = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل بيانات المحاضرة</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <style>
        /* Shared message styles */
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .message-success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .message-danger { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
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
            <section class="page-content" style="max-width: 800px; margin: 20px auto;">
                 <a href="teacher_lectures.php" class="submit-btn" style="display:inline-block; width: auto; margin-bottom: 20px; background-color:#6c757d; font-size: 14px; padding: 8px 15px;">
                        <i class="fas fa-arrow-right"></i> العودة لإدارة المحاضرات
                 </a>
                <h2><i class="fas fa-edit"></i> تعديل بيانات المحاضرة</h2>

                 <?php if ($message): ?>
                    <div class="message <?php echo $message_type === 'success' ? 'message-success' : 'message-danger'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                 <div class="contact-form">
                    <form action="edit_lecture.php?id=<?php echo $lecture_id; ?>" method="post">
                        <div class="form-group">
                            <label for="title">عنوان المحاضرة/الملف</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($lecture['title']); ?>" required>
                        </div>
                         <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div class="form-group">
                                <label for="subject_id">المادة الدراسية</label>
                                <select id="subject_id" name="subject_id" required>
                                    <option value="">-- اختر المادة --</option>
                                    <?php foreach($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo ($lecture['subject_id'] == $subject['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']) . " (" . htmlspecialchars($subject['code']) . ")"; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="department_id">القسم</label>
                                <select id="department_id" name="department_id" required>
                                    <option value="">-- اختر القسم --</option>
                                    <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($lecture['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="level_id">الفرقة الدراسية</label>
                                <select id="level_id" name="level_id" required>
                                    <option value="">-- اختر الفرقة --</option>
                                     <?php foreach($levels as $level): ?>
                                    <option value="<?php echo $level['id']; ?>" <?php echo ($lecture['level_id'] == $level['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($level['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description">وصف (اختياري)</label>
                            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($lecture['description']); ?></textarea>
                        </div>
                         <div class="form-group">
                            <label>الملف الحالي:</label>
                            <p><a href="<?php echo htmlspecialchars($lecture['file_path']); ?>" target="_blank"><?php echo basename($lecture['file_path']); ?></a></p>
                            <small>(لا يمكن تغيير الملف المرفق من هنا، لحذفه ورفع ملف جديد، يرجى حذف المحاضرة وإعادة رفعها).</small>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-save"></i> حفظ التعديلات
                        </button>
                    </form>
                 </div>
            </section>
        </div>
    </main>

    <footer><div class="container"><p>&copy; 2025 كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p></div></footer>
</body>
</html>
