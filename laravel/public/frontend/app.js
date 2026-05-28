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
    if (payload.token) {
        localStorage.setItem(TOKEN_KEY, payload.token);
    } else if (payload.data?.token) {
        localStorage.setItem(TOKEN_KEY, payload.data.token);
    }
    
    const userObj = payload.user || payload.data?.user || null;
    if (userObj) {
        localStorage.setItem(USER_KEY, JSON.stringify(userObj));
    }
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
    
    // If downloading a file, return the blob directly
    if (path.includes('/download')) {
        if (!response.ok) throw new Error('Download failed');
        return await response.blob();
    }

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

function firstPresent(...values) {
    return values.find((value) => value !== undefined && value !== null && value !== '');
}

function normalizeResume(uploadResponse) {
    const nestedResume = findResumeLike(uploadResponse);
    if (nestedResume) {
        return nestedResume;
    }

    const candidate = firstPresent(
        uploadResponse?.resume,
        uploadResponse?.data?.resume,
        uploadResponse?.data,
        uploadResponse
    );

    const resumeId = firstPresent(
        candidate?.resume_id,
        candidate?.id,
        uploadResponse?.resume_id,
        uploadResponse?.data?.resume_id,
        uploadResponse?.data?.id
    );

    return resumeId ? { ...candidate, resume_id: resumeId } : null;
}

function findResumeLike(value) {
    if (!value || typeof value !== 'object') {
        return null;
    }

    if (!Array.isArray(value)) {
        const resumeId = firstPresent(value.resume_id, value.id);
        const looksLikeResume = resumeId && (
            value.original_filename ||
            value.file_url ||
            value.file_type ||
            value.parsed_data ||
            value.ats_score !== undefined
        );

        if (looksLikeResume) {
            return { ...value, resume_id: resumeId };
        }
    }

    const children = Array.isArray(value) ? value : Object.values(value);
    for (const child of children) {
        const found = findResumeLike(child);
        if (found) {
            return found;
        }
    }

    return null;
}

function normalizeAnalysis(analysisResponse) {
    return firstPresent(
        analysisResponse?.analysis,
        analysisResponse?.data?.analysis,
        analysisResponse?.data,
        analysisResponse
    );
}

function describeResponseShape(response) {
    if (!response || typeof response !== 'object') {
        return typeof response;
    }

    const keys = Object.keys(response);
    const nested = response.data && typeof response.data === 'object'
        ? ` data keys: ${Object.keys(response.data).join(', ') || 'none'}.`
        : '';

    return `top-level keys: ${keys.join(', ') || 'none'}.${nested}`;
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
            setTimeout(() => {
                location.href = 'dashboard.html';
            }, 500);
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
            setTimeout(() => {
                location.href = 'upload-resume.html';
            }, 800);
        } catch (error) {
            setMessage(error.message, 'error');
        }
    });
}

