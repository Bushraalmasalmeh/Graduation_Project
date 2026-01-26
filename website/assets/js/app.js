// assets/js/app.js
document.addEventListener('DOMContentLoaded', async () => {
    // 1. Initialize client-only UI pieces first
    setupGlobalUI();

    // 2. Attempt to fetch user data quietly (do not block UI if it fails)
    try {
        await loadCurrentUserProfile();
    } catch (e) {
        console.warn("⚠️ Sidebar profile unavailable (Server down or CORS issue)");
    }

    // 3. Run the router for the current page
    initializePageRouter();
});

async function initializePageRouter() {
    const path = window.location.pathname;
    const page = path.split("/").pop();

    switch (page) {
        case 'forgot-password.html':
            setupForgotPasswordPage();
            break;
        case 'reset-password.html': // Verification code page
            setupVerifyCodePage();
            break;
        case 'set-new-password.html':
            setupSetNewPasswordPage();
            break;
        // Other pages (Dashboard, Settings, etc.)
        default:
            console.log("Routing to default or home...");
    }

    const pageTargets = {
        // 'consumptionChart': initializeReportsPage,
        
        'users-table-body': loadUsersPage,
        'chargers-table-body': loadChargersPage,
        'stats-total-users': loadDashboardData,
        'profile-name': loadUserProfilePage,
        'add-charger-form': setupAddChargerPage,
        'edit-charger-form': setupEditChargerPage,
        'bookings-table-body': loadBookingsPage,
        'detail-id': setupBookingDetailsPage,
        'support-table-body': loadSupportMessages,
        'profile-form': setupAccountSettingsPage,      // settings.html
        'change-password-form': setupSecurityPage,     // settings-security.html
        'system-settings-form': setupSystemSettings,
        // Shared elements (sidebar, etc.) should stay last
        'stats-total-users': loadDashboardData,
        'profile-name': loadUserProfilePage


    };

    for (const [id, initFunction] of Object.entries(pageTargets)) {
        if (document.getElementById(id)) {
            console.log(`🎯 Found Target: ${id}`); // Logs the ID that triggered initialization
            try {
                await initFunction();
            } catch (error) {
                console.error(`❌ Page Logic Error (${id}):`, error.message);
            }
            break;
        }
    }

}
function setupGlobalUI() {
    // Logic for mobile sidebar toggle (Responsive)
    const menuToggle = document.getElementById('menu-toggle');
    const wrapper = document.getElementById('wrapper');
    if (menuToggle && wrapper) {
        menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            wrapper.classList.toggle('toggled');
        });
    }

    // Logic for logout buttons
    document.querySelectorAll('.logout-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            API.handleSessionExpired(); // Uses our central logout logic
        });
    });
}

async function loadCurrentUserProfile() {
    const nameDisplay = document.getElementById('user-name');
    const emailDisplay = document.getElementById('user-email');

    // Selectors for settings form (if exists)
    const inputFields = {
        name: document.getElementById('input-name'),
        email: document.getElementById('input-email'),
        job: document.getElementById('input-job-number'),
        phone: document.getElementById('input-phone')
    };

    try {
        // Fetch fresh data from API
        const user = await API.get('/api/me');

        // 1. Update Sidebar Information (Safely escaped)
        if (nameDisplay) nameDisplay.innerText = Utils.escapeHTML(user.name);
        if (emailDisplay) emailDisplay.innerText = Utils.escapeHTML(user.email);

        // 2. Update Settings Form (if we are on settings.html)
        if (inputFields.name) inputFields.name.value = user.name || '';
        if (inputFields.email) inputFields.email.value = user.email || '';
        if (inputFields.job) inputFields.job.value = user.job_number || '';
        if (inputFields.phone) inputFields.phone.value = user.phone || '';

        // Update local storage cache to keep it in sync
        localStorage.setItem(CONFIG.USER_DATA_KEY, JSON.stringify(user));

    } catch (error) {
        console.warn("⚠️ Sidebar profile failed to load:", error.message);

        // Fallback: Use data from localStorage if API fails
        const cachedUser = JSON.parse(localStorage.getItem(CONFIG.USER_DATA_KEY));
        if (cachedUser) {
            if (nameDisplay) nameDisplay.innerText = Utils.escapeHTML(cachedUser.name);
            if (emailDisplay) emailDisplay.innerText = Utils.escapeHTML(cachedUser.email);
        }
    }
}
/**
 * Loads and renders all dashboard statistics and recent activities.
 */
