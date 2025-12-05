// app.js - Main application logic

// --- 1. Central User Database (Shared Data) ---
const allUsersData = [
    { id: 1, name: 'Islam Noor', email: 'islamabukhalaf5@gmail.com', role: 'Student', hours: '40h', status: 'Active', statusClass: 'confirmed', phone: '0776423581' },
    { id: 2, name: 'Fares AlH', email: 'Fares.alh.jaradat@gmail.com', role: 'Staff', hours: '4h', status: 'Active', statusClass: 'confirmed', phone: '0799999999' },
    { id: 3, name: 'Ibrahim Taqatqa', email: 'ibrahim@gmail.com', role: 'Student', hours: '12h', status: 'Inactive', statusClass: 'cancelled', phone: '0788888888' },
    { id: 4, name: 'Bushra Almsalmeh', email: 'bushraalmasalmeh@gmail.com', role: 'Staff', hours: '20h', status: 'Active', statusClass: 'confirmed', phone: '0777777777' },
    { id: 5, name: 'Khaled Omar', email: 'khaled@example.com', role: 'Student', hours: '0h', status: 'Inactive', statusClass: 'cancelled', phone: '0700000000' }
];

// --- 2. On Page Load ---
document.addEventListener('DOMContentLoaded', () => {
    /// --- Mobile Menu Toggle (Debugged) ---
    const menuToggle = document.getElementById('menu-toggle');
    const wrapper = document.getElementById('wrapper');

    if (menuToggle && wrapper) {
        menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('Toggle button clicked!'); // تأكدي أن هذه الرسالة تظهر في الكونسول
            wrapper.classList.toggle('toggled');
        });
    } else {
        console.error('Menu toggle or wrapper not found!');
    }

    // إغلاق القائمة عند الضغط خارجها
    // نغلق القائمة عند الضغط على أي مكان في الـ Wrapper إذا كان مفتوحاً
    // (باستثناء منطقة السايدبار نفسها)
    document.addEventListener('click', (e) => {
        const sidebar = document.getElementById('sidebar');
        
        // إذا كانت القائمة مفتوحة
        if (wrapper && wrapper.classList.contains('toggled')) {
            // والضغطة لم تكن على الزر ولا داخل السايدبار
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                wrapper.classList.remove('toggled');
            }
        }
    });

    // --- Sidebar Active State ---
    const currentPage = window.location.pathname.split('/').pop();
    const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
    
    document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));

    sidebarLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref === currentPage) {
            link.parentElement.classList.add('active');
        }
    });

    // --- Page Routing ---
    
    // 1. Dashboard
    if (document.getElementById('stats-total-users')) {
        loadDashboardData();
    }
    
    // 2. Chargers
    if (document.getElementById('chargers-table-body')) {
        loadChargersData();
        document.getElementById('chargers-table-body').addEventListener('click', handleChargersTableClick);
    }

    // 3. Users
    if (document.getElementById('users-table-body')) {
        loadUsersData();
    }

    // 4. User Profile
    if (document.getElementById('profile-name')) {
        loadUserProfile();
    }

    // 5. Bookings
    if (document.getElementById('bookings-table-body')) {
        loadBookingsData();
    }

    // 6. Reports
    if (document.getElementById('consumptionChart')) {
        loadReportsData();
    }

    // 7. Support Inbox
    if (document.getElementById('support-table-body')) {
        loadSupportData();
    }

    // --- Account Settings Save ---
    const saveProfileBtn = document.getElementById('save-profile-btn');
    if (saveProfileBtn) {
        saveProfileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const newName = document.getElementById('input-name').value;
            const newEmail = document.getElementById('input-email').value;

            if(newName.trim() === '' || newEmail.trim() === '') {
                Swal.fire({ icon: 'error', title: 'Oops...', text: 'Fields cannot be empty!', background: '#1a1a1a', color: '#ffffff' });
                return;
            }

            Swal.fire({
                title: 'Changes Saved!',
                text: 'Profile updated successfully.',
                icon: 'success',
                confirmButtonColor: '#66cd00',
                background: '#1a1a1a',
                color: '#ffffff'
            });
        });
    }

    // --- Add Booking Form ---
    const addBookingForm = document.getElementById('add-booking-form');
    if (addBookingForm) {
        addBookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Success!',
                text: 'New booking created.',
                icon: 'success',
                confirmButtonText: 'Go to Bookings',
                confirmButtonColor: '#66cd00',
                background: '#1a1a1a',
                color: '#ffffff'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = 'bookings.html';
            });
        });
    }

    // --- Logout Button ---
    const logoutLinks = document.querySelectorAll('.logout-btn, .btn-secondary-custom');
    logoutLinks.forEach(btn => {
        if (btn.innerText.includes('Logout') || btn.innerText.includes('Sign Out')) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You will be logged out.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, Logout',
                    background: '#1a1a1a',
                    color: '#ffffff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'index.html'; // Adjust if your login page name differs
                    }
                });
            });
        }
    });

}); // End DOMContentLoaded