function initLogout() {
    const buttons = document.querySelectorAll('#logoutButton');
    buttons.forEach(button => {
        button.addEventListener('click', async () => {
            try {
                await apiRequest('/auth/logout', { method: 'POST' });
            } catch {
                // Ignore local fail
            } finally {
                clearSession();
                location.href = 'login.html';
            }
        });
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

            const uploadedResume = normalizeResume(upload);
            if (!uploadedResume) {
                throw new Error(`Resume upload finished, but the API did not return a resume_id`);
            }

            setMessage('Checking resume against ATS signals...');
            const jobDescription = document.getElementById('jobDescription').value.trim();
            const analysis = await apiRequest('/analyze', {
                method: 'POST',
                body: {
                    resume_id: uploadedResume.resume_id,
                    job_description: jobDescription || null,
                },
            });

            const analysisResult = normalizeAnalysis(analysis);
            const recommendations = analysis.recommendations || analysis.data?.recommendations || [];
            const result = {
                resume: uploadedResume,
                analysis: analysisResult,
                recommendations,
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
        target.innerHTML = '<div class="spinner"></div><p class="muted" style="display:inline-block;">Processing resume metrics...</p>';
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
        <div style="margin-top: 20px; display: flex; gap: 12px;">
            <button onclick="triggerEmailReport('${resume.resume_id || resume.id}')" class="mini-btn">Email This Report</button>
            <button onclick="downloadResume('${resume.resume_id || resume.id}', '${resume.original_filename || 'resume.pdf'}')" class="mini-btn secondary">Download PDF</button>
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

// Global scope bindings for actions from dynamic markup
window.triggerEmailReport = async function(resumeId) {
    try {
        const user = getUser();
        if (!user || !user.email) throw new Error('User profile is not loaded.');

        setMessage('Sending report email...');
        const response = await apiRequest('/notifications/email', {
            method: 'POST',
            body: {
                to_email: user.email,
                to_name: user.name || 'User',
                subject: 'HireSmart AI ATS Match Report',
                message: `Hi ${user.name || 'User'},\n\nYour ATS match report is complete.\nScore: Checked on HireSmart Dashboard.\n\nThank you for choosing HireSmart AI!`,
            }
        });
        alert(response.message || 'Report sent to your email successfully!');
        setMessage('Report email sent!', 'success');
    } catch(err) {
        alert('Email failed: ' + err.message);
        setMessage(err.message, 'error');
    }
};

window.downloadResume = async function(id, filename) {
    try {
        const blob = await apiRequest(`/resumes/${id}/download`);
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename || 'resume.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    } catch(err) {
        alert('Download failed: ' + err.message);
    }
};

window.deleteResume = async function(id) {
    if (!confirm('Are you sure you want to delete this resume?')) return;
    try {
        await apiRequest(`/resumes/${id}`, { method: 'DELETE' });
        alert('Resume deleted successfully.');
        initDashboard();
    } catch (err) {
        alert('Deletion failed: ' + err.message);
    }
};

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
            list.innerHTML = '<div class="empty-state">No resumes uploaded yet. Go to Upload tab to get started.</div>';
            return;
        }

        list.innerHTML = resumes.map((resume) => {
            const id = resume.resume_id || resume.id;
            return `
                <article class="resume-row">
                    <div>
                        <input type="checkbox" name="compare-select" value="${id}" class="compare-checkbox" style="margin-right: 12px; transform: scale(1.15);">
                        <strong>${escapeHtml(resume.title)}</strong>
                        <span>${escapeHtml(resume.original_filename)}</span>
                    </div>
                    <div class="row-actions">
                        <div class="row-score" style="margin-right: 8px;">Score: ${escapeHtml(resume.ats_score ?? 'Pending')}</div>
                        <button onclick="downloadResume('${id}', '${resume.original_filename}')" class="mini-btn secondary" title="Download">Download</button>
                        <button onclick="deleteResume('${id}')" class="mini-btn danger" title="Delete">Delete</button>
                    </div>
                </article>
            `;
        }).join('');
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

// Workspace / Tabs switcher logic
function initTabs() {
    const tabButtons = document.querySelectorAll('.sidebar-tab-btn');
    if (!tabButtons.length) return;

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');

            // Set buttons active
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Toggle views
            document.querySelectorAll('.tab-content').forEach(view => {
                view.classList.remove('active');
            });
            
            const targetView = document.getElementById(targetTab);
            if (targetView) {
                targetView.classList.add('active');
            }

            // Route-specific triggers
            if (targetTab === 'profile-tab') {
                loadProfile();
            } else if (targetTab === 'jobs-tab') {
                loadJobs();
            } else if (targetTab === 'notifications-tab') {
                loadNotifications();
            } else if (targetTab === 'overview-tab') {
                initDashboard();
            }
        });
    });
}

// Profile settings tab loader and save
async function loadProfile() {
    const form = document.getElementById('profileForm');
    if (!form) return;

    const feedback = document.getElementById('profileFeedback');
    feedback.textContent = 'Loading profile details...';
    feedback.className = 'form-message';

    try {
        const data = await apiRequest('/users/profile');
        const userObj = data.user || data.data?.user || {};
        
        document.getElementById('profileName').value = userObj.name || '';
        document.getElementById('profileEmail').value = userObj.email || '';
        document.getElementById('profilePhone').value = userObj.phone || '';
        document.getElementById('profileBio').value = userObj.bio || '';
        
        // Sync local storage user
        localStorage.setItem(USER_KEY, JSON.stringify(userObj));
        feedback.textContent = '';
    } catch(err) {
        feedback.textContent = 'Error loading profile: ' + err.message;
        feedback.className = 'form-message error';
    }
}

