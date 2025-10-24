# Website Project: Faculty of Engineering - Beni Suef University (Simulation) 🏛️

## Description

This project is a web application simulating the website for the Faculty of Engineering at Beni Suef University. It includes static informational pages and dynamic services for students and staff, all packaged within a Docker environment for easy setup and development.

This project combines basic **HTML/CSS** for the front-end structure with **PHP** and **MySQL** for dynamic backend services like grade management, certificate lookup, and lecture sharing.

---

## Features ✨

* **Static Pages:**
    * Homepage (`index.html`)
    * About the Faculty (`about.html`)
    * Dean's Word (`dean-word.html`)
    * Vision, Mission & Goals (`vision-mission.html`)
    * Regulations (`regulations.html`, `laws-regulations.html`)
    * Department Pages (Civil, Architectural, Electrical)
    * Contact Us (`contact.html`)
* **Dynamic Services (PHP/MySQL):**
    * **Student Grade Management System:** Separate portals for Admin, Teacher, and Student roles (Located in `student-grades/`).
    * **Certificate Management:**
        * Admin panel (`services/admin_certificates.php`) to upload graduation certificates (PDFs) linked to National ID.
        * Student search page (`services/certificates.php`) to find and download certificates by National ID.
    * **Lecture Management:**
        * Teacher panel (`services/teacher_lectures.php`) to upload and manage lecture files (PDF, PPTX, etc.), categorized by subject, department, and academic level. Includes edit and delete functionality.
        * Student search page (`services/lectures.php`) with filters to find and download lectures.
* **Dockerized Environment:** Uses Docker Compose to set up Apache, PHP, MySQL, and phpMyAdmin with minimal configuration.

---

## Tech Stack 💻

* **Frontend:** HTML5, CSS3
* **Backend:** PHP 8.1 (using PDO for database interaction)
* **Database:** MySQL 5.7
* **Web Server:** Apache (via official PHP Docker image)
* **Containerization:** Docker & Docker Compose
* **Database Admin:** phpMyAdmin

---

## Project Structure 📁
project-root/
├── index.html                       # Main homepage
├── about.html                       # About page
├── contact.html                     # Contact page
├── *.html                           # Other static HTML pages
├── style.css                        # Main stylesheet

│
├── services/                        # Folder for custom PHP services
│   ├── admin_certificates.php        # Admin: Upload certificates
│   ├── certificates.php              # Student: Search certificates
│   ├── login.php                     # Login for certificate admin
│   ├── lecture_login.php             # Login for lecture management
│   ├── teacher_lectures.php          # Teacher: Upload/Manage lectures
│   ├── edit_lecture.php              # Teacher: Edit lecture details
│   ├── lectures.php                  # Student: Search lectures
│   ├── logout.php                    # Logout for certificate admin
│   ├── lecture_logout.php            # Logout for lecture management
│   └── uploads/                      # Directory for uploaded files
│       ├── certificates/
│       └── lectures/

│
├── student-grades/                  # Student Grade Management System module
│   ├── src/                         # PHP source files for the grade system
│   │   ├── includes/                # Connection and function files
│   │   ├── index.php                # Login page for grade system
│   │   ├── admin.php                # Admin dashboard
│   │   ├── teacher.php              # Teacher dashboard
│   │   ├── students.php             # Student dashboard
│   │   ├── grades.php               # Teacher: Manage grades
│   │   ├── classroom.php            # Teacher: Manage classrooms
│   │   └── logout.php               # Logout for grade system
│   │
│   └── db/
│       └── student_grade_management.sql   # Database schema

│
├── Dockerfile                      # Instructions to build the PHP/Apache image
├── docker-compose.yml              # Defines services (web, db, phpmyadmin)
├── uploads.ini                     # Custom PHP settings (e.g., upload size)
└── README.md                       # Project documentation

---

## Setup Instructions 🚀

**Prerequisites:**
* [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running.

**Steps:**

1.  **Clone the Repository:** (If applicable)
    ```bash
    git clone <repository-url>
    cd <project-folder>
    ```
2.  **(Important) Configure Teacher ID for Lectures:**
    * Open the file `services/lecture_login.php`.
    * Find the line `define('TEACHER_USER_ID', 2);`.
    * Change the `2` to the actual `id` of the 'teacher' user from the `users` table in your database (check via phpMyAdmin after setup if needed). This links uploaded lectures to the correct teacher account.
3.  **Build the Docker Images:** Open a terminal in the project's root directory and run:
    ```bash
    docker compose build
    ```
4.  **Start the Services:**
    ```bash
    docker compose up -d
    ```
    This will start the web server, database, and phpMyAdmin in the background. The database (`student_grade_management.sql`) will be imported automatically on the first run.
5.  **Access the Website & Services:**
    * **Main Website:** [http://localhost:8080](http://localhost:8080)
    * **Database Admin (phpMyAdmin):** [http://localhost:9090](http://localhost:9090) (or the port you configured)
        * Server: `db`
        * User (App): `bsu_user` / Pass: `bsu_password`
        * User (Root): `root` / Pass: `root_password`
    * **Student Grade System Login:** [http://localhost:8080/student-grades/src/](http://localhost:8080/student-grades/src/)
    * **Certificate Admin Login:** [http://localhost:8080/services/login.php](http://localhost:8080/services/login.php)
    * **Lecture Management Login:** [http://localhost:8080/services/lecture_login.php](http://localhost:8080/services/lecture_login.php)

6.  **Default Logins (For Testing):**
    * **Grade System:**
        * Admin: `admin` / `admin123`
        * Teacher: `teacher` / `teacher123`
        * Student: `student` / `student123`
    * **Certificate Admin:** `admin` / `BsuEng@2025`
    * **Lecture Management:** `teacher` / `LecturePass123`

---

## Stopping the Application 🛑

To stop all running services:
```bash
docker compose down

To stop services and remove the database volume (useful for a completely fresh start):
docker compose down -v

Future Enhancements (Ideas) 💡
Add remaining department pages (Mechanical).

Implement user authentication for accessing student/teacher specific sections of the main site (beyond the separate service logins).

Add functionality for teachers to edit uploaded lecture files (currently only details can be edited).

Develop remaining features of the Student Grade System (e.g., classroom management details).

Integrate suggested services: Class Schedules, Faculty Directory, Alumni Portal, Complaint System.

Improve UI/UX and responsiveness.
