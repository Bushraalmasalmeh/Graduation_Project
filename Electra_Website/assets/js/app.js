// assets/js/app.js - UPDATED FOR REAL API

document.addEventListener('DOMContentLoaded', async () => {
    setupSidebar();
    setupLogout();

    // التوجيه (Routing)
    if (document.getElementById('stats-total-users')) await loadDashboardData();
    if (document.getElementById('users-table-body')) await loadUsersPage();
    if (document.getElementById('chargers-table-body')) await loadChargersPage();
    // 2. صفحة إضافة شاحن
    if (document.getElementById('add-charger-form')) setupAddChargerPage();
    if (document.getElementById('edit-charger-form')) await setupEditChargerPage();

    if (document.getElementById('compose-notification-form')) setupNotificationPage();

    // الصفحات التي ليس لها API في الملفات المرفقة (Bookings, Support)
    if (document.getElementById('bookings-table-body')) await loadBookingsPage();

    if (document.getElementById('detail-id')) await setupBookingDetailsPage();
    // const addBookingForm = document.getElementById('add-booking-form');
    //     if (addBookingForm) {
    //         console.log("✅ Add Booking Page Detected!"); 
    //         await setupAddBookingPage();
    //     }
    if (document.getElementById('support-table-body')) await loadSupportPage();
    await loadCurrentUserProfile();
        if (document.getElementById('consumptionChart')) {
        loadReportsData();
    }
   
});
// دالة جديدة لجلب بياناتي من السيرفر مباشرة
async function loadCurrentUserProfile() {
    // تحديث الـ IDs لتطابق الموجود في ملف HTML الخاص بكِ
    const nameEl = document.getElementById('user-name'); 
    const emailEl = document.getElementById('user-email');

    try {
        // طلب البيانات من السيرفر
        const user = await API.get('/api/me');

        // 1. تحديث القائمة الجانبية (Sidebar)
        if (nameEl) nameEl.innerText = user.name;
        if (emailEl) emailEl.innerText = user.email;

        // 2. تحديث حقول الإدخال (Form Inputs) إذا وجدت في الصفحة
        const inputName = document.getElementById('input-name');
        const inputEmail = document.getElementById('input-email');
        const inputPhone = document.getElementById('input-phone');

        if (inputName) inputName.value = user.name;
        if (inputEmail) inputEmail.value = user.email;
        
        // استخدام رقم الوظيفة أو الهاتف بناءً على استجابة السيرفر
        if (inputPhone) {
            inputPhone.value = user.job_number || user.phone || '';
        }

    } catch (error) {
        console.error('Could not fetch user profile', error);
    }
}
async function updateAdminProfile() {
    const saveBtn = document.getElementById('save-profile-btn');
    if (!saveBtn) return;

    saveBtn.addEventListener('click', async (e) => {
        e.preventDefault(); // لمنع أي سلوك افتراضي للفورم

        // 1. نأخذ البيانات التي يسمح للآدمن بتغييرها فقط
        const updatedData = {
            name: document.getElementById('input-name').value.trim(),
            job_number: document.getElementById('input-phone').value.trim()
        };

        // 2. تحقق بسيط (Client-side validation)
        if (!updatedData.name) {
            Utils.showError('الاسم مطلوب ولا يمكن تركه فارغاً');
            return;
        }

        try {
            // استخدام Utils التي برمجتِها لإظهار حالة التحميل
            Utils.setLoading('save-profile-btn', true);
            
            // 3. الربط مع المسار المخصص للتحديث (حسب Admin.json)
            // لاحظي أننا لم نرسل "email" هنا
            await API.post('/api/admin/settings/update', updatedData);

            // 4. تحديث "السايد بار" فوراً بالاسم الجديد ليعكس التغيير للمستخدم
            const sidebarName = document.getElementById('user-name');
            if (sidebarName) sidebarName.innerText = updatedData.name;
            
            Utils.showSuccess('تم التحديث', 'تم حفظ التغييرات بنجاح');
            
        } catch (error) {
            // عرض رسالة الخطأ القادمة من الباك آند (مثل: رقم الوظيفة مكرر)
            Utils.showError('فشل التحديث: ' + error.message);
        } finally {
            // إعادة الزر لحالته الطبيعية
            Utils.setLoading('save-profile-btn', false, 'Save Changes');
        }
    });
}
// ==========================================
// 1. Dashboard Logic (Admin.json)
// ==========================================
async function loadDashboardData() {
    console.log("🚀 Loading Dashboard Data...");

    // 1. جلب الإحصائيات العامة (Users & Chargers)
    try {
        const stats = await API.get('/api/admin/dashboard');
        
        if(document.getElementById('stats-total-users')) 
            document.getElementById('stats-total-users').innerText = stats.total_users || 0;
        
        if(document.getElementById('stats-total-chargers')) 
            document.getElementById('stats-total-chargers').innerText = stats.total_stations || stats.total_chargers || 0;
        
        // ملاحظة: لن نعتمد على stats.active_sessions هنا لأنه يعطي 0
    } catch (error) {
        console.error('Stats Error:', error);
    }

    // 2. جلب الحجوزات لحساب Active Sessions وتعبئة الجدول
    const tableBody = document.getElementById('recent-bookings-body');
    const activeSessionsEl = document.getElementById('stats-active-sessions');

    try {
        // جلب كل الحجوزات
        const response = await API.get('/api/admin/bookings');
        let bookings = response.bookings || response.data || response;

        if (Array.isArray(bookings)) {
            // أ) حساب الجلسات النشطة (Active Sessions)
            // نعد الحجوزات التي حالتها 'active' (أو 'confirmed' إذا كنتِ تعتبرينها نشطة)
            const activeCount = bookings.filter(b => 
                (b.status || '').toLowerCase() === 'active'
            ).length;

            // تحديث الرقم في الكارد
            if(activeSessionsEl) activeSessionsEl.innerText = activeCount;


            // ب) ترتيب وعرض الجدول (آخر 5 حجوزات)
            bookings.sort((a, b) => b.id - a.id);
            const recent = bookings.slice(0, 5);

            if (tableBody) {
                tableBody.innerHTML = ''; 
                if (recent.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No reservations found.</td></tr>';
                } else {
                    recent.forEach(b => {
                        const start = new Date(b.start_time);
                        const end = new Date(b.end_time);
                        const durationMins = Math.round((end - start) / 60000);

                        let statusClass = 'pending';
                        const status = (b.status || 'unknown').toLowerCase();
                        if (status === 'active' || status === 'confirmed') statusClass = 'confirmed';
                        else if (status === 'cancelled') statusClass = 'cancelled';

                        const stationName = b.station ? b.station.station_name : `Station ${b.station_id}`;
                        const userName = b.user ? b.user.name : `User ${b.user_id}`;

                        tableBody.innerHTML += `
                            <tr>
                                <td>#${b.id}</td>
                                <td>${userName}</td>
                                <td>${stationName}</td>
                                <td>${durationMins} min</td>
                                <td><span class="status ${statusClass}">${b.status}</span></td>
                            </tr>
                        `;
                    });
                }
            }
        }
    } catch (error) {
        console.error('Bookings Data Error:', error);
        if(tableBody) tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load data.</td></tr>';
    }
}
// ==========================================
// 2. Users Page Logic (Admin.json)

