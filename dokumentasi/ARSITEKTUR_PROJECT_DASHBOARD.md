# ARSITEKTUR PROJECT DASHBOARD SE2026

## A. Tree Folder

```
dashboard-se2026/
│
├── index.php                  # Front controller (entry point)
├── .htaccess                  # URL rewriting & security
├── composer.json              # Autoload PSR-4
├── package.json               # Frontend dependencies
├── webpack.mix.js             # Asset bundling (opsional)
│
├── config/
│   ├── config.php             # Database, app settings, roles
│   └── constants.php          # Role & permission constants
│
├── src/                       # Application core (PSR-4: App\)
│   ├── Core/                  # Framework kernel
│   │   ├── App.php            # Application bootstrap
│   │   ├── Controller.php     # Base controller
│   │   ├── Model.php          # Base model
│   │   ├── Database.php       # PDO singleton
│   │   ├── Router.php         # Route parser
│   │   ├── Request.php        # Request abstraction
│   │   ├── Response.php       # JSON/HTML response
│   │   └── Middleware.php     # Middleware pipeline
│   │
│   ├── Controllers/           # Page & API controllers
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ImportController.php
│   │   ├── AssignmentController.php
│   │   ├── MonitoringController.php
│   │   ├── WilayahController.php
│   │   ├── PetugasController.php
│   │   └── ReportController.php
│   │
│   ├── Models/                # ORM/data access
│   │   ├── User.php
│   │   ├── SipwImport.php
│   │   ├── SipwAssignment.php
│   │   ├── Wilayah.php
│   │   ├── MonitoringProgress.php
│   │   ├── MonitoringSummary.php
│   │   └── AlokasiPetugas.php
│   │
│   ├── Services/              # Business logic layer
│   │   ├── AuthService.php
│   │   ├── ImportService.php
│   │   ├── AssignmentService.php
│   │   ├── DashboardService.php
│   │   └── ExcelService.php
│   │
│   ├── Middleware/             # Middleware classes
│   │   ├── AuthMiddleware.php
│   │   ├── RoleMiddleware.php
│   │   └── CsrfMiddleware.php
│   │
│   └── Helpers/               # Utility functions
│       ├── Security.php       # CSRF, XSS, hashing
│       ├── Format.php         # Number/date formatting
│       └── Session.php        # Flash messages
│
├── views/                     # Template files
│   ├── layouts/
│   │   ├── main.php           # Layout utama (sidebar + navbar)
│   │   └── auth.php           # Layout login (tanpa sidebar)
│   │
│   ├── partials/              # Komponen reusable
│   │   ├── header.php         # <head> section
│   │   ├── navbar.php         # Top navigation
│   │   ├── sidebar.php        # Sidebar menu
│   │   ├── footer.php         # Closing tags + scripts
│   │   ├── flash.php          # Flash message render
│   │   ├── breadcrumb.php     # Breadcrumb component
│   │   ├── stat_card.php      # Statistic card component
│   │   └── table.php          # DataTable wrapper
│   │
│   ├── auth/
│   │   └── login.php
│   │
│   ├── dashboard/
│   │   ├── index.php          # Halaman utama dashboard
│   │   └── partials/
│   │       ├── progress_chart.php
│   │       ├── assignment_table.php
│   │       └── wilayah_map.php
│   │
│   ├── import/
│   │   ├── index.php          # Form import SIPW
│   │   ├── history.php        # Riwayat import
│   │   └── partials/
│   │       ├── preview_table.php
│   │       └── result_summary.php
│   │
│   ├── assignment/
│   │   ├── index.php          # Daftar assignment
│   │   ├── create.php         # Form assignment baru
│   │   └── partials/
│   │       ├── petugas_modal.php
│   │       └── filter_form.php
│   │
│   ├── monitoring/
│   │   ├── index.php          # Monitoring realtime
│   │   └── partials/
│   │       ├── progress_table.php
│   │       └── detail_modal.php
│   │
│   ├── wilayah/
│   │   └── list.php
│   │
│   ├── petugas/
│   │   └── list.php
│   │
│   └── errors/
│       ├── 401.php
│       ├── 403.php
│       ├── 404.php
│       └── 500.php
│
├── assets/                    # Frontend assets (public)
│   ├── css/
│   │   ├── app.css            # Compiled CSS
│   │   └── app.min.css
│   ├── js/
│   │   ├── app.js             # Main JS
│   │   ├── dashboard.js       # Dashboard-specific
│   │   ├── import.js          # Import logic
│   │   ├── assignment.js      # Assignment logic
│   │   └── vendor/            # Vendor libs (local fallback)
│   │       ├── bootstrap.bundle.min.js
│   │       ├── jquery.min.js
│   │       ├── datatables.min.js
│   │       └── chart.min.js
│   └── images/
│       ├── logo-bps.png
│       └── favicon.ico
│
├── storage/                   # Runtime storage (non-public)
│   ├── logs/
│   │   └── app-YYYY-MM-DD.log
│   ├── uploads/
│   │   └── sipw/              # Uploaded SIPW Excel files
│   └── cache/
│       └── dashboard/         # Cached query results
│
├── database/                  # SQL patches
│   ├── patch_001_dashboard_base.sql
│   └── migrate.php            # Migration runner
│
├── .env                       # Environment variables
├── .env.example               # Template env
└── README.md
```

