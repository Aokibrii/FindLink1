# FindLink - Lost and Found Application

FindLink is a web application designed to help users report and search for lost and found items, making it easier to reconnect people with their belongings.

---

## System Overview

**1. System Name:**
FindLink - Lost and Found Application

**2. Purpose of the System (1–2 sentences):**
FindLink helps users report lost or found items and search for belongings, streamlining the process of reconnecting people with their possessions. It provides a centralized platform for posting, searching, and managing lost and found cases.

**3. Target Users:**

- General public (students, staff, visitors, community members)
- Administrators (system managers)

**4. Core Functionalities:**

- Allows users to register and log in
- Enables users to post lost or found items with images and details
- Provides search and browsing of posted items
- Sends notifications for updates and matches
- Supports user profile management
- Offers admin dashboard for managing users and posts
- Ensures secure authentication and session management

**5. Expected Outcome:**
Users can efficiently report, search, and recover lost or found items, resulting in higher chances of reuniting owners with their belongings. Administrators can manage the platform to ensure smooth operation and content moderation.

**6. Scope Boundaries (Optional but Recommended):**

- The system does not handle physical delivery or verification of items
- No payment or transaction processing is included
- Communication is limited to notifications and messages within the platform

---

## Features

- User registration and login
- Role-based access: regular users and admin
- Post lost or found items with images and details
- Search and browse items
- Notification system for updates and matches
- User profile management
- Admin dashboard for managing users and posts
- Secure authentication and session management

## Project Structure

- `index.php`: Entry point for login and registration
- `login_register.php`: Handles authentication logic
- `user_page.php`: User dashboard for posting and viewing items
- `admin/`: Admin dashboard and management scripts
- `css/`, `js/`: Stylesheets and JavaScript assets
- `database/`: SQL scripts for database setup
- `uploads/`: Uploaded images for posts and profiles

## Database Setup

The system uses two databases:

- `users_db`: Stores user credentials and roles
- `user_db`: Stores posts, notifications, and messages

To set up the databases, run the SQL scripts in the `database/` folder or use the provided batch files.

## Admin Account Setup

1. Access `admin/admin_setup.php` from your local machine (only accessible locally for security).
2. Create the admin account using the fixed email `admin@gmail.com` and a password of your choice.
3. Only one admin account is allowed.

## Security

- Passwords are securely hashed
- Role selection is removed from registration (all new users are regular users)
- Admin setup is restricted to local access
- Session management and input validation implemented

## Getting Started

1. Clone or copy the project to your XAMPP `htdocs` directory.
2. Set up the databases using the scripts in `database/`.
3. Start Apache and MySQL via XAMPP.
4. Access the application at `http://localhost/login_register/`.
