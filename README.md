# NEU-LIBRARY-LOG 📚
**Project for Midterm Information Management 2 - Web App**

## 📖 Overview
The **Library Log System** is a web-based application designed to manage and track library entry/exit logs. It features secure authentication, real-time session monitoring, and comprehensive administrative oversight to ensure policy compliance.

---

## ✨ Key Features
* **Secure Authentication:** User registration and login system.
* **Email Verification:** Powered by **PHPMailer** to ensure authentic user identities.
* **Real-time Session Management:** Automatic session timeouts for security.
* **Sunday Restrictions:** Automated system lockout every Sunday.
* **Status Notifications:** Real-time alerts for account changes (blocking, flagging, etc.).

## 👥 User Functionality
1.  **Registration:** Users fill out a registration form.
2.  **Verification:** A secure link is sent via email to activate the account.
3.  **Logging:** Users view a welcome dashboard upon login.
4.  **Manual Exit:** Users are required to record their exit time manually.
5.  **Policy Compliance:** Access is automatically restricted on Sundays.

## 🛡️ Administrative Tools
Admins have full control over the system database and user activity:
* **User Management:** Edit profiles, delete accounts, or kick active sessions.
* **Enforcement:** Block users or flag them for policy violations with specific reasons.
* **Log Management:** Delete or modify specific entry/exit logs.

## 📊 Data Visualization
The admin dashboard utilizes **Chart.js** to display:
* **Total Users:** Summary cards for quick reference.
* **Traffic Trends:** Line charts showing daily, weekly, and monthly user volume.
* **Usage Purpose:** Doughnut charts visualizing why users are visiting (e.g., Research, Study, Borrowing).

---

## 🛠️ Technical Requirements
* **Language:** PHP
* **Database:** MySQL (MariaDB)
* **Mail Client:** PHPMailer
* **Visualization:** Chart.js
* **Frontend:** HTML5, CSS3, JavaScript (Bootstrap recommended)

---
*Developed for Midterm Information Management 2.*