function initProfileFormSubmit() {
    const form = document.getElementById('profileForm');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const feedback = document.getElementById('profileFeedback');
        feedback.textContent = 'Saving changes...';
        feedback.className = 'form-message';

        try {
            const payload = {
                name: document.getElementById('profileName').value.trim(),
                phone: document.getElementById('profilePhone').value.trim(),
                bio: document.getElementById('profileBio').value.trim(),
            };

            const data = await apiRequest('/users/profile', {
                method: 'PUT',
                body: payload
            });

            feedback.textContent = 'Profile updated successfully!';
            feedback.className = 'form-message success';
            
            const updatedUser = data.user || data.data?.user;
            if (updatedUser) {
                localStorage.setItem(USER_KEY, JSON.stringify(updatedUser));
                const welcome = document.getElementById('welcomeTitle');
                if (welcome) welcome.textContent = `Welcome, ${updatedUser.name}`;
            }
        } catch(err) {
            feedback.textContent = err.message;
            feedback.className = 'form-message error';
        }
    });
}

// Jobs list search & ATS score analyzer
async function loadJobs() {
    const searchForm = document.getElementById('jobSearchForm');
    if (!searchForm) return;

    // Load initial jobs
    triggerJobSearch('Developer', '');
}

async function triggerJobSearch(what, where) {
    const jobList = document.getElementById('jobList');
    if (!jobList) return;

    jobList.innerHTML = '<div class="spinner"></div><p class="muted" style="display:inline-block;">Fetching live jobs...</p>';

    try {
        const data = await apiRequest(`/jobs/live?what=${encodeURIComponent(what)}&where=${encodeURIComponent(where)}`);
        const jobs = data.jobs || [];

        if (data.configured === false) {
            jobList.innerHTML = `
                <div class="empty-state" style="border-color: var(--accent); color: var(--accent); background: rgba(180, 83, 9, 0.05); padding: 20px; border-radius: 8px;">
                    <h4 style="margin:0 0 8px 0; font-size:1.1rem;">⚠️ Live Job Matches are not configured</h4>
                    <p style="margin:0 0 8px 0; font-size:0.95rem;">The server is missing the required Jooble integration credentials. To solve this, copy your <code>JOOBLE_API_KEY</code> and add it under the <strong>Environment</strong> section in your Render Web Service dashboard.</p>
                    <small style="opacity: 0.85;">Provider message: ${escapeHtml(data.provider_message || 'Missing JOOBLE_API_KEY')}</small>
                </div>
            `;
            return;
        }

        if (!jobs.length) {
            jobList.innerHTML = '<div class="empty-state">No matching live jobs found. Try different keywords.</div>';
            return;
        }

        jobList.innerHTML = `
            <div class="job-grid">
                ${jobs.map((job, idx) => {
                    const title = job.title || 'Untitled Role';
                    const company = job.company || 'Confidential';
                    const location = job.location || 'Remote / Worldwide';
                    const descSnippet = job.description || 'No description available.';
                    
                    // JSON stringify the description and values safely to invoke analysis
                    const descEscaped = escapeHtml(descSnippet);

                    return `
                        <article class="job-card">
                            <div class="job-card-header">
                                <h3>${escapeHtml(title)}</h3>
                                <div class="company">${escapeHtml(company)}</div>
                                <div class="location">${escapeHtml(location)}</div>
                            </div>
                            <p class="muted" style="font-size: 0.88rem; margin: 10px 0; max-height: 80px; overflow: hidden; text-overflow: ellipsis;">
                                ${descSnippet.replace(/<[^>]*>/g, '').slice(0, 160)}...
                            </p>
                            <div>
                                <button onclick="matchResumeToLiveJob(${idx}, '${escapeHtml(title)}')" class="mini-btn" style="width:100%">Run ATS Match</button>
                                <textarea id="raw-desc-${idx}" style="display:none;">${descEscaped}</textarea>
                            </div>
                        </article>
                    `;
                }).join('')}
            </div>
        `;
    } catch(err) {
        jobList.innerHTML = `<div class="empty-state error-box">Could not fetch jobs: ${err.message}</div>`;
    }
}

