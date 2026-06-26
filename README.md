# SIMDEX Student Index Management

The interface brand is **SIMDEX**, using the supplied Student Index Management artwork across authentication, navigation, and student forms.

This project is a Laravel-based student data management application with a React.js-powered main dashboard. It supports admin login, registration, password reset, student CRUD, searching, optimized sorting, importing, exporting, analytics, and responsive light/night themes.

## Laravel Support

The original assignment concepts are implemented using PHP and Laravel conventions:

- Student data is managed through Laravel controllers, models, routes, Blade views, and Eloquent.
- The main student dashboard is rendered with React.js through Vite while still using Laravel routes for server-side actions.
- Admin access is handled with Laravel session authentication.
- Arrays are used when student records are converted with `Student::all()->toArray()` for searching and sorting.
- Functions are used to separate validation, search, optimized sorting, CSV export/import, XLS/XLSX upload import, and time complexity reporting.
- CSV export is handled with Laravel's `Storage` facade, and uploaded CSV/XLS/XLSX files are read from Laravel's uploaded file object.
- Error handling is implemented with `try`, `catch`, and exception classes.

## Main Features

- Initial admin login access.
- Separate admin registration page.
- Three-step registration and password reset with a 6-digit SMTP email confirmation code.
- Input student data.
- Edit existing student data.
- Delete student data.
- Display all student data.
- Auto-generate student IDs when adding data manually or uploading rows without IDs.
- Save filtered student data to a CSV file.
- Upload new student data from CSV files.
- Upload new student data from XLS files.
- Upload new student data from XLSX files.
- Choose table display size: 5, 10, 20, 50, or 100 rows.
- Display top-right popup alerts after login, adding, editing, deleting, or importing student data.
- Search student data by name or exact student ID.
- Sort student data efficiently by ID, name, or GPA.
- Validate input using Regex.
- Display time complexity estimates for key features.
- Dominant-blue interface inspired by the supplied geometric login reference.
- Light and night modes saved in the browser.
- Collapsible desktop navigation and slide-out mobile navigation.
- Simplified search and side-by-side import/export tools.
- Responsive phone and iPad layouts, including mobile student cards and tablet-friendly controls.
- Simplified collapsible sidebar with student navigation, major counts, a compact theme toggle, and logout.
- Matching blue geometric add-student and edit-student interfaces.
- Polished SIMDEX form headers, progress bars, and grouped input cards.
- Fixed 10-record pages with Previous, numbered-page, and Next navigation.
- Dashboard-blue pagination styling with a white active-page indicator.
- Transparent SIMDEX logo treatment without white container boxes.
- Updated light palette: background `#F8FAFF`, blue `#2563EB`, navy `#0F172A`, cyan `#06B6D4`, pink `#F43F5E`, and purple `#7C3AED`.
- Import and export controls embedded directly in the Students records panel.
- Critical theme colors and a local system font stack prevent the initial white/text-style flash.
- Compacted production-ready CSS source with one rule per line.
- Additional student analysis for median/highest GPA, success rate, students needing attention, GPA distribution, and major distribution.
- Login dashboard background with a branded academic data visual area.
- React.js main dashboard with SIMDEX branding, navigation sidebar, analytics panels, upload tools, student-table sorting, filtered downloads, fixed 10-row pagination, and complexity cards.

```bash
php artisan db:seed
```

The authentication flow is separated into three pages:

- `/login` for admin login.
- `/register` verifies the new email with an SMTP code, then asks for the password and matching password confirmation.
- `/password-reset` sends a code to an existing account email before allowing a new password and matching password confirmation.

Login form placeholders use neutral helper text instead of directly showing the seeded email or password.

## SMTP Email Codes

