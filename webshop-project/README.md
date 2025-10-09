# Webshop Project

## Overview
This project is a simple webshop application built using HTML, CSS, and PHP. It allows users to browse products, manage their accounts, and place orders.

## Project Structure
```
webshop-project
├── src
│   ├── index.html          # Main entry point for the webshop
│   ├── css
│   │   └── style.css       # Styles for the webshop
│   ├── php
│   │   ├── db.php          # Database connection and configuration
│   │   ├── products.php     # Product management functionalities
│   │   ├── customers.php     # Customer account management
│   │   ├── orders.php        # Order processing functionalities
│   │   └── order_items.php    # Management of items within orders
│   └── templates
│       ├── header.php       # Header section of the website
│       └── footer.php       # Footer section of the website
├── db_build.txt            # SQL commands for database setup
└── README.md                # Documentation for the project
```

## Features
- **Product Management**: View, add, and remove products.
- **Customer Management**: User registration, login, and profile updates.
- **Order Processing**: Create and manage customer orders.
- **Responsive Design**: The webshop is designed to be responsive and user-friendly.

## Setup Instructions
1. Clone the repository to your local machine.
2. Navigate to the `webshop-project` directory.
3. Import the SQL commands from `db_build.txt` into your MySQL database to create the necessary tables.
4. Update the database connection settings in `src/php/db.php` with your database credentials.
5. Open `src/index.html` in your web browser to view the webshop.

## Usage Guidelines
- Ensure your web server is running and configured to serve PHP files.
- Access the webshop through your web browser to explore its features.
- Follow the instructions in the respective PHP files for managing products, customers, and orders.

## License
This project is open-source and available for modification and distribution.