function initJobSearchSubmit() {
    const form = document.getElementById('jobSearchForm');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const what = document.getElementById('jobSearchTitle').value.trim();
        const where = document.getElementById('jobSearchLocation').value.trim();
        triggerJobSearch(what, where);
    });
}

// Execute real-time matching of active/latest resume with a job card's description
window.matchResumeToLiveJob = async function(idx, jobTitle) {
    const descArea = document.getElementById(`raw-desc-${idx}`);
    if (!descArea) return;

    const jobDescription = descArea.value;

    // Fetch latest analysis/dashboard to get a resume ID
    try {
        setMessage('Matching your resume...');
        const dashboard = await apiRequest('/analysis/dashboard');
        const resumes = dashboard.resumes || [];

        if (!resumes.length) {
            alert('Please upload a resume in the Upload tab first before running job matches!');
            return;
        }

        const latestResume = resumes[0];
        const resumeId = latestResume.resume_id || latestResume.id;

        // Open a modal showing analysis matching progress
        openCompareModal(`ATS Matching with ${jobTitle}`, `
            <div style="text-align:center; padding:20px;">
                <div class="spinner"></div>
                <p>AI is assessing your resume skills against <strong>${escapeHtml(jobTitle)}</strong>...</p>
            </div>
        `);

        const matchResponse = await apiRequest('/analyze', {
            method: 'POST',
            body: {
                resume_id: resumeId,
                job_description: jobDescription
            }
        });

        const analysis = normalizeAnalysis(matchResponse);
        const score = analysis.total_score || analysis.score || 0;

        // Render matching results in the comparison modal
        document.getElementById('compareModalBody').innerHTML = `
            <div class="score-row">
                <div>
                    <span class="eyebrow">Target Role: ${escapeHtml(jobTitle)}</span>
                    <strong>ATS Match: ${score}%</strong>
                </div>
            </div>
            <div style="margin-top: 20px;">
                <p><strong>Assessment Summary:</strong></p>
                <p>${escapeHtml(analysis.summary || 'AI has successfully completed the ATS match.')}</p>
            </div>
            <div class="compare-grid" style="margin-top:20px;">
                <div class="compare-card">
                    <h4>Required / Matching Skills</h4>
                    <ul>${listItems(analysis.skills || analysis.strengths)}</ul>
                </div>
                <div class="compare-card">
                    <h4>Missing / Recommendations</h4>
                    <ul>${listItems(analysis.missing_keywords || analysis.weaknesses)}</ul>
                </div>
            </div>
        `;
    } catch(err) {
        alert('Match failed: ' + err.message);
        closeCompareModal();
    }
};

