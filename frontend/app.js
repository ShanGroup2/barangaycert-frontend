(() => {
    "use strict";

    const root = document.getElementById("app");
    const toast = document.getElementById("toast");
    const apiBase = new URL("api/", window.location.href).pathname;
    const state = {
        user: null,
        profile: null,
        page: "dashboard",
        requestFilter: "pending",
        selectedRequestId: null,
        toastTimer: null
    };

    const escapeHtml = (value) => String(value ?? "").replace(/[&<>"']/g, (character) => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    })[character]);

    const api = async (path, options = {}) => {
        const response = await fetch(`${apiBase}${path}`, {
            credentials: "same-origin",
            ...options,
            headers: {
                Accept: "application/json",
                ...(options.body instanceof FormData ? {} : { "Content-Type": "application/json" }),
                ...options.headers
            }
        });
        const contentType = response.headers.get("content-type") || "";
        const result = contentType.includes("application/json")
            ? await response.json()
            : await response.text();
        if (!response.ok) {
            const message = typeof result === "object" ? result.Message || result.error : result;
            throw new Error(message || `Request failed (${response.status}).`);
        }
        if (typeof result === "object" && result !== null && result.Status === "Error") {
            throw new Error(result.Message || "The request could not be completed.");
        }
        if (typeof result === "object" && result !== null && String(result.Message || "").startsWith("Error:")) {
            throw new Error(result.Message);
        }
        return result;
    };

    const post = (path, data) => api(path, { method: "POST", body: JSON.stringify(data) });

    const showToast = (message, isError = false) => {
        clearTimeout(state.toastTimer);
        toast.textContent = message;
        toast.className = `toast show${isError ? " error" : ""}`;
        state.toastTimer = setTimeout(() => { toast.className = "toast"; }, 3800);
    };

    const initials = (name) => String(name || "User").trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join("");
    const dateLabel = (value) => {
        if (!value) return "—";
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? escapeHtml(value) : new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(date);
    };
    const statusBadge = (status) => `<span class="status ${escapeHtml(String(status || "").toLowerCase())}">${escapeHtml(status || "Unknown")}</span>`;
    const pageTitle = {
        dashboard: ["Overview", "A snapshot of your barangay services."],
        requests: ["Certificate requests", "Submit and keep track of your certificate requests."],
        residents: ["Residents", "Register and manage resident information."],
        accounts: ["Staff access", "Assign BHW access to resident accounts."],
        certificate: ["Certificate generation", "Prepare and print the approved certificate."],
        report: ["Yearly report", "Completed certificate requests by year."],
        profile: ["My account", "Your account, verification status, and security settings."]
    };

    const renderAuth = (signup = false) => {
        root.innerHTML = `
            <main class="auth-shell">
                <aside class="auth-aside">
                    <div class="brand"><span class="brand-mark">B</span><span class="brand-name">BeSCMS</span></div>
                    <div class="auth-copy">
                        <p class="eyebrow">Barangay Polo · e-Services</p>
                        <h1>Local services, a little closer.</h1>
                        <p>Request barangay certificates, check your account, and stay up to date with your community services.</p>
                    </div>
                    <p class="auth-footer">Barangay Polo Service &amp; Certificate Management System</p>
                </aside>
                <section class="auth-content">
                    <div class="auth-card">
                        <p class="eyebrow">Welcome to BeSCMS</p>
                        <h2>${signup ? "Create your account" : "Sign in to continue"}</h2>
                        <p class="subheading">${signup ? "Use your registered resident name to set up online access." : "Sign in to manage your barangay services."}</p>
                        <form id="auth-form" data-form="${signup ? "signup" : "login"}">
                            ${signup ? `<div class="field"><label for="full-name">Registered full name</label><input id="full-name" name="full_name" autocomplete="name" placeholder="As registered at the barangay" required></div>` : ""}
                            <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" required></div>
                            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="${signup ? "new-password" : "current-password"}" minlength="6" placeholder="Enter your password" required></div>
                            ${signup ? `<div class="notice"><span>i</span><span>Online signup is available to residents already registered in the barangay. New accounts require verification before certificate requests can be submitted.</span></div>` : ""}
                            <div class="form-actions"><button class="button button-primary" type="submit">${signup ? "Create account" : "Sign in"} <span aria-hidden="true">→</span></button></div>
                        </form>
                        <p class="auth-switch">${signup ? "Already have an account?" : "New to online services?"} <button class="text-button" type="button" data-action="auth-toggle">${signup ? "Sign in" : "Create account"}</button></p>
                    </div>
                </section>
            </main>`;
    };

    const navigation = () => {
        const staff = ["admin", "bhw"].includes(state.user.role);
        const items = [
            ["dashboard", "⌂", "Overview"],
            ["requests", "▤", staff ? "Requests queue" : "My requests"]
        ];
        if (staff) items.push(["residents", "♙", "Residents"]);
        if (state.user.role === "admin") items.push(["accounts", "◌", "Staff access"]);
        if (staff) items.push(["report", "▥", "Yearly report"]);
        items.push(["profile", "◉", "My account"]);
        const uniqueItems = items.filter((item, index) => items.findIndex((candidate) => candidate[0] === item[0] && candidate[2] === item[2]) === index);
        return uniqueItems.map(([page, icon, label]) => `
            <button class="nav-button${state.page === page ? " active" : ""}" type="button" data-page="${page}" aria-current="${state.page === page ? "page" : "false"}">
                <span class="nav-icon" aria-hidden="true">${icon}</span><span class="nav-text">${label}</span>
            </button>`).join("");
    };

    const renderShell = (content) => {
        const name = state.profile?.full_name || state.user?.email || "Account";
        const currentTitle = pageTitle[state.page]?.[0] || "Overview";
        root.innerHTML = `
            <div class="workspace">
                <aside class="sidebar">
                    <div class="brand"><span class="brand-mark">B</span><span class="brand-name">BeSCMS</span></div>
                    <p class="nav-label">Workspace</p>
                    <nav class="nav-list" aria-label="Main navigation">${navigation()}</nav>
                    <div class="sidebar-bottom">
                        <div class="account-mini">
                            <span class="avatar">${escapeHtml(initials(name))}</span>
                            <div class="account-mini-copy"><strong>${escapeHtml(name)}</strong><span>${escapeHtml(state.user.role)}</span></div>
                            <button class="logout-button" type="button" data-action="logout" aria-label="Sign out" title="Sign out">↪</button>
                        </div>
                    </div>
                </aside>
                <main class="main-area">
                    <header class="topbar"><span class="topbar-user">${escapeHtml(currentTitle)}</span><span class="topbar-date">${new Intl.DateTimeFormat(undefined, { dateStyle: "full" }).format(new Date())}</span></header>
                    <section class="page-content">${content}</section>
                </main>
            </div>`;
    };

    const heading = (title, description, action = "") => `
        <div class="page-heading"><div><p class="eyebrow">Barangay services</p><h1>${title}</h1><p>${description}</p></div>${action}</div>`;

    const statCard = (icon, label, value, note) => `
        <article class="stat-card"><div class="stat-label"><span class="stat-icon">${icon}</span>${label}</div><p class="stat-value">${escapeHtml(value)}</p><p class="stat-note">${note}</p></article>`;

    const table = (headers, rows, emptyMessage) => `
        <div class="table-wrap"><table><thead><tr>${headers.map((header) => `<th>${header}</th>`).join("")}</tr></thead>
        <tbody>${rows || `<tr><td colspan="${headers.length}" class="empty-state">${emptyMessage}</td></tr>`}</tbody></table></div>`;

    const requestRows = (requests, admin = false) => requests.map((request) => `
        <tr>
            <td><strong>#${escapeHtml(request.id)}</strong></td>
            ${admin ? `<td>${escapeHtml(request.full_name || "Resident")}</td>` : ""}
            <td>${escapeHtml(request.certificate_type || "—")}</td>
            <td>${escapeHtml(request.purpose || "—")}</td>
            <td>${escapeHtml(request.quantity ?? "—")}</td>
            <td>${statusBadge(request.status)}</td>
            <td>${dateLabel(request.requested_at)}</td>
            <td>${escapeHtml(request.admin_notes || "—")}</td>
            ${admin ? `<td><div class="row-actions">${request.status === "pending" ? `<button class="button button-secondary button-small" type="button" data-action="approve-request" data-id="${escapeHtml(request.id)}">Approve</button><button class="button button-danger button-small" type="button" data-action="reject-request" data-id="${escapeHtml(request.id)}">Reject</button>` : request.status === "approved" ? `<button class="button button-secondary button-small" type="button" data-action="print-request" data-id="${escapeHtml(request.id)}">Print</button><button class="button button-primary button-small" type="button" data-action="complete-request" data-id="${escapeHtml(request.id)}">Complete</button>` : ""}</div></td>` : `<td><div class="row-actions">${request.status === "pending" ? `<button class="button button-secondary button-small" type="button" data-action="edit-request" data-id="${escapeHtml(request.id)}" data-quantity="${escapeHtml(request.quantity)}">Edit</button><button class="button button-danger button-small" type="button" data-action="cancel-request" data-id="${escapeHtml(request.id)}">Cancel</button>` : request.status === "cancelled" ? `<button class="button button-secondary button-small" type="button" data-action="edit-request" data-id="${escapeHtml(request.id)}" data-quantity="${escapeHtml(request.quantity)}">Resubmit</button>` : "—"}</div></td>`}
        </tr>`).join("");

    const dashboardPage = async () => {
        const admin = ["admin", "bhw"].includes(state.user.role);
        const [profileResponse, requestResponse, dashboardResponse, verificationResponse, residentResponse] = await Promise.all([
            api("auth/profile"),
            api(admin ? "certificate-processing/pending" : "certificate-request/my-requests"),
            admin ? api("certificate-processing/dashboard") : Promise.resolve(null),
            admin ? api("auth/verify/pending") : Promise.resolve(null),
            state.user.role === "admin" ? api("resident/list") : Promise.resolve(null)
        ]);
        state.profile = Array.isArray(profileResponse.Data) ? profileResponse.Data[0] || null : profileResponse.Data || null;
        const requests = requestResponse.Data || [];
        const counts = dashboardResponse?.Data || {};
        const verifications = verificationResponse?.Data || [];
        const verification = state.profile?.verification_status || state.user.verification_status || "pending";
        const residentCount = residentResponse?.Data?.filter((resident) => resident && resident.id).length ?? "—";
        const overview = admin
            ? `<div class="cards">${statCard("▤", "Pending requests", counts.pending_requests ?? 0, "Waiting for staff review")}${statCard("◉", "To verify", counts.pending_verifications ?? 0, "Accounts awaiting document review")}${statCard("♙", "Total residents", residentCount, "Registered in the barangay")}</div>`
            : `<div class="cards">${statCard("▤", "Your requests", requests.length, "Requests on your account")}${statCard("◉", "Verification", verification, "Account verification status")}${statCard("♙", "Account type", "Resident", "Barangay online services")}</div>`;
        const notice = !admin && verification !== "approved"
            ? `<div class="notice"><span>i</span><span>Your account verification is <strong>${escapeHtml(verification)}</strong>. You can submit certificate requests once your account has been approved. Upload your verification document in My account.</span></div>`
            : "";
        const columns = admin ? ["Request", "Resident", "Certificate", "Purpose", "Qty", "Status", "Submitted", "Staff notes", "Actions"] : ["Request", "Certificate", "Purpose", "Qty", "Status", "Submitted", "Staff notes", "Actions"];
        const rows = requestRows(requests.slice(0, 6), admin);
        const content = `${heading("Good day, " + escapeHtml((state.profile?.full_name || state.user.email).split(" ")[0]), "Here’s what’s happening with your barangay services today.", `<button class="button button-primary" type="button" data-page="requests">${admin ? "Review requests →" : "＋ New request"}</button>`)}
            ${overview}${notice}
            ${admin ? `<section class="panel"><div class="panel-header"><h2>Accounts to verify</h2><span class="status">${verifications.length} awaiting review</span></div>${table(["Resident", "Email", "Submitted", "ID document", "Actions"], verifications.map((account) => `<tr><td><strong>${escapeHtml(account.full_name)}</strong></td><td>${escapeHtml(account.email)}</td><td>${dateLabel(account.created_at)}</td><td>${account.verification_doc ? `<a class="button-link" href="${apiBase}auth/verify/document?user_id=${encodeURIComponent(account.id)}" target="_blank" rel="noopener">View document ↗</a>` : "Not uploaded"}</td><td><div class="row-actions"><button class="button button-primary button-small" type="button" data-action="approve-verification" data-id="${escapeHtml(account.id)}" ${account.verification_doc ? "" : "disabled"}>Accept</button><button class="button button-danger button-small" type="button" data-action="reject-verification" data-id="${escapeHtml(account.id)}">Reject</button></div></td></tr>`).join(""), "No accounts are waiting for verification.")}</section>` : ""}
            <section class="panel"><div class="panel-header"><h2>${admin ? "Requests awaiting review" : "Recent requests"}</h2><button class="button-link" type="button" data-page="requests">View all →</button></div>
            ${table(columns, rows, admin ? "There are no requests waiting for review." : "You haven’t submitted any certificate requests yet.")}</section>`;
        renderShell(content);
    };

    const requestsPage = async () => {
        const admin = ["admin", "bhw"].includes(state.user.role);
        const filter = state.requestFilter ? `?status=${encodeURIComponent(state.requestFilter)}` : "";
        const response = await api(admin
            ? `certificate-processing/requests${filter}`
            : `certificate-request/my-requests${filter}`);
        const requests = response.Data || [];
        const form = !admin
            ? `<section class="panel"><div class="panel-header"><h2>New certificate request</h2></div><div class="panel-body">
                ${state.profile?.verification_status !== "approved" ? `<p class="panel-intro">Certificate requests are available after your account verification is approved. You can upload your verification document from My account.</p>` : `
                <p class="panel-intro">Tell us what certificate you need and why. Your request will be sent to barangay staff for review.</p>
                <form data-form="request"><div class="form-grid">
                    <div class="field"><label for="certificate-type">Certificate type</label><select id="certificate-type" name="certificate_type" required><option value="">Choose a certificate</option><option>Barangay Clearance</option><option>Certificate of Residency</option><option>Certificate of Indigency</option><option>Business Clearance</option><option>Other</option></select></div>
                    <div class="field"><label for="quantity">Number of copies</label><input id="quantity" name="quantity" type="number" min="1" max="20" value="1" required></div>
                    <div class="field full"><label for="purpose">Purpose</label><textarea id="purpose" name="purpose" maxlength="500" placeholder="What will the certificate be used for?" required></textarea></div>
                </div><div class="form-actions"><button class="button button-primary" type="submit">Submit request <span aria-hidden="true">→</span></button></div></form>`}
                </div></section>`
            : `<div class="notice success"><span>i</span><span>Review incoming requests, add an optional note, and process approved certificates when residents claim them.</span></div>`;
        const columns = admin
            ? ["Request", "Resident", "Certificate", "Purpose", "Qty", "Status", "Submitted", "Staff notes", "Actions"]
            : ["Request", "Certificate", "Purpose", "Qty", "Status", "Submitted", "Staff notes", "Actions"];
        const statuses = ["", "pending", "approved", "completed", "cancelled", "rejected"];
        const filterControl = `<div class="status-tabs" role="tablist" aria-label="Filter requests">${statuses.map((status) => {
            const label = status ? status[0].toUpperCase() + status.slice(1) : "All";
            return `<button class="status-tab${state.requestFilter === status ? " active" : ""}" type="button" role="tab" aria-selected="${state.requestFilter === status}" data-action="filter-requests" data-status="${status}">${label}</button>`;
        }).join("")}</div>`;
        renderShell(`${heading(admin ? "Requests queue" : "Certificate requests", admin ? "Review certificate requests from residents." : "Request a certificate and follow its progress.", filterControl)}
            ${form}<section class="panel"><div class="panel-header"><h2>${admin ? (state.requestFilter || "All") + " requests" : "My requests"}</h2><span class="status">${requests.length} ${requests.length === 1 ? "request" : "requests"}</span></div>${table(columns, requestRows(requests, admin), admin ? "No requests found for this status." : "No requests found for this status.")}</section>`);
    };

    const residentsPage = async () => {
        const isAdmin = state.user.role === "admin";
        const response = isAdmin
            ? await api("resident/list")
            : state.residentSearch
                ? await api(`resident/search?search=${encodeURIComponent(state.residentSearch)}`)
                : { Data: [] };
        const residents = (response.Data || []).filter((resident) => resident && typeof resident === "object" && resident.id);
        const rows = residents.map((resident) => `<tr>
            <td><strong>${escapeHtml(resident.full_name || "—")}</strong></td>
            <td>${escapeHtml(resident.contact_number || "—")}</td>
            <td>${escapeHtml(resident.address || "—")}</td>
            <td>${dateLabel(resident.birthdate)}</td>
            <td>${escapeHtml(resident.gender || "—")}</td>
            <td>${escapeHtml(resident.registered_voter || "—")}</td>
            <td>${escapeHtml(resident.added_by || "—")}</td>
            <td>${escapeHtml(resident.last_updated_by || "—")}</td>
            <td><button class="button button-secondary button-small" type="button" data-action="edit-resident" data-id="${escapeHtml(resident.id)}" data-name="${escapeHtml(resident.full_name)}" data-birthdate="${escapeHtml(resident.birthdate)}" data-address="${escapeHtml(resident.address)}" data-contact="${escapeHtml(resident.contact_number)}" data-gender="${escapeHtml(resident.gender)}" data-voter="${escapeHtml(resident.registered_voter)}">Edit</button></td>
        </tr>`).join("");
        const form = `<section class="panel"><div class="panel-header"><h2 data-resident-form-title>Register a resident</h2></div><div class="panel-body">
            <p class="panel-intro">Residents must be registered here before they can create an online account.</p>
            <form data-form="resident"><input type="hidden" name="id">
                <div class="form-grid">
                    <div class="field"><label for="resident-name">Full name</label><input id="resident-name" name="full_name" required></div>
                    <div class="field"><label for="resident-birthdate">Birth date</label><input id="resident-birthdate" name="birthdate" type="date"></div>
                    <div class="field"><label for="resident-contact">Contact number</label><input id="resident-contact" name="contact_number" type="tel"></div>
                    <div class="field"><label for="resident-gender">Gender</label><select id="resident-gender" name="gender"><option value="">Not specified</option><option value="male">Male</option><option value="female">Female</option></select></div>
                    <div class="field"><label for="resident-voter">Registered voter</label><select id="resident-voter" name="registered_voter"><option value="no">No</option><option value="yes">Yes</option></select></div>
                    <div class="field"><label for="resident-address">Address</label><input id="resident-address" name="address"></div>
                </div>
                <div class="form-actions"><button class="button button-primary" type="submit" data-submit-label="Save resident">Save resident</button><button class="button button-secondary" type="button" data-action="reset-resident">Clear</button></div>
            </form></div></section>`;
        const tools = isAdmin
            ? `<div class="toolbar"><button class="button button-secondary" type="button" data-action="export-residents">Export list</button></div>`
            : `<form class="toolbar" data-form="resident-search"><input name="search" aria-label="Search residents" placeholder="Search resident name or address" value="${escapeHtml(state.residentSearch || "")}"><button class="button button-secondary" type="submit">Search</button></form>`;
        renderShell(`${heading("Residents", isAdmin ? "Register residents and manage barangay records." : "Register a resident or search existing records.", tools)}
            ${form}<section class="panel"><div class="panel-header"><h2>Resident directory</h2><span class="status">${residents.length} records</span></div>
            ${table(["Full name", "Contact", "Address", "Birth date", "Gender", "Voter", "Added by", "Updated by", "Actions"], rows, isAdmin ? "No resident records found." : "Enter a resident's name or address to search.")}</section>`);
    };

    const reportPage = async () => {
        const year = state.reportYear || new Date().getFullYear();
        const response = await api(`certificate-processing/report/yearly?year=${encodeURIComponent(year)}`);
        const reports = (response.Data || []).filter((item) => item && typeof item === "object" && Object.keys(item).length > 1);
        const rows = reports.map((item) => `<tr>${Object.values(item).map((value) => `<td>${escapeHtml(value)}</td>`).join("")}</tr>`).join("");
        const headers = reports.length ? Object.keys(reports[0]) : ["Report"];
        renderShell(`${heading("Yearly report", `Completed transactions recorded in ${escapeHtml(year)}.`)}
            <section class="panel"><div class="panel-header"><h2>Generate transaction list</h2><form class="toolbar" data-form="report"><label for="report-year">Year</label><input id="report-year" name="year" type="number" min="2000" max="${new Date().getFullYear()}" value="${escapeHtml(year)}" required><button class="button button-primary" type="submit">Generate list</button></form></div>
            <div class="panel-body"><p class="panel-intro">${reports.length} completed ${reports.length === 1 ? "transaction" : "transactions"} for ${escapeHtml(year)}.</p></div>
            ${table(headers, rows, `No completed transactions were found for ${escapeHtml(year)}.`)}</section>`);
    };

    const accountsPage = async () => {
        const response = await api("auth/accounts");
        const accounts = (response.Data || []).filter((account) => account && account.id);
        const rows = accounts.map((account) => `<tr>
            <td><strong>${escapeHtml(account.full_name)}</strong></td>
            <td>${escapeHtml(account.email)}</td>
            <td>${statusBadge(account.verification_status)}</td>
            <td>${account.role === "admin" ? `<span class="status">Administrator</span>` : `<form class="toolbar" data-form="account-role"><input type="hidden" name="user_id" value="${escapeHtml(account.id)}">
                <select name="role" aria-label="Role for ${escapeHtml(account.full_name)}"><option value="resident" ${account.role === "resident" ? "selected" : ""}>Resident</option><option value="bhw" ${account.role === "bhw" ? "selected" : ""}>BHW</option></select>
                <button class="button button-secondary button-small" type="submit">Save role</button></form>`}</td>
        </tr>`).join("");
        renderShell(`${heading("Staff access", "Assign Barangay Health Worker access to resident accounts.")}
            <div class="notice" style="margin-bottom:20px"><span>i</span><span>Residents need an account before they can be assigned as BHWs. BHWs can register and update resident records and review requests. Administrator access cannot be changed here.</span></div>
            <section class="panel"><div class="panel-header"><h2>Resident accounts</h2><span class="status">${accounts.length} accounts</span></div>
            ${table(["Resident", "Email", "Verification", "Access role"], rows, "No resident accounts found.")}</section>`);
    };

    const profilePage = async () => {
        const response = await api("auth/profile");
        state.profile = Array.isArray(response.Data) ? response.Data[0] || null : response.Data || null;
        const profile = state.profile || {};
        const verification = profile.verification_status || state.user.verification_status || "pending";
        const details = [
            ["Full name", profile.full_name], ["Email address", profile.email],
            ["Contact number", profile.contact_number], ["Address", profile.address],
            ["Birth date", profile.birthdate], ["Gender", profile.gender],
            ["Registered", dateLabel(profile.created_at)]
        ].map(([label, value]) => `<div class="profile-item"><span>${label}</span><strong>${escapeHtml(value || "—")}</strong></div>`).join("");
        renderShell(`${heading("My account", "Manage your profile and account security.")}
            <div class="profile-grid">
                <section class="panel"><div class="panel-header"><h2>Personal details</h2>${statusBadge(verification)}</div><div class="panel-body"><div class="profile-list">${details}</div></div></section>
                <div>
                    ${verification !== "approved" ? `<section class="panel"><div class="panel-header"><h2>Identity verification</h2></div><div class="panel-body">
                        <p class="panel-intro">Upload a photo or PDF of your identification document for account verification. Accepted formats: JPG, PNG, or PDF.</p>
                        <form data-form="upload-id"><div class="field"><label for="verification-doc">ID document</label><input id="verification-doc" name="verification_doc" type="file" accept=".jpg,.jpeg,.png,.pdf" required></div>
                        <div class="form-actions"><button class="button button-primary" type="submit">Upload document</button></div></form>
                    </div></section>` : ""}
                    <section class="panel"><div class="panel-header"><h2>Change password</h2></div><div class="panel-body">
                        <form data-form="change-password"><div class="field"><label for="old-password">Current password</label><input id="old-password" name="old_password" type="password" autocomplete="current-password" required></div>
                            <div class="field"><label for="new-password">New password</label><input id="new-password" name="new_password" type="password" minlength="6" autocomplete="new-password" required></div>
                            <div class="form-actions"><button class="button button-secondary" type="submit">Update password</button></div></form>
                    </div></section>
                </div>
            </div>`);
    };

    const certificatePage = async () => {
        const requestId = state.selectedRequestId;
        if (!requestId) throw new Error("Select an approved request to generate its certificate.");
        const certificateUrl = `${apiBase}certificate-processing/print?request_id=${encodeURIComponent(requestId)}`;
        renderShell(`${heading("Certificate generation", "Open the approved certificate to review and print it for signing.", `<a class="button button-primary" href="${certificateUrl}" target="_blank" rel="noopener">Open printable certificate ↗</a>`)}
            <section class="panel"><div class="panel-body"><p class="panel-intro">The print-ready certificate opens in a separate tab. Check the resident details and certificate purpose before printing.</p><p class="eyebrow">Request #${escapeHtml(requestId)}</p><a class="button-link" href="${certificateUrl}" target="_blank" rel="noopener">Open certificate preview ↗</a></div></section>
            <button class="button button-secondary" type="button" data-page="requests">← Back to requests</button>`);
    };

    const pageLoaders = { dashboard: dashboardPage, requests: requestsPage, residents: residentsPage, accounts: accountsPage, report: reportPage, profile: profilePage, certificate: certificatePage };
    const navigate = async (page) => {
        if (!pageLoaders[page]) return;
        if ((page === "accounts" && state.user.role !== "admin") ||
            (page === "residents" && !["admin", "bhw"].includes(state.user.role)) ||
            (page === "report" && !["admin", "bhw"].includes(state.user.role))) {
            showToast("Your account does not have access to that page.", true);
            return;
        }
        state.page = page;
        root.innerHTML = `<div class="loading-screen"><span class="spinner"></span><span>Loading ${escapeHtml(pageTitle[page][0].toLowerCase())}...</span></div>`;
        try {
            await pageLoaders[page]();
        } catch (error) {
            renderShell(`${heading(pageTitle[page][0], pageTitle[page][1])}<div class="notice error"><span>!</span><span>${escapeHtml(error.message)}</span></div>`);
            showToast(error.message, true);
        }
    };

    const setBusy = (form, busy) => {
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = busy;
            if (busy) {
                button.dataset.label = button.textContent;
                button.textContent = "Please wait...";
            } else if (button.dataset.label) {
                button.textContent = button.dataset.label;
                delete button.dataset.label;
            }
        }
    };

    root.addEventListener("submit", async (event) => {
        const form = event.target.closest("form[data-form]");
        if (!form) return;
        event.preventDefault();
        const kind = form.dataset.form;
        const values = Object.fromEntries(new FormData(form).entries());
        setBusy(form, true);
        try {
            if (kind === "login") {
                const result = await post("auth/login", { email: values.email, password: values.password });
                state.user = result.Data;
                state.page = "dashboard";
                await navigate("dashboard");
            } else if (kind === "signup") {
                const result = await post("auth/signup", { full_name: values.full_name, email: values.email, password: values.password });
                if (String(result.Message || "").startsWith("Error:")) {
                    throw new Error(result.Message);
                }
                showToast(result.Message || "Account created. You can now sign in.");
                renderAuth(false);
            } else if (kind === "request") {
                const result = await post("certificate-request/submit", {
                    certificate_type: values.certificate_type,
                    purpose: values.purpose,
                    quantity: Number(values.quantity)
                });
                showToast(result.Message || "Request submitted.");
                await navigate("requests");
            } else if (kind === "upload-id") {
                const data = new FormData(form);
                const result = await api("auth/upload-id", { method: "POST", body: data });
                showToast(result.Message || "Verification document uploaded.");
                await navigate("profile");
            } else if (kind === "change-password") {
                const result = await post("auth/change-password", {
                    old_password: values.old_password,
                    new_password: values.new_password
                });
                showToast(result.Message || "Password changed.");
                form.reset();
            } else if (kind === "resident") {
                const payload = {
                    full_name: values.full_name,
                    birthdate: values.birthdate || null,
                    address: values.address || "",
                    contact_number: values.contact_number || "",
                    gender: values.gender || null,
                    registered_voter: values.registered_voter || "no"
                };
                const editing = Boolean(values.id);
                if (editing) {
                    payload.id = values.id;
                    payload.updated_by = state.user.role === "admin" ? "Admin" : "BHW";
                } else {
                    payload.added_by = state.user.role === "admin" ? "Admin" : "BHW";
                }
                const result = await post(editing ? "resident/edit" : "resident/add", payload);
                showToast(result.Message || (editing ? "Resident updated." : "Resident added."));
                await navigate("residents");
            } else if (kind === "resident-search") {
                state.residentSearch = String(values.search || "").trim();
                await navigate("residents");
            } else if (kind === "report") {
                state.reportYear = Number(values.year);
                await navigate("report");
            } else if (kind === "account-role") {
                const result = await post("auth/accounts/role", {
                    user_id: values.user_id,
                    role: values.role
                });
                showToast(result.Message || "Account role updated.");
                await navigate("accounts");
            }
        } catch (error) {
            showToast(error.message, true);
        } finally {
            if (form.isConnected) setBusy(form, false);
        }
    });

    root.addEventListener("click", async (event) => {
        const target = event.target.closest("[data-action], [data-page]");
        if (!target) return;
        if (target.dataset.page) {
            await navigate(target.dataset.page);
            return;
        }
        const action = target.dataset.action;
        if (action === "filter-requests") {
            state.requestFilter = target.dataset.status || "";
            await navigate("requests");
            return;
        }
        if (action === "auth-toggle") {
            renderAuth(!document.querySelector('[data-form="signup"]'));
            return;
        }
        if (action === "logout") {
            try {
                await post("auth/logout", {});
                state.user = null;
                state.profile = null;
                state.page = "dashboard";
                renderAuth(false);
                showToast("You have signed out.");
            } catch (error) {
                showToast(error.message, true);
            }
            return;
        }
        if (action === "reset-resident") {
            const form = root.querySelector('form[data-form="resident"]');
            if (form) {
                form.reset();
                form.elements.id.value = "";
                root.querySelector("[data-resident-form-title]").textContent = "Register a resident";
                form.querySelector('[data-submit-label]').textContent = "Save resident";
            }
            return;
        }
        if (action === "export-residents") {
            target.disabled = true;
            try {
                const result = await api("resident/export");
                const residents = result.Data || [];
                const fields = ["full_name", "birthdate", "address", "contact_number", "gender", "registered_voter", "added_by"];
                const csv = [fields.join(","), ...residents.map((resident) => fields.map((field) => `"${String(resident[field] ?? "").replace(/"/g, '""')}"`).join(","))].join("\r\n");
                const url = URL.createObjectURL(new Blob([csv], { type: "text/csv;charset=utf-8" }));
                const link = document.createElement("a");
                link.href = url;
                link.download = `bescms-residents-${new Date().toISOString().slice(0, 10)}.csv`;
                link.click();
                URL.revokeObjectURL(url);
                showToast("Resident list exported.");
            } catch (error) {
                showToast(error.message, true);
            } finally {
                target.disabled = false;
            }
            return;
        }
        if (action === "edit-resident") {
            const form = root.querySelector('form[data-form="resident"]');
            if (!form) return;
            form.elements.id.value = target.dataset.id || "";
            form.elements.full_name.value = target.dataset.name || "";
            form.elements.birthdate.value = target.dataset.birthdate || "";
            form.elements.address.value = target.dataset.address || "";
            form.elements.contact_number.value = target.dataset.contact || "";
            form.elements.gender.value = target.dataset.gender || "";
            form.elements.registered_voter.value = target.dataset.voter || "no";
            root.querySelector("[data-resident-form-title]").textContent = "Update resident information";
            form.querySelector('[data-submit-label]').textContent = "Save changes";
            form.scrollIntoView({ behavior: "smooth", block: "start" });
            return;
        }
        const id = target.dataset.id;
        if (!id) return;
        if (action === "edit-request") {
            const currentQuantity = Number(target.dataset.quantity || 1);
            const value = window.prompt("How many copies do you need?", String(currentQuantity));
            if (value === null) return;
            const quantity = Number(value);
            if (!Number.isInteger(quantity) || quantity < 1 || quantity > 20) {
                showToast("Enter a quantity between 1 and 20.", true);
                return;
            }
            target.disabled = true;
            try {
                const result = await post("certificate-request/resubmit", { id, quantity });
                showToast(result.Message || "Request updated.");
                await navigate("requests");
            } catch (error) {
                showToast(error.message, true);
                target.disabled = false;
            }
            return;
        }
        if (action === "approve-verification" || action === "reject-verification") {
            const isApprove = action === "approve-verification";
            let reason = "";
            if (isApprove) {
                if (!window.confirm("Approve this resident's account verification?")) return;
            } else {
                reason = window.prompt("Enter a reason for rejecting this verification:");
                if (reason === null) return;
                if (!reason.trim()) {
                    showToast("Please provide a rejection reason.", true);
                    return;
                }
            }
            target.disabled = true;
            try {
                const result = await post(isApprove ? "auth/verify/approve" : "auth/verify/reject",
                    isApprove ? { user_id: id } : { user_id: id, reason });
                showToast(result.Message || (isApprove ? "Account approved." : "Verification rejected."));
                await navigate("dashboard");
            } catch (error) {
                showToast(error.message, true);
                target.disabled = false;
            }
            return;
        }
        if (action === "print-request") {
            state.selectedRequestId = id;
            await navigate("certificate");
            return;
        }
        let endpoint;
        let payload;
        let confirmation;
        if (action === "cancel-request") {
            endpoint = "certificate-request/cancel";
            payload = { id };
            confirmation = "Cancel this pending request?";
        } else if (action === "approve-request") {
            if (!window.confirm("Approve this request and continue to certificate generation?")) return;
            const adminNotes = window.prompt("Optional message for the resident:", "");
            if (adminNotes === null) return;
            endpoint = "certificate-processing/approve";
            payload = { request_id: id, admin_notes: adminNotes };
        } else if (action === "reject-request") {
            if (!window.confirm("Reject this certificate request?")) return;
            const adminNotes = window.prompt("Optional rejection note for the resident:", "");
            if (adminNotes === null) return;
            endpoint = "certificate-processing/reject";
            payload = { request_id: id, admin_notes: adminNotes };
        } else if (action === "complete-request") {
            endpoint = "certificate-processing/complete";
            payload = { request_id: id };
            confirmation = "Mark this request as completed?";
        } else {
            return;
        }
        if (confirmation && !window.confirm(confirmation)) return;
        target.disabled = true;
        try {
            const result = await post(endpoint, payload);
            showToast(result.Message || "Request updated.");
            if (action === "approve-request") {
                state.selectedRequestId = id;
                await navigate("certificate");
            } else {
                await navigate("requests");
            }
        } catch (error) {
            showToast(error.message, true);
            target.disabled = false;
        }
    });

    const restoreSession = async () => {
        renderAuth(false);
        try {
            const response = await api("auth/profile");
            const profile = Array.isArray(response.Data) ? response.Data[0] || {} : response.Data || {};
            if (!profile.id) return;
            state.user = {
                user_id: profile.id,
                role: profile.role || "resident",
                verification_status: profile.verification_status,
                email: profile.email
            };
            state.profile = profile;
            await navigate("dashboard");
        } catch (error) {
            renderAuth(false);
            if (error.message !== "Not logged in.") {
                showToast(error.message, true);
            }
        }
    };

    restoreSession();
})();
