const API_BASE = localStorage.getItem('hiresmart_api_base') || `${location.origin}/api`;
const TOKEN_KEY = 'hiresmart_token';
const USER_KEY = 'hiresmart_user';
const LAST_RESULT_KEY = 'hiresmart_last_result';

const page = document.body;
const message = document.getElementById('message');

function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

function getUser() {
    try {
        return JSON.parse(localStorage.getItem(USER_KEY) || 'null');
    } catch {
        return null;
    }
}

function setMessage(text, type = 'info') {
    if (!message) {
        return;
    }

    message.textContent = text;
    message.className = `form-message ${type}`;
}

function saveSession(payload) {
    localStorage.setItem(TOKEN_KEY, payload.token);
    localStorage.setItem(USER_KEY, JSON.stringify(payload.user || null));
}

function clearSession() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    localStorage.removeItem(LAST_RESULT_KEY);
}

function requireAuth() {
    const publicPages = ['login.html', 'register.html'];
    const currentPage = location.pathname.split('/').pop() || 'dashboard.html';

    if (!getToken() && !publicPages.includes(currentPage)) {
        location.href = 'login.html';
    }
}

async function apiRequest(path, options = {}) {
    const headers = {
        Accept: 'application/json',
        ...(options.headers || {}),
    };

    if (options.auth !== false) {
        const token = getToken();
        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }
    }

    if (options.body && !(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(options.body);
    }

    const response = await fetch(`${API_BASE}${path}`, {
        ...options,
        headers,
    });

    const contentType = response.headers.get('content-type') || '';
    const rawBody = await response.text();
    const data = contentType.includes('application/json') ? parseJsonBody(rawBody) : rawBody;

    if (!response.ok) {
        const error = new Error(extractError(data) || `Request failed with status ${response.status}`);
        error.status = response.status;
        error.payload = data;
        throw error;
    }

    return data;
}

function parseJsonBody(rawBody) {
    try {
        return rawBody ? JSON.parse(rawBody) : {};
    } catch {
        return {
            message: cleanHtmlError(rawBody) || 'The API returned an invalid JSON response.',
        };
    }
}

function extractError(data) {
    if (!data) {
        return '';
    }

    if (typeof data === 'string') {
        return cleanHtmlError(data) || data;
    }

    if (data.message) {
        return data.message;
    }

    if (data.errors) {
        return Object.values(data.errors).flat().join(' ');
    }

    return '';
}

function cleanHtmlError(value) {
    const text = String(value || '')
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    if (!text) {
        return '';
    }

    return text.length > 240 ? `${text.slice(0, 240)}...` : text;
}

function initLogin() {
    const form = document.getElementById('loginForm');
    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setMessage('Logging in...');

        try {
            const data = await apiRequest('/auth/login', {
                method: 'POST',
                auth: false,
                body: {
                    email: document.getElementById('email').value.trim(),
                    password: document.getElementById('password').value,
                },
            });

            saveSession(data);
            setMessage('Login successful. Opening dashboard...', 'success');
            location.href = 'dashboard.html';
        } catch (error) {
            setMessage(error.message, 'error');
        }
    });
}

function initRegister() {
    const form = document.getElementById('registerForm');
    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setMessage('Creating account...');

        try {
            const data = await apiRequest('/auth/register', {
                method: 'POST',
                auth: false,
                body: {
                    name: document.getElementById('name').value.trim(),
                    email: document.getElementById('email').value.trim(),
                    password: document.getElementById('password').value,
                    password_confirmation: document.getElementById('password_confirmation').value,
                },
            });

            saveSession(data);
            setMessage('Account created. Opening upload page...', 'success');
            location.href = 'upload-resume.html';
        } catch (error) {
            setMessage(error.message, 'error');
        }
    });
}

function initLogout() {
    const button = document.getElementById('logoutButton');
    if (!button) {
        return;
    }

    button.addEventListener('click', async () => {
        try {
            await apiRequest('/auth/logout', { method: 'POST' });
        } catch {
            // Local logout should still complete if the token is already expired.
        } finally {
            clearSession();
            location.href = 'login.html';
        }
    });
}