---

## B. Tanggung Jawab Setiap Folder

| Folder | Tanggung Jawab | Visibility |
|--------|---------------|-----------|
| `public/` (root) | Hanya `index.php` + `.htaccess` + `assets/` | Public (document root) |
| `config/` | Konfigurasi database, app metadata, role constants | Internal |
| `src/Core/` | Kernel framework: bootstrap, base classes, router | Internal |
| `src/Controllers/` | Menerima request, validasi input, panggil service, render view | Internal |
| `src/Models/` | Representasi tabel database, query methods | Internal |
| `src/Services/` | Business logic murni (ex: parsing Excel, aggregasi dashboard) | Internal |
| `src/Middleware/` | Pre-processing request: auth check, role check, CSRF | Internal |
| `src/Helpers/` | Fungsi statis utility (format angka, CSRF token, flash session) | Internal |
| `views/` | Template PHP dengan HTML minimal, logika presentasi saja | Internal |
| `assets/` | CSS, JS, images — diakses langsung via browser | Public |
| `storage/` | Logs, uploaded files, cache — aman dari akses langsung | Internal |

---

## C. Arsitektur Request Flow

```
                          ┌─────────────┐
                          │   Browser    │
                          └──────┬──────┘
                                 │ GET /dashboard/
                                 ▼
                     ┌───────────────────┐
                     │    index.php      │
                     │  (Front Controller)│
                     └───────┬───────────┘
                             │
                     ┌───────▼───────────┐
                     │   bootstrap.php   │
                     │  - Load config    │
                     │  - Init Database  │
                     │  - Start session  │
                     │  - Autoload PSR-4 │
                     └───────┬───────────┘
                             │
                     ┌───────▼───────────┐
                     │     Router        │
                     │  parse URL:       │
                     │  /import →        │
                     │  ImportController  │
                     └───────┬───────────┘
                             │
                     ┌───────▼───────────┐
                     │  Middleware Chain  │
                     │  ├─ CsrfMiddleware │
                     │  ├─ AuthMiddleware │
                     │  └─ RoleMiddleware │
                     └───────┬───────────┘
                             │ pass
                     ┌───────▼───────────┐
                     │  Controller::action│
                     │  ├─ Validate input │
                     │  ├─ Call Service   │
                     │  ├─ Call Model     │
                     │  └─ Render View    │
                     └───────┬───────────┘
                             │
                     ┌───────▼───────────┐
                     │  View (layout +   │
                     │   content)        │
                     └───────┬───────────┘
                             │
                     ┌───────▼───────────┐
                     │    Response       │
                     │  HTML / JSON      │
                     └───────────────────┘
```

**Detail alur:**

1. **index.php** — Satu-satunya entry point. Meng-include `bootstrap.php`.
2. **bootstrap** — Load config, init PDO via `Database::connect()`, start session, register autoloader.
3. **Router** — Parse URL pattern `?page=x&sub=y` (sama seperti existing app) atau path `/{controller}/{action}`. Cocokkan ke Controller + method. Jalankan middleware chain.
4. **Middleware** — Auth check, role check, CSRF validation. Jika gagal → redirect/403.
5. **Controller** — Ambil input dari `Request`, panggil `Service` untuk logika bisnis, panggil `Model` jika perlu query, render `View` dengan data.
6. **Service** — Logika bisnis pure (tidak tahu soal HTTP/request). Ex: parsing Excel, aggregasi data, validasi assignment.
7. **Model** — Query database via PDO. Setiap model mewakili satu tabel.
8. **View** — Include layout, render content, include partials.

---

## D. Base Controller Strategy