// ==========================================
// Page Functions
// ==========================================

function loadDashboardData() {
    console.log('Loading Dashboard...');
    const statsData = { totalUsers: 350, totalChargers: 15, activeSessions: 56 };
    document.getElementById('stats-total-users').innerText = statsData.totalUsers;
    document.getElementById('stats-total-chargers').innerText = statsData.totalChargers;
    document.getElementById('stats-active-sessions').innerText = statsData.activeSessions;

    const reservationsData = [
        { id: 'R001', user: 'Islam', charger: 'C001', duration: '1h 30min', status: 'Confirmed', statusClass: 'confirmed' },
        { id: 'R002', user: 'Bushra', charger: 'C002', duration: '-', status: 'Pending', statusClass: 'pending' },
        { id: 'R003', user: 'Ahmad', charger: 'C001', duration: '2h 00min', status: 'Completed', statusClass: 'pending' }
    ];

    const tableBody = document.getElementById('reservations-table-body');
    tableBody.innerHTML = ''; 
    reservationsData.forEach(res => {
        tableBody.innerHTML += `
            <tr>
                <td>${res.id}</td><td>${res.user}</td><td>${res.charger}</td><td>${res.duration}</td>
                <td><span class="status ${res.statusClass}">${res.status}</span></td>
            </tr>`;
    });
}

function loadChargersData() {
    console.log('Loading Chargers Data...');
    
    const allChargers = [
        { id: 'Station 001', location: 'Health Center', status: 'Available', statusClass: 'confirmed' },
        { id: 'Station 002', location: 'IT College', status: 'In Use', statusClass: 'pending' },
        { id: 'Station 003', location: 'Business College', status: 'Offline', statusClass: 'cancelled' },
        { id: 'Station 004', location: 'Engineering Bld', status: 'Available', statusClass: 'confirmed' }
    ];

    const tableBody = document.getElementById('chargers-table-body');
    const statusFilter = document.getElementById('charger-filter-status');

    function renderTable(data) {
        tableBody.innerHTML = ''; 
        
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4">No chargers found.</td></tr>';
            return;
        }

        data.forEach(charger => {
            const row = `
                <tr data-charger-id="${charger.id}">
                    <td>${charger.id}</td>
                    <td>${charger.location}</td>
                    <td><span class="status ${charger.statusClass}">${charger.status}</span></td>
                    <td>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-secondary-custom" data-action="edit">Edit</button>
                            <button class="btn btn-sm btn-danger-custom" data-action="remove">Remove</button>
                        </div>
                    </td>
                </tr>
            `;
            tableBody.innerHTML += row;
        });
    }

    function filterChargers() {
        const selectedStatus = statusFilter.value;
        const filteredData = allChargers.filter(charger => {
            return selectedStatus === 'all' || charger.status === selectedStatus;
        });
        renderTable(filteredData);
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', filterChargers);
    }

    renderTable(allChargers);
}

function handleChargersTableClick(event) {
    const button = event.target.closest('button[data-action]');
    if (!button) return;
    const row = button.closest('tr');
    const chargerId = row.dataset.chargerId;
    const action = button.dataset.action;

    if (action === 'edit') {
        window.location.href = `edit-charger.html?id=${encodeURIComponent(chargerId)}`;
    } else if (action === 'remove') {
        Swal.fire({
            title: 'Are you sure?', text: "Remove this charger?", icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Yes, remove!', background: '#1a1a1a', color: '#ffffff'
        }).then((result) => {
            if(result.isConfirmed) { row.remove(); Swal.fire('Deleted!', '', 'success'); }
        });
    }
}