function initUpload() {
    const form = document.getElementById('resumeForm');
    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const file = document.getElementById('resume').files[0];
        if (!file) {
            setMessage('Choose a PDF or DOCX resume first.', 'error');
            return;
        }

        const extension = file.name.split('.').pop().toLowerCase();
        if (!['pdf', 'docx'].includes(extension)) {
            setMessage('Only PDF and DOCX resumes are supported.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('title', document.getElementById('title').value.trim());
        formData.append('resume', file);

        setMessage('Uploading resume...');
        renderResultLoading();

        try {
            const upload = await apiRequest('/resumes/upload', {
                method: 'POST',
                body: formData,
            });

            setMessage('Checking resume against ATS signals...');
            const jobDescription = document.getElementById('jobDescription').value.trim();
            const analysis = await apiRequest('/analyze', {
                method: 'POST',
                body: {
                    resume_id: upload.resume.resume_id,
                    job_description: jobDescription || null,
                },
            });

            const result = {
                resume: upload.resume,
                analysis: analysis.analysis,
                recommendations: analysis.recommendations || [],
            };

            localStorage.setItem(LAST_RESULT_KEY, JSON.stringify(result));
            renderResult(result);
            setMessage('Resume checked successfully.', 'success');
        } catch (error) {
            renderResultError(error.message);
            setMessage(error.message, 'error');
        }
    });
}

function renderResultLoading() {
    const target = document.getElementById('result');
    if (target) {
        target.className = 'result-card';
        target.innerHTML = '<p class="muted">Processing resume...</p>';
    }
}

function renderResultError(text) {
    const target = document.getElementById('result');
    if (target) {
        target.className = 'result-card error-box';
        target.textContent = text;
    }
}

function renderResult(result) {
    const target = document.getElementById('result');
    if (!target) {
        return;
    }

    const analysis = result.analysis || {};
    const resume = result.resume || {};
    const score = analysis.total_score ?? resume.ats_score ?? 0;
    const strengths = listItems(analysis.strengths);
    const weaknesses = listItems(analysis.weaknesses);
    const missing = listItems(analysis.missing_keywords || result.recommendations);
    const skills = listItems(analysis.skills || resume.parsed_data?.skills);

    target.className = 'result-card';
    target.innerHTML = `
        <div class="score-row">
            <div>
                <span class="eyebrow">ATS score</span>
                <strong>${escapeHtml(score)}%</strong>
            </div>
            <span>${escapeHtml(resume.original_filename || resume.title || 'Resume')}</span>
        </div>
        <p>${escapeHtml(analysis.summary || 'Resume analysis completed.')}</p>
        <div class="result-columns">
            <section>
                <h3>Strengths</h3>
                <ul>${strengths}</ul>
            </section>
            <section>
                <h3>Needs work</h3>
                <ul>${weaknesses}</ul>
            </section>
            <section>
                <h3>Missing keywords</h3>
                <ul>${missing}</ul>
            </section>
            <section>
                <h3>Detected skills</h3>
                <ul>${skills}</ul>
            </section>
        </div>
    `;
}

function listItems(items) {
    const values = Array.isArray(items) && items.length ? items : ['None detected'];
    return values.map((item) => `<li>${escapeHtml(item)}</li>`).join('');
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[char]));
}

async function initDashboard() {
    const list = document.getElementById('resumeList');
    if (!list) {
        return;
    }

    const user = getUser();
    const welcome = document.getElementById('welcomeTitle');
    if (welcome && user?.name) {
        welcome.textContent = `Welcome, ${user.name}`;
    }

    try {
        const data = await apiRequest('/analysis/dashboard');
        const resumes = data.resumes || [];

        document.getElementById('totalResumes').textContent = data.total_resumes ?? resumes.length;
        document.getElementById('averageScore').textContent = Math.round(data.average_score || 0);
        document.getElementById('latestScore').textContent = data.latest_resume?.ats_score ?? 0;

        if (!resumes.length) {
            list.innerHTML = '<div class="empty-state">No resumes uploaded yet.</div>';
            return;
        }

        list.innerHTML = resumes.map((resume) => `
            <article class="resume-row">
                <div>
                    <strong>${escapeHtml(resume.title)}</strong>
                    <span>${escapeHtml(resume.original_filename)}</span>
                </div>
                <div class="row-score">${escapeHtml(resume.ats_score ?? 'Pending')}</div>
            </article>
        `).join('');
    } catch (error) {
        const dashboardMessage = document.getElementById('dashboardMessage');
        if (dashboardMessage) {
            dashboardMessage.textContent = error.message;
        }
        list.innerHTML = '<div class="empty-state">Dashboard data could not be loaded.</div>';
    }
}

function initStoredResult() {
    if (!document.getElementById('result') || document.getElementById('resumeForm')) {
        return;
    }

    const result = localStorage.getItem(LAST_RESULT_KEY);
    if (result) {
        renderResult(JSON.parse(result));
    }
}

requireAuth();
initLogin();
initRegister();
initLogout();
initUpload();
initDashboard();
initStoredResult();

if (page.classList.contains('auth-page') && getToken()) {
    location.href = 'dashboard.html';
}
