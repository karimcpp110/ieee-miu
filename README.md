# IEEE MIU Student Branch Platform 🚀
**The Ultimate Hybrid Learning & Community Ecosystem**

[![Status](https://img.shields.io/badge/Status-Production-00ffa3?style=for-the-badge&logo=rocket)](https://ieeemiu-portal.rf.gd)
[![Tech](https://img.shields.io/badge/Tech-PHP_%7C_MySQL_%7C_InfinityFree-blue?style=for-the-badge)](https://github.com/karimcpp110/ieee-miu)
[![Performance](https://img.shields.io/badge/Performance-Optimized-orange?style=for-the-badge&logo=speedtest)](https://ieeemiu-portal.rf.gd)

---

## 🌟 The Vision: IEEEMIU-PORTAL (Official Hub)
This platform represents a breakthrough in student community engineering. It is a full-scale, professional management ecosystem built from absolute zero—no CMS, no LMS plugins, and no templates. Designed to scale with the needs of the **IEEE MIU Student Branch**, it serves as a self-sustaining powerhouse for training, assessment, and administrative automation.

### 🏁 Objectives:
- **Academic Rigor**: Enforcing mastery through linked assessments and passing thresholds.
- **Operational Excellence**: Automating the entire lifecycle from enrollment to certification.
- **Visual Impact**: Providing a world-class, modern UI that inspires students to engage.

---

## 📑 Table of Contents
- [✨ Core Capabilities](#-core-capabilities)
  - [🧪 The Evolution (New Features)](#-the-evolution-new-features)
  - [🏗️ The Core (Original Architecture)](#-the-core-original-architecture)
- [🏗️ Technical Architecture](#-technical-architecture)
- [🛡️ Security & Integrity](#-security--integrity)
- [⚙️ Setup & Deployment](#-setup--deployment)
- [🚀 Future Roadmap](#-future-roadmap)
- [👨‍💻 The Engineer](#-the-engineer)

---

## ✨ Core Capabilities

- 🧬 **Linked Course Assessments**: Exams are now first-class citizens of the curriculum. Instructors can link specific assessments to courses, enforcing a **60% passing score** (configurable) before completion is unlocked. This ensures that every IEEE MIU certificate represents a verified level of mastery.
- 🎓 **Automated Course Certification**: A seamless integration between the grading engine and the certificate generator. Upon passing the final assessment, the system instantly triggers a premium "Congratulations" email and unlocks the official PDF certificate for download on the student dashboard.
- 📉 **Instant Automated Grading Engine**: A robust backend logic capable of grading complex question types beyond simple MCQs:
    - **Short Answer**: Case-insensitive string matching.
    - **Matching & Ordering**: Structured array comparison for logical reasoning questions.
    - **Real-time Feedback**: Interactive grade badges and dynamic percentage indicators provided immediately upon submission.
- 🎨 **Premium Choice UI (ES6+ & CSS3)**: A high-end, glassmorphic design for assessment components.
    - **Interactive States**: Neon-selected states, bounce animations, and floating cards.
    - **Media Integration**: Support for images in both questions and answer choices, maintaining a professional academic aesthetic.
- 🛡️ **Self-Healing Database Architecture**: A custom PHP-based system monitor. On every admin load, it checks for schema inconsistencies (e.g., missing `exam_id` or `passing_score` columns) and executes `ALTER TABLE` commands automatically. **Zero downtime maintenance.**
- 🤖 **Dynamic Email Automation**: Real-time evaluation of student submissions to trigger tailored responses. 
    - **Smart Placeholders**: Automatically injects `[Score]`, `[Percentage]`, `[Status]`, and `[Course Title]` into templates.
    - **Conditional Logic**: Delivers different email templates based on whether the student passed or failed.
- 📢 **Admin Email Broadcast Engine**: A new administrative tool to send mass communications. Instructors can input a list of recipients and blast an email template to the entire group instantly using the internal SMTP queue.
- 📊 **Advanced Exam Analytics**: A full diagnostic dashboard (`exam_analytics.php`) that lets instructors see question-level success rates, identify bottleneck questions (failure rate > 50%), and track the most common incorrect answers across all submissions.
- 🎓 **Student Achievement Hub**: Public-facing student profiles (`profile.php`) showcasing earned badges, mastery levels (Novice → Master), and verified certifications. Includes a one-click **"Add to LinkedIn"** button for every certificate.
- 🔗 **Public Certificate Verification**: An external endpoint (`verify_certificate.php`) that allows anyone to verify the authenticity of an IEEE MIU certificate via a unique link.
- 🔐 **Student Privacy Controls**: A built-in toggle in the student dashboard allowing members to set their profile to Public or Private.
- 🚀 **Stabilized Admin Experience**: Unified tab management logic and optimized "Edit/Create" workflows, ensuring a seamless experience for instructors managing global content across InfinityFree's specific hosting constraints.

### 🏗️ The Core (Original Built-from-Scratch Logic)
- 🎓 **Custom LMS Engine**: Manages the complete course lifecycle, including supplemental resources, PDF downloads, and real-time enrollment tracking.
- 📅 **Event Lifecycle Management**: Complete CRUD operations for community events, including category labeling, date badging, and registration-to-ticket flows.
- 📸 **Digital Time Capsule**: A dynamic gallery system with a proprietary shuffling algorithm that generates a visually stunning mosaic feed for event highlights.
- 📝 **No-Code Form Builder**: Allows for the dynamic creation of custom registration forms with field-level configuration and deep submission analytics.
- 🔐 **Hardened Legacy Security**: Native implementation of CSRF protection, XSS sanitization, hashed password management, and SQL-safe prepared queries.

---

### **The Stack**
> **Logic**: PHP 8.1 with Custom PDO Abstraction  
> **Database**: MariaDB / MySQL (Optimized Query Engine)  
> **Hosting**: InfinityFree (Production)  
> **Tooling**: PHPMailer, FPDF (PDF Logic), Gamification Engines  
> **Frontend**: Vanilla CSS3, ES6+ JavaScript, Font Awesome

---

## 🛡️ Security & Integrity
-   **CSRF Protection**: Native guards on all legacy forms.
-   **XSS Sanitization**: Rigorous output encoding to prevent script injection.
-   **Secure RBAC**: Granular permission matrix for Admins, HR, and Instructors.
-   **Auth Isolation**: Ported authentication tokens for secure cross-portal navigation.

---

## ⚙️ Setup & Deployment

### **PHP Platform (InfinityFree)**
1.  Upload all PHP files to your InfinityFree `htdocs/` directory.
2.  Import `database_setup.sql` into your MySQL database via phpMyAdmin.
3.  The system will automatically perform **Self-Healing** upon your first admin login via `db_repair.php`.
4.  Ensure SMTP credentials are set in `EmailQueue.php`.

---

## 🚀 Future Roadmap
- [x] **Advanced Exam Analytics Dashboard**: Question-level performance insights for instructors.
- [x] **Student Achievement Hub**: Public profiles with LinkedIn certification integration.
- [ ] **AI Course Assistant**: Dynamic tutoring based on course transcripts.
- [ ] **Mobile App Integration**: Native iOS/Android apps.

---

## 👨‍💻 The Engineer & Architect

**Karim Wael**  
*Co-Head R&D Committee | Full-Stack Software Engineer*

> "Building digital experiences that matter."

📫 [kwael7934@gmail.com](mailto:kwael7934@gmail.com) | [LinkedIn](https://www.linkedin.com/in/karim-wael-40132b360/)

---
*Built with ❤️ for the IEEE MIU Student Branch.*
