<?php
session_start(); // ابدأ الجلسة أولاً

// --- التحقق الجديد ---
// إذا لم يكن مسجل الدخول عبر نظام المحاضرات، أعده لصفحة الدخول الخاصة به
if (!isset($_SESSION['is_lecture_manager']) || $_SESSION['is_lecture_manager'] !== true) {
    header("Location: lecture_login.php");
    exit;
}

// --- تضمين ملفات قاعدة البيانات ---
include '../student-grades/src/includes/conn.php';
include '../student-grades/src/includes/functions.php';

// --- الحصول على بيانات المدرس من الجلسة الجديدة ---
$teacher_id = $_SESSION['lecture_teacher_id']; // استخدم الـ ID المخزن
$teacher_name = $_SESSION['lecture_teacher_name'] ?? "مدرس"; // اسم افتراضي

$message = '';
$message_type = ''; // success or danger

// ... (باقي كود معالجة الحذف ورفع الملفات يبقى كما هو) ...

// --- (الكود الجديد ينتهي هنا) ---

// --- Fetch data for dropdowns ---
// ... (الكود الحالي لجلب البيانات يبقى كما هو) ...

$teacher_id = $_SESSION['user_id'];
$message = '';
$message_type = ''; // success or danger

// --- Fetch data for dropdowns ---
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$levels = $pdo->query("SELECT * FROM academic_levels ORDER BY id")->fetchAll(PDO::FETCH_ASSOC); // Order by ID is usually logical for levels

// --- Handle Lecture Upload ---
// --- (الكود الجديد يبدأ هنا) ---
// Handle Lecture Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_lecture' && isset($_GET['id'])) {
    $lecture_id = $_GET['id'];

    // First, get the file path to delete the actual file
    $get_file_stmt = $pdo->prepare("SELECT file_path FROM lectures WHERE id = ? AND teacher_id = ?");
    $get_file_stmt->execute([$lecture_id, $teacher_id]);
    $lecture_file = $get_file_stmt->fetch(PDO::FETCH_ASSOC);

    if ($lecture_file) {
        // Lecture found and belongs to the teacher, proceed with deletion
        try {
            // Begin transaction for safety
            $pdo->beginTransaction();

            // Delete the database record
            $delete_stmt = $pdo->prepare("DELETE FROM lectures WHERE id = ? AND teacher_id = ?");
            $delete_stmt->execute([$lecture_id, $teacher_id]);

            // Delete the actual file if it exists
            if (file_exists($lecture_file['file_path'])) {
                unlink($lecture_file['file_path']);
            }

            // Commit the transaction
            $pdo->commit();
            $message = "تم حذف المحاضرة بنجاح.";
            $message_type = "success";

        } catch (PDOException $e) {
            // Roll back transaction on error
            $pdo->rollBack();
            $message = "خطأ أثناء حذف المحاضرة: " . $e->getMessage();
            $message_type = "danger";
            error_log("Lecture Deletion Error: " . $e->getMessage());
        }
    } else {
        $message = "المحاضرة غير موجودة أو ليس لديك صلاحية لحذفها.";
        $message_type = "danger";
    }
    // Redirect back to the page to clear GET parameters and prevent re-deletion
    header("Location: teacher_lectures.php?msg=" . urlencode($message) . "&msg_type=" . $message_type);
    exit;
}

// Get message from URL if redirected after deletion/action
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['msg_type'];
}
// --- (الكود الجديد ينتهي هنا) ---

