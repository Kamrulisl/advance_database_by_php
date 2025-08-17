# 🗄️ Advanced SQL Database Manager

A powerful, web-based MySQL database management system built with PHP and Bootstrap. This tool provides a comprehensive interface for database administration, offering both basic and advanced features for developers and database administrators.

![Database Manager Screenshot](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)

## ✨ Features

### 🔍 **Advanced Search & Filtering**
- **Global Search**: Search across all columns simultaneously
- **Column-Specific Search**: Target specific fields for precise results
- **Real-time Search**: Instant results with Enter key support
- **Search Highlighting**: Visual indicators for active searches

### 🛠️ **Table Management**
- **Create Tables**: Design custom tables with various data types
- **Drop Tables**: Remove tables with safety confirmations
- **Truncate Tables**: Clear all data while preserving structure
- **Table Structure Viewer**: Inspect column details and constraints

### 📝 **Data Operations**
- **Inline Editing**: Click-to-edit cells with auto-save
- **Bulk Insert**: Add multiple rows efficiently
- **Bulk Delete**: Remove multiple records at once
- **Row Selection**: Multi-select with checkboxes

### 🗂️ **Column Management**
- **Add Columns**: Dynamically add new columns to existing tables
- **Drop Columns**: Remove columns with confirmation
- **Data Type Support**: VARCHAR, INT, TEXT, DATETIME, DATE, DECIMAL, BOOLEAN
- **Constraint Management**: NULL/NOT NULL, Default values, Primary keys

### 💻 **SQL Editor**
- **Query Execution**: Run custom SQL queries directly
- **Syntax Area**: Monospace font with proper formatting
- **Sample Templates**: Pre-built SELECT, INSERT, UPDATE, DELETE queries
- **Results Display**: Formatted table output for query results
- **Keyboard Shortcuts**: Ctrl+Enter to execute, Tab for indentation

### 📊 **Export & Import**
- **Complete Database Export**: Download entire database as SQL
- **Single Table Export**: Export specific tables
- **Selected Rows Export**: Export only chosen records
- **Raw SQL View**: View table structure and data as SQL

### 🎨 **User Experience**
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Dark Theme Headers**: Professional appearance
- **Toast Notifications**: Real-time feedback for all actions
- **Loading Indicators**: Visual feedback for operations
- **Keyboard Navigation**: Full keyboard support

## 🚀 Getting Started

### Prerequisites
- **PHP 7.4+** with PDO MySQL extension
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Web Server** (Apache, Nginx, or built-in PHP server)
- **Modern Web Browser** (Chrome, Firefox, Safari, Edge)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/advanced-sql-database-manager.git
   cd advanced-sql-database-manager
   ```

2. **Configure Database Connection**
   Edit the database configuration at the top of the PHP file:
   ```php
   // Database configuration
   $host = 'localhost';        // Your MySQL host
   $dbname = 'your_database';  // Your database name
   $username = 'your_user';    // Your MySQL username
   $password = 'your_pass';    // Your MySQL password
   ```

3. **Set Permissions** (if needed)
   ```bash
   chmod 755 index.php
   ```

4. **Start the Application**
   
   **Option A: Using Built-in PHP Server**
   ```bash
   php -S localhost:8080
   ```
   
   **Option B: Using Apache/Nginx**
   - Place files in your web server's document root
   - Access via your configured domain/IP

5. **Access the Application**
   Open your browser and navigate to:
   - `http://localhost:8080` (PHP built-in server)
   - `http://your-domain.com` (Web server)

## 📖 Usage Guide

### 🏠 **Dashboard Overview**
- **Left Sidebar**: Lists all database tables with row/column counts
- **Main Area**: Displays selected table data with pagination
- **Top Actions**: Global database operations and table creation
- **Search Bar**: Located above table data for filtering

### 🔎 **Searching Data**
1. Select a table from the sidebar
2. Use the search bar to enter your search term
3. Choose "All Columns" or specific column from dropdown
4. Press Enter or click Search button
5. Use "Clear" to reset search filters

### 🆕 **Creating Tables**
1. Click **"Create New Table"** button
2. Enter table name
3. Add columns with:
   - Column name
   - Data type (VARCHAR, INT, TEXT, etc.)
   - Length (optional)
   - NULL/NOT NULL constraint
   - Default value (optional)
   - Primary key checkbox
4. Click **"Create Table"**

### ✏️ **Editing Data**
- **Edit Cell**: Click on any cell to edit inline
- **Add Row**: Click "Add New Row" and fill in the form
- **Delete Row**: Click the red trash icon next to each row
- **Bulk Operations**: Select multiple rows using checkboxes

### 💾 **Exporting Data**
- **Complete Database**: Use "Download Complete Database" button
- **Single Table**: Use "Download Table" button
- **Selected Rows**: Select rows and click "Download Selected"
- **View SQL**: Click "View Raw SQL" to see generated SQL code

### 🔧 **SQL Editor**
1. Click **"SQL Editor"** button
2. Write your SQL query in the text area
3. Use sample query buttons for templates
4. Press **Ctrl+Enter** or click **"Execute Query"**
5. View results in the formatted table below