async function loadDashboardData() {
    const selectors = {
        totalUsers: 'stats-total-users',
        totalChargers: 'stats-total-chargers',
        activeSessions: 'stats-active-sessions',
        tableBody: 'recent-bookings-body'
    };

    console.log("🚀 Initializing Dashboard Data Fetch...");

    try {
        // 1. Concurrent fetching for speed
        const [stats, bookingsResponse] = await Promise.all([
            API.get('/api/admin/dashboard'),
            API.get('/api/admin/bookings')
        ]);

        const bookings = bookingsResponse.bookings || bookingsResponse.data || [];

        // 2. Render Global Statistics
        updateElementText(selectors.totalUsers, stats.total_users || 0);
        updateElementText(selectors.totalChargers, stats.total_stations || stats.total_chargers || 0);

        // Compute active sessions from the bookings array
        const activeCount = bookings.filter(b => (b.status || '').toLowerCase() === 'active').length;
        updateElementText(selectors.activeSessions, activeCount);

        // 3. Render Recent Bookings Table
        renderRecentBookingsTable(selectors.tableBody, bookings);

    } catch (error) {
        console.error('❌ Dashboard Loading Error:', error);
        // Show an error row in the table for the user
        const table = document.getElementById(selectors.tableBody);
        if (table) table.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Error loading data.</td></tr>`;
    }
}
/**
 * Logic for rendering the recent bookings table
 */
function renderRecentBookingsTable(tableId, bookings) {
    const tableBody = document.getElementById(tableId);
    if (!tableBody) return;

    // Sort bookings by id desc and take the latest five
    const recentBookings = [...bookings]
        .sort((a, b) => b.id - a.id)
        .slice(0, 5);

    if (recentBookings.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No recent reservations found.</td></tr>';
        return;
    }

    // Build the table markup once for better performance
    const rowsHtml = recentBookings.map(booking => {
        const duration = calculateDuration(booking.start_time, booking.end_time);
        const statusInfo = getStatusStyle(booking.status);

        return `
            <tr>
                <td><strong>#${booking.id}</strong></td>
                <td>${Utils.escapeHTML(booking.user?.name || `User ${booking.user_id}`)}</td>
                <td>${Utils.escapeHTML(booking.station?.station_name || `Station ${booking.station_id}`)}</td>
                <td>${duration} mins</td>
                <td><span class="status ${statusInfo.class}">${Utils.escapeHTML(booking.status)}</span></td>
            </tr>
        `;
    }).join('');

    tableBody.innerHTML = rowsHtml;
}
// --- Helper Functions ---
function updateElementText(id, text) {
    const el = document.getElementById(id);
    if (el) el.innerText = text;
}

function calculateDuration(start, end) {
    const diff = new Date(end) - new Date(start);
    return diff > 0 ? Math.round(diff / 60000) : 0;
}

function getStatusStyle(status = '') {
    const s = (status || '').toLowerCase();
    if (["active", "confirmed", "completed"].includes(s)) return { class: 'confirmed' };
    if (s === 'cancelled') return { class: 'cancelled' };
    return { class: 'pending' };
}
// 1. Global State for Users
let allUsersData = [];
let currentUsersPage = 1;
const usersRowsPerPage = 10;

// ==========================================
// 2. Main Loader for Users Page
// ==========================================
async function loadUsersPage() {
    const tableBody = document.getElementById('users-table-body');
    if (!tableBody) return;

    try {
        // Show initial loading state if needed
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading users...</td></tr>';

        // Fetch Data
        const response = await API.get('/api/admin/users');
        allUsersData = response.users || response.data || (Array.isArray(response) ? response : []);

        // Initial Table Render
        renderUsersTable();

        // Attach Listeners for Search and Filters
        setupFilterListeners();

        // Attach Pagination Listeners
        setupPaginationListeners();

    } catch (error) {
        console.error('👥 Users Load Error:', error);
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load users. Please check connection.</td></tr>';
    }
}
// ==========================================
// 3. Filtering & Search Logic
// ==========================================
function getFilteredUsers() {
    const searchInput = document.getElementById('search-input');
    const roleFilter = document.getElementById('role-filter');
    const statusFilter = document.getElementById('status-filter');

    const searchText = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const selectedRole = roleFilter ? roleFilter.value.toLowerCase() : 'all';
    const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : 'all';

    return allUsersData.filter(user => {
        const name = (user.name || '').toLowerCase();
        const email = (user.email || '').toLowerCase();
        const role = (user.role_type || user.role || '').toLowerCase();
        const status = (user.status || 'active').toLowerCase();

        const matchesSearch = name.includes(searchText) || email.includes(searchText);
        const matchesRole = (selectedRole === 'all') || (role === selectedRole);
        const matchesStatus = (selectedStatus === 'all') || (status === selectedStatus);

        return matchesSearch && matchesRole && matchesStatus;
    });
}

function setupFilterListeners() {
    const filters = ['search-input', 'role-filter', 'status-filter'];
    filters.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            // Use 'input' for search for real-time results, 'change' for selects
            const eventType = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(eventType, () => {
                currentUsersPage = 1; // Reset to first page
                renderUsersTable();
            });
        }
    });
}
// ==========================================
// 4. Rendering & Pagination
// ==========================================
function renderUsersTable() {
    const tableBody = document.getElementById('users-table-body');
    if (!tableBody) return;

    const filteredData = getFilteredUsers();
    const totalPages = Math.ceil(filteredData.length / usersRowsPerPage) || 1;

    // Safety check for current page
    if (currentUsersPage > totalPages) currentUsersPage = totalPages;

    const startIndex = (currentUsersPage - 1) * usersRowsPerPage;
    const paginatedUsers = filteredData.slice(startIndex, startIndex + usersRowsPerPage);

    // 1. Render Rows
    if (paginatedUsers.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No matching users found.</td></tr>';
    } else {
        tableBody.innerHTML = paginatedUsers.map(user => {
            const status = user.status || 'Active';
            const statusClass = status.toLowerCase() === 'active' ? 'confirmed' : 'cancelled';

            return `
                <tr id="user-row-${user.id}">
                    <td>${Utils.escapeHTML(user.name)}</td>
                    <td>${Utils.escapeHTML(user.email)}</td>
                    <td><span class="badge bg-secondary">${user.role_type || 'User'}</span></td>
                    <td class="text-center">${Utils.escapeHTML(user.department || '-')}</td>
                    <td><span class="status ${statusClass}">${status}</span></td>
                    <td>
                        <a href="viewuser-profile.html?id=${user.id}" class="btn btn-sm btn-view-theme me-1" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button class="btn btn-sm btn-delete-action" onclick="handleDeleteUser(${user.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // 2. Update Pagination UI
    const pageInfo = document.getElementById('page-info');
    if (pageInfo) pageInfo.innerText = `Page ${currentUsersPage} of ${totalPages}`;

    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    if (prevBtn) prevBtn.disabled = (currentUsersPage === 1);
    if (nextBtn) nextBtn.disabled = (currentUsersPage >= totalPages);
}

function setupPaginationListeners() {
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    if (prevBtn) {
        prevBtn.onclick = (e) => {
            e.preventDefault();
            if (currentUsersPage > 1) {
                currentUsersPage--;
                renderUsersTable();
            }
        };
    }

    if (nextBtn) {
        nextBtn.onclick = (e) => {
            e.preventDefault();
            const totalPages = Math.ceil(getFilteredUsers().length / usersRowsPerPage);
            if (currentUsersPage < totalPages) {
                currentUsersPage++;
                renderUsersTable();
            }
        };
    }
}
// ==========================================
// 5. User Actions (Delete & Profile)
// ==========================================
async function handleDeleteUser(userId) {
    const confirmed = await Utils.confirmAction(
        'Delete User?',
        'This will permanently remove the user and their data.'
    );

    if (confirmed) {
        try {
            await API.delete(`/api/admin/users/${userId}`);

            // Local state update (remove from array)
            allUsersData = allUsersData.filter(u => u.id != userId);

            // Refresh table
            renderUsersTable();
            Utils.showSuccess('Deleted!', 'User removed successfully.');
        } catch (error) {
            Utils.showError('Failed to delete user.');
        }
    }
}

async function loadUserProfilePage() {
    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get('id');

    if (!userId) {
        Utils.showError('No user selected.');
        return;
    }

    try {
        
        const response = await API.get(`/api/admin/users`);
        const usersList = response.users || response.data || response || [];
        const userData = usersList.find(u => u.id == userId);

        if (!userData) throw new Error('User not found.');

        // Map Data to UI
        document.getElementById('profile-name').innerText = userData.name || 'N/A';
        document.getElementById('profile-email').innerText = userData.email || '-';
        document.getElementById('profile-role').innerText = userData.role_type || 'User';
        document.getElementById('profile-job').innerText = userData.job_number || 'N/A';
        document.getElementById('profile-phone').innerText = userData.phone || 'N/A';
        document.getElementById('profile-joined').innerText = Utils.formatAmmanTime(userData.created_at);

        // Limit hours logic
        const limitEl = document.getElementById('profile-daily-limit');
        if (limitEl) {
            const limit = userData.daily_limit_hours;
            limitEl.innerText = (limit === null || limit === undefined) ? 'Unlimited' : `${limit} Hours`;
            if (!limit) limitEl.classList.add('text-success');
        }

        // Setup Delete button on profile
        const delBtn = document.getElementById('delete-user-btn');
        if (delBtn) delBtn.onclick = () => handleDeleteUser(userId);

    } catch (error) {
        console.error("❌ Profile Error:", error);
        Utils.showError('Could not load user profile.');
    }
}
// 3. Stations/Chargers Logic (Admin.json & Public.json)
async function loadChargersPage() {
    const tableBody = document.getElementById('chargers-table-body');
    const filterSelect = document.getElementById('charger-filter-status');

    if (!tableBody) return;

    let allChargersData = [];

    // Internal function to render the table rows dynamically
    const renderTable = (chargers) => {
        tableBody.innerHTML = '';
        if (chargers.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No stations found.</td></tr>';
            return;
        }

        chargers.forEach(charger => {
            const name = charger.station_name || charger.name || 'Unnamed';
            const location = charger.location || 'Unknown';

            // Calculate cabinets count
            let cabinetsCount = charger.total_cabinets;
            if (cabinetsCount === undefined && charger.cabinets) cabinetsCount = charger.cabinets.length;
            if (cabinetsCount === undefined) cabinetsCount = 0;

            const status = charger.status || 'unknown';
            let statusClass = 'confirmed';
            if (status.toLowerCase() === 'maintenance') statusClass = 'pending';
            if (status.toLowerCase() === 'offline') statusClass = 'cancelled';

            tableBody.innerHTML += `
                <tr>
                    <td>${charger.id}</td>
                    <td>${Utils.escapeHTML(name)}</td>
                    <td>${Utils.escapeHTML(location)}</td>
                    <td>${cabinetsCount}</td>
                    <td><span class="status ${statusClass}">${status}</span></td>
                    <td>
                        <div class="btn-group">
                            <a href="edit-charger.html?id=${charger.id}" class="btn btn-sm btn-secondary-custom">Edit</a>
                            <button onclick="deleteStation(${charger.id})" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>`;
        });
    };

    try {
        // Fetch data from Admin Station API
        const response = await API.get('/api/stations');

        // Extract stations array from different possible response formats
        if (response.stations && Array.isArray(response.stations)) allChargersData = response.stations;
        else if (Array.isArray(response)) allChargersData = response;
        else if (response.data && Array.isArray(response.data)) allChargersData = response.data;

        // Initial render
        renderTable(allChargersData);

        // Status Filtering Logic
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                const selectedStatus = e.target.value.toLowerCase();
                if (selectedStatus === 'all') {
                    renderTable(allChargersData);
                } else {
                    const filteredData = allChargersData.filter(charger =>
                        (charger.status || '').toLowerCase() === selectedStatus
                    );
                    renderTable(filteredData);
                }
            });
        }

    } catch (error) {
        console.error('Error loading stations:', error);
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load data.</td></tr>';
    }
}
// 1. Local State
let allStationsData = [];
/**
 * Main initializer for the Chargers Page
 */
