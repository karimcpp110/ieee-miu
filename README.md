# IEEE MIU Student Branch Platform 🚀
**The Ultimate Hybrid Learning & Community Ecosystem**

[![State](https://img.shields.io/badge/Status-Scale--Ready-00ffa3?style=for-the-badge&logo=rocket)](https://ieee-miu-portal.web.app)
[![Tech](https://img.shields.io/badge/Tech-React_%7C_PHP_%7C_Firebase-blue?style=for-the-badge)](https://github.com/karimcpp110/ieee-miu)
[![Performance](https://img.shields.io/badge/Performance-Optimized-orange?style=for-the-badge&logo=speedtest)](https://ieee-miu-portal.web.app)

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
- 🚀 **Stabilized Admin Experience**: Unified tab management logic and optimized "Edit/Create" workflows, ensuring a seamless experience for instructors managing global content across InfinityFree's specific hosting constraints.

### 🏗️ The Core (Original Built-from-Scratch Logic)
- 🎓 **Custom LMS Engine**: Manages the complete course lifecycle, including supplemental resources, PDF downloads, and real-time enrollment tracking.
- 📅 **Event Lifecycle Management**: Complete CRUD operations for community events, including category labeling, date badging, and registration-to-ticket flows.
- 📸 **Digital Time Capsule**: A dynamic gallery system with a proprietary shuffling algorithm that generates a visually stunning mosaic feed for event highlights.
- 📝 **No-Code Form Builder**: Allows for the dynamic creation of custom registration forms with field-level configuration and deep submission analytics.
- 🔐 **Hardened Legacy Security**: Native implementation of CSRF protection, XSS sanitization, hashed password management, and SQL-safe prepared queries.

---

## 🏗️ Technical Architecture

### **The Modern Stack**
> **Frontend**: React 18, TypeScript, Tailwind CSS, Framer Motion  
> **Backend Service**: Google Firebase (Auth, Firestore, Hosting)  
> **Interactivity**: Real-time Webhooks & Observers

### **The Legacy Stack**
> **Logic**: PHP 8.1 with Custom PDO abstraction  
> **Database**: MariaDB / MySQL (Optimized Query Engine)  
> **Tooling**: PHPMailer, FPDF (PDF Logic), Gamification Engines

---

## 🛡️ Security & Integrity
-   **CSRF Protection**: Native guards on all legacy forms.
-   **XSS Sanitization**: Rigorous output encoding to prevent script injection.
-   **Secure RBAC**: Granular permission matrix for Admins, HR, and Instructors.
-   **Auth Isolation**: Ported authentication tokens for secure cross-portal navigation.

---

## ⚙️ Setup & Deployment

### **React Portal**
```bash
cd portal-beta
npm install
npm run deploy
```

### **Legacy PHP Platform**
1.  Upload the contents of `htdocs/` and the newly created `lib/` directory.
2.  The system will automatically perform **Self-Healing** upon your first admin login.
3.  Ensure SMTP credentials are set in `EmailQueue.php`.

---

## 🚀 Future Roadmap
- [ ] **AI Course Assistant**: Dynamic tutoring based on course transcripts.
- [ ] **Mobile App Integration**: Native iOS/Android apps via React Native.
- [ ] **Advanced Analytics Dashboard**: Visualizing student progress with Chart.js.

---

## 👨‍💻 The Engineer & Architect

**Karim Wael**  
*Co-Head R&D Committee | Full-Stack Software Engineer*

> "Building digital experiences that matter."

📫 [kwael7934@gmail.com](mailto:kwael7934@gmail.com) | [LinkedIn](https://www.linkedin.com/in/karim-wael-40132b360/)

---
*Built with ❤️ for the IEEE MIU Student Branch.*