Configure these values in `.env` before using registration or password reset:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_TIMEOUT=10
MAIL_USERNAME="your-email@gmail.com"
MAIL_PASSWORD="your-smtp-app-password"
MAIL_FROM_ADDRESS="your-email@gmail.com"
MAIL_FROM_NAME="SIMDEX"
```

For Gmail, `MAIL_PASSWORD` must be a Google App Password rather than the normal account password. After changing `.env`, clear the cached configuration:

```bash
php artisan config:clear
```

Confirmation codes contain 6 digits, expire after 10 minutes, and allow at most five incorrect attempts. These limits can be changed with `AUTH_ACCESS_CODE_TIMEOUT` and `AUTH_ACCESS_CODE_MAX_ATTEMPTS`.

## Interface Design

The interface has been modernized while keeping the academic dashboard purpose clear:

- React.js is used for the main student dashboard.
- Vite bundles the React entry from `resources/js/app.js`.
- Multi-color gradient authentication background.
- Poppins as the main interface font.
- Inter for numeric/statistical values.
- Rounded dashboard cards, modern buttons, and cleaner input focus states.
- Colored statistics cards for total students, average GPA, majors, and upload format.
- Glass-style navigation and improved responsive spacing.

## Assignment Requirement Mapping

| Requirement | Laravel Implementation |
| --- | --- |
| Input, edit, delete, and display student data | `StudentController`, Blade views, and resource routes |
| React.js main dashboard | `resources/js/components/StudentDashboard.jsx` mounted from `students/index.blade.php` |
| Sidebar and analysis UI | `DashboardSidebar.jsx` and `StudentAnalysis.jsx` |
| Initial admin login, registration, and password reset | `AuthController`, auth views, auth routes, and `DatabaseSeeder` |
| Arrays | Student records are converted to arrays before search and sort operations |
| Functions | Controller helper methods separate CRUD support logic, validation, search, sort, and complexity data |
| File I/O | `export()` saves the current filtered/sorted data and `upload()` reads CSV/XLS/XLSX data |
| Classes and objects | `Person`, `StudentRecord`, and `Student` classes |
| Encapsulation | Private properties with getter methods in `Person` and `StudentRecord` |
| Inheritance | `StudentRecord extends Person` |
| Polymorphism | `getRole()` is overridden in `StudentRecord` |
| Linear Search | `linearSearchByStudentId()` |
| Sequential Search | `sequentialSearchByName()` |
| Binary Search | `binarySearchByStudentId()` |
| Efficient sorting | PHP `usort()` comparators provide average O(n log n) sorting by ID, name, or GPA |
| Regex validation | Student ID and email patterns in `validatedData()` and `validateRecord()` |
| Try-catch and exceptions | CRUD, export, and upload actions catch errors and display messages |
| Time complexity estimation | `complexities()` returns estimates displayed on the index page |
| Best practices | Modular methods, clear naming, model separation, validation, and focused comments |

## Data Fields

Each student record contains:

- Student ID
- Name
- Email
- GPA
- Major

## Validation Rules

- Student ID is generated automatically for new manual entries and uploaded rows that do not include an ID.
- Existing or uploaded Student IDs must start with `S` followed by 3 to 6 digits, for example `S1001`.
- Email must match a valid email format.
- GPA must be numeric and between `0` and `4`.
- Name is required.
- Major is optional.

## Search Algorithms

- Linear Search by student ID: checks records one by one until the matching ID is found.
- Sequential Search by name: checks each record and returns names containing the search keyword.
- Binary Search by student ID: prepares records with O(n log n) sorting, then repeatedly divides the search range in half.

## Sorting Algorithms

The dashboard uses PHP's optimized `usort()` implementation:

- Student ID in ascending order.
- Student name from A to Z.
- GPA in descending order.

## File I/O

Filtered student data can be saved and downloaded as:

```txt
storage/app/private/students.csv
```

The Download CSV button stores the current filtered and sorted student list in Laravel storage and downloads `students.csv` with a standard header row.

CSV, XLS, and XLSX files can also be uploaded from the student list page to insert new student data. Existing records are skipped when the uploaded student ID or email already exists. If an uploaded row does not include a student ID, the application generates the next available ID automatically.

## CSV/XLS/XLSX Upload Format

Uploaded CSV, XLS, and XLSX files may use this header row:

```txt
student_id,name,email,gpa,major
```

The `student_id` column is optional when the file has headers:

```txt
name,email,gpa,major
```

The header row is optional if the columns are already in one of these orders:

```txt
Student ID, Name, Email, GPA, Major
Name, Email, GPA, Major
```

Example:

```csv
student_id,name,email,gpa,major
S1001,Alya Putri,alya@example.com,3.75,Information Systems
S1002,Budi Santoso,budi@example.com,3.50,Computer Science
```

CSV files may use comma, semicolon, tab, or pipe separators. XLS uploads support simple Excel files, Excel HTML-table exports, and tab/comma-separated files saved with an `.xls` extension.

## Time Complexity

| Feature | Estimate |
| --- | --- |
| Create/update/delete | O(1) indexed record access |
| Name or ID search | O(n), or O(log n) after binary-search preparation |
| Record sorting | O(n log n) average |
| File export/upload | O(n) |

## Important Files

```txt
app/Http/Controllers/StudentController.php
app/Http/Controllers/AuthController.php
app/Models/Person.php
app/Models/Student.php
app/Models/StudentRecord.php
resources/js/app.js
resources/js/components/StudentDashboard.jsx
resources/views/auth/login.blade.php
resources/views/auth/register.blade.php
resources/views/auth/password-reset.blade.php
resources/views/layouts/app.blade.php
resources/views/students/index.blade.php
resources/views/students/create.blade.php
resources/views/students/edit.blade.php
resources/views/students/_form.blade.php
routes/web.php
storage/app/private/students.csv
```

## Running the Project

```bash
composer install
npm install
npm run build
php artisan migrate
php artisan db:seed
php artisan serve
```

Then open the local Laravel URL shown in the terminal.