// متغيرات عامة لحفظ حالة الصفحة
let allUsersData = [];       // مخزن لكل المستخدمين القادمين من السيرفر
let currentUsersPage = 1;    // الصفحة الحالية
const usersRowsPerPage = 10;  // عدد الأسطر في كل صفحة

async function loadUsersPage() {
    const tableBody = document.getElementById('users-table-body');
    if (!tableBody) return;

    const searchInput = document.querySelector('input[type="search"]') || document.getElementById('search-input');
    const roleFilter = document.getElementById('role-filter'); // تأكدي من وجود هذا الـ ID في الـ HTML للقائمة
    const statusFilter = document.getElementById('status-filter'); // ✅ إضافة جديدة
    const prevBtn = document.getElementById('prev-btn'); // زر السابق
    const nextBtn = document.getElementById('next-btn'); // زر التالي
    const pageInfo = document.getElementById('page-info'); // نص "Page 1 of 5"

    try {
        // 1. جلب البيانات من السيرفر
        const response = await API.get('/api/admin/users');

        // فك تغليف البيانات (Unwrapping)
        if (response.users && Array.isArray(response.users)) allUsersData = response.users;
        else if (response.data && Array.isArray(response.data)) allUsersData = response.data;
        else if (Array.isArray(response)) allUsersData = response;

        // 🔍 طباعة البيانات لمعرفة اسم حقل الساعات الصحيح
        console.log('👥 Users Data (Check for hours field):', allUsersData[0]);

        // 2. تشغيل الجدول لأول مرة
        renderUsersTable();

        // 3. تفعيل البحث (Search)
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                currentUsersPage = 1; // العودة للصفحة الأولى عند البحث
                renderUsersTable();
            });
        }

        // 4. تفعيل الفلترة (Filter by Role)
        if (roleFilter) {
            roleFilter.addEventListener('change', () => {
                currentUsersPage = 1;
                renderUsersTable();
            });
        }
        // داخل loadUsersPage
        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                currentUsersPage = 1;
                renderUsersTable();
            });
        }

        // 5. تفعيل أزرار التنقل (Pagination)
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

    } catch (error) {
        console.error('Error loading users:', error);
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load users.</td></tr>';
    }
}

// دالة مساعدة لفلترة البيانات واسترجاع النتائج المطلوبة فقط
function getFilteredUsers() {
    const searchInput = document.getElementById('search-input');
    const roleFilter = document.getElementById('role-filter');
    const statusFilter = document.getElementById('status-filter'); // ✅ إضافة جديدة

    const searchText = searchInput ? searchInput.value.toLowerCase() : '';
    const selectedRole = roleFilter ? roleFilter.value.toLowerCase() : 'all';
    const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : 'all'; // ✅ إضافة جديدة

    return allUsersData.filter(user => {
        // 1. بحث بالاسم والايميل
        const name = (user.name || '').toLowerCase();
        const email = (user.email || '').toLowerCase();
        const matchesSearch = name.includes(searchText) || email.includes(searchText);

        // 2. فلترة الدور
        const role = (user.role_type || user.role || '').toLowerCase();
        const matchesRole = (selectedRole === 'all') || (role === selectedRole);

        // 3. فلترة الحالة ✅
        const status = (user.status || 'active').toLowerCase();
        const matchesStatus = (selectedStatus === 'all') || (status === selectedStatus);

        return matchesSearch && matchesRole && matchesStatus;
    });
}

