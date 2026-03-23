# 📚 NEU-LIBRARY-LOG
**Midterm Project for Information Management 2** *A web-based solution for tracking library entry/exit logs with automated policy enforcement.*

[**🚀 Live Demo**](https://neulibproject.page.gd/login.php)

---

## 📖 Overview
The **Library Log System** is a robust application designed to manage library foot traffic at New Era University. It features secure authentication, real-time session monitoring, and comprehensive administrative oversight to ensure policy compliance and data integrity.

---

## ✨ Key Features

### 🔐 Security & Access
* **Secure Authentication:** Full registration and login system.
* **Email Verification:** Powered by **PHPMailer** to ensure authentic user identities.
* **Session Management:** Automatic timeouts for enhanced security.
* **Sunday Restrictions:** Automated system lockout every Sunday to align with library hours.

### 👤 User Functionality
* **Manual Exit Logging:** Users are required to record their departure time manually for accurate data.
* **Status Notifications:** Real-time alerts for account changes (blocking, flagging, or warnings).
* **Intuitive Dashboard:** A clean welcome interface upon successful login.

### 🛡️ Administrative Tools
* **User Management:** Edit profiles, delete accounts, or "kick" active sessions.
* **Enforcement:** Block users or flag them for policy violations with logged reasons.
* **Log Management:** Full CRUD (Create, Read, Update, Delete) capabilities for entry/exit logs.

---

## 📊 Data Visualization
The admin dashboard utilizes **Chart.js** to provide visual insights:
* **Total Users:** Quick-reference summary cards.
* **Traffic Trends:** Line charts showing daily, weekly, and monthly user volume.
* **Usage Purpose:** Doughnut charts visualizing why users visit (e.g., Research, Study, Borrowing).

---

## 🔑 Admin Credentials
For testing and evaluation purposes, use the following credentials to access the Administrative Dashboard:

> **Username:** `neulib@login.2526`  
> **Password:** `********`

---

## 🛠️ Technical Stack
* **Language:** PHP
* **Database:** MySQL (MariaDB)
* **Mail Client:** PHPMailer
* **Visualization:** Chart.js
* **Frontend:** HTML5, CSS3, JavaScript (Bootstrap)

---

## 📂 Project Structure
```text
├── assets/             # Images, CSS, and JS files
├── config/             # Database connection and PHPMailer settings
├── controllers/        # Logical processing (Login, Logout, Logs)
├── db/                 # SQL export files (Database Schema)
├── views/              # User and Admin dashboard templates
└── index.php           # Landing page