```php
namespace App\Core;

abstract class Controller
{
    protected Request $request;
    protected Database $db;
    protected array $data = [];  // Data yg dikirim ke view

    public function __construct()
    {
        $this->request = new Request();
        $this->db = Database::instance();
        $this->data['base_url'] = BASE_URL;
        $this->data['current_user'] = $this->getCurrentUser();
        $this->data['flash'] = Session::flash();
    }

    // Render view dengan layout
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $this->data = array_merge($this->data, $data);
        $this->data['content'] = $this->getViewContent($view, $this->data);

        extract($this->data);
        require VIEW_PATH . "/layouts/{$layout}.php";
    }

    // Render partial (tanpa layout) — untuk AJAX
    protected function renderPartial(string $view, array $data = []): void
    {
        extract(array_merge($this->data, $data));
        require VIEW_PATH . "/{$view}.php";
    }

    // JSON response — untuk API
    protected function json(array $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    // Redirect
    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }

    // Validasi input sederhana
    protected function validate(array $rules): array
    {
        // return array of errors (empty = valid)
    }

    private function getViewContent(string $view, array $data): string
    {
        ob_start();
        extract($data);
        require VIEW_PATH . "/{$view}.php";
        return ob_get_clean();
    }

    // Helper auth
    protected function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }
}
```

**Controller khusus:**

```php
namespace App\Controllers;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function index(): void
    {
        $service = new \App\Services\DashboardService();
        $summary = $service->getSummary();

        $this->render('dashboard/index', [
            'title'   => 'Dashboard SE2026',
            'summary' => $summary,
            'js'      => ['dashboard'],
        ]);
    }
}
```

---

## E. Config Strategy

**File: `config/config.php`**
```php
// Load .env
require_once __DIR__ . '/../src/Helpers/Env.php';

// Database
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'bps_jember_se2026'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// App
define('BASE_URL', env('BASE_URL', '/dashboard-se2026/'));
define('APP_NAME', 'Dashboard SE2026 JEMBER');
define('APP_TIMEZONE', 'Asia/Jakarta');

// Role constants (sinkron dengan DB)
defined('ROLE_ADMIN')      || define('ROLE_ADMIN',      'admin');
defined('ROLE_OPERATOR')   || define('ROLE_OPERATOR',   'operator');
defined('ROLE_PEGAWAI')    || define('ROLE_PEGAWAI',    'pegawai');
defined('ROLE_MITRA')      || define('ROLE_MITRA',      'mitra');
defined('ROLE_PML')        || define('ROLE_PML',        'pml');
defined('ROLE_PCL')        || define('ROLE_PCL',        'pcl');
defined('ROLE_TASK_FORCE') || define('ROLE_TASK_FORCE', 'task_force');

// Paths
defined('VIEW_PATH')  || define('VIEW_PATH', __DIR__ . '/../views');
defined('STORAGE_PATH') || define('STORAGE_PATH', __DIR__ . '/../storage');
defined('UPLOAD_PATH')   || define('UPLOAD_PATH', STORAGE_PATH . '/uploads');

// Session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
```

**File: `.env`**
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=bps_jember_se2026
DB_USER=root
DB_PASS=
BASE_URL=/dashboard-se2026/
```

**Strategi:** Config di-load di `bootstrap.php` SEBELUM apapun. Constants didefinisikan dengan `defined()` agar tidak error jika file di-include ganda. File `.env` dibaca oleh helper `Env::load()`.

---

## F. Auth Strategy

### F.1 Authentication Flow

```
┌──────────────┐     ┌──────────────────┐     ┌───────────────┐
│  Login Page  │────>│  AuthController  │────>│  AuthService  │
│  /login      │     │  ::login()       │     │  authenticate │
└──────────────┘     └──────────────────┘     └───────┬───────┘
                                                       │
                                          ┌────────────┴────────────┐
                                          │  SELECT * FROM users    │
                                          │  WHERE username = ?     │
                                          │  AND status_akun='active'│
                                          └────────────┬────────────┘
                                                       │
                                          ┌────────────▼────────────┐
                                          │  verify password_hash   │
                                          └────────────┬────────────┘
                                                       │
                                          ┌────────────▼────────────┐
                                          │  Session::set('user',   │
                                          │    user_data)           │
                                          │  Session::regenerate()  │
                                          │  Redirect to dashboard  │
                                          └─────────────────────────┘
```

### F.2 Session Payload

```php
$_SESSION['user'] = [
    'id'        => 1,
    'username'  => 'admin',
    'role'      => 'admin',
    'nama'      => 'Admin BPS',
    'login_at'  => '2026-05-27 08:00:00',
];

$_SESSION['csrf_token'] = 'random-token-value';
$_SESSION['flash'] = [];  // Flash messages
```

### F.3 Middleware Chain

```php
// Di bootstrap.php setelah router match