function loadUsersData() {
    console.log('Loading Users with Pagination...');
    
    // إعدادات التقسيم
    const rowsPerPage = 3; // عدد المستخدمين في كل صفحة
    let currentPage = 1;
    
    // العناصر
    const tableBody = document.getElementById('users-table-body');
    const searchInput = document.getElementById('search-input');
    const roleFilter = document.getElementById('filter-role');
    const statusFilter = document.getElementById('filter-status');
    
    // عناصر التقسيم
    const pageInfo = document.getElementById('page-info');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    // دالة الرسم (مع التقسيم)
    function renderTable(data) {
        tableBody.innerHTML = '';
        
        // 1. حساب البداية والنهاية
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        // 2. قص البيانات للصفحة الحالية فقط
        const paginatedData = data.slice(start, end);

        if (paginatedData.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No users found.</td></tr>';
            pageInfo.innerText = 'Showing 0 to 0 of 0 results';
            prevBtn.disabled = true;
            nextBtn.disabled = true;
            return;
        }

        // 3. عرض البيانات
        paginatedData.forEach(user => {
            tableBody.innerHTML += `
                <tr id="user-row-${user.id}">
                    <td>${user.name}</td><td>${user.email}</td><td>${user.role}</td>
                    <td class="text-center">${user.hours}</td>
                    <td><span class="status ${user.statusClass}">${user.status}</span></td>
                    <td>
                        <a href="viewuser-profile.html?id=${user.id}" class="btn btn-sm btn-secondary-custom">View Profile</a>
                        <button class="btn btn-sm btn-danger-custom delete-btn" data-id="${user.id}">Delete</button>
                    </td>
                </tr>`;
        });
        
        // 4. تحديث معلومات الصفحة والأزرار
        pageInfo.innerText = `Showing ${start + 1} to ${Math.min(end, data.length)} of ${data.length} results`;
        
        // تعطيل زر Previous إذا كنا في الصفحة الأولى
        prevBtn.disabled = currentPage === 1;
        
        // تعطيل زر Next إذا وصلنا لآخر صفحة
        nextBtn.disabled = end >= data.length;

        // تفعيل أزرار الحذف (كما سبق)
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                Swal.fire({ title: 'Delete?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete!', background: '#1a1a1a', color: '#ffffff' })
                .then((result) => { if (result.isConfirmed) { this.closest('tr').remove(); Swal.fire('Deleted!', '', 'success'); } });
            });
        });
    }

    // دالة الفلترة والبحث
    function filterAndRender() {
        const term = searchInput.value.toLowerCase();
        const role = roleFilter.value;
        const status = statusFilter.value;

        // تصفية البيانات الأصلية
        const filteredData = allUsersData.filter(user => {
            const matchesSearch = user.name.toLowerCase().includes(term) || user.email.toLowerCase().includes(term);
            const matchesRole = role === 'all' || user.role === role;
            const matchesStatus = status === 'all' || user.status === status;
            return matchesSearch && matchesRole && matchesStatus;
        });

        // رسم الجدول بالبيانات المفلترة
        renderTable(filteredData);
        
        // ملاحظة: عند الفلترة نعيد الصفحة للأولى لتجنب الأخطاء
        // لكننا هنا سنعتمد على المتغيرات الحالية داخل النطاق
        return filteredData; // نرجع البيانات لنستخدمها في أزرار التنقل
    }

    // الحصول على البيانات الحالية (بعد الفلترة)
    function getCurrentData() {
        const term = searchInput.value.toLowerCase();
        const role = roleFilter.value;
        const status = statusFilter.value;
        return allUsersData.filter(user => {
            return (user.name.toLowerCase().includes(term) || user.email.toLowerCase().includes(term)) &&
                   (role === 'all' || user.role === role) && 
                   (status === 'all' || user.status === status);
        });
    }

    // ربط أحداث أزرار التنقل
    prevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            renderTable(getCurrentData());
        }
    });

    nextBtn.addEventListener('click', () => {
        const currentData = getCurrentData();
        if ((currentPage * rowsPerPage) < currentData.length) {
            currentPage++;
            renderTable(currentData);
        }
    });

    // إعادة الصفحة لرقم 1 عند البحث أو الفلترة
    function resetAndFilter() {
        currentPage = 1;
        filterAndRender();
    }

    if(searchInput) searchInput.addEventListener('input', resetAndFilter);
    if(roleFilter) roleFilter.addEventListener('change', resetAndFilter);
    if(statusFilter) statusFilter.addEventListener('change', resetAndFilter);

    // التشغيل الأولي
    renderTable(allUsersData);
}

