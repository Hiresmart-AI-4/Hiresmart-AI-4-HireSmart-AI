<?php

use App\Models\Job;
use App\Models\Resume;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

$withoutWebState = [
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
];

$renderApp = function (string $section = 'dashboard') {
    $databaseStatus = 'Connected';
    $databaseNote = 'Live PostgreSQL metrics';

    try {
        $totalResumes = Resume::count();
        $analyzedResumes = Resume::whereNotNull('ats_score')->count();
        $averageScore = round((float) Resume::whereNotNull('ats_score')->avg('ats_score'), 1);
        $bestScore = Resume::whereNotNull('ats_score')->max('ats_score') ?? 0;
        $activeJobs = Job::where('is_active', true)->count();
        $latestResumes = Resume::orderByDesc('created_at')->take(6)->get()->map(fn (Resume $resume) => [
            'id' => $resume->resume_id,
            'title' => $resume->title,
            'file' => $resume->original_filename,
            'score' => $resume->ats_score,
            'date' => optional($resume->created_at)->format('M d, Y'),
        ])->values();
        $latestJobs = Job::where('is_active', true)->orderByDesc('created_at')->take(5)->get()->map(fn (Job $job) => [
            'id' => $job->job_id,
            'title' => $job->title,
            'company' => $job->company,
            'location' => $job->location,
            'skills' => array_values(array_slice($job->required_skills ?? [], 0, 4)),
        ])->values();
        $scoreTrend = Resume::whereNotNull('ats_score')->orderBy('created_at')->take(8)->get()->map(fn (Resume $resume) => [
            'label' => optional($resume->created_at)->format('m/d'),
            'title' => $resume->title,
            'score' => (int) $resume->ats_score,
        ])->values();
    } catch (Throwable $e) {
        $databaseStatus = 'Offline';
        $databaseNote = 'Check database connection settings (DB_* or DB_URL)';
        $totalResumes = 0;
        $analyzedResumes = 0;
        $averageScore = 0;
        $bestScore = 0;
        $activeJobs = 0;
        $latestResumes = collect();
        $latestJobs = collect();
        $scoreTrend = collect();
    }

    $docsPath = storage_path('app/deploy-docs/API_DOCUMENTATION.md');
    $docs = file_exists($docsPath) ? file_get_contents($docsPath) : 'API documentation file not found.';
    $boot = json_encode([
        'activeSection' => $section,
        'baseUrl' => url('/api'),
        'collectionUrl' => url('/api-docs/postman-collection'),
        'metrics' => [
            'totalResumes' => $totalResumes,
            'analyzedResumes' => $analyzedResumes,
            'averageScore' => $averageScore,
            'bestScore' => $bestScore,
            'activeJobs' => $activeJobs,
            'databaseStatus' => $databaseStatus,
            'databaseNote' => $databaseNote,
        ],
        'latestResumes' => $latestResumes,
        'latestJobs' => $latestJobs,
        'scoreTrend' => $scoreTrend,
        'docs' => $docs,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

    return response(<<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>HireSmart AI Workspace</title>
            <link rel="stylesheet" href="/hiresmart-ui.css">
        </head>
        <body>
            <div class="app-shell">
                <aside class="sidebar">
                    <a class="brand" href="/dashboard-preview" aria-label="HireSmart AI dashboard">
                        <span class="brand-mark">HS</span>
                        <span>
                            <strong>HireSmart AI</strong>
                            <small>Resume intelligence</small>
                        </span>
                    </a>
                    <nav class="nav-block" aria-label="Main navigation">
                        <span class="nav-label">Workspace</span>
                        <button class="nav-item active" data-section="dashboard">Dashboard</button>
                        <button class="nav-item" data-section="auth">Site 1 Auth</button>
                        <button class="nav-item" data-section="profile">Site 2 Profile</button>
                        <button class="nav-item" data-section="docs">Documentation</button>
                    </nav>
                    <nav class="nav-block" aria-label="Resources">
                        <span class="nav-label">Quick Links</span>
                        <a class="nav-link" href="/api/test">API Test</a>
                        <a class="nav-link" href="/api/gateway/routes">Gateway Map</a>
                        <a class="nav-link" href="/api-docs/postman-collection">Postman Collection</a>
                    </nav>
                </aside>

                <main class="workspace">
                    <header class="topbar">
                        <div>
                            <p class="eyebrow">System Integration and Architecture</p>
                            <h1>Professional API Workspace</h1>
                            <p class="top-copy">Operate the HireSmart AI API, test gateway routes, and review Postman-ready documentation in one place.</p>
                        </div>
                        <div class="top-actions">
                            <span class="status-pill" id="databaseStatus">DB {$databaseStatus}</span>
                            <button class="icon-button" id="refreshDashboard" title="Refresh dashboard" aria-label="Refresh dashboard">Refresh</button>
                        </div>
                    </header>

                    <section class="notice" id="tokenNotice">
                        <div>
                            <strong>Bearer token</strong>
                            <span id="tokenState">No token saved yet. Register or login in Site 1 Auth.</span>
                        </div>
                        <button class="ghost-button" id="clearToken">Clear Token</button>
                    </section>

                    <section class="view active" id="view-dashboard">
                        <div class="metric-grid" id="metricGrid"></div>
                        <div class="content-grid">
                            <section class="panel wide">
                                <div class="panel-head">
                                    <div>
                                        <h2>Latest Resumes</h2>
                                        <p>Recent uploads and ATS scoring progress.</p>
                                    </div>
                                    <button class="ghost-button" data-api-action="dashboard">Load API Data</button>
                                </div>
                                <div class="table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Resume</th>
                                                <th>File</th>
                                                <th>ATS Score</th>
                                                <th>Uploaded</th>
                                            </tr>
                                        </thead>
                                        <tbody id="resumeRows"></tbody>
                                    </table>
                                </div>
                            </section>
                            <aside class="panel">
                                <div class="panel-head">
                                    <div>
                                        <h2>Score Trend</h2>
                                        <p>Latest analyzed resume scores.</p>
                                    </div>
                                </div>
                                <div class="trend" id="scoreTrend"></div>
                            </aside>
                        </div>
                        <div class="content-grid">
                            <section class="panel">
                                <div class="panel-head">
                                    <div>
                                        <h2>Active Jobs</h2>
                                        <p>Open postings ready for matching.</p>
                                    </div>
                                </div>
                                <div id="jobList"></div>
                            </section>
                            <section class="panel">
                                <div class="panel-head">
                                    <div>
                                        <h2>Service Architecture</h2>
                                        <p>Two backend services routed through one gateway.</p>
                                    </div>
                                </div>
                                <div class="service-list">
                                    <div class="service-row">
                                        <span class="dot blue"></span>
                                        <div><strong>ddsbe</strong><small>User Service 1: register and login</small></div>
                                    </div>
                                    <div class="service-row">
                                        <span class="dot green"></span>
                                        <div><strong>ddsbe2</strong><small>User Service 2: profile, update, logout</small></div>
                                    </div>
                                    <div class="service-row">
                                        <span class="dot amber"></span>
                                        <div><strong>ddsgateway</strong><small>Gateway routes: /api/site1 and /api/site2</small></div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </section>

                    <section class="view" id="view-auth">
                        <div class="content-grid">
                            <section class="panel">
                                <div class="panel-head">
                                    <div>
                                        <h2>Site 1 Register</h2>
                                        <p>Create a user through User Service 1.</p>
                                    </div>
                                </div>
                                <form id="registerForm" class="form-grid">
                                    <label>Name<input name="name" value="Test User" required></label>
                                    <label>Email<input name="email" type="email" value="test@example.com" required></label>
                                    <label>Password<input name="password" type="password" value="password123" required></label>
                                    <label>Confirm Password<input name="password_confirmation" type="password" value="password123" required></label>
                                    <button class="primary-button" type="submit">Register and Save Token</button>
                                </form>
                            </section>
                            <section class="panel">
                                <div class="panel-head">
                                    <div>
                                        <h2>Site 1 Login</h2>
                                        <p>Login and store the Bearer token for protected routes.</p>
                                    </div>
                                </div>
                                <form id="loginForm" class="form-grid">
                                    <label>Email<input name="email" type="email" value="test@example.com" required></label>
                                    <label>Password<input name="password" type="password" value="password123" required></label>
                                    <button class="primary-button" type="submit">Login and Save Token</button>
                                </form>
                            </section>
                        </div>
                    </section>

                    <section class="view" id="view-profile">
                        <div class="content-grid">
                            <section class="panel">
                                <div class="panel-head">
                                    <div>
                                        <h2>Site 2 Profile</h2>
                                        <p>Use the saved Bearer token to call protected User Service 2 routes.</p>
                                    </div>
                                    <button class="ghost-button" data-api-action="profile">Load Profile</button>
                                </div>
                                <form id="profileForm" class="form-grid">
                                    <label>Name<input name="name" placeholder="Updated name"></label>
                                    <label>Phone<input name="phone" placeholder="09123456789"></label>
                                    <label class="span-2">Bio<textarea name="bio" placeholder="Short profile bio"></textarea></label>
                                    <button class="primary-button" type="submit">Update Profile</button>
                                    <button class="danger-button" id="logoutButton" type="button">Logout Token</button>
                                </form>
                            </section>
                            <section class="panel">
                                <div class="panel-head">
                                    <div>
                                        <h2>Current Token</h2>
                                        <p>Copy this into Postman as Authorization: Bearer token.</p>
                                    </div>
                                    <button class="ghost-button" id="copyToken">Copy</button>
                                </div>
                                <pre class="code-output" id="tokenOutput">No token saved.</pre>
                            </section>
                        </div>
                    </section>

                    <section class="view" id="view-docs">
                        <div class="panel">
                            <div class="panel-head">
                                <div>
                                    <h2>Postman Documentation</h2>
                                    <p>Professional SIA documentation structure with request and response examples.</p>
                                </div>
                                <a class="primary-link" href="/api-docs/postman-collection">Download Collection</a>
                            </div>
                            <pre class="docs-block" id="docsBlock"></pre>
                        </div>
                    </section>

                    <section class="panel output-panel">
                        <div class="panel-head">
                            <div>
                                <h2>Response Output</h2>
                                <p>Latest API result appears here.</p>
                            </div>
                            <button class="ghost-button" id="clearOutput">Clear</button>
                        </div>
                        <pre class="code-output" id="apiOutput">Ready.</pre>
                    </section>
                </main>
            </div>
            <script>
                window.HIRE_SMART_BOOT = {$boot};
            </script>
            <script src="/hiresmart-ui.js"></script>
        </body>
        </html>
    HTML);
};

Route::get('/', fn () => response()->file(public_path('frontend/login.html')))->withoutMiddleware($withoutWebState);
Route::get('/workspace', fn () => $renderApp('dashboard'))->withoutMiddleware($withoutWebState);
Route::get('/dashboard-preview', fn () => $renderApp('dashboard'))->withoutMiddleware($withoutWebState);
Route::get('/api-docs', fn () => $renderApp('docs'))->withoutMiddleware($withoutWebState);

Route::get('/login.html', fn () => response()->file(public_path('frontend/login.html')))->withoutMiddleware($withoutWebState);
Route::get('/register.html', fn () => response()->file(public_path('frontend/register.html')))->withoutMiddleware($withoutWebState);
Route::get('/dashboard.html', fn () => response()->file(public_path('frontend/dashboard.html')))->withoutMiddleware($withoutWebState);
Route::get('/upload-resume.html', fn () => response()->file(public_path('frontend/upload-resume.html')))->withoutMiddleware($withoutWebState);
Route::get('/results.html', fn () => response()->file(public_path('frontend/results.html')))->withoutMiddleware($withoutWebState);
Route::get('/app.js', fn () => response()->file(public_path('frontend/app.js'), ['Content-Type' => 'application/javascript']))->withoutMiddleware($withoutWebState);
Route::get('/style.css', fn () => response()->file(public_path('frontend/style.css'), ['Content-Type' => 'text/css']))->withoutMiddleware($withoutWebState);

Route::get('/api-docs/postman-collection', function () {
    $path = storage_path('app/deploy-docs/HireSmart_AI_Postman_Collection.json');

    abort_unless(file_exists($path), 404, 'Postman collection not found.');

    return response()->download($path);
})->withoutMiddleware($withoutWebState);
