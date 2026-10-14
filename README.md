# Community Book Exchange

Community--Bookexchange-final is a community book-sharing application.

![PHP](https://img.shields.io/badge/PHP-2563EB?style=flat-square) ![MySQL](https://img.shields.io/badge/MySQL-0F172A?style=flat-square)

## Overview

The data model covers `admins`, `books`, `messages`, `reports`, `requests`, `reviews`, `users`.

## Application areas

| Area | Pages in the repository |
| --- | --- |
| Admin | [`admin/add_user.php`](admin/add_user.php), [`admin/admin_home.php`](admin/admin_home.php), [`admin/admin_login.php`](admin/admin_login.php), [`admin/admin_navbar.php`](admin/admin_navbar.php), [`admin/edit_user.php`](admin/edit_user.php) |
| Borrower | [`borrower/borrower_dashboard.php`](borrower/borrower_dashboard.php), [`borrower/messages.php`](borrower/messages.php), [`borrower/my_requests.php`](borrower/my_requests.php), [`borrower/request_book.php`](borrower/request_book.php), [`borrower/reviews.php`](borrower/reviews.php) |
| Root Pages | [`login.php`](login.php), [`register.php`](register.php) |
| Owner | [`owner/add_book.php`](owner/add_book.php), [`owner/edit_book.php`](owner/edit_book.php), [`owner/messages.php`](owner/messages.php), [`owner/owner_dashboard.php`](owner/owner_dashboard.php), [`owner/owner_requests.php`](owner/owner_requests.php) |

## Getting started

### 1. Get the project

```bash
git clone https://github.com/nasirabbas827/Community--Bookexchange-final.git
cd Community--Bookexchange-final
```

### 2. Configure PHP and the database

1. Use a local PHP server with MySQL or MariaDB, such as XAMPP.
2. Import [`Database/bookexchange_db.sql`](Database/bookexchange_db.sql) into your local database server.
3. Set your local database connection details in [`admin/config.php`](admin/config.php), [`borrower/config.php`](borrower/config.php), [`config.php`](config.php), [`owner/config.php`](owner/config.php).
4. Keep the existing asset and upload directory paths intact.

### 3. Start the application

With the database running, start a local development server from the repository root:

```bash
php -S 127.0.0.1:8000
```

Open http://127.0.0.1:8000/index.php. If using Apache/XAMPP instead, place the project in its document root and open the matching localhost folder.

## Repository guide

| Path | Purpose |
| --- | --- |
| [`Database`](Database) | Database scripts |
| [`config.php`](config.php) | Application/database configuration |
| [`css`](css) | Stylesheets |
| [`images`](images) | Image assets |
| [`index.php`](index.php) | Web entry page |

## Development notes

These setup instructions are based on the repository source. The application has not been executed as part of this documentation update; verify dependencies and configuration in your local environment.
