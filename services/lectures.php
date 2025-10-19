<?php
include '../student-grades/src/includes/conn.php';
include '../student-grades/src/includes/functions.php'; // For potential future use

// --- Fetch data for filters ---
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$levels = $pdo->query("SELECT * FROM academic_levels ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// --- Handle Search/Filter ---
$lectures = [];
$search_active = false;

// Basic WHERE clause
$sql = "SELECT l.*, s.name as subject_name, d.name as department_name, al.name as level_name, u.first_name, u.last_name
        FROM lectures l
        JOIN subjects s ON l.subject_id = s.id
        JOIN departments d ON l.department_id = d.id
        JOIN academic_levels al ON l.level_id = al.id
        JOIN users u ON l.teacher_id = u.id
        WHERE 1=1"; // Start with a condition that's always true

$params = [];

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!empty($_GET['level_id'])) {
        $sql .= " AND l.level_id = :level_id";
        $params[':level_id'] = $_GET['level_id'];
        $search_active = true;
    }
    if (!empty($_GET['department_id'])) {
        $sql .= " AND l.department_id = :department_id";
        $params[':department_id'] = $_GET['department_id'];
        $search_active = true;
    }
    if (!empty($_GET['subject_id'])) {
        $sql .= " AND l.subject_id = :subject_id";
        $params[':subject_id'] = $_GET['subject_id'];
        $search_active = true;
    }
    if (!empty($_GET['keyword'])) {
        $sql .= " AND (l.title LIKE :keyword OR l.description LIKE :keyword)";
        $params[':keyword'] = '%' . $_GET['keyword'] . '%';
        $search_active = true;
    }
}

$sql .= " ORDER BY l.upload_date DESC"; // Always order by date

// Execute the query only if a search was performed or no filters exist (show all initially?)
// Let's decide to show results ONLY after searching
if ($search_active) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $lectures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $search_error = "حدث خطأ أثناء البحث.";
         error_log("Lecture search error: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المحاضرات الدراسية</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <style>
        /* Styles similar to admin/teacher pages */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 10px; text-align: right; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; color: #333; }
        .data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tr:hover { background-color: #f1f1f1; }
        .data-table .actions a { margin: 0 5px; padding: 5px 8px; font-size: 13px; text-decoration: none; border: 1px solid #007bff; color: #007bff; border-radius: 4px; }
        .data-table .actions a:hover { opacity: 0.7; }
        .filter-form { background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #eee; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end; }
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
                <h2><i class="fas fa-book-open"></i> البحث عن المحاضرات الدراسية</h2>
                <div style="margin-bottom: 20px; text-align: left;">
                    <a href="teacher_lectures.php" class="submit-btn" style="display: inline-block; width: auto; font-size: 14px; padding: 8px 15px; background-color: #5a6268;">
                        <i class="fas fa-cog"></i> الذهاب لإدارة المحاضرات (للمدرسين)
                    </a>
                </div>
                <form action="lectures.php" method="get" class="filter-form contact-form" style="box-shadow: none;">
                    <h4><i class="fas fa-filter"></i> تصفية النتائج</h4>
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="level_id">الفرقة الدراسية</label>
                            <select id="level_id" name="level_id">
                                <option value="">-- الكل --</option>
                                <?php foreach($levels as $level): ?>
                                <option value="<?php echo $level['id']; ?>" <?php echo (isset($_GET['level_id']) && $_GET['level_id'] == $level['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($level['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="department_id">القسم</label>
                            <select id="department_id" name="department_id">
                                <option value="">-- الكل --</option>
                                <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_GET['department_id']) && $_GET['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subject_id">المادة الدراسية</label>
                            <select id="subject_id" name="subject_id">
                                <option value="">-- الكل --</option>
                                <?php foreach($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo (isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="keyword">كلمة مفتاحية (العنوان/الوصف)</label>
                            <input type="text" id="keyword" name="keyword" value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                        </div>
                        <button type="submit" class="submit-btn" style="height: 50px;">
                            <i class="fas fa-search"></i> بحث
                        </button>
                    </div>
                </form>

                <?php if ($search_active): ?>
                    <h3><i class="fas fa-list-ul"></i> نتائج البحث</h3>
                    <?php if (isset($search_error)): ?>
                        <p style="color: red; text-align: center;"><?php echo $search_error; ?></p>
                    <?php elseif (empty($lectures)): ?>
                        <p style="text-align: center;">لم يتم العثور على محاضرات تطابق معايير البحث.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>العنوان</th>
                                        <th>المادة</th>
                                        <th>القسم</th>
                                        <th>الفرقة</th>
                                        <th>المدرس</th>
                                        <th>تاريخ الرفع</th>
                                        <th>تحميل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($lectures as $lecture): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lecture['title']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['department_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lecture['level_name']); ?></td>
                                        <td>د. <?php echo htmlspecialchars($lecture['first_name'] . ' ' . $lecture['last_name']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($lecture['upload_date'])); ?></td>
                                        <td class="actions">
                                            <a href="<?php echo htmlspecialchars($lecture['file_path']); ?>" target="_blank">
                                                <i class="fas fa-download"></i> تحميل
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                 <?php else: ?>
                      <p style="text-align: center; color: #555;">الرجاء استخدام الفلاتر أعلاه للبحث عن المحاضرات.</p>
                 <?php endif; ?>

            </section>
        </div>
    </main>

    <footer><div class="container"><p>&copy; 2025 كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p></div></footer>
</body>
</html>
