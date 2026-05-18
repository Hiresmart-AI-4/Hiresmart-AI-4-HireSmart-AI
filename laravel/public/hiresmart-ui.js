const boot = window.HIRE_SMART_BOOT || {};
const state = {
    token: localStorage.getItem("hiresmart_token") || "",
};

const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => Array.from(document.querySelectorAll(selector));

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function pretty(value) {
    return JSON.stringify(value, null, 2);
}

function setOutput(value) {
    $("#apiOutput").textContent = typeof value === "string" ? value : pretty(value);
}

function updateTokenUI() {
    const tokenState = $("#tokenState");
    const tokenOutput = $("#tokenOutput");

    if (state.token) {
        tokenState.textContent = `Token saved (${state.token.slice(0, 12)}...)`;
        tokenOutput.textContent = state.token;
        return;
    }

    tokenState.textContent = "No token saved yet. Register or login in Site 1 Auth.";
    tokenOutput.textContent = "No token saved.";
}

function saveToken(token) {
    if (!token) {
        return;
    }

    state.token = token;
    localStorage.setItem("hiresmart_token", token);
    updateTokenUI();
}

function setSection(section) {
    $$(".view").forEach((view) => view.classList.remove("active"));
    $$(".nav-item").forEach((item) => item.classList.toggle("active", item.dataset.section === section));

    const target = $(`#view-${section}`);
    if (target) {
        target.classList.add("active");
    }
}

async function apiRequest(path, options = {}) {
    const headers = {
        Accept: "application/json",
        ...(options.headers || {}),
    };

    if (options.json) {
        headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(options.json);
    }

    if (options.auth !== false && state.token) {
        headers.Authorization = `Bearer ${state.token}`;
    }

    const response = await fetch(`${boot.baseUrl}${path}`, {
        ...options,
        headers,
    });

    const text = await response.text();
    let data;

    try {
        data = text ? JSON.parse(text) : {};
    } catch {
        data = text;
    }

    if (!response.ok) {
        throw { status: response.status, data };
    }

    return data;
}

function formToObject(form) {
    return Object.fromEntries(new FormData(form).entries());
}

function renderMetrics() {
    const metrics = boot.metrics || {};
    const items = [
        ["Total Resumes", metrics.totalResumes ?? 0, "Uploaded records"],
        ["Average ATS", metrics.averageScore ?? 0, `${metrics.analyzedResumes ?? 0} analyzed`],
        ["Best Score", metrics.bestScore ?? 0, "Highest ATS score"],
        ["Active Jobs", metrics.activeJobs ?? 0, "Open postings"],
        ["Database", metrics.databaseStatus ?? "Unknown", metrics.databaseNote ?? ""],
    ];

    $("#databaseStatus").textContent = `DB ${metrics.databaseStatus ?? "Unknown"}`;
    $("#metricGrid").innerHTML = items.map(([label, value, note]) => `
        <article class="card">
            <div class="metric-label">${escapeHtml(label)}</div>
            <div class="metric-value">${escapeHtml(value)}</div>
            <div class="metric-note">${escapeHtml(note)}</div>
        </article>
    `).join("");
}

function renderResumes(resumes = boot.latestResumes || []) {
    const rows = resumes.map((resume) => {
        const score = resume.score ?? resume.ats_score;
        const scoreHtml = score === null || score === undefined
            ? '<span class="pill">Pending</span>'
            : `<div class="score-wrap"><div class="meter"><span style="width:${Math.max(0, Math.min(100, score))}%"></span></div><strong>${escapeHtml(score)}</strong></div>`;

        return `
            <tr>
                <td><strong>${escapeHtml(resume.title)}</strong><small>${escapeHtml(resume.id || resume.resume_id || "")}</small></td>
                <td>${escapeHtml(resume.file || resume.original_filename || "Uploaded resume")}</td>
                <td>${scoreHtml}</td>
                <td>${escapeHtml(resume.date || resume.created_at || "")}</td>
            </tr>
        `;
    }).join("");

    $("#resumeRows").innerHTML = rows || '<tr><td colspan="4" class="empty">No resumes uploaded yet.</td></tr>';
}