$middleware = [
    new \App\Middleware\CsrfMiddleware(),  // Hanya POST
    new \App\Middleware\AuthMiddleware(),   // Cek login
    new \App\Middleware\RoleMiddleware($required_roles),
];

foreach ($middleware as $mw) {
    if (!$mw->handle($request)) {
        // Redirect ke login atau 403
        exit;
    }
}
```

### F.4 Route-Protected Access

```php
// Router config
$router->get('/login', 'AuthController::loginForm');
$router->post('/login', 'AuthController::login');
$router->get('/logout', 'AuthController::logout');

$router->group(['middleware' => ['auth']], function ($router) {
    $router->get('/', 'DashboardController::index');
    $router->get('/import', 'ImportController::index');
    $router->post('/import/upload', 'ImportController::upload');
});

$router->group(['middleware' => ['auth', 'role:admin']], function ($router) {
    $router->get('/assignment/create', 'AssignmentController::create');
    $router->post('/assignment/store', 'AssignmentController::store');
});
```

---

## G. Naming Convention

| Elemen | Convention | Contoh |
|--------|-----------|--------|
| **Namespace** | `App\{Folder}` | `App\Controllers`, `App\Models` |
| **Class** | PascalCase | `SipwImport`, `DashboardController` |
| **Method** | camelCase | `getSummary()`, `uploadImport()` |
| **Property** | camelCase | `$this->wilayahId` |
| **Variable** | snake_case | `$total_sls`, `$data_kecamatan` |
| **View file** | snake_case | `dashboard/index.php`, `import/history.php` |
| **SQL table** | snake_case | `sipw_import`, `dash_monitoring_summary` |
| **SQL column** | snake_case | `kdkec`, `pencacah_id` |
| **URL route** | kebab-case | `/import/history`, `/assignment/create` |
| **JS file** | camelCase | `dashboard.js`, `importHandler.js` |
| **CSS class** | BEM | `.stat-card__title`, `.btn--primary` |

---

## H. Reusable Layout System

**Layout `views/layouts/main.php`:**
```php
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require VIEW_PATH . '/partials/header.php'; ?>
</head>
<body>
    <div class="d-flex">
        <?php require VIEW_PATH . '/partials/sidebar.php'; ?>
        <div class="main-content flex-grow-1">
            <?php require VIEW_PATH . '/partials/navbar.php'; ?>
            <?php require VIEW_PATH . '/partials/breadcrumb.php'; ?>
            <?php require VIEW_PATH . '/partials/flash.php'; ?>
            <main class="p-4">
                <?= $content ?? '' ?>
            </main>
            <?php require VIEW_PATH . '/partials/footer.php'; ?>
        </div>
    </div>
</body>
</html>
```

**Cara pakai di controller:**
```php
$this->render('dashboard/index', ['title' => 'Home', 'data' => $data]);
// → otomatis inject $content dengan isi dashboard/index.php
// → render dalam layout main.php
```

**Render tanpa sidebar (layout auth):**
```php
$this->render('auth/login', [], 'auth');
// → pake views/layouts/auth.php
```

---

## I. Perbandingan dengan Existing App

| Aspek | SE2026 Existing (`se2026-jember/`) | Dashboard (`dashboard-se2026/`) |
|-------|-----------------------------------|--------------------------------|
| **Entry point** | `index.php` | `index.php` |
| **Routing** | Manual switch-case di index.php | Router class |
| **Autoload** | Manual require_once | Composer PSR-4 |
| **Controller** | Flat di `src/controllers/` | Namespaced `App\Controllers\` |
| **Model** | Flat di `src/models/` | Namespaced `App\Models\` |
| **View** | Include langsung | Layout system + render() |
| **Middleware** | Inline `require_role()` | Middleware chain |
| **Asset** | Tailwind CDN | Bootstrap 5 lokal + bundling |
| **DB** | PDO global `$pdo` | Database singleton class |
| **Session** | Native | Helper class |

---

## J. Catatan Penting

1. **Tidak ada dependency framework** — murni PHP native, ringan, cocok untuk dashboard internal
2. **Composer autoload** — semua class di `src/` auto-loaded via PSR-4
3. **Zero impact ke existing** — folder `dashboard-se2026/` terpisah total dari `se2026-jember/`
4. **Database user terpisah** — rekomendasi buat user MySQL khusus dashboard dengan privilege SELECT + INSERT/UPDATE pada tabel `sipw_*` dan `dash_*` saja
5. **Asset management** — Bootstrap 5 CSS/JS via CDN atau file lokal, DataTables dan Chart.js via CDN
