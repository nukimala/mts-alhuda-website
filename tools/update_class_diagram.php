<?php
/**
 * Penyelarasan Bahasa Semi-Indonesia untuk Tab 2: Class Diagram (UML)
 * - Nama kelas, atribut, method, dan label relasi menggunakan bahasa semi-Indonesia yang konsisten dengan ERD
 * - Tetap mempertahankan layout zero-collision dan garis siku orthogonal rapi.
 */

function escapeXml($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

$existingFile = dirname(__DIR__) . '/ERD_dan_ClassDiagram_MTs_AlHuda.drawio';
$content = file_get_contents($existingFile);

// Cari posisi pemisah Halaman 1 dan Halaman 2
$page2Start = strpos($content, '<diagram id="page_class_clean"');
if ($page2Start === false) {
    $page2Start = strpos($content, '<diagram id="page_class_complete"');
}
if ($page2Start === false) {
    $page2Start = strpos($content, '<diagram id="page_class_uml"');
}

$page1Content = substr($content, 0, $page2Start);

// Bangun Halaman 2 Baru dengan Bahasa Semi-Indonesia
$p2 = '  <diagram id="page_class_clean" name="Class Diagram (UML)">' . "\n";
$p2 .= '    <mxGraphModel grid="1" page="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" pageScale="1" pageWidth="2800" pageHeight="2000" background="#FFFFFF" math="0" shadow="0">' . "\n";
$p2 .= '      <root>' . "\n";
$p2 .= '        <mxCell id="0"/>' . "\n";
$p2 .= '        <mxCell id="1" parent="0"/>' . "\n";

// Judul Halaman 2
$p2 .= '        <mxCell id="title_class" value="&lt;h1 style=&quot;margin:0;font-family:Arial;font-size:22px;&quot;&gt;CLASS DIAGRAM SISTEM INFORMASI MTS AL-HUDA GONDANG&lt;/h1&gt;&lt;p style=&quot;margin:5px 0 0 0;font-size:13px;color:#555555;&quot;&gt;Arsitektur Berorientasi Objek: Akademik, LMS Pembelajaran &amp;amp; CMS Portal Publik (Semi-Indonesia)&lt;/p&gt;" style="text;html=1;strokeColor=none;fillColor=none;align=center;verticalAlign=middle;whiteSpace=wrap;rounded=0;fontColor=#000000;" vertex="1" parent="1">' . "\n";
$p2 .= '          <mxGeometry x="700" y="20" width="900" height="60" as="geometry"/>' . "\n";
$p2 .= '        </mxCell>' . "\n";

function addUmlClassBox(&$xml, $id, $name, $attrs, $methods, $x, $y, $w = 210, $h = 200) {
    $attrText = '';
    foreach ($attrs as $a) {
        $attrText .= '+ ' . escapeXml($a) . '&lt;br/&gt;';
    }
    $methText = '';
    foreach ($methods as $m) {
        $methText .= '+ ' . escapeXml($m) . '&lt;br/&gt;';
    }

    $val = '&lt;p style=&quot;margin:0px;padding:6px;text-align:center;font-weight:bold;font-family:Arial;font-size:12px;color:#000000;background-color:#F8FAFC;&quot;&gt;' . escapeXml($name) . '&lt;/p&gt;'
         . '&lt;hr size=&quot;1&quot; style=&quot;border-color:#000000;margin:0;&quot;/&gt;'
         . '&lt;p style=&quot;margin:0px;padding:6px;text-align:left;font-family:Arial;font-size:11px;color:#000000;&quot;&gt;' . $attrText . '&lt;/p&gt;'
         . '&lt;hr size=&quot;1&quot; style=&quot;border-color:#000000;margin:0;&quot;/&gt;'
         . '&lt;p style=&quot;margin:0px;padding:6px;text-align:left;font-family:Arial;font-size:11px;color:#000000;&quot;&gt;' . $methText . '&lt;/p&gt;';

    $style = "verticalAlign=top;align=left;overflow=fill;fontSize=11;fontFamily=Arial;html=1;rounded=0;strokeWidth=1.5;fillColor=#FFFFFF;strokeColor=#000000;fontColor=#000000;";
    $xml .= '        <mxCell id="' . $id . '" value="' . $val . '" style="' . $style . '" vertex="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

$cEdgeId = 300;

function addUmlInherit(&$xml, &$edgeId, $subClass, $superClass) {
    $id = 'edge_inh_' . ($edgeId++);
    $style = "edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;strokeColor=#000000;strokeWidth=1.6;endArrow=block;endSize=14;endFill=0;";
    $xml .= '        <mxCell id="' . $id . '" style="' . $style . '" source="' . $subClass . '" target="' . $superClass . '" edge="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry relative="1" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

function addUmlComposition(&$xml, &$edgeId, $whole, $part, $card = '') {
    $id = 'edge_comp_' . ($edgeId++);
    $style = "edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;strokeColor=#000000;strokeWidth=1.6;startArrow=diamond;startSize=14;startFill=1;endArrow=none;fontColor=#000000;fontSize=11;fontFamily=Arial;";
    $xml .= '        <mxCell id="' . $id . '" value="' . escapeXml($card) . '" style="' . $style . '" source="' . $whole . '" target="' . $part . '" edge="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry relative="1" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

function addUmlAssociation(&$xml, &$edgeId, $src, $target, $card = '', $label = '') {
    $id = 'edge_assoc_' . ($edgeId++);
    $style = "edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;strokeColor=#000000;strokeWidth=1.5;endArrow=classic;endSize=8;fontColor=#000000;fontSize=11;fontFamily=Arial;";
    $lbl = trim($card . ' ' . $label);
    $xml .= '        <mxCell id="' . $id . '" value="' . escapeXml($lbl) . '" style="' . $style . '" source="' . $src . '" target="' . $target . '" edge="1" parent="1">' . "\n";
    $xml .= '          <mxGeometry relative="1" as="geometry"/>' . "\n";
    $xml .= '        </mxCell>' . "\n";
}

// =========================================================================
// PILAR 1: AKADEMIK, IDENTITAS & LMS (KIRI & TENGAH)
// =========================================================================

// --- BARIS 1: IDENTITAS (y=120) ---
// Pengguna (User)
addUmlClassBox($p2, 'C_User', 'Pengguna', 
    ['id: int', 'nama: String', 'username: String', 'email: String', 'password_hash: String', 'role: String', 'status_aktif: bool'], 
    ['login(user, pass): bool', 'logout(): void'], 
    600, 120, 210, 190);

// Siswa (Student)
addUmlClassBox($p2, 'C_Student', 'Siswa', 
    ['id: int', 'user_id: int', 'nisn: String', 'nis: String', 'jenis_kelamin: String', 'class_id: int'], 
    ['kumpulTugas(): bool', 'lihatJadwal(): List'], 
    180, 120, 210, 190);
addUmlInherit($p2, $cEdgeId, 'C_Student', 'C_User');

// Guru (Teacher)
addUmlClassBox($p2, 'C_Teacher', 'Guru', 
    ['id: int', 'user_id: int', 'nip: String', 'nuptk: String', 'status_kepegawaian: String', 'bidang_studi: String'], 
    ['catatPresensi(): bool', 'buatMateri(): Materi', 'buatTugas(): Tugas'], 
    1020, 120, 220, 200);
addUmlInherit($p2, $cEdgeId, 'C_Teacher', 'C_User');

// PresensiGuru (Attendance)
addUmlClassBox($p2, 'C_Attendance', 'PresensiGuru', 
    ['id: int', 'teacher_id: int', 'tanggal: Date', 'jenis_presensi: String', 'jam_masuk: Time', 'status_hadir: String', 'lampiran_bukti: String'], 
    ['verifikasi(admin_id): void', 'cekTerlambat(): bool'], 
    1440, 120, 210, 190);
addUmlAssociation($p2, $cEdgeId, 'C_Teacher', 'C_Attendance', '1..*', 'mencatat');

// --- BARIS 2: KELAS & STRUKTUR AKADEMIK (y=430) ---

// Kelas (ClassRoom)
addUmlClassBox($p2, 'C_ClassRoom', 'Kelas', 
    ['id: int', 'academic_year_id: int', 'wali_kelas_id: int', 'tingkat: String', 'nama_kelas: String'], 
    ['tambahSiswa(siswa_id): void'], 
    600, 430, 210, 140);

// TahunAkademik (AcademicYear)
addUmlClassBox($p2, 'C_AcademicYear', 'TahunAkademik', 
    ['id: int', 'nama_tahun: String', 'semester: String', 'status_aktif: bool'], 
    ['aktifkan(): void'], 
    1020, 430, 210, 130);
addUmlComposition($p2, $cEdgeId, 'C_AcademicYear', 'C_ClassRoom', '1..*');

addUmlAssociation($p2, $cEdgeId, 'C_ClassRoom', 'C_Student', '1..*', 'anggota');
addUmlAssociation($p2, $cEdgeId, 'C_Teacher', 'C_ClassRoom', '1', 'wali_kelas');

// --- BARIS 3: MAPEL, KELAS-MAPEL & JADWAL (y=690) ---

// MataPelajaranKelas (ClassSubject Pivot)
addUmlClassBox($p2, 'C_ClassSubject', 'MataPelajaranKelas', 
    ['id: int', 'class_id: int', 'subject_id: int', 'teacher_id: int'], 
    ['getJadwal(): List'], 
    600, 690, 210, 120);
addUmlComposition($p2, $cEdgeId, 'C_ClassRoom', 'C_ClassSubject', '1..*');

// MataPelajaran (Subject)
addUmlClassBox($p2, 'C_Subject', 'MataPelajaran', 
    ['id: int', 'kode_mapel: String', 'nama_mapel: String'], 
    ['getKurikulum(): void'], 
    1020, 690, 210, 120);
addUmlAssociation($p2, $cEdgeId, 'C_Subject', 'C_ClassSubject', '1');
addUmlAssociation($p2, $cEdgeId, 'C_Teacher', 'C_ClassSubject', '1..*', 'mengampu');

// JadwalPelajaran (Schedule)
addUmlClassBox($p2, 'C_Schedule', 'JadwalPelajaran', 
    ['id: int', 'class_subject_id: int', 'teacher_id: int', 'academic_year_id: int', 'hari: String', 'jam_mulai: Time', 'jam_selesai: Time', 'ruangan: String'], 
    ['cekBentrok(): bool'], 
    180, 690, 210, 170);
addUmlComposition($p2, $cEdgeId, 'C_ClassSubject', 'C_Schedule', '1..*');

// --- BARIS 4: LMS (TUGAS, PENGUMPULAN & MATERI) (y=980) ---

// Tugas (Assignment)
addUmlClassBox($p2, 'C_Assignment', 'Tugas', 
    ['id: int', 'class_subject_id: int', 'created_by: int', 'judul_tugas: String', 'deskripsi: String', 'batas_waktu: DateTime'], 
    ['cekDeadline(): bool'], 
    600, 980, 210, 150);
addUmlComposition($p2, $cEdgeId, 'C_ClassSubject', 'C_Assignment', '1..*');

// Materi (Material)
addUmlClassBox($p2, 'C_Material', 'Materi', 
    ['id: int', 'class_subject_id: int', 'created_by: int', 'judul_materi: String', 'isi_materi: String', 'file_materi: String'], 
    ['unduhFile(): File'], 
    1020, 980, 210, 150);
addUmlComposition($p2, $cEdgeId, 'C_ClassSubject', 'C_Material', '1..*');

// PengumpulanTugas (Submission)
addUmlClassBox($p2, 'C_Submission', 'PengumpulanTugas', 
    ['id: int', 'assignment_id: int', 'student_id: int', 'file_jawaban: String', 'waktu_kumpul: DateTime', 'nilai: float', 'catatan_guru: String', 'status: String'], 
    ['beriNilai(skor, catatan): void'], 
    180, 980, 210, 180);
addUmlComposition($p2, $cEdgeId, 'C_Assignment', 'C_Submission', '1..*');
addUmlAssociation($p2, $cEdgeId, 'C_Student', 'C_Submission', '1..*', 'mengumpulkan');

// =========================================================================
// PILAR 2: KONTEN & PORTAL PUBLIK MADRASAH (ZONA KANAN)
// =========================================================================

// Border Pembungkus Pilar CMS
$p2 .= '        <mxCell id="box_cms_border" value="" style="rounded=0;whiteSpace=wrap;html=1;fillColor=none;strokeColor=#999999;strokeWidth=1;dashed=1;" vertex="1" parent="1">' . "\n";
$p2 .= '          <mxGeometry x="1800" y="90" width="880" height="1100" as="geometry"/>' . "\n";
$p2 .= '        </mxCell>' . "\n";
$p2 .= '        <mxCell id="label_cms_pilar" value="&lt;b style=&quot;font-family:Arial;font-size:14px;color:#000000;&quot;&gt;KONTEN &amp;amp; PORTAL PUBLIK MADRASAH&lt;/b&gt;" style="text;html=1;strokeColor=none;fillColor=#FFFFFF;align=center;verticalAlign=middle;whiteSpace=wrap;rounded=0;" vertex="1" parent="1">' . "\n";
$p2 .= '          <mxGeometry x="2070" y="80" width="340" height="25" as="geometry"/>' . "\n";
$p2 .= '        </mxCell>' . "\n";

// --- KOLOM CMS 1 (x=1850) ---
// Berita (Post)
addUmlClassBox($p2, 'C_Post', 'Berita', 
    ['id: int', 'author_id: int', 'judul: String', 'slug: String', 'isi_berita: String', 'status: String'], 
    ['publikasikan(): void'], 
    1850, 140, 200, 150);

// Fasilitas (Facility)
addUmlClassBox($p2, 'C_Facility', 'Fasilitas', 
    ['id: int', 'nama_sarpras: String', 'deskripsi: String', 'foto_sarpras: String', 'kondisi: String'], 
    ['updateKondisi(): void'], 
    1850, 390, 200, 150);

// Agenda (Agenda Acara)
addUmlClassBox($p2, 'C_Agenda', 'Agenda', 
    ['id: int', 'author_id: int', 'nama_agenda: String', 'lokasi: String', 'tgl_mulai: Date', 'tgl_selesai: Date'], 
    ['buatAcara(): void'], 
    1850, 640, 200, 150);

// DokumenPPDB (PPDB Document)
addUmlClassBox($p2, 'C_PPDBDocument', 'DokumenPPDB', 
    ['id: int', 'judul_dokumen: String', 'file_syarat: String', 'jumlah_unduh: int'], 
    ['hitungUnduh(): void'], 
    1850, 890, 200, 140);

// --- KOLOM CMS 2 (x=2400) ---
// Galeri (Gallery)
addUmlClassBox($p2, 'C_Gallery', 'Galeri', 
    ['id: int', 'album: String', 'judul: String', 'file_foto: String', 'keterangan: String'], 
    ['unggahFoto(): void'], 
    2400, 140, 200, 150);

// Pengumuman (Announcement)
addUmlClassBox($p2, 'C_Announcement', 'Pengumuman', 
    ['id: int', 'author_id: int', 'judul: String', 'isi_pengumuman: String', 'tgl_terbit: Date'], 
    ['siarkan(): void'], 
    2400, 390, 200, 150);

// HalamanProfil (Page)
addUmlClassBox($p2, 'C_Page', 'HalamanProfil', 
    ['id: int', 'slug: String', 'judul: String', 'isi_konten: String'], 
    ['updateKonten(): void'], 
    2400, 640, 200, 140);

// PesanKontak (ContactMessage)
addUmlClassBox($p2, 'C_ContactMessage', 'PesanKontak', 
    ['id: int', 'nama_pengirim: String', 'email: String', 'telepon: String', 'isi_pesan: String', 'dibalas_oleh: int'], 
    ['balasPesan(konten): void'], 
    2400, 890, 200, 150);

// Relasi Induk dari Pengguna ke Konten CMS
addUmlAssociation($p2, $cEdgeId, 'C_User', 'C_Post', '1..*', 'mengelola');
addUmlAssociation($p2, $cEdgeId, 'C_User', 'C_Gallery', '1..*', 'mengelola');

$p2 .= '      </root>' . "\n";
$p2 .= '    </mxGraphModel>' . "\n";
$p2 .= '  </diagram>' . "\n";
$p2 .= '</mxfile>' . "\n";

$finalXml = $page1Content . $p2;
file_put_contents($existingFile, $finalXml);
echo "Berhasil menyelaraskan bahasa Class Diagram ke semi-Indonesia!\n";

$downloadsDir = 'C:/Users/Dell/Downloads/ERD_dan_ClassDiagram_MTs_AlHuda.drawio';
@copy($existingFile, $downloadsDir);
if (file_exists($downloadsDir)) {
    echo "Salinan juga di-update di Downloads: " . $downloadsDir . "\n";
}