// دالة رسم الجدول (تستدعى عند كل تغيير)
function renderUsersTable() {
    const tableBody = document.getElementById('users-table-body');
    const filteredData = getFilteredUsers(); // نجيب البيانات المفلترة

    // حساب التقطيع (Pagination Logic)
    const totalPages = Math.ceil(filteredData.length / usersRowsPerPage) || 1;

    // تصحيح الصفحة الحالية إذا خرجت عن النطاق
    if (currentUsersPage > totalPages) currentUsersPage = totalPages;

    const startIndex = (currentUsersPage - 1) * usersRowsPerPage;
    const endIndex = startIndex + usersRowsPerPage;
    const paginatedUsers = filteredData.slice(startIndex, endIndex);

    // رسم الصفوف
    tableBody.innerHTML = '';

    if (paginatedUsers.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No matching users found.</td></tr>';
    } else {
        paginatedUsers.forEach(user => {
            // محاولة التقاط الساعات بأسماء مختلفة
            const hours = user.total_hours || user.hours_requested || user.charging_hours || user.usage || 0;

            const status = user.status || 'Active';
            const statusClass = (status.toLowerCase() === 'active') ? 'confirmed' : 'cancelled';
            const role = user.role_type || user.role || 'User';

            tableBody.innerHTML += `
                <tr id="user-row-${user.id}">
                    <td>${Utils.escapeHTML(user.name || 'Unknown')}</td>
                    <td>${Utils.escapeHTML(user.email || '-')}</td>
                    <td><span class="badge bg-secondary">${role}</span></td>
                    <td class="text-center">${hours}h</td> <td><span class="status ${statusClass}">${status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-danger-custom delete-user-btn" data-id="${user.id}">Delete</button>
                    </td>
                </tr>`;
        });
    }

    const pageInfo = document.getElementById('page-info');
    if (pageInfo) pageInfo.innerText = `Page ${currentUsersPage} of ${totalPages}`;

    // ✅ التصحيح: التعامل مع خاصية .disabled الحقيقية للزر
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    if (prevBtn) {
        // إذا كنا في الصفحة 1، نضع القفل (true)، غير ذلك نزيله (false)
        prevBtn.disabled = (currentUsersPage === 1);
    }

    if (nextBtn) {
        // إذا وصلنا للصفحة الأخيرة، نضع القفل
        nextBtn.disabled = (currentUsersPage >= totalPages);
    }

    // إعادة تفعيل زر الحذف للصفوف الجديدة
    setupDeleteButtons();
}

