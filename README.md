# Clevis Mark Portfolio

Professional portfolio website for Clevis Mark built with HTML, CSS, JavaScript, PHP, and MySQL.

## Files

- `index.php`: Main portfolio page
- `submit_request.php`: Handles client service requests and generates request codes
- `receipt.php`: Printable receipt and feedback lookup page
- `receipt_pdf.php`: Downloads the receipt as a PDF
- `admin-login.php`: Private admin login page
- `includes/config.php`: Database connection settings
- `database/portfolio_one.sql`: Creates the `portfolio_one` database and request/feedback tables

## Setup

1. Install PHP and MySQL or use XAMPP/WAMP.
2. Create or select your `portfolio_one` database in the hosting panel, then import `database/portfolio_one.sql` into that selected database.
3. Update `includes/config.php` with your hosting database host, database name, username, and password.
4. Add the real profile image as `assets/images/profile-photo.png` to replace the fallback artwork.
5. Run the project from a PHP-enabled server.
6. Use `admin-login` for the private dashboard login page.
7. After submitting a request, give the client the request code shown on the success message so they can return to the Feedback section and view replies.
8. To seed or change the admin account without storing the password in code, set `ADMIN_EMAIL` and `ADMIN_PASSWORD` as environment variables on your host.
9. For email delivery of the receipt PDF, set `MAIL_FROM_EMAIL` and `MAIL_FROM_NAME` on your host if your mail setup supports PHP `mail()`.

## Hosting tips

- On many free hosts, the MySQL host is `localhost`, but some providers use a different internal hostname. Use the exact value from the hosting control panel.
- Import `database/portfolio_one.sql` into the database you created on the host. The file creates the tables used by the site.
- If the login page still shows a connection error after import, double-check that the database user has permission on that database.
- If your host uses a custom port, set `DB_PORT` in the hosting environment or change the value in `includes/config.php`.
