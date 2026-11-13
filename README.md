# ✈️ SAM AIRLINE – Airline Management System

A web-based Airline Management System developed to manage airline operations, including passengers, airports, aircraft, flights, bookings, crew, payments and ticketing.

## 📌 Project Overview

SAM AIRLINE is a database-driven web application designed to simplify airline management operations. It provides a graphical interface through which administrators can manage airline information and perform database operations efficiently.

## 🛠️ Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Development Environment:** Visual Studio Code
- **Local Server:** XAMPP
- **Database Management:** phpMyAdmin

## ✨ Main Features

- Dashboard for viewing airline information.
- Passenger management.
- Airport management.
- Aircraft management.
- Flight management.
- Booking management.
- Crew management.
- Crew assignment management.
- Payment management.
- Ticket management.
- Database operations (Create, Read, Update and Delete).

## 🗄️ Database Structure

The project uses MySQL and includes the following main tables:

1. Passengers
2. Flights
3. Airports
4. Planes
5. Bookings
6. Crew
7. Crew Assignments
8. Payments
9. Tickets

## ⚙️ Installation and Setup

### Prerequisites

- XAMPP
- Visual Studio Code
- Web browser

### Steps

1. Install and start Apache and MySQL using XAMPP.
2. Download or clone this repository.
3. Copy the project folder into:

   `C:\xampp\htdocs\`

4. Open phpMyAdmin:

   `http://localhost/phpmyadmin/`

5. Create the project database and import the provided SQL file from the project folder.
6. Configure the database connection settings in the PHP configuration file.
7. Open the application in your browser:

   `http://localhost/sam_airline/`

## 📂 Project Structure

```text
sam_airline/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── config/
├── includes/
├── database/
│   └── database.sql
│
├── index.php
├── passengers.php
├── flights.php
├── bookings.php
└── README.md
```

*Note: The actual folder and file structure may differ depending on the implementation.*

## 🎯 Project Objective

The main objective of SAM AIRLINE is to demonstrate the practical implementation of database management concepts, relational database design, primary keys, foreign keys, SQL queries, and PHP-MySQL connectivity through a real-world airline management application.

## 👨‍💻 Developed For

Database Management Systems (DBMS) Academic Project.

## 📜 License

This project is developed for educational purposes.