function loadUserProfile() {
    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get('id');
    const user = allUsersData.find(u => u.id == userId);

    if (user) {
        document.getElementById('profile-name').innerText = user.name;
        document.getElementById('profile-email').innerText = user.email;
        document.getElementById('profile-role').innerText = user.role;
        document.getElementById('profile-hours').innerText = user.hours;
        
        const statusEl = document.getElementById('profile-status');
        statusEl.innerText = user.status;
        statusEl.className = `status ${user.statusClass}`;

        if(document.getElementById('input-name')) document.getElementById('input-name').value = user.name;
        if(document.getElementById('input-email')) document.getElementById('input-email').value = user.email;
        if(document.getElementById('input-phone')) document.getElementById('input-phone').value = user.phone;
    }
}

function loadBookingsData() {
    console.log('Loading Bookings Data with Filters...');

    const allBookings = [
        { id: 'R001', user: 'Islam Noor', station: '001', datetime: 'Oct 25, 2025, 12:00 PM', status: 'Confirmed', statusClass: 'confirmed' },
        { id: 'R002', user: 'Fares Ali', station: '002', datetime: 'Oct 25, 2025, 2:00 PM', status: 'Completed', statusClass: 'pending' },
        { id: 'R003', user: 'Ibrahim', station: '003', datetime: 'Oct 24, 2025, 10:00 AM', status: 'Cancelled', statusClass: 'cancelled' },
        { id: 'R004', user: 'Bushra', station: '001', datetime: 'Oct 26, 2025, 09:00 AM', status: 'Confirmed', statusClass: 'confirmed' },
        { id: 'R005', user: 'Sara', station: '004', datetime: 'Oct 27, 2025, 04:00 PM', status: 'Confirmed', statusClass: 'confirmed' }
    ];

    const tableBody = document.getElementById('bookings-table-body');
    const searchInput = document.getElementById('booking-search');
    const statusFilter = document.getElementById('booking-filter-status');

    function renderTable(data) {
        tableBody.innerHTML = '';
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No bookings found.</td></tr>';
            return;
        }
        data.forEach(booking => {
            tableBody.innerHTML += `
                <tr>
                    <td>${booking.user}</td>
                    <td>${booking.station}</td>
                    <td>${booking.datetime}</td>
                    <td><span class="status ${booking.statusClass}">${booking.status}</span></td>
                    <td>
                        <a href="view-booking.html?id=${booking.id}" class="btn btn-sm btn-secondary-custom">View</a>
                    </td>
                </tr>`;
        });
    }

    function filterBookings() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const status = statusFilter.value;

        const filtered = allBookings.filter(item => {
            const matchesSearch = item.user.toLowerCase().includes(searchTerm) || 
                                  item.station.toLowerCase().includes(searchTerm);
            const matchesStatus = status === 'all' || item.status === status;
            return matchesSearch && matchesStatus;
        });
        renderTable(filtered);
    }

    if (searchInput) searchInput.addEventListener('input', filterBookings);
    if (statusFilter) statusFilter.addEventListener('change', filterBookings);

    renderTable(allBookings);
}

