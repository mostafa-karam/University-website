# 🎓 University Website

A modern web application for managing university data, including students, courses, departments, faculties, and professors.

## 🚀 Overview

This project provides an administrative dashboard and user portal for university management. It supports CRUD operations for all major entities and includes analytics, statistics, and user authentication.

## ✨ Main Features

- 🏛️ **Admin Dashboard:** Manage students, courses, departments, faculties, and professors
- 👤 **User Registration & Login:** Secure authentication for students and staff
- 🏫 **Assignments:** Assign students to departments/courses and professors to departments/faculties
- 📊 **Real-time Statistics:** Interactive charts for enrollment, grades, and more
- 🌗 **Responsive UI:** Light/dark mode support for a modern experience

## 🛠️ Technology Stack

- **Frontend:** HTML, CSS (custom, Bootstrap), JavaScript (jQuery, Chart.js)
- **Backend:** PHP (PDO/MySQL)
- **Database:** MySQL
- **Other:** DataTables, FontAwesome

## ⚡ Setup Instructions

1. **Clone the repository**

   ```bash
   git clone https://github.com/mostafa-karam/University-website.git
   cd University-website
   ```

2. **Database Setup**
   - 🗄️ Create a MySQL database and import the provided schema (see `/admin_conect.php` for connection details).
   - 🔑 Update database credentials in `admin_conect.php` and `connect.php`.

3. **Run Locally**
   - 🖥️ Place the project in your web server's root directory (e.g., `htdocs` for XAMPP).
   - 🔗 Access via [http://localhost/University-website/admin/loginpage.php](http://localhost/University-website/admin/loginpage.php) for admin login.

4. **Dependencies**
   - 📦 All dependencies are loaded via CDN (Bootstrap, jQuery, Chart.js, DataTables, FontAwesome).

## 📝 Usage

- 🗂️ **Admin Panel:** Manage all university data and view analytics.
- 📝 **User Registration:** Students and staff can register and log in.
- 📈 **Dashboard:** View statistics, recent activities, and manage assignments.
