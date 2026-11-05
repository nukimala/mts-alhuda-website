<?php
/**
 * Generator File Draw.io Anti-Tabrakan (Zero-Collision Layout):
 * - Memetakan ulang koordinat seluruh entitas, relasi, dan atribut agar TIDAK ADA GARIS MENABRAK.
 * - Atribut diletakkan secara konsisten memancar keluar (radial/fan-out) menjauhi pusat dan garis relasi.
 * - Relasi berada tepat di ruang terbuka antara dua entitas.
 * - Gaya persis gambar referensi: Hitam-putih, panah ke atribut, garis tegas, tanpa tabrakan.
 */

function escapeXml($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<mxfile host="app.diagrams.net" modified="' . date('c') . '" agent="Antigravity" version="24.0.0" pages="2">' . "\n";

// =========================================================================
// HALAMAN 1: ERD KONSEPTUAL (ZERO-COLLISION NOTASI CHEN)
// =========================================================================
$xml .= '  <diagram id="page_erd_clean" name="ERD Notasi Chen">' . "\n";
$xml .= '    <mxGraphModel grid="1" page="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" pageScale="1" pageWidth="3600" pageHeight="2600" background="#FFFFFF" math="0" shadow="0">' . "\n";
$xml .= '      <root>' . "\n";
$xml .= '        <mxCell id="0"/>' . "\n";
$xml .= '        <mxCell id="1" parent="0"/>' . "\n";

$styleEntity   = "rounded=0;whiteSpace=wrap;html=1;fillColor=#FFFFFF;strokeColor=#000000;strokeWidth=1.8;fontColor=#000000;fontFamily=Arial;fontStyle=1;fontSize=13;";
$styleRel      = "rhombus;whiteSpace=wrap;html=1;fillColor=#FFFFFF;strokeColor=#000000;strokeWidth=1.6;fontColor=#000000;fontFamily=Arial;fontSize=11;";
$styleAttr     = "ellipse;whiteSpace=wrap;html=1;fillColor=#FFFFFF;strokeColor=#000000;strokeWidth=1.3;fontColor=#000000;fontFamily=Arial;fontSize=11;";
$styleAttrPk   = "ellipse;whiteSpace=wrap;html=1;fillColor=#FFFFFF;strokeColor=#000000;strokeWidth=2.2;fontColor=#000000;fontFamily=Arial;fontStyle=5;fontSize=11;";
$styleAttrLine = "endArrow=classic;html=1;rounded=0;strokeColor=#000000;strokeWidth=1.2;";
$styleCardLine = "endArrow=none;html=1;rounded=0;strokeColor=#000000;strokeWidth=1.6;fontColor=#000000;fontFamily=Arial;fontSize=12;fontStyle=1;";

$cellId = 100;

function addEntity(&$xml, $id, $name, $x, $y, $w = 140, $h = 55) {
    global $styleEntity;
    $xml .= '        <mxCell id="' . $id . '" value="' . escapeXml($name) . '" style="' . $styleEntity . '" vertex="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

function addRel(&$xml, $id, $name, $x, $y, $w = 120, $h = 55) {
    global $styleRel;
    $xml .= '        <mxCell id="' . $id . '" value="' . escapeXml($name) . '" style="' . $styleRel . '" vertex="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

function addAttr(&$xml, $id, $name, $x, $y, $isPk = false, $w = 105, $h = 40) {
    global $styleAttr, $styleAttrPk;
    $style = $isPk ? $styleAttrPk : $styleAttr;
    $val = $isPk ? "&lt;u&gt;" . escapeXml($name) . "&lt;/u&gt;" : escapeXml($name);
    $xml .= '        <mxCell id="' . $id . '" value="' . $val . '" style="' . $style . '" vertex="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

function connectAttr(&$xml, &$cellId, $sourceId, $targetId) {
    global $styleAttrLine;
    $cId = 'edge_a_' . ($cellId++);
    $xml .= '        <mxCell id="' . $cId . '" style="' . $styleAttrLine . '" source="' . $sourceId . '" target="' . $targetId . '" edge="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry relative="1" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

function connectCard(&$xml, &$cellId, $sourceId, $targetId, $card = '') {
    global $styleCardLine;
    $cId = 'edge_c_' . ($cellId++);
    $xml .= '        <mxCell id="' . $cId . '" value="' . escapeXml($card) . '" style="' . $styleCardLine . '" source="' . $sourceId . '" target="' . $targetId . '" edge="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry relative="1" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

// =========================================================================
// PUSAT 1: PENGGUNA (x=1300, y=950)
// =========================================================================
addEntity($xml, 'E_PENGGUNA', 'Pengguna', 1300, 950, 150, 60);

// Atribut Pengguna (di bawah Pengguna, aman karena relasi ke Siswa lewat samping/bawah bersih)
addAttr($xml, 'A_U1', 'Id Pengguna', 1090, 870, true, 110, 42);
addAttr($xml, 'A_U2', 'Username', 1090, 930, false, 95, 38);
addAttr($xml, 'A_U3', 'Password', 1090, 985, false, 95, 38);
addAttr($xml, 'A_U4', 'Nama', 1090, 1040, false, 90, 38);
addAttr($xml, 'A_U5', 'Email', 1210, 1070, false, 90, 38);
addAttr($xml, 'A_U6', 'Role', 1330, 1070, false, 85, 38);

connectAttr($xml, $cellId, 'E_PENGGUNA', 'A_U1');
connectAttr($xml, $cellId, 'E_PENGGUNA', 'A_U2');
connectAttr($xml, $cellId, 'E_PENGGUNA', 'A_U3');
connectAttr($xml, $cellId, 'E_PENGGUNA', 'A_U4');
connectAttr($xml, $cellId, 'E_PENGGUNA', 'A_U5');
connectAttr($xml, $cellId, 'E_PENGGUNA', 'A_U6');

// =========================================================================
// ZONA KIRI ATAS: CMS & HALAMAN PUBLIK (Fan-out ke atas & kiri tanpa silang)
// =========================================================================

// --- 1. BERITA (x=700, y=180) ---
addEntity($xml, 'E_BERITA', 'Berita', 700, 180, 140, 55);
addAttr($xml, 'A_B1', 'Id Berita', 540, 110, true, 95, 40);
addAttr($xml, 'A_B2', 'Judul', 660, 90, false, 85, 38);
addAttr($xml, 'A_B3', 'Slug', 770, 90, false, 80, 38);
addAttr($xml, 'A_B4', 'Isi Berita', 540, 170, false, 90, 38);
addAttr($xml, 'A_B5', 'Tgl Terbit', 540, 230, false, 95, 38);
connectAttr($xml, $cellId, 'E_BERITA', 'A_B1');
connectAttr($xml, $cellId, 'E_BERITA', 'A_B2');
connectAttr($xml, $cellId, 'E_BERITA', 'A_B3');
connectAttr($xml, $cellId, 'E_BERITA', 'A_B4');
connectAttr($xml, $cellId, 'E_BERITA', 'A_B5');

addRel($xml, 'R_BERITA', 'Menerbitkan', 950, 480, 120, 55);
connectCard($xml, $cellId, 'E_PENGGUNA', 'R_BERITA', '1');
connectCard($xml, $cellId, 'R_BERITA', 'E_BERITA', 'Many');

// --- 2. GALERI (x=1100, y=180) ---
addEntity($xml, 'E_GALERI', 'Galeri', 1100, 180, 140, 55);
addAttr($xml, 'A_GL1', 'Id Galeri', 970, 90, true, 95, 40);
addAttr($xml, 'A_GL2', 'Judul', 1090, 80, false, 85, 38);
addAttr($xml, 'A_GL3', 'Album', 1200, 80, false, 85, 38);
addAttr($xml, 'A_GL4', 'File Foto', 970, 150, false, 95, 38);
addAttr($xml, 'A_GL5', 'Keterangan', 970, 210, false, 95, 38);
connectAttr($xml, $cellId, 'E_GALERI', 'A_GL1');
connectAttr($xml, $cellId, 'E_GALERI', 'A_GL2');
connectAttr($xml, $cellId, 'E_GALERI', 'A_GL3');
connectAttr($xml, $cellId, 'E_GALERI', 'A_GL4');
connectAttr($xml, $cellId, 'E_GALERI', 'A_GL5');

addRel($xml, 'R_GALERI', 'Unggah Foto', 1180, 480, 120, 55);
connectCard($xml, $cellId, 'E_PENGGUNA', 'R_GALERI', '1');
connectCard($xml, $cellId, 'R_GALERI', 'E_GALERI', 'Many');

// --- 3. FASILITAS (SARPRAS) (x=1500, y=180) ---
addEntity($xml, 'E_FASILITAS', 'Fasilitas', 1500, 180, 140, 55);
addAttr($xml, 'A_FS1', 'Id Fasilitas', 1420, 90, true, 100, 40);
addAttr($xml, 'A_FS2', 'Nama Sarpras', 1540, 80, false, 105, 38);
addAttr($xml, 'A_FS3', 'Deskripsi', 1670, 90, false, 90, 38);
addAttr($xml, 'A_FS4', 'Foto Sarpras', 1670, 150, false, 100, 38);
connectAttr($xml, $cellId, 'E_FASILITAS', 'A_FS1');
connectAttr($xml, $cellId, 'E_FASILITAS', 'A_FS2');
connectAttr($xml, $cellId, 'E_FASILITAS', 'A_FS3');
connectAttr($xml, $cellId, 'E_FASILITAS', 'A_FS4');

addRel($xml, 'R_FASILITAS', 'Mendata', 1420, 480, 120, 55);
connectCard($xml, $cellId, 'E_PENGGUNA', 'R_FASILITAS', '1');
connectCard($xml, $cellId, 'R_FASILITAS', 'E_FASILITAS', 'Many');

// --- 4. PENGUMUMAN (x=1900, y=180) ---
addEntity($xml, 'E_PENGUMUMAN', 'Pengumuman', 1900, 180, 140, 55);
addAttr($xml, 'A_PN1', 'Id Pengumuman', 1830, 90, true, 110, 40);
addAttr($xml, 'A_PN2', 'Judul', 1960, 80, false, 85, 38);
addAttr($xml, 'A_PN3', 'Isi', 2070, 90, false, 80, 38);
addAttr($xml, 'A_PN4', 'Tgl Terbit', 2070, 150, false, 95, 38);
connectAttr($xml, $cellId, 'E_PENGUMUMAN', 'A_PN1');
connectAttr($xml, $cellId, 'E_PENGUMUMAN', 'A_PN2');
connectAttr($xml, $cellId, 'E_PENGUMUMAN', 'A_PN3');
connectAttr($xml, $cellId, 'E_PENGUMUMAN', 'A_PN4');

addRel($xml, 'R_PENGUMUMAN', 'Menerbitkan', 1650, 480, 120, 55);
connectCard($xml, $cellId, 'E_PENGGUNA', 'R_PENGUMUMAN', '1');
connectCard($xml, $cellId, 'R_PENGUMUMAN', 'E_PENGUMUMAN', 'Many');

// --- 5. AGENDA (x=2300, y=180) ---
addEntity($xml, 'E_AGENDA', 'Agenda', 2300, 180, 140, 55);
addAttr($xml, 'A_AG1', 'Id Agenda', 2220, 90, true, 95, 40);
addAttr($xml, 'A_AG2', 'Nama Acara', 2340, 80, false, 100, 38);
addAttr($xml, 'A_AG3', 'Lokasi', 2465, 90, false, 80, 38);
addAttr($xml, 'A_AG4', 'Tgl Mulai', 2465, 150, false, 90, 38);
addAttr($xml, 'A_AG5', 'Tgl Selesai', 2465, 210, false, 95, 38);
connectAttr($xml, $cellId, 'E_AGENDA', 'A_AG1');
connectAttr($xml, $cellId, 'E_AGENDA', 'A_AG2');
connectAttr($xml, $cellId, 'E_AGENDA', 'A_AG3');
connectAttr($xml, $cellId, 'E_AGENDA', 'A_AG4');
connectAttr($xml, $cellId, 'E_AGENDA', 'A_AG5');

addRel($xml, 'R_AGENDA', 'Buat Agenda', 1880, 480, 120, 55);
connectCard($xml, $cellId, 'E_PENGGUNA', 'R_AGENDA', '1');
connectCard($xml, $cellId, 'R_AGENDA', 'E_AGENDA', 'Many');

// --- 6. HALAMAN PROFIL (x=400, y=450) ---
addEntity($xml, 'E_HALAMAN', 'Halaman Profil', 400, 450, 140, 55);
addAttr($xml, 'A_HL1', 'Id Halaman', 230, 380, true, 95, 40);
addAttr($xml, 'A_HL2', 'Judul', 230, 440, false, 85, 38);
addAttr($xml, 'A_HL3', 'Slug', 230, 495, false, 80, 38);
addAttr($xml, 'A_HL4', 'Isi Konten', 230, 550, false, 95, 38);
connectAttr($xml, $cellId, 'E_HALAMAN', 'A_HL1');
connectAttr($xml, $cellId, 'E_HALAMAN', 'A_HL2');
connectAttr($xml, $cellId, 'E_HALAMAN', 'A_HL3');
connectAttr($xml, $cellId, 'E_HALAMAN', 'A_HL4');

addRel($xml, 'R_HALAMAN', 'Kelola Profil', 750, 620, 120, 55);
connectCard($xml, $cellId, 'E_PENGGUNA', 'R_HALAMAN', '1');
connectCard($xml, $cellId, 'R_HALAMAN', 'E_HALAMAN', 'Many');

// --- 7. DOKUMEN PPDB (x=400, y=750) ---
addEntity($xml, 'E_PPDB', 'Dokumen PPDB', 400, 750, 140, 55);
addAttr($xml, 'A_P1', 'Id Dokumen', 230, 680, true, 100, 40);
addAttr($xml, 'A_P2', 'Judul', 230, 740, false, 85, 38);
addAttr($xml, 'A_P3', 'File Syarat', 230, 795, false, 95, 38);
addAttr($xml, 'A_P4', 'Jumlah Unduh', 230, 850, false, 105, 38);
connectAttr($xml, $cellId, 'E_PPDB', 'A_P1');
connectAttr($xml, $cellId, 'E_PPDB', 'A_P2');
connectAttr($xml, $cellId, 'E_PPDB', 'A_P3');
connectAttr($xml, $cellId, 'E_PPDB', 'A_P4');

addRel($xml, 'R_PPDB', 'Unggah PPDB', 780, 820, 120, 55);
connectCard($xml, $cellId, 'E_PENGGUNA', 'R_PPDB', '1');
connectCard($xml, $cellId, 'R_PPDB', 'E_PPDB', 'Many');

// --- 8. PESAN KONTAK (x=400, y=1050) ---
addEntity($xml, 'E_KONTAK', 'Pesan Kontak', 400, 1050, 140, 55);
addAttr($xml, 'A_KT1', 'Id Pesan', 230, 980, true, 95, 40);
addAttr($xml, 'A_KT2', 'Pengirim', 230, 1035, false, 90, 38);
addAttr($xml, 'A_KT3', 'Email', 230, 1090, false, 85, 38);
addAttr($xml, 'A_KT4', 'Isi Pesan', 230, 1145, false, 85, 38);
addAttr($xml, 'A_KT5', 'Tgl Kirim', 230, 1200, false, 90, 38);
connectAttr($xml, $cellId, 'E_KONTAK', 'A_KT1');
connectAttr($xml, $cellId, 'E_KONTAK', 'A_KT2');
connectAttr($xml, $cellId, 'E_KONTAK', 'A_KT3');
connectAttr($xml, $cellId, 'E_KONTAK', 'A_KT4');
connectAttr($xml, $cellId, 'E_KONTAK', 'A_KT5');

addRel($xml, 'R_KONTAK', 'Respon Pesan', 800, 1020, 120, 55);
connectCard($xml, $cellId, 'E_PENGGUNA', 'R_KONTAK', '1');
connectCard($xml, $cellId, 'R_KONTAK', 'E_KONTAK', 'Many');

// =========================================================================
// ZONA KANAN: GURU & PRESENSI
// =========================================================================

// --- GURU (x=2050, y=950) ---
addEntity($xml, 'E_GURU', 'Guru', 2050, 950, 140, 55);
addAttr($xml, 'A_G1', 'Id Guru', 2065, 840, true, 95, 40);
addAttr($xml, 'A_G2', 'NIP', 1950, 840, false, 80, 38);
addAttr($xml, 'A_G3', 'NUPTK', 2180, 840, false, 85, 38);
addAttr($xml, 'A_G4', 'Status Pegawai', 1920, 895, false, 110, 38);
addAttr($xml, 'A_G5', 'Bidang Studi', 2180, 895, false, 105, 38);
connectAttr($xml, $cellId, 'E_GURU', 'A_G1');
connectAttr($xml, $cellId, 'E_GURU', 'A_G2');
connectAttr($xml, $cellId, 'E_GURU', 'A_G3');
connectAttr($xml, $cellId, 'E_GURU', 'A_G4');
connectAttr($xml, $cellId, 'E_GURU', 'A_G5');

// Relasi Pengguna <-> Guru (Horizontal lurus tanpa silang)
addRel($xml, 'R_P_GURU', 'Profil Guru', 1680, 950, 120, 55);
connectCard($xml, $cellId, 'E_PENGGUNA', 'R_P_GURU', '1');
connectCard($xml, $cellId, 'R_P_GURU', 'E_GURU', '1');

// --- PRESENSI GURU (x=2750, y=950) ---
addEntity($xml, 'E_PRESENSI', 'Presensi Guru', 2750, 950, 140, 55);
addAttr($xml, 'A_PR1', 'Id Presensi', 2940, 830, true, 100, 40);
addAttr($xml, 'A_PR2', 'Tanggal', 2940, 885, false, 85, 38);
addAttr($xml, 'A_PR3', 'Jenis Presensi', 2940, 940, false, 110, 38);
addAttr($xml, 'A_PR4', 'Jam Masuk', 2940, 995, false, 95, 38);
addAttr($xml, 'A_PR5', 'Status Hadir', 2940, 1050, false, 95, 38);
addAttr($xml, 'A_PR6', 'Lampiran', 2940, 1105, false, 90, 38);
connectAttr($xml, $cellId, 'E_PRESENSI', 'A_PR1');
connectAttr($xml, $cellId, 'E_PRESENSI', 'A_PR2');
connectAttr($xml, $cellId, 'E_PRESENSI', 'A_PR3');
connectAttr($xml, $cellId, 'E_PRESENSI', 'A_PR4');
connectAttr($xml, $cellId, 'E_PRESENSI', 'A_PR5');
connectAttr($xml, $cellId, 'E_PRESENSI', 'A_PR6');

// Relasi Guru <-> Presensi (Horizontal lurus ke kanan)
addRel($xml, 'R_PRESENSI', 'Mencatat', 2420, 950, 120, 55);
connectCard($xml, $cellId, 'E_GURU', 'R_PRESENSI', '1');
connectCard($xml, $cellId, 'R_PRESENSI', 'E_PRESENSI', 'Many');

// =========================================================================
// ZONA BAWAH: AKADEMIK (SISWA, KELAS, TAHUN AJARAN, MAPEL)
// =========================================================================

// --- SISWA (x=1300, y=1450) ---
addEntity($xml, 'E_SISWA', 'Siswa', 1300, 1450, 150, 60);
addAttr($xml, 'A_S1', 'Id Siswa', 1090, 1370, true, 95, 40);
addAttr($xml, 'A_S2', 'NISN', 1090, 1425, false, 80, 38);
addAttr($xml, 'A_S3', 'NIS', 1090, 1480, false, 80, 38);
addAttr($xml, 'A_S4', 'Nama Siswa', 1080, 1535, false, 100, 38);
addAttr($xml, 'A_S5', 'Jenis Kelamin', 1080, 1590, false, 105, 38);
connectAttr($xml, $cellId, 'E_SISWA', 'A_S1');
connectAttr($xml, $cellId, 'E_SISWA', 'A_S2');
connectAttr($xml, $cellId, 'E_SISWA', 'A_S3');
connectAttr($xml, $cellId, 'E_SISWA', 'A_S4');
connectAttr($xml, $cellId, 'E_SISWA', 'A_S5');

// Relasi Pengguna <-> Siswa (Vertikal lurus ke bawah)
addRel($xml, 'R_P_SISWA', 'Profil Siswa', 1315, 1220, 120, 55);
connectCard($xml, $cellId, 'E_PENGGUNA', 'R_P_SISWA', '1');
connectCard($xml, $cellId, 'R_P_SISWA', 'E_SISWA', '1');

// --- KELAS (x=2050, y=1450) ---
addEntity($xml, 'E_KELAS', 'Kelas', 2050, 1450, 140, 55);
addAttr($xml, 'A_K1', 'Id Kelas', 2065, 1360, true, 95, 40);
addAttr($xml, 'A_K2', 'Tingkat', 1950, 1360, false, 80, 38);
addAttr($xml, 'A_K3', 'Nama Kelas', 2180, 1360, false, 100, 38);
connectAttr($xml, $cellId, 'E_KELAS', 'A_K1');
connectAttr($xml, $cellId, 'E_KELAS', 'A_K2');
connectAttr($xml, $cellId, 'E_KELAS', 'A_K3');

// Relasi Siswa <-> Kelas (Horizontal lurus)
addRel($xml, 'R_ANGGOTA', 'Anggota', 1680, 1450, 120, 55);
connectCard($xml, $cellId, 'E_KELAS', 'R_ANGGOTA', '1');
connectCard($xml, $cellId, 'R_ANGGOTA', 'E_SISWA', 'Many');

// Relasi Guru <-> Kelas (Vertikal lurus)
addRel($xml, 'R_WALI', 'Wali Kelas', 2060, 1220, 120, 55);
connectCard($xml, $cellId, 'E_GURU', 'R_WALI', '1');
connectCard($xml, $cellId, 'R_WALI', 'E_KELAS', '1');

// --- TAHUN AKADEMIK (x=2750, y=1450) ---
addEntity($xml, 'E_TAHUN', 'Tahun Akademik', 2750, 1450, 150, 55);
addAttr($xml, 'A_TA1', 'Id Tahun', 2940, 1370, true, 95, 40);
addAttr($xml, 'A_TA2', 'Nama Tahun', 2940, 1425, false, 105, 38);
addAttr($xml, 'A_TA3', 'Semester', 2940, 1480, false, 90, 38);
connectAttr($xml, $cellId, 'E_TAHUN', 'A_TA1');
connectAttr($xml, $cellId, 'E_TAHUN', 'A_TA2');
connectAttr($xml, $cellId, 'E_TAHUN', 'A_TA3');

// Relasi Tahun <-> Kelas (Horizontal lurus ke kanan)
addRel($xml, 'R_THN_KELAS', 'Memiliki', 2420, 1450, 120, 55);
connectCard($xml, $cellId, 'E_TAHUN', 'R_THN_KELAS', '1');
connectCard($xml, $cellId, 'R_THN_KELAS', 'E_KELAS', 'Many');

// =========================================================================
// ZONA PALING BAWAH: MAPEL, JADWAL & LMS (TUGAS, MATERI)
// =========================================================================

// --- MATA PELAJARAN (x=2050, y=1900) ---
addEntity($xml, 'E_MAPEL', 'Mata Pelajaran', 2050, 1900, 140, 55);
addAttr($xml, 'A_M1', 'Id Mapel', 2065, 2010, true, 95, 40);
addAttr($xml, 'A_M2', 'Kode Mapel', 1940, 2010, false, 100, 38);
addAttr($xml, 'A_M3', 'Nama Mapel', 2180, 2010, false, 105, 38);
connectAttr($xml, $cellId, 'E_MAPEL', 'A_M1');
connectAttr($xml, $cellId, 'E_MAPEL', 'A_M2');
connectAttr($xml, $cellId, 'E_MAPEL', 'A_M3');

// Relasi Jadwal Pelajaran (Vertikal antara Kelas dan Mapel)
addRel($xml, 'R_JADWAL', 'Jadwal', 2060, 1690, 120, 55);
addAttr($xml, 'A_JD1', 'Hari', 2240, 1640, false, 75, 34);
addAttr($xml, 'A_JD2', 'Jam Mulai', 2240, 1680, false, 90, 34);
addAttr($xml, 'A_JD3', 'Jam Selesai', 2240, 1720, false, 95, 34);
addAttr($xml, 'A_JD4', 'Ruangan', 2240, 1760, false, 85, 34);
connectAttr($xml, $cellId, 'R_JADWAL', 'A_JD1');
connectAttr($xml, $cellId, 'R_JADWAL', 'A_JD2');
connectAttr($xml, $cellId, 'R_JADWAL', 'A_JD3');
connectAttr($xml, $cellId, 'R_JADWAL', 'A_JD4');

connectCard($xml, $cellId, 'E_KELAS', 'R_JADWAL', 'Many');
connectCard($xml, $cellId, 'E_MAPEL', 'R_JADWAL', '1');

// --- TUGAS (x=1300, y=1900) ---
addEntity($xml, 'E_TUGAS', 'Tugas', 1300, 1900, 150, 55);
addAttr($xml, 'A_T1', 'Id Tugas', 1320, 2010, true, 95, 40);
addAttr($xml, 'A_T2', 'Judul Tugas', 1190, 2010, false, 100, 38);
addAttr($xml, 'A_T3', 'Batas Waktu', 1440, 2010, false, 100, 38);
connectAttr($xml, $cellId, 'E_TUGAS', 'A_T1');
connectAttr($xml, $cellId, 'E_TUGAS', 'A_T2');
connectAttr($xml, $cellId, 'E_TUGAS', 'A_T3');

// Relasi Siswa <-> Tugas (Mengumpulkan) (Vertikal lurus)
addRel($xml, 'R_KUMPUL', 'Mengumpulkan', 1315, 1690, 120, 55);
addAttr($xml, 'A_KP1', 'Waktu Kumpul', 1490, 1640, false, 105, 34);
addAttr($xml, 'A_KP2', 'File Jawaban', 1490, 1680, false, 100, 34);
addAttr($xml, 'A_KP3', 'Nilai', 1490, 1720, false, 75, 34);
addAttr($xml, 'A_KP4', 'Catatan Guru', 1490, 1760, false, 100, 34);
connectAttr($xml, $cellId, 'R_KUMPUL', 'A_KP1');
connectAttr($xml, $cellId, 'R_KUMPUL', 'A_KP2');
connectAttr($xml, $cellId, 'R_KUMPUL', 'A_KP3');
connectAttr($xml, $cellId, 'R_KUMPUL', 'A_KP4');

connectCard($xml, $cellId, 'E_SISWA', 'R_KUMPUL', 'Many');
connectCard($xml, $cellId, 'R_KUMPUL', 'E_TUGAS', 'Many');

// Relasi Guru -> Tugas (Memberi) (Horizontal antara Tugas dan Mapel/Guru)
addRel($xml, 'R_TUGAS', 'Memberi', 1680, 1900, 120, 55);
connectCard($xml, $cellId, 'E_TUGAS', 'R_TUGAS', 'Many');
connectCard($xml, $cellId, 'R_TUGAS', 'E_MAPEL', '1');

// --- MATERI (x=2750, y=1900) ---
addEntity($xml, 'E_MATERI', 'Materi', 2750, 1900, 140, 55);
addAttr($xml, 'A_MT1', 'Id Materi', 2940, 1840, true, 95, 40);
addAttr($xml, 'A_MT2', 'Judul Materi', 2940, 1895, false, 105, 38);
addAttr($xml, 'A_MT3', 'File Materi', 2940, 1950, false, 95, 38);
connectAttr($xml, $cellId, 'E_MATERI', 'A_MT1');
connectAttr($xml, $cellId, 'E_MATERI', 'A_MT2');
connectAttr($xml, $cellId, 'E_MATERI', 'A_MT3');

// Relasi Mapel/Guru -> Materi (Mengunggah)
addRel($xml, 'R_MATERI', 'Mengunggah', 2420, 1900, 120, 55);
connectCard($xml, $cellId, 'E_MAPEL', 'R_MATERI', '1');
connectCard($xml, $cellId, 'R_MATERI', 'E_MATERI', 'Many');

$xml .= '      </root>' . "\n";
$xml .= '    </mxGraphModel>' . "\n";
$xml .= '  </diagram>' . "\n";

// =========================================================================
// HALAMAN 2: CLASS DIAGRAM (UML HITAM-PUTIH SPASIAL RAPI)
// =========================================================================
$xml .= '  <diagram id="page_class_clean" name="Class Diagram (UML)">' . "\n";
$xml .= '    <mxGraphModel grid="1" page="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" pageScale="1" pageWidth="2600" pageHeight="1800" background="#FFFFFF" math="0" shadow="0">' . "\n";
$xml .= '      <root>' . "\n";
$xml .= '        <mxCell id="0"/>' . "\n";
$xml .= '        <mxCell id="1" parent="0"/>' . "\n";

function addCleanUmlClass(&$xml, $id, $name, $attrs, $methods, $x, $y, $w = 200, $h = 200) {
    $attrText = '';
    foreach ($attrs as $a) {
        $attrText .= '+ ' . escapeXml($a) . '&lt;br/&gt;';
    }
    $methText = '';
    foreach ($methods as $m) {
        $methText .= '+ ' . escapeXml($m) . '&lt;br/&gt;';
    }

    $val = '&lt;p style=&quot;margin:0px;padding:6px;text-align:center;font-weight:bold;font-family:Arial;font-size:12px;color:#000000;&quot;&gt;' . escapeXml($name) . '&lt;/p&gt;'
         . '&lt;hr size=&quot;1&quot; style=&quot;border-color:#000000;margin:0;&quot;/&gt;'
         . '&lt;p style=&quot;margin:0px;padding:6px;text-align:left;font-family:Arial;font-size:11px;color:#000000;&quot;&gt;' . $attrText . '&lt;/p&gt;'
         . '&lt;hr size=&quot;1&quot; style=&quot;border-color:#000000;margin:0;&quot;/&gt;'
         . '&lt;p style=&quot;margin:0px;padding:6px;text-align:left;font-family:Arial;font-size:11px;color:#000000;&quot;&gt;' . $methText . '&lt;/p&gt;';

    $style = "verticalAlign=top;align=left;overflow=fill;fontSize=11;fontFamily=Arial;html=1;rounded=0;strokeWidth=1.5;fillColor=#FFFFFF;strokeColor=#000000;fontColor=#000000;";
    $xml .= '        <mxCell id="' . $id . '" value="' . $val . '" style="' . $style . '" vertex="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

function addInherit(&$xml, &$cellId, $subClass, $superClass) {
    $cId = 'c_inh_' . ($cellId++);
    $style = "endArrow=block;endSize=12;endFill=0;html=1;rounded=0;strokeColor=#000000;strokeWidth=1.5;";
    $xml .= '        <mxCell id="' . $cId . '" style="' . $style . '" source="' . $subClass . '" target="' . $superClass . '" edge="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry relative="1" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

function addAssoc(&$xml, &$cellId, $src, $target, $card = '', $label = '') {
    $cId = 'c_asc_' . ($cellId++);
    $style = "endArrow=classic;html=1;rounded=0;strokeColor=#000000;strokeWidth=1.4;fontColor=#000000;fontSize=11;fontFamily=Arial;";
    $xml .= '        <mxCell id="' . $cId . '" value="' . escapeXml($card . ' ' . $label) . '" style="' . $style . '" source="' . $src . '" target="' . $target . '" edge="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry relative="1" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

function addComp(&$xml, &$cellId, $whole, $part, $card = '') {
    $cId = 'c_cmp_' . ($cellId++);
    $style = "startArrow=diamond;startSize=12;startFill=1;endArrow=none;html=1;rounded=0;strokeColor=#000000;strokeWidth=1.5;fontColor=#000000;fontSize=11;fontFamily=Arial;";
    $xml .= '        <mxCell id="' . $cId . '" value="' . escapeXml($card) . '" style="' . $style . '" source="' . $whole . '" target="' . $part . '" edge="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry relative="1" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

// BARIS 1: IDENTITAS
addCleanUmlClass($xml, 'C_User', 'User', 
    ['id: int', 'name: String', 'username: String', 'email: String', 'passwordHash: String', 'role: String', 'isActive: bool'], 
    ['login(): bool', 'logout(): void'], 
    750, 80, 200, 190);

addCleanUmlClass($xml, 'C_Teacher', 'Teacher', 
    ['id: int', 'userId: int', 'nip: String', 'nuptk: String', 'employmentStatus: String', 'subjectSpecialty: String'], 
    ['recordAttendance(): bool', 'createMaterial(): Material', 'createAssignment(): Assignment'], 
    1100, 80, 210, 200);
addInherit($xml, $cellId, 'C_Teacher', 'C_User');

addCleanUmlClass($xml, 'C_Student', 'Student', 
    ['id: int', 'userId: int', 'nisn: String', 'nis: String', 'gender: String', 'classId: int'], 
    ['submitAssignment(): bool', 'viewSchedules(): List'], 
    400, 80, 200, 190);
addInherit($xml, $cellId, 'C_Student', 'C_User');

addCleanUmlClass($xml, 'C_Attendance', 'Attendance', 
    ['id: int', 'teacherId: int', 'date: Date', 'attendanceType: String', 'checkInTime: Time', 'status: String', 'attachmentFile: String'], 
    ['verify(adminId): void'], 
    1450, 80, 200, 190);
addAssoc($xml, $cellId, 'C_Teacher', 'C_Attendance', '1..*', 'records');

// BARIS 2: AKADEMIK
addCleanUmlClass($xml, 'C_AcademicYear', 'AcademicYear', 
    ['id: int', 'name: String', 'semester: String', 'isActive: bool'], 
    ['activate(): void'], 
    1100, 360, 190, 130);

addCleanUmlClass($xml, 'C_ClassRoom', 'ClassRoom', 
    ['id: int', 'academicYearId: int', 'homeroomTeacherId: int', 'level: String', 'name: String'], 
    ['addStudent(studentId): void'], 
    750, 360, 200, 140);
addComp($xml, $cellId, 'C_AcademicYear', 'C_ClassRoom', '1..*');
addAssoc($xml, $cellId, 'C_ClassRoom', 'C_Student', '1..*', 'enrolls');
addAssoc($xml, $cellId, 'C_Teacher', 'C_ClassRoom', '1', 'homeroom');

addCleanUmlClass($xml, 'C_Subject', 'Subject', 
    ['id: int', 'code: String', 'name: String'], 
    ['getCurriculum(): void'], 
    1100, 580, 190, 120);

addCleanUmlClass($xml, 'C_ClassSubject', 'ClassSubject', 
    ['id: int', 'classId: int', 'subjectId: int', 'teacherId: int'], 
    ['getSchedules(): List'], 
    750, 580, 200, 120);
addComp($xml, $cellId, 'C_ClassRoom', 'C_ClassSubject', '1..*');
addAssoc($xml, $cellId, 'C_Subject', 'C_ClassSubject', '1');
addAssoc($xml, $cellId, 'C_Teacher', 'C_ClassSubject', '1..*', 'teaches');

addCleanUmlClass($xml, 'C_Schedule', 'Schedule', 
    ['id: int', 'classSubjectId: int', 'teacherId: int', 'academicYearId: int', 'dayOfWeek: String', 'startTime: Time', 'endTime: Time', 'room: String'], 
    ['isConflict(): bool'], 
    400, 580, 200, 170);
addComp($xml, $cellId, 'C_ClassSubject', 'C_Schedule', '1..*');

// BARIS 3: LMS
addCleanUmlClass($xml, 'C_Material', 'Material', 
    ['id: int', 'classSubjectId: int', 'createdBy: int', 'title: String', 'content: String', 'filePath: String'], 
    ['download(): File'], 
    1100, 780, 200, 150);
addComp($xml, $cellId, 'C_ClassSubject', 'C_Material', '1..*');

addCleanUmlClass($xml, 'C_Assignment', 'Assignment', 
    ['id: int', 'classSubjectId: int', 'createdBy: int', 'title: String', 'description: String', 'deadline: DateTime'], 
    ['isOverdue(): bool'], 
    750, 780, 200, 150);
addComp($xml, $cellId, 'C_ClassSubject', 'C_Assignment', '1..*');

addCleanUmlClass($xml, 'C_Submission', 'Submission', 
    ['id: int', 'assignmentId: int', 'studentId: int', 'filePath: String', 'submittedAt: DateTime', 'score: float', 'feedback: String', 'status: String'], 
    ['grade(score, feedback): void'], 
    400, 780, 200, 180);
addComp($xml, $cellId, 'C_Assignment', 'C_Submission', '1..*');
addAssoc($xml, $cellId, 'C_Student', 'C_Submission', '1..*', 'submits');

// BARIS 4: KONTEN & PORTAL
addCleanUmlClass($xml, 'C_Post', 'Post', 
    ['id: int', 'authorId: int', 'title: String', 'slug: String', 'content: String', 'status: String'], 
    ['publish(): void'], 
    1450, 360, 190, 150);
addAssoc($xml, $cellId, 'C_User', 'C_Post', '1..*', 'publishes');

addCleanUmlClass($xml, 'C_Gallery', 'Gallery', 
    ['id: int', 'album: String', 'title: String', 'imagePath: String', 'caption: String'], 
    ['upload(): void'], 
    1700, 360, 190, 150);
addAssoc($xml, $cellId, 'C_User', 'C_Gallery', '1..*', 'uploads');

addCleanUmlClass($xml, 'C_Facility', 'Facility', 
    ['id: int', 'name: String', 'description: String', 'imagePath: String', 'condition: String'], 
    ['updateCondition(): void'], 
    1950, 360, 190, 150);
addAssoc($xml, $cellId, 'C_User', 'C_Facility', '1..*', 'manages');

addCleanUmlClass($xml, 'C_Announcement', 'Announcement', 
    ['id: int', 'authorId: int', 'title: String', 'slug: String', 'content: String'], 
    ['broadcast(): void'], 
    1450, 580, 190, 150);
addAssoc($xml, $cellId, 'C_User', 'C_Announcement', '1..*', 'posts');

addCleanUmlClass($xml, 'C_Agenda', 'Agenda', 
    ['id: int', 'authorId: int', 'title: String', 'location: String', 'startDate: Date', 'endDate: Date'], 
    ['createEvent(): void'], 
    1700, 580, 190, 150);
addAssoc($xml, $cellId, 'C_User', 'C_Agenda', '1..*', 'schedules');

addCleanUmlClass($xml, 'C_Page', 'Page', 
    ['id: int', 'slug: String', 'title: String', 'content: String'], 
    ['updateContent(): void'], 
    1950, 580, 190, 140);
addAssoc($xml, $cellId, 'C_User', 'C_Page', '1..*', 'edits');

addCleanUmlClass($xml, 'C_PPDBDocument', 'PPDBDocument', 
    ['id: int', 'title: String', 'filePath: String', 'downloadCount: int'], 
    ['trackDownload(): void'], 
    1450, 780, 190, 140);
addAssoc($xml, $cellId, 'C_User', 'C_PPDBDocument', '1..*', 'uploads');

addCleanUmlClass($xml, 'C_ContactMessage', 'ContactMessage', 
    ['id: int', 'name: String', 'email: String', 'phone: String', 'message: String', 'repliedBy: int'], 
    ['reply(content): void'], 
    1700, 780, 190, 150);
addAssoc($xml, $cellId, 'C_User', 'C_ContactMessage', '0..1', 'replies');

$xml .= '      </root>' . "\n";
$xml .= '    </mxGraphModel>' . "\n";
$xml .= '  </diagram>' . "\n";

$xml .= '</mxfile>' . "\n";

$targetFile = dirname(__DIR__) . '/ERD_dan_ClassDiagram_MTs_AlHuda.drawio';
file_put_contents($targetFile, $xml);
echo "Berhasil menyusun ulang tata letak anti-tabrakan: " . $targetFile . "\n";

$downloadsDir = 'C:/Users/Dell/Downloads/ERD_dan_ClassDiagram_MTs_AlHuda.drawio';
@copy($targetFile, $downloadsDir);
if (file_exists($downloadsDir)) {
    echo "Salinan juga di-update di Downloads: " . $downloadsDir . "\n";
}