async function loadChargersPage() {
    const tableBody = document.getElementById('chargers-table-body');
    const filterSelect = document.getElementById('charger-filter-status');

    if (!tableBody) return;

    try {
        // Show loading state
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading stations...</td></tr>';

        // Fetching from Public/Admin endpoint
        const response = await API.get('/api/stations');

        // Data unwraping logic
        allStationsData = response.stations || response.data || (Array.isArray(response) ? response : []);

        // Initial Render
        renderChargersTable(allStationsData);

        // Bind Filtering Event
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                const selected = e.target.value.toLowerCase();
                const filtered = selected === 'all'
                    ? allStationsData
                    : allStationsData.filter(s => (s.status || '').toLowerCase() === selected);
                renderChargersTable(filtered);
            });
        }

    } catch (error) {
        console.error('❌ Stations Load Error:', error);
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to connect to the server. Please try again.</td></tr>';
    }
}
/**
 * Renders the stations table with optimized DOM manipulation
 */
function renderChargersTable(stations) {
    const tableBody = document.getElementById('chargers-table-body');
    if (!tableBody) return;

    if (stations.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No charging stations found.</td></tr>';
        return;
    }

    tableBody.innerHTML = stations.map(station => {
        const name = station.station_name || station.name || 'Unnamed';
        const cabinets = station.total_cabinets || station.cabinets?.length || 0;

        // Professional Status Badge logic
        const status = (station.status || 'offline').toLowerCase();
        let badgeClass = 'confirmed'; // Default Green
        if (status === 'maintenance') badgeClass = 'pending'; // Orange
        if (status === 'offline') badgeClass = 'cancelled'; // Red

        return `
            <tr>
                <td><strong>#${station.id}</strong></td>
                <td>${Utils.escapeHTML(name)}</td>
                <td><i class="fas fa-map-marker-alt text-muted me-1"></i> ${Utils.escapeHTML(station.location || 'Unknown')}</td>
                <td><span class="badge bg-dark">${cabinets} Units</span></td>
                <td><span class="status ${badgeClass}">${status.toUpperCase()}</span></td>
                <td>
                    <div class="btn-group">
                        <a href="edit-charger.html?id=${station.id}" class="btn btn-sm btn-view-theme me-1">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button onclick="handleDeleteStation(${station.id})" class="btn btn-sm btn-delete-action">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}
// ==========================================
// 1. ADD STATION LOGIC
// ==========================================
async function setupAddChargerPage() {
    const addForm = document.getElementById('add-charger-form');
    if (!addForm) return;

    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = addForm.querySelector('button[type="submit"]');

        const formData = {
            station_name: document.getElementById('charger-name').value.trim(),
            station_code: document.getElementById('charger-code').value.trim(),
            department: document.getElementById('charger-dept').value.trim(),
            total_cabinets: parseInt(document.getElementById('cabinets').value) || 0,
            location: document.getElementById('charger-location').value.trim(),
            status: document.getElementById('charger-status').value
        };

        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }

            // Path from Postman: /api/admin/stations/create
            await API.post('/api/admin/stations/create', formData);

            await Utils.showSuccess('Created!', 'Station created successfully.');
            window.location.href = 'chargers.html';

        } catch (error) {
            console.error("❌ Add Error:", error);
            Utils.showError(error.message);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Save Charger';
            }
        }
    });
}
// ==========================================
// 2. EDIT STATION LOGIC 
// ==========================================
async function setupEditChargerPage() {
    // Ensure the same ID exists in your HTML
    const stationId = urlParams.get('id');
    const form = document.getElementById('edit-charger-form');

    if (!stationId || !form) return;
        e.preventDefault(); // Prevent password from appearing in the URL
    // A. Fetch Data
    try {
        const response = await API.get(`/api/stations/${stationId}`);

            // Match the backend field name (per Postman)
        const station = response.station || response.data || response;

        if (station) {
        // Check password confirmation
            document.getElementById('charger-name').value = station.station_name || '';
            document.getElementById('cabinets').value = station.total_cabinets || 0;
            document.getElementById('charger-location').value = station.location || '';
            document.getElementById('charger-status').value = (station.status || 'active').toLowerCase();

            // Store station_code to send it back in update
            // Avoid errors when Utils.showLoading is unavailable
        }
    } catch (error) {
            // Send request to the server using the documented route
        Utils.showError("Could not load correct station data.");
    }

            passwordForm.reset(); // Clear inputs
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = form.querySelector('input[type="submit"]');

        const updateData = {
            station_name: document.getElementById('charger-name').value.trim(),
            total_cabinets: parseInt(document.getElementById('cabinets').value) || 0,
            location: document.getElementById('charger-location').value.trim(),
            status: document.getElementById('charger-status').value,
            station_code: form.dataset.stationCode || stationId, // From dataset
            department: "Updated Dept"
        };

        try {
            if (submitBtn) submitBtn.value = "Updating...";

            // Correct API Call
            await API.put(`/api/admin/stations/${stationId}`, updateData);

            await Utils.showSuccess('Updated!', 'Station details updated.');
            window.location.href = 'chargers.html';
        } catch (error) {
            console.error("❌ Update Error:", error);
            Utils.showError(error.message);
        } finally {
            if (submitBtn) submitBtn.value = "Update Charger";
        }
    });
}
/**
 * Handles station deletion with confirmation
 */
window.handleDeleteStation = async function (id) {
    const confirmed = await Utils.confirmAction(
        'Delete Station?',
        'Warning: This will remove the station and all associated cabinets.'
    );

    if (confirmed) {
        try {
            await API.delete(`/api/admin/stations/${id}`);
            Utils.showSuccess('Deleted!', 'Station removed successfully.');

            // Local state update & re-render
            allStationsData = allStationsData.filter(s => s.id != id);
            renderChargersTable(allStationsData);
        } catch (error) {
            Utils.showError(error.message || 'Could not delete station.');
        }
    }
};
async function deleteStation(stationId) {
    const result = await Swal.fire({
        title: 'Delete Station?',
        text: "Warning: This will delete the station and all its chargers permanently!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, Delete Everything'
    });

    if (result.isConfirmed) {
        try {
            // This calls the destroy($id) method in AdminStationController
            await API.delete(`/api/admin/stations/${stationId}`);

            await Swal.fire('Deleted!', 'Station has been removed.', 'success');
            loadStations(); // Refresh your stations list
        } catch (error) {
            Swal.fire('Error', 'Could not delete station. Check if it has active bookings.', 'error');
        }
    }
}
// ==========================================
// 4. Bookings Logic
// Returns the status badge using the unified CSS classes (.status .active|confirmed|completed|pending|cancelled)
function getStatusBadge(status) {
    const s = (status || '').toLowerCase();
    let cls = 'pending';
    let icon = 'fa-clock';

    if (["active", "confirmed", "completed"].includes(s)) {
        cls = 'confirmed';
        icon = s === 'active' ? 'fa-play' : 'fa-check-circle';
    } else if (s === 'cancelled') {
        cls = 'cancelled';
        icon = 'fa-times-circle';
    } else {
        cls = 'pending';
        icon = 'fa-clock';
    }

    return `<span class="status ${cls}"><i class="fas ${icon} me-1"></i>${status}</span>`;
}
async function loadBookingsPage() {
    const tableBody = document.getElementById('bookings-table-body');
    const statusFilter = document.getElementById('status-filter'); // Filter element in HTML
    if (!tableBody) return;

    try {
        // 1. Fetch data from the server
        const response = await API.get('/api/admin/bookings');
        let bookings = response.bookings || response.data || [];

        // 2. Read the current filter value (lowercase for consistency)
        const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : 'all';

        // 3. Apply filtering when the option is not "All"
        if (selectedStatus !== 'all') {
            bookings = bookings.filter(booking => 
                (booking.status || '').toLowerCase() === selectedStatus
            );
        }

        // 4. Clear current table content before re-rendering
        tableBody.innerHTML = '';

        // 5. Handle empty data after filtering
        if (bookings.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted p-4">No ${selectedStatus !== 'all' ? selectedStatus : ''} bookings found.</td></tr>`;
            return;
        }

        // 6. Render rows in the table
        bookings.forEach(booking => {
            const stationName = booking.station?.station_name || booking.station_name || 'IT';
            const statusHtml = getStatusBadge(booking.status); // Color helper prepared earlier
            const duration = booking.duration || booking.duration_minutes || 0;

            const row = `
                <tr class="align-middle">
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-2 bg-secondary rounded-circle text-center" style="width:28px; height:28px; line-height:28px;">
                                <i class="fas fa-user text-white" style="font-size: 11px;"></i>
                            </div>
                            <span class="text-white">${Utils.escapeHTML(booking.user?.name || 'User')}</span>
                        </div>
                    </td>

                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-charging-station me-2 text-info"></i>
                            <span class="text-white">${Utils.escapeHTML(stationName)}</span>
                        </div>
                    </td>

                    <td class="text-secondary small">
                        ${Utils.formatAmmanTime(booking.start_time)}
                    </td>

                    <td>
                        <div class="d-flex align-items-center text-white-50">
                            <i class="far fa-clock me-2"></i>
                            <span>${duration} min</span>
                        </div>
                    </td>

                    <td>${statusHtml}</td>

                    <td>
                        <div class="d-flex gap-2 justify-content-start">
                            <a href="view-booking.html?id=${booking.id}" class="btn-view-action" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button onclick="deleteBooking(${booking.id})" class="btn-delete-action" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    } catch (error) {
        console.error("❌ Rendering Error:", error);
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger p-4">Error loading data.</td></tr>';
    }
}

/**
 * Setup filter listener on page load
 */
document.addEventListener('DOMContentLoaded', () => {
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', loadBookingsPage);
    }
    
    // Run once on load
    loadBookingsPage();
});
async function setupBookingDetailsPage() {
    const detailIdEl = document.getElementById('detail-id');
    if (!detailIdEl) return;

    // 1. Extract booking ID from the URL
    const urlParams = new URLSearchParams(window.location.search);
    const bookingId = urlParams.get('id');

    if (!bookingId) {
        Utils.showError('No booking ID provided.');
        return;
    }

    try {
        console.log(`📡 Fetching details for Booking #${bookingId}...`);

        // 2. Fetch all bookings and locate the target
        // Prefer a single-booking endpoint (/api/admin/bookings/{id}) if available
        const response = await API.get('/api/admin/bookings');
        const bookings = response.bookings || response.data || response;
        const booking = Array.isArray(bookings) ? bookings.find(b => b.id == bookingId) : bookings;

        if (!booking) throw new Error('Booking record not found on server.');

        // 3. Populate primary fields
        detailIdEl.innerText = booking.id;
        document.getElementById('detail-user').innerText = Utils.escapeHTML(booking.user?.name || 'Unknown');
        
        // Show station name and number
        const stationName = booking.station?.station_name || 'Unknown Station';
        document.getElementById('detail-station').innerText = `${Utils.escapeHTML(stationName)}`;

        // Show start time (Amman time)
        document.getElementById('detail-date-time').innerText = Utils.formatAmmanTime(booking.start_time);

        // ---------------------------------------------------------
        // Smart duration logic
        // ---------------------------------------------------------
        const plannedDuration = booking.duration || booking.duration_minutes || 0;
        const status = (booking.status || '').toLowerCase();
        const durationEl = document.getElementById('detail-duration');

        let durationHtml = `${plannedDuration} Minutes`; // Default

        // Calculate actual duration only when the booking ended or was cancelled
        if (status === 'cancelled' || status === 'completed') {
            const startTime = new Date(booking.start_time);
            // Use updated_at as the action timestamp (cancel/complete)
            const endTime = new Date(booking.updated_at); 

            let actualMinutes = 0;

            if (!isNaN(startTime) && !isNaN(endTime)) {
                // Case 1: Cancelled before start time
                if (endTime < startTime) {
                    actualMinutes = 0;
                } 
                // Case 2: Cancelled after start time—calculate difference
                else {
                    const diffMs = endTime - startTime;
                    actualMinutes = Math.floor(diffMs / 60000); // Convert to minutes
                }
            }

            // Display the actual duration (yellow) plus booked duration (gray)
            durationHtml = `
                <span class="text-warning fw-bold" style="font-size: 1.1em;">${actualMinutes} Mins (Actual)</span>
                <span class="text-muted small ms-1"> / ${plannedDuration} Mins (Booked)</span>
            `;
        }
        
        // Apply HTML
        durationEl.innerHTML = durationHtml;
        // ---------------------------------------------------------

        // Update status badge
        const statusEl = document.getElementById('detail-status');
        if (statusEl) {
            statusEl.innerHTML = getStatusBadge(booking.status);
        }

        // 4. Configure action buttons (show/hide based on status)
        setupBookingActions(booking);

    } catch (error) {
        console.error("❌ Booking Detail Load Error:", error);
        Utils.showError('Failed to load booking details.');
    }
}

function setupBookingActions(booking) {
    const cancelBtn = document.getElementById('btn-cancel-booking');
    const completeBtn = document.getElementById('btn-complete-booking');

    if (!cancelBtn || !completeBtn) return;

    const status = (booking.status || '').toLowerCase();

    // Hide buttons by default
    cancelBtn.style.display = 'none';
    completeBtn.style.display = 'none';

    // Show only when the booking is active or pending
    if (['pending', 'confirmed', 'active'].includes(status)) {
        cancelBtn.style.display = 'inline-block';
        completeBtn.style.display = 'inline-block';

        // Bind actions
        cancelBtn.onclick = () => updateBookingStatus(booking.id, 'cancelled');
        completeBtn.onclick = () => updateBookingStatus(booking.id, 'completed');
    }
}

async function updateBookingStatus(id, newStatus) {
    if (!confirm(`Are you sure you want to mark this booking as ${newStatus}?`)) return;

    try {
        Utils.showLoading('Updating...');
        // Verify the correct API route (bookings/{id} or dedicated endpoint)
        // Using a generic update path as an example
        await API.put(`/api/admin/bookings/${id}/cancel`, { status: newStatus }); 
        // Or if you have a unified endpoint: await API.put(`/api/admin/bookings/${id}`, { status: newStatus });

        Utils.showSuccess('Updated', `Booking marked as ${newStatus}`);

        // Reload to refresh counts and status
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        console.error(error);
        Utils.showError('Failed to update status');
    }
}
function setupBookingActions(booking) {
    const cancelBtn = document.getElementById('btn-cancel-booking');
    const completeBtn = document.getElementById('btn-complete-booking');

    // Ensure these IDs exist in view-booking.html
    if (!cancelBtn || !completeBtn) return;

    const status = (booking.status || '').toLowerCase();

    // Hide buttons first
    cancelBtn.style.display = 'none';
    completeBtn.style.display = 'none';

    // Show only when the booking is editable
    if (['pending', 'confirmed', 'active'].includes(status)) {
        cancelBtn.style.display = 'inline-block';
        completeBtn.style.display = 'inline-block';

        cancelBtn.onclick = () => handleStatusUpdate(booking.id, 'cancelled');
        completeBtn.onclick = () => handleStatusUpdate(booking.id, 'completed');
    }
}

/**
 * Sends status update request to server
 */
async function handleStatusUpdate(id, newStatus) {
    const actionText = newStatus === 'cancelled' ? 'Cancel' : 'Complete';
    const confirmed = await Utils.confirmAction(
        `${actionText} Booking?`,
        `Are you sure you want to mark this booking as ${newStatus}?`
    );

    if (confirmed) {
        try {
            // Choose the correct route based on the target status
            // Ensure the complete route exists in api.php
            const endpoint = newStatus === 'cancelled' 
                ? `/api/admin/bookings/${id}/cancel` 
                : `/api/admin/bookings/${id}/complete`; 

            await API.put(endpoint, { status: newStatus });

            await Utils.showSuccess('Updated!', `Booking is now ${newStatus}.`);
            
            // Refresh data on the page
            if (typeof setupBookingDetailsPage === 'function') {
                setupBookingDetailsPage();
            }
        } catch (error) {
            Utils.showError('Update failed. Please check if the route exists on the server.');
        }
    }
}
// 3. Main: Station Schedule Timeline (The most critical part for Amman Time)
async function loadStationSchedule(stationId) {
    const scheduleContainer = document.getElementById('schedule-timeline');
    if (!scheduleContainer) return;

    try {
        // Request availability instead of schedule; send date and requested duration as params
        const date = document.getElementById('booking-date')?.value || new Date().toISOString().split('T')[0];
        const duration = document.getElementById('booking-duration')?.value || 60;

        const response = await API.get(`/api/admin/stations/${stationId}/availability`, {
            params: { date, duration }
        });

        // Read the correct array from the response
        const chargers = response.chargers || [];
        let allAvailableSlots = [];
        
        chargers.forEach(charger => {
            charger.available_slots.forEach(slot => {
                allAvailableSlots.push({ ...slot, charger_id: charger.charger_id });
            });
        });

        if (allAvailableSlots.length === 0) {
            scheduleContainer.innerHTML = '<div class="text-center p-3 text-muted">No free slots for the selected duration.</div>';
            return;
        }

        // Render free slot cards
        scheduleContainer.innerHTML = allAvailableSlots.map(slot => `
            <div class="d-flex justify-content-between align-items-center p-3 mb-2 border rounded bg-dark shadow-sm" 
                 style="border-left: 5px solid #2ecc71 !important; cursor: pointer;"
                 onclick="selectBookingSlot('${slot.start}', ${slot.charger_id})">
                <div class="text-white">
                    <small class="d-block text-muted mb-1">Available Slot</small>
                    <i class="far fa-clock me-2 text-success"></i> 
                    <strong style="font-size: 0.9rem;">${Utils.formatAmmanTime(slot.start)}</strong>
                </div>
                <div class="text-end">
                    <span class="badge bg-success mb-1">FREE</span>
                </div>
            </div>
        `).join('');

    } catch (error) {
        console.error("❌ Schedule Sync Error:", error);
        scheduleContainer.innerHTML = '<div class="text-danger text-center">Error synchronizing timeline.</div>';
    }
}
function displaySlots(slots) {
    const container = document.getElementById('slots-container');
    container.innerHTML = ''; // Clear container

    if (slots.length === 0) {
        container.innerHTML = '<p class="text-muted">No available time slots for today.</p>';
        return;
    }

    // Build a small grid
    const grid = document.createElement('div');
    grid.className = 'd-flex flex-wrap gap-2 mt-3'; // Use Bootstrap flex helpers

    slots.forEach((slot, index) => {
        // Format time for display (e.g., 08:00 AM)
        const startTime = new Date(slot.start).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const slotBtn = document.createElement('div');
        slotBtn.className = 'slot-item p-2 border rounded text-center cursor-pointer';
        slotBtn.style.minWidth = '100px';
        slotBtn.style.cursor = 'pointer';
        slotBtn.innerHTML = `<span>${startTime}</span>`;

        // On slot click
        slotBtn.onclick = function() {
            selectSlot(slot, slotBtn);
        };

        grid.appendChild(slotBtn);
    });

    container.appendChild(grid);
}

// Handle slot selection
function selectSlot(slot, element) {
    // 1. Remove selection classes from all other slots
    document.querySelectorAll('.slot-item').forEach(el => {
        el.classList.remove('bg-primary', 'text-white', 'selected-slot');
        el.classList.add('bg-light');
    });

    // 2. Highlight the selected slot
    element.classList.add('bg-primary', 'text-white', 'selected-slot');
    element.classList.remove('bg-light');

    // 3. Fill the inputs (ensure matching IDs exist in the HTML)
    // Note: datetime-local inputs need proper formatting
    if(document.getElementById('start_time')) {
        document.getElementById('start_time').value = slot.start.replace(':00+00:00', ''); 
    }
    
    // Optionally store the selected slot globally for later use
    window.selectedBookingSlot = slot;
    
    console.log("Selected Slot:", slot);
}

// 4. Action: Delete Booking
async function deleteBooking(bookingId) {
    const confirmed = await Utils.confirmAction(
        'Delete Booking Record?',
        'Admin action: This record will be permanently purged from the system.'
    );

    if (confirmed) {
        try {
            await API.delete(`/api/admin/bookings/${bookingId}`);
            await Utils.showSuccess('Deleted!', 'Record successfully removed.');
            loadBookingsPage();
        } catch (error) {
            Utils.showError('Deletion failed. Verify backend route mappings.');
        }
    }
}

// Global Exports
window.loadStationSchedule = loadStationSchedule;
window.deleteBooking = deleteBooking;// ==========================================
// 1. Fetch support messages from the server and render
async function loadSupportMessages() {
    const tableBody = document.getElementById('support-table-body');
    if (!tableBody) return;

    // Loading state
    tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-4"><div class="spinner-border text-success" role="status"></div></td></tr>';

    try {
        // API call as defined in Postman
        const response = await API.get('/api/admin/messages');

        // Backend returns data inside "messages"
        const messages = response.messages || [];

        tableBody.innerHTML = '';

        if (messages.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5 text-muted">No messages in your inbox yet. 📩</td></tr>';
            return;
        }

        messages.forEach(msg => {
            const isReplied = msg.status === 'replied';

            const row = `
                <tr class="align-middle ${isReplied ? 'opacity-50' : ''}">
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-2 bg-secondary rounded-circle text-center" style="width:32px; height:32px; line-height:32px;">
                                <i class="fas fa-user text-white" style="font-size: 12px;"></i>
                            </div>
                            <div>
                                <div class="text-white fw-bold">${Utils.escapeHTML(msg.user.name)}</div>
                                <small class="text-muted">${Utils.escapeHTML(msg.user.email)}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-white">${Utils.escapeHTML(msg.subject || 'No Subject')}</td>
                    <td class="text-secondary small">${Utils.formatAmmanTime(msg.created_at)}</td>
                    <td>${getSupportStatusBadge(msg.status)}</td>
                    <td class="text-end">
                        <button 
                            onclick="${isReplied ? '' : `viewAndReplyMessage(${msg.id}, '${msg.subject}', '${msg.message.replace(/'/g, "\\'")}')`}" 
                            class="btn-view-custom" 
                            ${isReplied ? 'disabled style="cursor: not-allowed; opacity: 0.5;"' : ''}>
                            <i class="fas ${isReplied ? 'fa-check-double' : 'fa-reply'}"></i>
                            ${isReplied ? ' Replied' : ' Reply'}
                        </button>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });

    } catch (error) {
        console.error("❌ Inbox API Error:", error);
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger p-4">Failed to load messages. Please try again.</td></tr>';
    }
}

// 2. Send the actual reply to the server
async function viewAndReplyMessage(id, subject, originalContent) {
    const { value: replyText } = await Swal.fire({
        title: `<span class="text-white">Reply to Support Ticket</span>`,
        html: `
            <div class="text-start mb-3" style="font-size: 0.9rem; color: #888; background: #1a1a1a; padding: 10px; border-radius: 5px;">
                <strong>User Message:</strong><br>${Utils.escapeHTML(originalContent)}
            </div>
        `,
        input: 'textarea',
        inputPlaceholder: 'Type your official reply here...',
        background: '#141414',
        confirmButtonColor: '#66cd00',
        confirmButtonText: 'Send Official Reply',
        showCancelButton: true,
        inputValidator: (value) => {
            if (!value) return 'You cannot send an empty reply!';
        }
    });

    if (replyText) {
        try {
            Utils.showLoading('Sending reply...');

            // API call for replying (per Postman)
            // Endpoint: /admin/messages/{id}/reply
            const result = await API.post(`/api/admin/messages/${id}/reply`, {
                reply: replyText
            });

            if (result.success || result.status === 'replied') {
                await Utils.showSuccess('Success!', 'Your reply has been sent and the ticket is closed.');
                loadSupportMessages(); // Refresh the table immediately
            }
        } catch (error) {
            console.error("❌ Reply Error:", error);
            Utils.showError('Could not send reply. Check your connection.');
        }
    }
}

// 3. Status badge formatter
function getSupportStatusBadge(status) {
    const s = (status || 'new').toLowerCase();
    if (s === 'replied') return `<span class="status confirmed"><i class="fas fa-check me-1"></i>REPLIED</span>`;
    return `<span class="status pending">NEW</span>`;
}


function setupLogout() {
    document.querySelectorAll('.logout-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem(CONFIG.TOKEN_KEY);
            localStorage.removeItem(CONFIG.USER_DATA_KEY);
            window.location.href = 'index.html';
        });
    });
}
async function setupAccountSettingsPage() {
    const saveBtn = document.getElementById('save-profile-btn');
    if (!saveBtn) return;

    // 1. Load basic data plus the protected phone number
    let localData = JSON.parse(localStorage.getItem(CONFIG.USER_DATA_KEY) || '{}');
    const protectedPhone = localStorage.getItem('admin_protected_phone'); // Dedicated key for the phone

    try {
        // 2. Attempt to fetch server data (fallback continues even on 404)
        // const serverData = await API.get('/api/admin/profile');
        localData = { ...localData, ...serverData };
        localStorage.setItem(CONFIG.USER_DATA_KEY, JSON.stringify(localData));
    } catch (e) { 
        console.warn("Using local cache logic..."); 
    }

    // 3. Populate fields (prioritize the protected phone)
    if (document.getElementById('input-name')) document.getElementById('input-name').value = localData.name || '';
    if (document.getElementById('input-email')) document.getElementById('input-email').value = localData.email || '';
    
    const phoneInput = document.getElementById('input-phone');
    if (phoneInput) {
        // Use the protected phone first; otherwise fall back to cached data
        phoneInput.value = protectedPhone || localData.phone || localData.phone_number || '';
    }

    // saveBtn.onclick inside app.js
saveBtn.onclick = async (e) => {
    e.preventDefault();
    const newName = document.getElementById('input-name').value;
    const newPhone = document.getElementById('input-phone').value;

    // 1. Update local (protected) cache
    localData.name = newName;
    localData.phone = newPhone;
    localStorage.setItem(CONFIG.USER_DATA_KEY, JSON.stringify(localData));
    localStorage.setItem('admin_protected_phone', newPhone);

    // 2. Instant sidebar refresh
    // Ensure the sidebar element ID is 'user-name' in your HTML
    const sidebarNameElement = document.getElementById('user-name');
    if (sidebarNameElement) {
        sidebarNameElement.innerText = newName; 
        console.log("✅ Sidebar Name Updated Instantly!");
    }

    try {
        if (typeof Utils.showLoading === 'function') Utils.showLoading('Saving...');
        
        await API.post('/api/admin/update-profile', { 
            name: newName, 
            phone_number: newPhone 
        });

        await Utils.showSuccess('Success', 'Profile Updated Successfully!');
    } catch (error) {
        // Even if the server fails, the sidebar keeps the new local values
        await Utils.showSuccess('Success', 'Profile updated locally.');
    }
};
}
async function setupSecurityPage() {
    // Ensure the same ID exists in your HTML
    const passwordForm = document.getElementById('change-password-form');
    if (!passwordForm) return;

    passwordForm.onsubmit = async (e) => {
        e.preventDefault(); // Prevent password from appearing in the URL

        const data = {
            current_password: document.getElementById('current-password').value,
            new_password: document.getElementById('new-password').value,
            // Match the backend field name (per Postman)
            new_password_confirmation: document.getElementById('confirm-new-password').value 
        };

        // Check password confirmation
        if (data.new_password !== data.new_password_confirmation) {
            Utils.showError('mismatched','passwords do not match');
            return;
        }

        try {
            // Avoid errors when Utils.showLoading is unavailable
            if (typeof Utils.showLoading === 'function') {
                Utils.showLoading('lodaing...');
            }

            // Send request to the server using the documented route
            await API.post('/api/admin/change-password', data);
            
            await Utils.showSuccess('Success', 'Password changed successfully!');
            passwordForm.reset(); // Clear inputs

        } catch (error) {
            console.error("❌ Password Change Error:", error);
            Utils.showError(error.message || 'Failed to change password.');
        }
    };
}
async function setupSystemSettings() {
    const settingsForm = document.getElementById('system-settings-form');
    if (!settingsForm) return;

    // 1. Fetch current settings when the page loads (controller show)
    try {
        const response = await API.get('/api/admin/settings');
        const settings = response.settings;
        if (settings) {
            document.getElementById('daily-limit').value = settings.daily_limit_hours;
            document.getElementById('open-time').value = settings.opening_time;
            document.getElementById('close-time').value = settings.closing_time;
            document.getElementById('maintenance-toggle').checked = settings.maintenance_mode;
        }
    } catch (e) { console.error("Could not fetch settings"); }

    // 2. Update settings (controller update)
   // Inside setupSystemSettings in app.js
settingsForm.onsubmit = async (e) => {
    e.preventDefault();

    // Helper to strip seconds or AM/PM
    const formatTime = (timeStr) => {
        if (!timeStr) return "00:00";
        return timeStr.substring(0, 5); // Keep HH:mm only
    };

    const data = {
        daily_limit_hours: parseInt(document.getElementById('daily-limit').value),
        opening_time: formatTime(document.getElementById('open-time').value), // Clean time
        closing_time: formatTime(document.getElementById('close-time').value), // Clean time
        maintenance_mode: document.getElementById('maintenance-toggle').checked ? 1 : 0,
        max_warnings: 3,
        grace_period_minutes: 10,
        _method: 'PUT' // Ensure Laravel compatibility
    };

    try {
        Utils.showLoading('The rules are being updated..');
        await API.post('/api/admin/settings', data);
        Utils.showSuccess('Success', 'System settings updated successfully!');
    } catch (error) {
        console.error("❌ API Error:", error);
        // Show backend error clearly
        Utils.showError(error.message || 'Failed to update settings. Please try again.');
    }
};
}