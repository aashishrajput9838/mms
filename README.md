# Money Management System (MMS)

A comprehensive web application for managing personal finances, tracking income, expenses, and investments.

## Features

- **User Authentication**
  - Secure registration and login system
  - Password encryption
  - Session management

- **Dashboard**
  - Overview of financial status
  - Income vs Expenses visualization
  - Expense distribution charts
  - Recent transactions summary
  - Financial health indicators

- **Income Management**
  - Add and track income from multiple sources
  - Detailed income history
  - Income source analysis

- **Expense Management**
  - Track expenses by categories
  - Expense distribution analysis
  - Detailed expense history

- **Investment Tracking**
  - Track investments (FD/TD/LIC/Mutual Funds/etc.)
  - Monitor interest rates and maturity dates
  - Investment distribution visualization

## Technical Details

### Technology Stack

- **Frontend:** HTML, CSS, JavaScript, Bootstrap 5
- **Backend:** PHP
- **Database:** MySQL
- **Charts:** Chart.js
- **Date Picker:** Flatpickr
- **Icons:** Font Awesome

### Database Schema

- **USER** - Stores user information (ID, name, email, password)
- **INCOME** - Tracks income records (source, amount, date, description)
- **EXPENSE** - Tracks expense records (category, amount, date, description)
- **INVESTMENT** - Tracks investment records (type, amount, dates, interest rate, description)

All tables are connected via foreign keys for efficient data retrieval.

## Installation

1. Clone the repository to your local server directory
2. Import the database schema (or let the application create it automatically)
3. Configure your database connection in `config/db.php`
4. Access the application through your web browser

```php
// Update database configuration in config/db.php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'MMSdb');
```

## Usage

1. Register a new account
2. Log in with your credentials
3. Add your income sources, expenses, and investments
4. View detailed analysis and reports on the dashboard
5. Track your financial progress over time

## Security Features

- Password hashing using PHP's `password_hash()` function
- Prepared statements to prevent SQL injection
- Input validation and sanitization
- CSRF protection
- Session security

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## License

This project is licensed under the MIT License.

## Support

For any questions or issues, please open an issue on the repository or contact the developer. 