function renderTrend(points = boot.scoreTrend || []) {
    $("#scoreTrend").innerHTML = points.map((point) => `
        <div class="trend-col" title="${escapeHtml(point.title)}: ${escapeHtml(point.score)}">
            <div class="trend-bar" style="height:${Math.max(8, Math.min(100, point.score || 0))}%"></div>
            <span>${escapeHtml(point.label)}</span>
        </div>
    `).join("") || '<div class="empty">Analyze a resume to see score movement.</div>';
}

function renderJobs(jobs = boot.latestJobs || []) {
    $("#jobList").innerHTML = jobs.map((job) => `
        <article class="job-card">
            <strong>${escapeHtml(job.title)}</strong>
            <small class="small-muted">${escapeHtml(job.company)} - ${escapeHtml(job.location)}</small>
            <div class="chips">${(job.skills || []).map((skill) => `<span class="chip">${escapeHtml(skill)}</span>`).join("")}</div>
        </article>
    `).join("") || '<div class="empty">No active job posts yet.</div>';
}

async function handleApiAction(action) {
    const actions = {
        test: { path: "/test", auth: false },
        gateway: { path: "/gateway/routes", auth: false },
        dashboard: { path: "/analysis/dashboard" },
        profile: { path: "/site2/users/profile" },
    };
    const selected = actions[action];

    if (!selected) {
        return;
    }

    try {
        setOutput(`Loading ${selected.path}...`);
        const data = await apiRequest(selected.path, { auth: selected.auth });
        setOutput(data);

        if (action === "dashboard") {
            renderResumes(data.resumes || []);
        }
    } catch (error) {
        setOutput(error);
    }
}

function bindEvents() {
    $$(".nav-item").forEach((button) => {
        button.addEventListener("click", () => setSection(button.dataset.section));
    });

    $$("[data-api-action]").forEach((button) => {
        button.addEventListener("click", () => handleApiAction(button.dataset.apiAction));
    });

    $("#registerForm").addEventListener("submit", async (event) => {
        event.preventDefault();
        try {
            const data = await apiRequest("/site1/register", {
                method: "POST",
                auth: false,
                json: formToObject(event.currentTarget),
            });
            saveToken(data.token);
            setOutput(data);
        } catch (error) {
            setOutput(error);
        }
    });

    $("#loginForm").addEventListener("submit", async (event) => {
        event.preventDefault();
        try {
            const data = await apiRequest("/site1/login", {
                method: "POST",
                auth: false,
                json: formToObject(event.currentTarget),
            });
            saveToken(data.token);
            setOutput(data);
        } catch (error) {
            setOutput(error);
        }
    });

    $("#profileForm").addEventListener("submit", async (event) => {
        event.preventDefault();
        try {
            const payload = Object.fromEntries(Object.entries(formToObject(event.currentTarget)).filter(([, value]) => value));
            const data = await apiRequest("/site2/users/profile", {
                method: "PUT",
                json: payload,
            });
            setOutput(data);
        } catch (error) {
            setOutput(error);
        }
    });

    $("#logoutButton").addEventListener("click", async () => {
        try {
            const data = await apiRequest("/site2/logout", { method: "POST" });
            localStorage.removeItem("hiresmart_token");
            state.token = "";
            updateTokenUI();
            setOutput(data);
        } catch (error) {
            setOutput(error);
        }
    });

    $("#clearToken").addEventListener("click", () => {
        localStorage.removeItem("hiresmart_token");
        state.token = "";
        updateTokenUI();
        setOutput("Token cleared.");
    });

    $("#copyToken").addEventListener("click", async () => {
        if (!state.token) {
            setOutput("No token to copy.");
            return;
        }

        await navigator.clipboard.writeText(state.token);
        setOutput("Token copied to clipboard.");
    });

    $("#refreshDashboard").addEventListener("click", () => handleApiAction("dashboard"));
    $("#clearOutput").addEventListener("click", () => setOutput("Ready."));
}

function init() {
    renderMetrics();
    renderResumes();
    renderTrend();
    renderJobs();
    $("#docsBlock").textContent = boot.docs || "No documentation loaded.";
    bindEvents();
    updateTokenUI();
    setSection(boot.activeSection || "dashboard");
}

init();
