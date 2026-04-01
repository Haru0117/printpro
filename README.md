# PrintPro 🖨️

**PrintPro** is a web-based print order management system that allows customers to place print orders, manage subscriptions, and track order statuses — all through a clean, easy-to-use interface. Administrators can manage and update orders from a dedicated dashboard.

---

## ✨ Features

- **User Authentication** – Secure registration and login system
- **Order Management** – Customers can place and track print orders
- **Order Status Updates** – Admins can update the status of orders in real time
- **Subscription Management** – Users can manage their subscription plans
- **Admin Dashboard** – Centralized panel for administrators to oversee all orders
- **RESTful API Layer** – Backend API endpoints for handling business logic

---

## 🛠️ Technology Stack

| Layer      | Technology        |
|------------|-------------------|
| Frontend   | HTML, CSS, JavaScript |
| Backend    | PHP                |
| Database   | MySQL              |
| API        | PHP (REST)         |

---

## 📁 Directory Structure

```
printpro/
├── admin/              # Admin dashboard panel
├── api/                # RESTful API endpoints
│   ├── get_orders.php
│   ├── login.php
│   ├── place_order.php
│   ├── register.php
│   ├── update_order_status.php
│   └── update_subscription.php
├── assets/             # Static assets (CSS, JS, images)
├── includes/           # Reusable PHP components
│   ├── auth.php        # Authentication logic
│   ├── db.php          # Database connection
│   ├── header.php      # Page header
│   └── footer.php      # Page footer
├── pages/              # User-facing pages
│   ├── dashboard.php
│   ├── orders.php
│   ├── place-order.php
│   └── subscription.php
├── index.html          # Landing / home page
├── login.html          # Login page
└── register.html       # Registration page
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- A local server (e.g., [XAMPP](https://www.apachefriends.org/), WAMP, or Laragon)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/YOUR_USERNAME/printpro.git
   ```

2. **Move the project to your server's web root:**
   ```bash
   # For XAMPP on Windows
   mv printpro C:/xampp/htdocs/printpro
   ```

3. **Set up the database:**
   - Open phpMyAdmin and create a new database (e.g., `printpro_db`)
   - Import the provided SQL file (if available) or create tables manually

4. **Configure the database connection:**
   - Open `includes/db.php`
   - Update the database credentials:
     ```php
     $host = 'localhost';
     $db   = 'printpro_db';
     $user = 'root';
     $pass = '';
     ```

5. **Open in browser:**
   ```
   http://localhost/printpro/
   ```

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).