function loadSupportData() {
    console.log('Loading Support Data...');

    const supportData = [
        { 
            id: 101, user: 'Islam Noor', subject: 'Charger 001 Malfunction', date: 'Oct 26, 2025', status: 'New', statusClass: 'cancelled',
            message: 'Hello Admin, I tried to use Charger 001 at the Health Center but the screen is black and it is not responding. Please check it.' 
        },
        { 
            id: 102, user: 'Bushra', subject: 'Billing Issue', date: 'Oct 25, 2025', status: 'In Progress', statusClass: 'pending',
            message: 'I was charged twice for my last session on Oct 24th. Can you please refund the extra amount?' 
        },
        { 
            id: 103, user: 'Ahmad', subject: 'App Feedback', date: 'Oct 24, 2025', status: 'Resolved', statusClass: 'confirmed',
            message: 'The new update looks great! It would be nice if we could see the charging speed in real-time.' 
        }
    ];

    const tableBody = document.getElementById('support-table-body');
    const statusFilter = document.getElementById('support-filter-status');

    function renderTable(data) {
        tableBody.innerHTML = ''; 
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No messages found.</td></tr>';
            return;
        }

        data.forEach(msg => {
            const row = `
                <tr data-id="${msg.id}">
                    <td>${msg.user}</td>
                    <td>${msg.subject}</td>
                    <td>${msg.date}</td>
                    <td><span class="status ${msg.statusClass}">${msg.status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-secondary-custom view-ticket-btn" data-id="${msg.id}">View</button>
                        ${msg.status !== 'Resolved' ? `<button class="btn btn-sm btn-success-custom ms-1 resolve-btn">Resolve</button>` : ''}
                    </td>
                </tr>`;
            tableBody.innerHTML += row;
        });

        attachTicketListeners();
    }

    function attachTicketListeners() {
        document.querySelectorAll('.view-ticket-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const ticket = supportData.find(t => t.id == id);
                if (ticket) {
                    Swal.fire({
                        title: ticket.subject,
                        html: `
                            <div style="text-align: left; color: #ccc;">
                                <p><strong>From:</strong> ${ticket.user}</p>
                                <p><strong>Date:</strong> ${ticket.date}</p>
                                <hr style="border-color: #444;">
                                <p style="font-size: 1.1em; color: #fff;">${ticket.message}</p>
                            </div>
                        `,
                        background: '#1a1a1a', color: '#ffffff', confirmButtonText: 'Close', confirmButtonColor: '#6c757d', width: '600px'
                    });
                }
            });
        });

        document.querySelectorAll('.resolve-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Mark as Resolved?', text: "This will update the ticket status.", icon: 'question',
                    showCancelButton: true, confirmButtonColor: '#66cd00', confirmButtonText: 'Yes, Resolve', background: '#1a1a1a', color: '#ffffff'
                }).then((res) => {
                    if (res.isConfirmed) { Swal.fire('Resolved!', 'Ticket closed successfully.', 'success'); }
                });
            });
        });
    }

    function filterSupport() {
        const status = statusFilter.value;
        const filtered = supportData.filter(msg => {
            return status === 'all' || msg.status === status;
        });
        renderTable(filtered);
    }

    if (statusFilter) statusFilter.addEventListener('change', filterSupport);

    renderTable(supportData);
}

function loadReportsData() {
    console.log('Loading Charts...');
    const ctx1 = document.getElementById('consumptionChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Consumption', data: [65, 59, 80, 81, 56, 120],
                borderColor: '#66cd00', backgroundColor: 'rgba(102, 205, 0, 0.2)', tension: 0.4, fill: true
            }]
        },
        options: { responsive: true, plugins: { legend: { labels: { color: '#a0b0ab' } } }, scales: { y: { grid: { color: '#2a3b3b' }, ticks: { color: '#a0b0ab' } }, x: { grid: { color: '#2a3b3b' }, ticks: { color: '#a0b0ab' } } } }
    });

    const ctx2 = document.getElementById('capacityChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: ['Station A', 'Station B', 'Station C', 'Station D'],
            datasets: [{
                label: 'Usage', data: [120, 165, 90, 140],
                backgroundColor: ['rgba(102, 205, 0, 0.7)', 'rgba(255, 255, 255, 0.7)', 'rgba(102, 205, 0, 0.7)', 'rgba(255, 255, 255, 0.7)'],
                borderColor: 'transparent', borderWidth: 1
            }]
        },
        options: { responsive: true, plugins: { legend: { labels: { color: '#a0b0ab' } } }, scales: { y: { grid: { color: '#2a3b3b' }, ticks: { color: '#a0b0ab' } }, x: { grid: { display: false }, ticks: { color: '#a0b0ab' } } } }
    });
}