function setupDeleteButtons() {
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const userId = e.target.getAttribute('data-id');
            const confirm = await Swal.fire({ title: 'Delete?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' });

            if (confirm.isConfirmed) {
                try {
                    await API.delete(`/api/admin/users/${userId}`);
                    // نعيد تحميل الجدول لتحديث البيانات والأرقام
                    const index = allUsersData.findIndex(u => u.id == userId);
                    if (index > -1) allUsersData.splice(index, 1); // حذف محلياً للسرعة
                    renderUsersTable();
                    Swal.fire('Deleted!', '', 'success');
                } catch (err) { Utils.showError('Failed to delete.'); }
            }
        });
    });
}
// ==========================================
// 3. Stations/Chargers Logic (Admin.json & Public.json)
// // A. صفحة عرض الشواحن (الجدول + الفلترة)
// ==========================================
async function loadChargersPage() {
    const tableBody = document.getElementById('chargers-table-body');
    const filterSelect = document.getElementById('charger-filter-status'); // القائمة التي أضفناها

    if (!tableBody) return;

    // متغير لتخزين جميع البيانات القادمة من السيرفر
    let allChargersData = [];

    // دالة داخلية لرسم الجدول بناءً على داتا معينة
    const renderTable = (chargers) => {
        tableBody.innerHTML = '';
        if (chargers.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No chargers found.</td></tr>';
            return;
        }

        chargers.forEach(charger => {
            const name = charger.station_name || charger.name || 'Unnamed';
            const location = charger.location || 'Unknown';
            // حساب الكبائن: نتأكد أنه رقم
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
                        <a href="edit-charger.html?id=${charger.id}" class="btn btn-sm btn-secondary-custom">Edit</a>
                    </td>
                </tr>`;
        });
    };

    try {
        // 1. جلب البيانات من السيرفر
        const response = await API.get('/api/stations');

        // تخزين البيانات في المتغير
        if (response.stations && Array.isArray(response.stations)) allChargersData = response.stations;
        else if (Array.isArray(response)) allChargersData = response;
        else if (response.data && Array.isArray(response.data)) allChargersData = response.data;

        // 2. الرسم الأولي (عرض كل شيء)
        renderTable(allChargersData);

        // 3. تفعيل الفلترة (عند تغيير القائمة)
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                const selectedStatus = e.target.value.toLowerCase();

                if (selectedStatus === 'all') {
                    // إذا اختار الكل، نعرض البيانات الأصلية كاملة
                    renderTable(allChargersData);
                } else {
                    // نفلتر البيانات ونعرض النتيجة فقط
                    const filteredData = allChargersData.filter(charger =>
                        (charger.status || '').toLowerCase() === selectedStatus
                    );
                    renderTable(filteredData);
                }
            });
        }

    } catch (error) {
        console.error('Error:', error);
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load data.</td></tr>';
    }
}

// ==========================================
// 3. صفحة إضافة حجز جديد (المصححة والمضمونة)
// ==========================================
// async function setupAddBookingPage() {
//     console.log("🚀 Setup Add Booking Page Started...");

//     const form = document.getElementById('add-booking-form');
//     if (!form) return;

//     const userSelect = document.getElementById('booking-user');
//     const stationSelect = document.getElementById('booking-station');
//     const cabinetSelect = document.getElementById('booking-cabinet');
//     const chargerSelect = document.getElementById('booking-charger');

//     // سنخزن البيانات هنا
//     let allStationsData = [];

//     // 1. تعبئة المستخدمين
//     try {
//         const usersResp = await API.get('/api/admin/users');
//         const users = usersResp.users || usersResp.data || usersResp;
//         userSelect.innerHTML = '<option value="" disabled selected>Select User</option>';
//         if (Array.isArray(users)) {
//             users.forEach(u => userSelect.innerHTML += `<option value="${u.id}">${u.name} (${u.email})</option>`);
//         }
//     } catch (e) { console.error('Users Error:', e); }

//     // 2. تعبئة المحطات
//     try {
//         const stationsResp = await API.get('/api/stations');
//         console.log("📦 Full API Response:", stationsResp);

//         // محاولة استخراج المصفوفة بذكاء
//         if (Array.isArray(stationsResp)) allStationsData = stationsResp;
//         else if (stationsResp.stations && Array.isArray(stationsResp.stations)) allStationsData = stationsResp.stations;
//         else if (stationsResp.data && Array.isArray(stationsResp.data)) allStationsData = stationsResp.data;

//         console.log("✅ Parsed Stations Data:", allStationsData);

//         stationSelect.innerHTML = '<option value="" disabled selected>Select Station</option>';
//         if (allStationsData.length > 0) {
//             allStationsData.forEach(st => {
//                 stationSelect.innerHTML += `<option value="${st.id}">${st.station_name || st.name || 'Station ' + st.id}</option>`;
//             });
//         }
//     } catch (e) { console.error('Stations Error:', e); }

//     // 3. عند اختيار المحطة
//     stationSelect.addEventListener('change', function () {
//         const selectedId = this.value; // هذا الرقم قد يكون نصاً "1"
//         console.log("👉 Selected Station ID:", selectedId);

//         // البحث الآمن (نحول الاثنين لنصوص لضمان المطابقة)
//         const station = allStationsData.find(st => String(st.id) === String(selectedId));

//         console.log("🔎 Found Station Object:", station);

//         // إعادة ضبط القوائم
//         cabinetSelect.innerHTML = '<option value="" disabled selected>Select Cabinet</option>';
//         cabinetSelect.disabled = true;
//         chargerSelect.innerHTML = '<option value="" disabled selected>Select Cabinet first</option>';
//         chargerSelect.disabled = true;

//         // التحقق: هل المحطة موجودة؟ وهل فيها كبائن؟
//         if (station) {
//             if (station.cabinets && Array.isArray(station.cabinets) && station.cabinets.length > 0) {
//                 // ✅ الحالة السليمة: تفعيل القائمة
//                 cabinetSelect.disabled = false;
//                 station.cabinets.forEach(cab => {
//                     cabinetSelect.innerHTML += `<option value="${cab.id}">Cabinet ${cab.cabinet_number || cab.id}</option>`;
//                 });
//             } else {
//                 // ❌ الحالة الناقصة
//                 console.warn("⚠️ Station found, but 'cabinets' array is empty or missing!", station);
//                 // محاولة بديلة: ربما البيانات ليست متداخلة؟
//                 cabinetSelect.innerHTML = '<option>No cabinets data found</option>';
//             }
//         } else {
//             console.error("❌ Critical: Could not find station details in memory.");
//         }
//     });

//     // 4. عند اختيار الكابينة
//     cabinetSelect.addEventListener('change', function () {
//         const stationId = stationSelect.value;
//         const cabinetId = this.value;

//         const station = allStationsData.find(st => String(st.id) === String(stationId));
//         const cabinet = station ? station.cabinets.find(c => String(c.id) === String(cabinetId)) : null;

//         chargerSelect.innerHTML = '<option value="" disabled selected>Select Charger</option>';
//         chargerSelect.disabled = true;

//         if (cabinet && cabinet.chargers && cabinet.chargers.length > 0) {
//             chargerSelect.disabled = false;
//             cabinet.chargers.forEach(ch => {
//                 const status = ch.status ? `(${ch.status})` : '';
//                 chargerSelect.innerHTML += `<option value="${ch.id}">Charger ${ch.charger_number || ch.id} ${status}</option>`;
//             });
//         } else {
//             chargerSelect.innerHTML = '<option>No chargers available</option>';
//         }
//     });

//     // 5. الحفظ
//     form.addEventListener('submit', async (e) => {
//         e.preventDefault();
//         const btn = form.querySelector('input[type="submit"]');

//         // التحقق من الحقول قبل الإرسال
//         if (!userSelect.value || !stationSelect.value || !cabinetSelect.value || !chargerSelect.value) {
//             Swal.fire('Missing Data', 'Please select all fields (User, Station, Cabinet, Charger)', 'warning');
//             return;
//         }

//         const data = {
//             user_id: userSelect.value,
//             station_id: stationSelect.value,
//             cabinet_id: cabinetSelect.value,
//             charger_id: chargerSelect.value,
//             start_time: `${document.getElementById('booking-date').value} ${document.getElementById('booking-start-time').value}:00`,
//             end_time: `${document.getElementById('booking-date').value} ${document.getElementById('booking-end-time').value}:00`,
//             status: document.getElementById('booking-status').value
//         };

//         try {
//             if (btn) { btn.value = "Saving..."; btn.disabled = true; }
//             await API.post('/api/admin/bookings', data);
//             Swal.fire('Success', 'Booking Created!', 'success').then(() => window.location.href = 'bookings.html');
//         } catch (err) {
//             Swal.fire('Error', err.message || 'Failed to save', 'error');
//         } finally {
//             if (btn) { btn.value = "Save Booking"; btn.disabled = false; }
//         }
//     });
// }
// ==========================================
// C. صفحة تعديل شاحن (Edit Page)
// ==========================================
async function setupEditChargerPage() {
    // 1. جلب الـ ID من رابط الصفحة (URL)
    const urlParams = new URLSearchParams(window.location.search);
    const stationId = urlParams.get('id');

    if (!stationId) {
        Swal.fire('Error', 'No station ID provided', 'error').then(() => window.location.href = 'chargers.html');
        return;
    }

    const form = document.getElementById('edit-charger-form');

    // 2. جلب بيانات الشاحن وتعبئة الفورم
    try {
        // الرابط: GET /api/stations/{id}
        const response = await API.get(`/api/stations/${stationId}`);
        const station = response.station || response; // حسب شكل الداتا

        document.getElementById('charger-name').value = station.station_name || station.name;
        document.getElementById('cabinets').value = station.total_cabinets || 0;
        document.getElementById('charger-location').value = station.location;
        document.getElementById('charger-status').value = station.status;

    } catch (error) {
        console.error(error);
        Utils.showError('Failed to fetch station details');
    }

    // 3. حفظ التعديلات
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = form.querySelector('input[type="submit"]');

        const updateData = {
            station_name: document.getElementById('charger-name').value,
            total_cabinets: parseInt(document.getElementById('cabinets').value) || 0,
            location: document.getElementById('charger-location').value,
            status: document.getElementById('charger-status').value,

            // الحقول الناقصة نرسلها كما هي لتجنب المشاكل
            department: 'Updated Dept',
            station_code: stationId.toString()
        };

        try {
            Utils.setLoading(submitBtn, true);
            // الرابط: PUT /api/admin/stations/{id}
            await API.put(`/api/admin/stations/${stationId}`, updateData);

            Utils.showSuccess('Updated', 'Charger updated successfully!')
                .then(() => {
                    window.location.href = 'chargers.html';
                });
        } catch (error) {
            Utils.showError(error.message || 'Failed to update charger');
        } finally {
            Utils.setLoading(submitBtn, false, 'Update Charger');
        }
    });
}
// assets/js/app.js

function setupAddChargerPage() {
    const form = document.getElementById('add-charger-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // تعديل 1: تحديد الزر بشكل أدق (لأننا غيرنا الـ HTML ليستخدم button)
        const submitBtn = form.querySelector('button[type="submit"]') || form.querySelector('.btn-submit-custom');

        // تعديل 2: توليد رمز المحطة تلقائياً بدلاً من طلبه من المستخدم
        const autoGeneratedCode = 'ST-' + Math.floor(1000 + Math.random() * 9000);

        const payload = {
            station_name: document.getElementById('charger-name').value,
            station_code: autoGeneratedCode, // إرسال الكود المولد تلقائياً
            location: document.getElementById('charger-location').value,
            total_cabinets: document.getElementById('cabinets').value,
            status: document.getElementById('charger-status').value,
            // التأكد من وجود قيمة للقسم أو إرسال قيمة افتراضية
            department: document.getElementById('charger-dept') ? document.getElementById('charger-dept').value : 'General'
            
        };

        try {
            Utils.setLoading(submitBtn, true);

            // تم اعتماد المسار الصحيح مع /api
            await API.post('/api/admin/stations/create', payload);

            // عرض الكود المولد في رسالة النجاح
            Utils.showSuccess('Created!', `Station added with Code: ${autoGeneratedCode}`)
                .then(() => {
                    window.location.href = 'chargers.html';
                });

        } catch (error) {
            Utils.showError(error.message || 'Failed to add station');
        } finally {
            Utils.setLoading(submitBtn, false, 'Save Charger');
        }
    });
}
// ==========================================
// 4. Notification Logic (Admin.json)
// ==========================================
/**
 * منطق صفحة الإشعارات
 * يربط بين اختيار الجمهور المستهدف وجلب قائمة المستخدمين
 */
async function setupNotificationPage() {
    const form = document.getElementById('compose-notification-form');
    const specificDiv = document.getElementById('specific-user-div');
    const usersContainer = document.getElementById('users-list-container');
    const audienceRadios = document.querySelectorAll('input[name="audience"]');

    if (!form) return;

    // 1. التحكم في ظهور قائمة اختيار المستخدمين
    audienceRadios.forEach(radio => {
        radio.addEventListener('change', async (e) => {
            if (e.target.value === 'specific') {
                specificDiv.style.display = 'block';
                await loadUsersForNotifications(); // جلب المستخدمين عند الطلب
            } else {
                specificDiv.style.display = 'none';
            }
        });
    });

    // 2. دالة جلب المستخدمين وحقنهم في القائمة
    async function loadUsersForNotifications() {
        try {
            usersContainer.innerHTML = '<p class="text-muted small p-2">Loading users...</p>';
            const response = await API.get('/api/admin/users');
            const users = response.users || response.data || response;

            if (users.length === 0) {
                usersContainer.innerHTML = '<p class="text-danger small p-2">No users found.</p>';
                return;
            }

            // توليد الـ HTML للمستخدمين مع تنظيف البيانات
            usersContainer.innerHTML = users.map(user => `
                <div class="form-check mb-2">
                    <input class="form-check-input user-checkbox" type="checkbox" value="${user.id}" id="user-${user.id}">
                    <label class="form-check-label text-white small" for="user-${user.id}">
                        ${Utils.escapeHTML(user.name)} <span class="text-muted">(${Utils.escapeHTML(user.email)})</span>
                    </label>
                </div>
            `).join('');
        } catch (error) {
            usersContainer.innerHTML = '<p class="text-danger small p-2">Failed to load users.</p>';
        }
    }

    // 3. معالجة إرسال النموذج
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        const title = document.getElementById('notif-title').value;
        const message = document.getElementById('notif-message').value;
        const audience = document.querySelector('input[name="audience"]:checked').value;

        // جمع الـ IDs المختارة في حال كان الجمهور محدداً
        const selectedUserIds = Array.from(document.querySelectorAll('.user-checkbox:checked'))
                                     .map(cb => cb.value);

        if (!title || !message) {
            Utils.showError('Please fill in both title and message.');
            return;
        }

        if (audience === 'specific' && selectedUserIds.length === 0) {
            Utils.showError('Please select at least one recipient.');
            return;
        }

        try {
            Utils.setLoading(submitBtn, true);

            // Payload بناءً على Admin.json
            const payload = {
                title: title,
                message: message,
                type: 'warning', 
                target: audience,
                user_ids: audience === 'specific' ? selectedUserIds : null
            };

            await API.post('/api/admin/notifications/send', payload);
            
            Utils.showSuccess('Sent!', 'Notification has been sent successfully.');
            form.reset();
            specificDiv.style.display = 'none';
        } catch (error) {
            Utils.showError(error.message || 'Failed to send notification');
        } finally {
            Utils.setLoading(submitBtn, false, 'Send Notification');
        }
    });
}
// ==========================================
// BOOKINGS PAGE LOGIC
// ==========================================
async function loadBookingsPage() {
    const tableBody = document.getElementById('bookings-table-body');
    const filterSelect = document.getElementById('booking-filter-status');

    if (!tableBody) return;

    // متغير لتخزين البيانات الأصلية للفلترة
    let allBookings = [];

    // دالة رسم الجدول
    const renderTable = (bookings) => {
        tableBody.innerHTML = '';
        if (bookings.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No bookings found.</td></tr>';
            return;
        }

        bookings.forEach(booking => {
            // 1. استخراج اسم المستخدم (مع حماية من القيم الفارغة)
            const userName = booking.user ? booking.user.name : 'Unknown User';

            // 2. استخراج اسم المحطة (استخدمنا الاسم لأنه أوضح من الـ ID)
            const stationName = booking.station ? booking.station.station_name : 'Unknown Station';

            // 3. تنسيق التاريخ والوقت
            const startDate = new Date(booking.start_time);
            const dateStr = startDate.toLocaleDateString('en-GB');
            const timeStr = startDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });

            // 4. تنسيق الحالة والألوان
            const status = booking.status || 'unknown';
            let statusClass = 'pending'; // أصفر افتراضياً

            if (status.toLowerCase() === 'completed') statusClass = 'confirmed'; // أخضر
            if (status.toLowerCase() === 'confirmed') statusClass = 'confirmed'; // أخضر
            if (status.toLowerCase() === 'active') statusClass = 'confirmed'; // أخضر
            if (status.toLowerCase() === 'cancelled') statusClass = 'cancelled'; // أحمر

            tableBody.innerHTML += `
                <tr>
                    <td>${Utils.escapeHTML(userName)}</td>
                    <td>${Utils.escapeHTML(stationName)}</td>
                    <td>${dateStr} <small class="text-muted ms-1">${timeStr}</small></td>
                    <td><span class="status ${statusClass}">${status}</span></td>
                    <td>
                      <a href="view-booking.html?id=${booking.id}" class="btn btn-sm btn-secondary-custom">Details</a>
                    </td>
                </tr>`;
        });
    };

    try {
        // جلب البيانات من السيرفر
        const response = await API.get('/api/admin/bookings');

        // التعامل مع شكل البيانات (حسب الصورة التي أرسلتِها: response.bookings هو المصفوفة)
        if (response.bookings && Array.isArray(response.bookings)) {
            allBookings = response.bookings;
        } else if (Array.isArray(response)) {
            allBookings = response;
        } else if (response.data) {
            allBookings = response.data;
        }

        // رسم الجدول لأول مرة
        renderTable(allBookings);

        // تفعيل الفلترة (Filter)
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                const selectedStatus = e.target.value.toLowerCase();

                if (selectedStatus === 'all') {
                    renderTable(allBookings);
                } else {
                    const filtered = allBookings.filter(b =>
                        (b.status || '').toLowerCase() === selectedStatus
                    );
                    renderTable(filtered);
                }
            });
        }

    } catch (error) {
        console.error('Error loading bookings:', error);
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load bookings.</td></tr>';
    }
}
// ==========================================
// 3. صفحة إضافة حجز جديد (Logic for add-new-booking.html)
// ==========================================
async function setupAddBookingPage() {
    const form = document.getElementById('add-booking-form');
    if (!form) return;

    const userSelect = document.getElementById('booking-user');
    const stationSelect = document.getElementById('booking-station');

    // 1. تعبئة قائمة المستخدمين
    try {
        const usersResp = await API.get('/api/admin/users');
        const users = usersResp.users || usersResp.data || usersResp;

        userSelect.innerHTML = '<option value="" disabled selected>Select User</option>';
        users.forEach(u => {
            userSelect.innerHTML += `<option value="${u.id}">${u.name} (${u.email})</option>`;
        });
    } catch (e) { console.error('Failed to load users', e); }

    // 2. تعبئة قائمة المحطات
    try {
        const stationsResp = await API.get('/api/stations');
        const stations = stationsResp.stations || stationsResp.data || stationsResp;

        stationSelect.innerHTML = '<option value="" disabled selected>Select Station</option>';
        stations.forEach(s => {
            const name = s.station_name || s.name || 'Station ' + s.id;
            stationSelect.innerHTML += `<option value="${s.id}">${name}</option>`;
        });
    } catch (e) { console.error('Failed to load stations', e); }

    // 3. عند الحفظ
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('input[type="submit"]');

        // تجميع البيانات
        const dateVal = document.getElementById('booking-date').value;
        const startTimeVal = document.getElementById('booking-start-time').value;
        const endTimeVal = document.getElementById('booking-end-time').value;

        // دمج التاريخ مع الوقت لعمل Timestamp كامل (Y-m-d H:i:s)
        const startDateTime = `${dateVal} ${startTimeVal}:00`;
        const endDateTime = `${dateVal} ${endTimeVal}:00`;

        const data = {
            user_id: userSelect.value,
            station_id: stationSelect.value,
            start_time: startDateTime,
            end_time: endDateTime,
            status: document.getElementById('booking-status').value
        };

        try {
            Utils.setLoading(btn, true);
            await API.post('/api/admin/bookings', data); // تأكدي من الرابط الصحيح في Postman

            Swal.fire('Success', 'Booking created successfully!', 'success')
                .then(() => window.location.href = 'bookings.html');
        } catch (error) {
            Utils.showError(error.message || 'Failed to create booking');
        } finally {
            Utils.setLoading(btn, false, 'Save Booking');
        }
    });
}
// دالة (وهمية حالياً) لعرض التفاصيل - يمكن برمجتها لاحقاً لفتح مودال
// ==========================================
// 4. صفحة تفاصيل الحجز (Logic for view-booking.html)
// ==========================================
async function setupBookingDetailsPage() {
    const detailIdEl = document.getElementById('detail-id');
    if (!detailIdEl) return; // لسنا في صفحة التفاصيل

    // جلب الـ ID من الرابط (view-booking.html?id=5)
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');

    if (!id) {
        window.location.href = 'bookings.html';
        return;
    }

    try {
        // بما أننا لا نملك endpoint لجلب حجز واحد، سنجلب الكل ونبحث
        const response = await API.get('/api/admin/bookings');
        let bookings = response.bookings || response.data || response;

        const booking = bookings.find(b => b.id == id);

        if (!booking) {
            throw new Error('Booking not found');
        }

        // تعبئة البيانات في الـ HTML
        detailIdEl.innerText = booking.id;

        // الحالة واللون
        const statusEl = document.getElementById('detail-status');
        const status = booking.status || 'Unknown';
        statusEl.innerText = status;
        statusEl.className = 'status'; // reset
        if (status.toLowerCase() === 'confirmed' || status.toLowerCase() === 'completed') statusEl.classList.add('confirmed');
        else if (status.toLowerCase() === 'cancelled') statusEl.classList.add('cancelled');
        else statusEl.classList.add('pending');

        // المستخدم والمحطة
        const userName = booking.user ? booking.user.name : 'Unknown';
        const userEmail = booking.user ? booking.user.email : '';
        document.getElementById('detail-user').innerText = `${userName} (${userEmail})`;

        const stationName = booking.station ? booking.station.station_name : 'Unknown Station';
        document.getElementById('detail-station').innerText = stationName;

        // التواريخ والمدة
        const start = new Date(booking.start_time);
        const end = new Date(booking.end_time);
        const dateStr = start.toLocaleDateString('en-GB');
        const timeFrom = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const timeTo = end.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        document.getElementById('detail-date-time').innerText = `${dateStr}, ${timeFrom} - ${timeTo}`;

        // حساب المدة (إذا لم تأت من السيرفر)
        const duration = booking.duration || Math.round((end - start) / 60000);
        document.getElementById('detail-duration').innerText = `${duration} mins`;

        // زر الإلغاء
        const cancelBtn = document.getElementById('btn-cancel-booking');
        if (status.toLowerCase() !== 'cancelled' && status.toLowerCase() !== 'completed') {
            cancelBtn.style.display = 'inline-block';
            cancelBtn.onclick = async () => {
                const confirm = await Swal.fire({ title: 'Cancel Booking?', icon: 'warning', showCancelButton: true });
                if (confirm.isConfirmed) {
                    try {
                        // طلب الإلغاء (تأكدي من الرابط في Postman)
                        // قد يكون DELETE أو PUT لتحديث الحالة
                        // فرضاً سنستخدم PUT لتحديث الحالة
                        await API.put(`/api/admin/bookings/${id}`, { status: 'cancelled' });
                        // أو await API.delete(`/api/admin/bookings/${id}`);

                        Swal.fire('Cancelled', '', 'success').then(() => location.reload());
                    } catch (e) { Utils.showError('Failed to cancel'); }
                }
            };
        }

    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Could not load booking details', 'error')
            .then(() => window.location.href = 'bookings.html');
    }
}
async function loadSupportPage() {
    const tableBody = document.getElementById('support-table-body');
    const filterSelect = document.getElementById('support-filter-status');

    if (!tableBody) return;

    // دالة رسم الجدول
    const renderTable = (messages) => {
        tableBody.innerHTML = '';
        if (!messages || messages.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No messages found.</td></tr>';
            return;
        }

        messages.forEach(msg => {
            // التعامل مع البيانات القادمة من الباك-إند
            const userName = msg.user ? msg.user.name : (msg.name || 'Unknown');
            const userEmail = msg.user ? msg.user.email : (msg.email || '');
            const dateStr = msg.created_at ? new Date(msg.created_at).toLocaleDateString() : msg.date;
            
            // تحديد الألوان حسب الحالة
            let statusClass = 'pending'; 
            const status = (msg.status || 'new').toLowerCase();
            
            if (status === 'resolved') statusClass = 'confirmed';
            else if (status === 'in progress') statusClass = 'cancelled'; // لون برتقالي تقريباً

            // زر الرد
            const actionBtn = status === 'resolved' 
                ? `<button class="btn btn-sm btn-secondary" disabled>Closed</button>`
                : `<button class="btn btn-sm btn-primary reply-btn" data-id="${msg.id}" data-user="${userName}">Reply</button>`;

            tableBody.innerHTML += `
                <tr id="msg-row-${msg.id}">
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold">${Utils.escapeHTML(userName)}</span>
                            <small class="text-muted">${Utils.escapeHTML(userEmail)}</small>
                        </div>
                    </td>
                    <td>${Utils.escapeHTML(msg.subject || 'No Subject')}</td>
                    <td>${dateStr}</td>
                    <td><span class="status ${statusClass}">${msg.status || 'New'}</span></td>
                    <td>${actionBtn}</td>
                </tr>
            `;
        });
        
        // إعادة تفعيل الأزرار
        setupReplyButtons();
    };

    try {
        // 1. الاتصال بالرابط الحقيقي (هذا هو الناقص حالياً)
        const response = await API.get('/api/admin/messages');
        
        // استخراج المصفوفة
        const allMessages = response.messages || response.data || response;
        
        // 2. الرسم الأولي
        renderTable(allMessages);

        // 3. الفلترة
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                const status = e.target.value.toLowerCase();
                if (status === 'all') {
                    renderTable(allMessages);
                } else {
                    const filtered = allMessages.filter(m => (m.status || '').toLowerCase() === status);
                    renderTable(filtered);
                }
            });
        }

    } catch (error) {
        console.error('Failed to load messages:', error);
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load messages (API Missing).</td></tr>';
    }

    // برمجة زر الرد (موجود في Postman باسم replay message)
    function setupReplyButtons() {
        document.querySelectorAll('.reply-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                const user = btn.getAttribute('data-user');

                const { value: text } = await Swal.fire({
                    title: `Reply to ${user}`,
                    input: 'textarea',
                    inputLabel: 'Your Reply',
                    inputPlaceholder: 'Type message...',
                    showCancelButton: true,
                    confirmButtonText: 'Send'
                });

                if (text) {
                    try {
                        // استخدام الرابط الموجود في Admin.json
                        await API.post(`/api/admin/messages/reply/${id}`, {
                            reply: text,
                            // قد يطلب الباك إند حقولاً إضافية هنا حسب Postman مثل phone/message
                            // لكن المفترض أن الرد فقط يكفي
                        });

                        Swal.fire('Sent!', 'Reply sent successfully', 'success');
                        // تحديث الصفحة لرؤية الحالة الجديدة
                        loadSupportPage(); 
                    } catch (err) {
                        Utils.showError(err.message || 'Failed to send reply');
                    }
                }
            });
        });
    }
}
// ==========================================
// 9. Add Station Logic (New)
// ==========================================
function setupStationForm() {
    const form = document.getElementById('add-station-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');

        // تجهيز البيانات حسب ملف Postman
        const stationData = {
            station_name: document.getElementById('station_name').value,
            station_code: document.getElementById('station_code').value, // تأكدي من وجود هذا الحقل في الـ HTML
            location: document.getElementById('location').value,
            total_cabinets: document.getElementById('total_cabinets').value,
            status: 'maintenance', // أو القيمة التي تختارينها
            department: document.getElementById('department').value
        };

        try {
            Utils.setLoading(submitBtn, true);

            // الرابط من ملف Postman
            await API.post('/api/admin/stations/create', stationData);

            Utils.showSuccess('Created', 'Station added successfully.')
                .then(() => {
                    // تحديث الجدول أو إغلاق المودال
                    loadChargersPage();
                    form.reset();
                });

        } catch (error) {
            Utils.showError(error.message || 'Failed to create station');
        } finally {
            Utils.setLoading(submitBtn, false);
        }
    });
}
// ==========================================
// Helper Functions
// ==========================================
function setupSidebar() {
    const menuToggle = document.getElementById('menu-toggle');
    const wrapper = document.getElementById('wrapper');
    if (menuToggle && wrapper) {
        menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            wrapper.classList.toggle('toggled');
        });
    }
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
// no