// --- Fetch data for dropdowns ---
// ... (الكود الحالي لجلب البيانات يبقى كما هو) ...
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["lecture_file"])) {
    $title = $_POST['title'];
    $description = $_POST['description'] ?? '';
    $subject_id = $_POST['subject_id'];
    $department_id = $_POST['department_id'];
    $level_id = $_POST['level_id'];
    $file = $_FILES["lecture_file"];

    // --- File Handling ---
    $target_dir = "uploads/lectures/";
    // Sanitize filename and create a unique name to prevent overwrites/conflicts
    $original_filename = basename($file["name"]);
    $safe_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $original_filename); // Remove unsafe characters
    $file_extension = pathinfo($safe_filename, PATHINFO_EXTENSION);
    $unique_prefix = time() . '_' . uniqid(); // Add timestamp and unique ID
    $target_file_name = $unique_prefix . '_' . $safe_filename;
    $target_file_path = $target_dir . $target_file_name;
    $file_type = $file["type"]; // Get MIME type
    $file_size = $file["size"]; // Get file size

    // Basic validation (you might want more robust validation)
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $message = "خطأ أثناء رفع الملف (رمز الخطأ: " . $file["error"] . ")";
        $message_type = "danger";
    } elseif ($file_size > 50 * 1024 * 1024) { // Example: Limit to 50MB
        $message = "خطأ: حجم الملف يتجاوز الحد المسموح به (50MB).";
        $message_type = "danger";
    } elseif (empty($title) || empty($subject_id) || empty($department_id) || empty($level_id)) {
         $message = "خطأ: يرجى ملء جميع الحقول المطلوبة.";
         $message_type = "danger";
    } else {
        // Attempt to move the uploaded file
        if (move_uploaded_file($file["tmp_name"], $target_file_path)) {
            // File uploaded successfully, insert into database
            try {
                $sql = "INSERT INTO lectures (title, description, file_path, file_type, file_size, teacher_id, subject_id, department_id, level_id)
                        VALUES (:title, :description, :file_path, :file_type, :file_size, :teacher_id, :subject_id, :department_id, :level_id)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':file_path' => $target_file_path,
                    ':file_type' => $file_type,
                    ':file_size' => $file_size,
                    ':teacher_id' => $teacher_id,
                    ':subject_id' => $subject_id,
                    ':department_id' => $department_id,
                    ':level_id' => $level_id
                ]);
                $message = "تم رفع المحاضرة '" . htmlspecialchars($title) . "' بنجاح.";
                $message_type = "success";
            } catch (PDOException $e) {
                // If DB insert fails, try to delete the uploaded file
                unlink($target_file_path);
                $message = "خطأ في قاعدة البيانات: " . $e->getMessage();
                $message_type = "danger";
                error_log("DB Error inserting lecture: " . $e->getMessage()); // Log detailed error
            }
        } else {
            $message = "خطأ: لم يتم نقل الملف إلى المجلد الصحيح. تحقق من صلاحيات المجلد.";
            $message_type = "danger";
            error_log("File Move Error: Failed to move " . $file["tmp_name"] . " to " . $target_file_path); // Log detailed error
        }
    }
}

// --- Fetch teacher's uploaded lectures for display ---
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
    <title>إدارة المحاضرات</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Add styles similar to admin.php and teacher.php for consistency */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 10px; text-align: right; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; color: #333; }
        .data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tr:hover { background-color: #f1f1f1; }
        .data-table .actions a { margin: 0 5px; padding: 5px 8px; font-size: 13px; text-decoration: none; border: 1px solid #dc3545; color: #dc3545; border-radius: 4px; }
        .data-table .actions a:hover { opacity: 0.7; }
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
            <section class="page-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2><i class="fas fa-upload"></i> إدارة ورفع المحاضرات</h2>
                    <span>مرحباً، <?php echo htmlspecialchars($teacher_name); ?>! [<a href="lecture_logout.php" style="color: red; text-decoration: none;">تسجيل الخروج</a>]</span>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $message_type === 'success' ? 'message-success' : 'message-danger'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="contact-form" style="margin-bottom: 30px;">
                     <h3><i class="fas fa-plus-circle"></i> رفع محاضرة جديدة</h3>
                     <form action="teacher_lectures.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">عنوان المحاضرة/الملف</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
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
                            <textarea id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="lecture_file">ملف المحاضرة (PDF, PPT, DOCX, etc.)</label>
                            <input type="file" id="lecture_file" name="lecture_file" required>
                        </div>
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-upload"></i> رفع الملف
                        </button>
                     </form>
                </div>

                <div>
                    <h3><i class="fas fa-list-ul"></i> المحاضرات التي قمت برفعها</h3>
                    <?php if (empty($uploaded_lectures)): ?>
                        <p>لم تقم برفع أي محاضرات بعد.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>العنوان</th>
                                        <th>المادة</th>
                                        <th>القسم</th>
                                        <th>الفرقة</th>
                                        <th>نوع الملف</th>
                                        <th>تاريخ الرفع</th>
                                        <th>الإجراء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($uploaded_lectures as $lecture): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lecture['title']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['department_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['level_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['file_type']) ?: 'غير معروف'; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($lecture['upload_date'])); ?></td>
                                        <td class="actions">
                        <a href="<?php echo htmlspecialchars($lecture['file_path']); ?>" target="_blank" style="border-color: #007bff; color:#007bff; margin-left: 5px;">
                            <i class="fas fa-download"></i> عرض
                        </a>
                        <a href="edit_lecture.php?id=<?php echo $lecture['id']; ?>" style="border-color: #ffc107; color:#ffc107; margin-left: 5px;">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                        <a href="?action=delete_lecture&id=<?php echo $lecture['id']; ?>"
                           onclick="return confirm('هل أنت متأكد من حذف هذه المحاضرة؟ سيتم حذف الملف نهائياً.')"
                           style="border-color: #dc3545; color:#dc3545;">
                            <i class="fas fa-trash"></i> حذف
                        </a>
                    </td>
                    ```
                                            <a href="<?php echo htmlspecialchars($lecture['file_path']); ?>" target="_blank" style="border-color: #007bff; color:#007bff;"><i class="fas fa-download"></i> عرض</a>
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

    <footer><div class="container"><p>&copy; 2025 كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p></div></footer>
</body>
</html>