### ⚙️ **Table Management**
- **Table Actions**: Click the three-dot menu next to table names
- **Add Column**: Select "Add Column" from table menu
- **Drop Column**: Use dropdown next to column headers
- **Table Structure**: View detailed column information

## ⌨️ Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+A` | Select all rows in current table |
| `Ctrl+Enter` | Execute SQL query (in SQL Editor) |
| `Escape` | Cancel current operation |
| `Enter` | Submit forms or execute search |
| `Tab` | Indent in SQL Editor |

## 🛡️ Security Features

- **Prepared Statements**: All database queries use prepared statements
- **Input Validation**: Server-side validation for all inputs
- **XSS Protection**: HTML escaping for all output
- **Confirmation Dialogs**: Safety prompts for destructive operations
- **Error Handling**: Comprehensive error catching and reporting

## 📁 File Structure

```
advanced-sql-database-manager/
├── index.php              # Main application file
├── README.md              # Documentation
├── LICENSE               # License file
└── screenshots/          # Application screenshots
    ├── dashboard.png
    ├── sql-editor.png
    └── table-creation.png
```

## 🔧 Configuration Options

### Database Connection
Modify these variables in the PHP file:
```php
$host = 'localhost';      // Database host
$dbname = 'your_db';      // Database name
$username = 'user';       // MySQL username
$password = 'pass';       // MySQL password
```

### Pagination
Adjust the number of records per page:
```php
$limit = 20;  // Change to your preferred page size
```

### UI Customization
- Bootstrap classes can be modified for different themes
- CSS variables can be adjusted for color schemes
- Modal sizes and table layouts are customizable

## 🤝 Contributing

We welcome contributions! Here's how to get involved:

1. **Fork the Repository**
   ```bash
   git fork https://github.com/yourusername/advanced-sql-database-manager.git
   ```

2. **Create Feature Branch**
   ```bash
   git checkout -b feature/amazing-feature
   ```

3. **Commit Changes**
   ```bash
   git commit -m 'Add amazing feature'
   ```

4. **Push to Branch**
   ```bash
   git push origin feature/amazing-feature
   ```

5. **Open Pull Request**
   - Provide clear description of changes
   - Include screenshots if UI changes
   - Ensure code follows existing style

### 📋 Contributing Guidelines
- Follow PSR-12 coding standards for PHP
- Use meaningful commit messages
- Test all features before submitting
- Update documentation for new features
- Maintain backward compatibility

## 🐛 Bug Reports & Feature Requests

Found a bug or have an idea? We'd love to hear from you!

### 🐞 **Bug Reports**
Please include:
- PHP version and MySQL version
- Browser and operating system
- Steps to reproduce the issue
- Expected vs actual behavior
- Error messages or screenshots

### 💡 **Feature Requests**
Please describe:
- The feature you'd like to see
- Why it would be useful
- How it should work
- Any examples from other tools

**Create Issues**: [GitHub Issues](https://github.com/yourusername/advanced-sql-database-manager/issues)

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

```
MIT License

Copyright (c) 2024 Advanced SQL Database Manager

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

## 👥 Authors & Acknowledgments

- **Primary Developer**: Your Name (@yourusername)
- **Contributors**: See [Contributors](https://github.com/yourusername/advanced-sql-database-manager/contributors)

### 🙏 **Acknowledgments**
- [Bootstrap](https://getbootstrap.com/) for the responsive UI framework
- [Bootstrap Icons](https://icons.getbootstrap.com/) for the icon set
- [PHP PDO](https://www.php.net/manual/en/book.pdo.php) for secure database connections
- Community contributors and testers

## 📊 Project Stats

![GitHub stars](https://img.shields.io/github/stars/yourusername/advanced-sql-database-manager?style=social)
![GitHub forks](https://img.shields.io/github/forks/yourusername/advanced-sql-database-manager?style=social)
![GitHub issues](https://img.shields.io/github/issues/yourusername/advanced-sql-database-manager)
![GitHub license](https://img.shields.io/github/license/yourusername/advanced-sql-database-manager)

## 🆕 Changelog

### Version 2.0.0 (Latest)
- ✨ Added advanced search functionality
- 🆕 Implemented SQL query editor
- 🛠️ Added table creation and management
- 📱 Improved mobile responsiveness
- 🔒 Enhanced security measures
- 🎨 Updated UI with modern design

### Version 1.0.0
- 🎉 Initial release
- 📋 Basic CRUD operations
- 📊 Table view and pagination
- 💾 Export functionality

## 📞 Support

Need help? Here are your options:

- 📖 **Documentation**: Check this README and inline comments
- 🐛 **Bug Reports**: [GitHub Issues](https://github.com/yourusername/advanced-sql-database-manager/issues)
- 💬 **Discussions**: [GitHub Discussions](https://github.com/yourusername/advanced-sql-database-manager/discussions)
- 📧 **Email**: your.email@example.com

---

⭐ **If you find this project helpful, please consider giving it a star!** ⭐

Made with ❤️ by [Your Name](https://github.com/yourusername)
