# 📚 NEU Library Log

**A web-based visitor log for the New Era University Library** — tracks who enters and leaves, why they visit, and helps staff enforce library policies.

*Midterm project for Information Management 2.*

🔗 **Live demo:** https://neulibproject.page.gd/login.php

Works on **phones, tablets and desktops** — the visitor form, the "Out" screen, and the whole admin dashboard adapt to small screens.

---

## ✨ Features

### For visitors
- **Sign up with email** (activation link sent by email) **or with Google**
- **Time-in form** — name, ID number, program, and reason for visiting; your ID is remembered for next time
- **"Out" screen** with a live clock and a one-tap **OUT** button to record your time-out
- **Live notices** — you're told right away if staff end your session, block your account, or flag you
- **Forgot password** — reset link sent to your email
- **Closed on Sundays** — logins are disabled on Sundays (admins can still log in)

### For library staff (admin dashboard)
- **Visitor logs** — see who is inside now and past visits; filter by Today / Last Week / Last Month; search by name or ID
- **Add, edit, time-out or delete** any visit, including walk-in guests without an account
- **Export to Excel** (`.xls`) of whatever is currently shown
- **Charts** (Chart.js) — weekly visitor trend and purpose-of-visit breakdown
- **User management** — send password resets, block/unblock, promote to admin, delete accounts
- **Flagging** — flag a visitor for a policy violation with a reason; edit or remove flags later
- **Mobile friendly** — slide-out menu, swipeable tables, full-width forms

---

## 🛠️ Tech stack

| Part | Used |
|---|---|
| Backend | PHP 8 (plain PHP, no framework) |
| Database | MySQL / MariaDB |
| Email | [PHPMailer](https://github.com/PHPMailer/PHPMailer) via Gmail SMTP (bundled in `PHPMailer/`) |
| Login | Email + password, or Google Sign-In |
| Frontend | HTML, CSS, JavaScript, [Chart.js](https://www.chartjs.org/), [SweetAlert2](https://sweetalert2.github.io/) |

---

## 🚀 Setup

### 1. Get the files
```bash
git clone https://github.com/NathanielCoronado/NEU-LIBRARY-LOG.git
```
Put the folder in your web server (for XAMPP: `C:\xampp\htdocs\NEU-LIBRARY-LOG`). On a free host like InfinityFree, upload the files to `htdocs/`.

### 2. Create the database
Open **phpMyAdmin → Import** and import **`database.sql`**. It creates the `neu_library` database and all 5 tables.

> On a free host where you can't create databases from SQL, create the database in the host's control panel first, then remove the first two lines (`CREATE DATABASE…` and `USE…`) from `database.sql` before importing.

### 3. Add your settings
Copy **`config.example.php`** and rename the copy to **`config.php`**, then fill it in:

| Setting | What to put |
|---|---|
| `db` | Your database host, username, password and database name |
| `admin` | Email + password for the built-in master admin account |
| `mail` | Your Gmail address and a **Google App Password** ([how to create one](https://support.google.com/accounts/answer/185833)) |
| `google_client_id` | Your Google Sign-In client ID ([Google Cloud Console](https://console.cloud.google.com/apis/credentials)) — add your site URL as an authorized JavaScript origin |
| `app.secret` | Any long random text (used to sign email-verification links) |

> ⚠️ `config.php` holds your passwords. It's listed in `.gitignore`, so it won't be uploaded to GitHub. **Never commit it.**

### 4. Open the site
Go to `http://localhost/NEU-LIBRARY-LOG/` and log in with the admin email/password you set in `config.php`.

---

## 🔄 How it works

```
index.php (splash) ─► login.php ─┬─► form.php (visitor time-in) ─► out.php (OUT button)
                                 └─► admin.php (staff dashboard)
```

1. A visitor logs in and fills in the time-in form → a row is added to **`active_sessions`** (they're "inside").
2. Pressing **OUT** (or an admin pressing Out) moves that row to **`library_logs`** with the time-out.
3. While inside, `out.php` checks with the server every few seconds, so blocks, removals and new flags show up right away.

---

## 📂 Project structure

```text
├── index.php              # Splash screen → login
├── intro2.php             # Splash screen → sign up
├── login.php              # Email/password login
├── signup.php             # Registration (sends activation email)
├── verify.php             # Activation link handler
├── forgot.php / reset.php # Password reset
├── google_handler.php     # Google Sign-In
├── form.php               # Visitor time-in form
├── out.php                # Visitor "inside" screen + OUT button
├── check_session.php      # Live status polling for out.php
├── logout.php
│
├── admin.php              # Admin dashboard
├── add_visitor.php        # ┐
├── update_process.php     # │
├── out_process.php        # │
├── delete_visitor.php     # │  Admin-only actions
├── delete_user.php        # │  (all return JSON and reject
├── toggle_block.php       # │   non-admins)
├── update_role.php        # │
├── flag_process.php       # │
├── check_id_conflict.php  # │
├── generate_reset_link.php# ┘
│
├── db_conn.php            # Loads config.php + connects to MySQL
├── auth.php               # Session + admin access checks
├── mailer.php             # PHPMailer setup + email templates
├── config.example.php     # Settings template → copy to config.php
├── database.sql           # Database schema
├── admin.css              # Dashboard styles (incl. mobile layout)
├── responsive.css         # Mobile fixes for all other pages
├── PHPMailer/             # Bundled PHPMailer library
└── neu-logo.jpg, neu-library.jpg, favicon.ico
```

---

## 🔐 Security notes
- Passwords are hashed with `password_hash()`; all database queries use prepared statements.
- Admin pages **and** every admin action check that the logged-in user is really an admin.
- Email-verification links are signed and expire after 24 hours; password-reset links expire (10 minutes by default) and can't be reused.
- Google Sign-In tokens are checked against your own client ID.
- Each visitor only gets notified about **their own** flags.

---

## 👤 Author
**Nathaniel Coronado** — New Era University
