# IEEE MIU Website & LMS

A modern, glassmorphism-styled website for the IEEE MIU Student Branch, featuring a dynamic form builder and a custom Learning Management System (LMS).

## 🚀 Features

- **Dynamic Form Builder**: Create custom registration forms with support for text, date, radio, checkbox, and select inputs.
- **Little Moodle (LMS)**: 
    - Admin-managed course content (lectures, videos, etc. via HTML).
    - File upload support for course resources (PDF, Word, PPT).
    - Restricted content access for enrolled/logged-in students.
- **Student Accounts**: Dedicated registration and login system for students.
- **Board Management**: Manage board members with local image uploads.
- **Events Section**: List and manage upcoming club events.
- **Modern UI**: Fully responsive design with glassmorphism effects and tailored color palettes.
- **Database Support**: Dual support for SQLite (Local) and PostgreSQL (Production).

## 🛠️ Local Setup

### Prerequisites
- XAMPP or any PHP 7.4+ environment.
- PHP SQLite extension enabled.

### Installation
1. Clone the repository:
   ```bash
   git clone https://github.com/karimcpp110/ieee-miu.git
   ```
2. Open the project in your local server (e.g., `C:\xampp\htdocs\ieee-miu`).
3. Run the migration script once to initialize the database:
   ```bash
   php migrate_all.php
   ```
4. Access the site via your browser (e.g., `http://localhost/ieee-miu/index.php`).

## ☁️ Deployment (Render)

1. Create a **PostgreSQL** database on Render.
2. Create a **Web Service** on Render and connect this GitHub repository.
3. Set the Environment Variable `DATABASE_URL` to your Render Internal Database URL.
4. Visit `your-site.onrender.com/migrate_all.php` to initialize the production database.

## 👤 Credits
Created by **Karim Wael**.

---
© 2026 IEEE MIU Student Branch.