// Resume Deletion, Download and Side-by-Side Comparison selector
function initResumeComparison() {
    const btn = document.getElementById('compareResumesBtn');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        const checkedBoxes = document.querySelectorAll('.compare-checkbox:checked');
        if (checkedBoxes.length !== 2) {
            alert('Please select exactly two resumes from the list to compare side-by-side.');
            return;
        }

        const id1 = checkedBoxes[0].value;
        const id2 = checkedBoxes[1].value;

        openCompareModal('Resume Comparison', `
            <div style="text-align:center; padding:20px;">
                <div class="spinner"></div>
                <p>Analyzing side-by-side improvements...</p>
            </div>
        `);

        try {
            const [r1, r2, compareData] = await Promise.all([
                apiRequest(`/resumes/${id1}`),
                apiRequest(`/resumes/${id2}`),
                apiRequest(`/resumes/compare/${id1}/${id2}`)
            ]);

            const s1 = r1.ats_score ?? compareData.original_score ?? 0;
            const s2 = r2.ats_score ?? compareData.improved_score ?? 0;
            const diff = compareData.score_increase ?? (s2 - s1);
            const trendClass = diff >= 0 ? 'success' : 'danger';
            const prefix = diff >= 0 ? '+' : '';

            const skills1 = r1.parsed_data?.skills || [];
            const skills2 = r2.parsed_data?.skills || [];

            document.getElementById('compareModalBody').innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--line); padding-bottom:16px;">
                    <div>
                        <h2>Resume Difference Analysis</h2>
                        <p class="muted">Side-by-side comparison score progress.</p>
                    </div>
                    <div style="text-align:right;">
                        <span class="eyebrow">Score Shift</span>
                        <h2 class="${trendClass}" style="margin:0; font-size:2rem; font-weight:900;">${prefix}${diff}%</h2>
                    </div>
                </div>
                <div class="compare-grid" style="margin-top:20px;">
                    <div class="compare-card">
                        <h4>${escapeHtml(r1.title || 'Original Resume')}</h4>
                        <p class="muted">${escapeHtml(r1.original_filename)}</p>
                        <div class="compare-score">${s1}%</div>
                        <p><strong>Primary Skills:</strong></p>
                        <ul>${listItems(skills1.length ? skills1 : ['None parsed'])}</ul>
                    </div>
                    <div class="compare-card">
                        <h4>${escapeHtml(r2.title || 'Revised Resume')}</h4>
                        <p class="muted">${escapeHtml(r2.original_filename)}</p>
                        <div class="compare-score">${s2}%</div>
                        <p><strong>Primary Skills:</strong></p>
                        <ul>${listItems(skills2.length ? skills2 : ['None parsed'])}</ul>
                    </div>
                </div>
                <div style="margin-top:24px; padding:16px; border-radius:6px; background:var(--surface-alt);">
                    <p><strong>AI Suggestions Applied:</strong></p>
                    <p>${escapeHtml(compareData.suggestions_applied?.summary || 'ATS score change is calculated based on skill keyword matching and alignment depth improvements.')}</p>
                </div>
            `;
        } catch(err) {
            alert('Comparison failed: ' + err.message);
            closeCompareModal();
        }
    });
}

// Notifications retrieval
async function loadNotifications() {
    const list = document.getElementById('notificationList');
    if (!list) return;

    list.innerHTML = '<div class="spinner"></div><p class="muted" style="display:inline-block;">Loading notifications...</p>';

    try {
        const data = await apiRequest('/notifications');
        const notifs = data.notifications || [];

        // Check if we have recent scans in localStorage to mock clean user notification logs
        const localResult = localStorage.getItem(LAST_RESULT_KEY);
        const log = localResult ? JSON.parse(localResult) : null;
        
        if (!notifs.length && !log) {
            list.innerHTML = '<div class="empty-state">Your notification log is clear. Scan logs will appear here.</div>';
            return;
        }

        let html = '';
        if (log) {
            html += `
                <article class="notif-card unread">
                    <div>
                        <span class="eyebrow">Recent Scan Success</span>
                        <p>Your resume "<strong>${escapeHtml(log.resume?.title || 'Resume')}</strong>" achieved an ATS score of <strong>${log.analysis?.total_score || 0}%</strong>.</p>
                        <span>Just now</span>
                    </div>
                    <button onclick="triggerEmailReport('${log.resume?.resume_id}')" class="mini-btn">Email Report</button>
                </article>
            `;
        }

        notifs.forEach(notif => {
            html += `
                <article class="notif-card">
                    <div>
                        <p>${escapeHtml(notif.message || 'Notification received')}</p>
                        <span>${escapeHtml(notif.created_at || 'Recently')}</span>
                    </div>
                </article>
            `;
        });

        list.innerHTML = `<div class="notif-list">${html}</div>`;
    } catch(err) {
        list.innerHTML = `<div class="empty-state error-box">Could not load notification dashboard logs: ${err.message}</div>`;
    }
}

// Modal helper methods
function openCompareModal(title, bodyHtml) {
    const modal = document.getElementById('compareModal');
    if (!modal) return;

    document.getElementById('compareModalTitle').textContent = title;
    document.getElementById('compareModalBody').innerHTML = bodyHtml;
    modal.classList.add('active');
}

function closeCompareModal() {
    const modal = document.getElementById('compareModal');
    if (modal) modal.classList.remove('active');
}

window.closeCompareModal = closeCompareModal;

// Initialize Workspace Logic
requireAuth();
initLogin();
initRegister();
initLogout();
initUpload();
initDashboard();
initStoredResult();

initTabs();
initProfileFormSubmit();
initJobSearchSubmit();
initResumeComparison();

if (page.classList.contains('auth-page') && getToken()) {
    location.href = 'dashboard.html';
}
