/* Shell admin: merender sidebar + topbar agar tidak duplikat di setiap halaman. */
(function () {
    var ICONS = {
        home: '<path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V21h13V9.5"/>',
        news: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h7M7 12h10M7 16h6"/>',
        image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.6"/><path d="m4 18 5-5 4 4 3-3 4 4"/>',
        building: '<path d="M4 21V6l8-3 8 3v15"/><path d="M9 21v-6h6v6M9 9h2M13 9h2M9 12h2M13 12h2"/>',
        page: '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
        menu: '<path d="M4 7h16M4 12h16M4 17h10"/>',
        users: '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 11a3 3 0 1 0 0-6"/><path d="M18 20c0-2.2-.9-4.2-2.3-5.6"/>',
        download: '<path d="M12 3v12"/><path d="m7 11 5 5 5-5"/><path d="M4 21h16"/>',
        calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        book: '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M20 18v3H6.5A2.5 2.5 0 0 1 4 18.5"/>',
        check: '<path d="M20 6 9 17l-5-5"/>',
        award: '<circle cx="12" cy="9" r="5"/><path d="m8.5 13.5-1.5 7 5-2.6 5 2.6-1.5-7"/>',
        settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-2.9-1.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 15H2.8a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.2-2.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10 4.1V4a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 2.9 1.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1A1.7 1.7 0 0 0 20.9 11h.1a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        bell: '<path d="M18 8a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7"/><path d="M10.3 20a2 2 0 0 0 3.4 0"/>',
        plus: '<path d="M12 5v14M5 12h14"/>',
        edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        trash: '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/>',
        eye: '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6"/><circle cx="12" cy="12" r="2.6"/>',
        clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        upload: '<path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 20h16"/>',
        chart: '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        link: '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7L12.5 19"/>',
        info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        warn: '<path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        lock: '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        grid: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        task: '<path d="M9 5H5v16h14V5h-4"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="m8 13 2 2 4-4"/>'
    };

    function icon(name, cls) {
        var p = ICONS[name] || ICONS.info;
        return '<svg viewBox="0 0 24 24" aria-hidden="true"' + (cls ? ' class="' + cls + '"' : '') + '>' + p + '</svg>';
    }
    window.mkIcon = icon;

    var script = document.currentScript || document.querySelector('script[data-active]');
    var active = script ? script.getAttribute('data-active') : '';

    var NAV = [
        ['Utama', [
            ['dashboard', 'Beranda', 'home', 'admin-dashboard.html'],
            ['', 'Laporan', 'chart', '#']
        ]],
        ['Konten Website', [
            ['berita', 'Berita', 'news', 'admin-berita.html'],
            ['galeri', 'Galeri', 'image', 'admin-berita.html'],
            ['fasilitas', 'Fasilitas', 'building', 'admin-berita.html'],
            ['halaman', 'Halaman Statis', 'page', 'admin-berita.html'],
            ['menu', 'Menu Navigasi', 'menu', 'admin-berita.html']
        ]],
        ['Layanan', [
            ['pengguna', 'Pengguna & Role', 'users', 'admin-pengguna.html'],
            ['ppdb', 'Info & Dokumen PPDB', 'download', 'admin-ppdb.html']
        ]],
        ['Akademik', [
            ['jadwal', 'Jadwal Pelajaran', 'calendar', 'admin-jadwal.html'],
            ['presensi', 'Presensi Guru', 'check', 'admin-presensi.html', '3']
        ]],
        ['Akreditasi IA2024', [
            ['akreditasi-dashboard', 'Dashboard Kesiapan', 'award', 'admin-akreditasi-dashboard.html'],
            ['akreditasi-bukti', 'Bank Bukti', 'upload', 'admin-akreditasi-bukti.html'],
            ['akreditasi-dka', 'Editor DKA', 'task', 'admin-akreditasi-dka.html']
        ]],
        ['Sistem', [
            ['pengaturan', 'Pengaturan Situs', 'settings', 'admin-pengaturan.html']
        ]]
    ];

    var html = '<div class="brand">' +
        '<img src="../../public/assets/images/LOGO_MTS 1.png" alt="Logo MTs Al-Huda">' +
        '<div><b>MTs Al-Huda</b><span>Panel Admin</span></div></div>';

    NAV.forEach(function (group) {
        html += '<div class="nav-group"><div class="nav-label">' + group[0] + '</div>';
        group[1].forEach(function (item) {
            var isOn = item[0] === active ? ' active' : '';
            var badge = item[4] ? '<span class="count">' + item[4] + '</span>' : '';
            html += '<a class="nav-item' + isOn + '" href="' + item[3] + '">' +
                icon(item[2]) + '<span>' + item[1] + '</span>' + badge + '</a>';
        });
        html += '</div>';
    });

    var sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.innerHTML = html;

    var topbar = document.getElementById('topbar');
    if (topbar) {
        topbar.innerHTML =
            '<div class="search">' + icon('search') +
            '<input type="text" placeholder="Cari berita, pengguna, dokumen..."></div>' +
            '<div class="spacer"></div>' +
            '<button class="icon-btn" title="Notifikasi">' + icon('bell') + '<span class="dot"></span></button>' +
            '<button class="icon-btn" title="Bantuan">' + icon('info') + '</button>' +
            '<div class="user-chip"><div class="avatar">OP</div>' +
            '<div><b>Operator Madrasah</b><span>Super Admin</span></div></div>';
    }
})();
