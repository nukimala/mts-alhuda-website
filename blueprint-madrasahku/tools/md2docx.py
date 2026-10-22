"""Konversi file Markdown blueprint menjadi dokumen DOCX (A4, formal, siap kirim)."""
import re
import sys
from pathlib import Path

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

GREEN = "15803D"
DARK_GREEN = "0B3D1E"
SOFT = "F9FAFB"
BORDER = "E1ECE5"
TEXT = RGBColor(0x11, 0x18, 0x27)
GRAY = RGBColor(0x6B, 0x72, 0x80)

BODY_FONT = "Calibri"
MONO_FONT = "Consolas"


# ---------------------------------------------------------------- utilitas xml
def shade(cell, fill):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), fill)
    tc_pr.append(shd)


def repeat_header(row):
    tr_pr = row._tr.get_or_add_trPr()
    el = OxmlElement("w:tblHeader")
    el.set(qn("w:val"), "true")
    tr_pr.append(el)


def cell_margins(table, top=60, bottom=60, left=100, right=100):
    tbl_pr = table._tbl.tblPr
    mar = OxmlElement("w:tblCellMar")
    for tag, val in (("top", top), ("left", left), ("bottom", bottom), ("right", right)):
        node = OxmlElement(f"w:{tag}")
        node.set(qn("w:w"), str(val))
        node.set(qn("w:type"), "dxa")
        mar.append(node)
    tbl_pr.append(mar)


def add_field(paragraph, instr):
    run = paragraph.add_run()
    fld_begin = OxmlElement("w:fldChar")
    fld_begin.set(qn("w:fldCharType"), "begin")
    instr_el = OxmlElement("w:instrText")
    instr_el.set(qn("xml:space"), "preserve")
    instr_el.text = instr
    fld_end = OxmlElement("w:fldChar")
    fld_end.set(qn("w:fldCharType"), "end")
    run._r.append(fld_begin)
    run._r.append(instr_el)
    run._r.append(fld_end)


# ------------------------------------------------------------ format inline
TOKEN = re.compile(r"(\*\*.+?\*\*|`.+?`|\*[^*]+?\*)")


def add_inline(paragraph, text, size=None, color=None, bold=False, italic=False):
    for part in TOKEN.split(text):
        if not part:
            continue
        if part.startswith("**") and part.endswith("**") and len(part) > 4:
            r = paragraph.add_run(part[2:-2])
            r.bold = True
        elif part.startswith("`") and part.endswith("`") and len(part) > 2:
            r = paragraph.add_run(part[1:-1])
            r.font.name = MONO_FONT
            r.font.size = Pt((size or 10.5) - 1)
            r.font.color.rgb = RGBColor(0x0B, 0x3D, 0x1E)
        elif part.startswith("*") and part.endswith("*") and len(part) > 2:
            r = paragraph.add_run(part[1:-1])
            r.italic = True
        else:
            r = paragraph.add_run(part)
        if bold:
            r.bold = True
        if italic:
            r.italic = True
        if size:
            r.font.size = Pt(size)
        if color is not None:
            r.font.color.rgb = color
    return paragraph


# ------------------------------------------------------------------- builder
def build(md_path: Path, out_path: Path):
    doc = Document()

    sec = doc.sections[0]
    sec.page_width = Cm(21.0)
    sec.page_height = Cm(29.7)
    sec.left_margin = Cm(2.2)
    sec.right_margin = Cm(2.2)
    sec.top_margin = Cm(2.0)
    sec.bottom_margin = Cm(1.8)

    normal = doc.styles["Normal"]
    normal.font.name = BODY_FONT
    normal.font.size = Pt(10.5)
    normal.font.color.rgb = TEXT
    normal.element.rPr.rFonts.set(qn("w:eastAsia"), BODY_FONT)
    normal.paragraph_format.space_after = Pt(6)
    normal.paragraph_format.line_spacing = 1.15

    for name, size in (("Heading 1", 16), ("Heading 2", 13), ("Heading 3", 11.5), ("Heading 4", 10.5)):
        st = doc.styles[name]
        st.font.name = BODY_FONT
        st.font.size = Pt(size)
        st.font.bold = True
        st.font.color.rgb = RGBColor(0x0B, 0x3D, 0x1E)
        st.paragraph_format.space_before = Pt(14 if size > 12 else 10)
        st.paragraph_format.space_after = Pt(6)
        st.paragraph_format.keep_with_next = True

    # footer
    footer_p = sec.footer.paragraphs[0]
    footer_p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = footer_p.add_run("Dokumen Permintaan Persetujuan Rancangan · MTs Al-Huda Gondang · v1.0 · Halaman ")
    r.font.size = Pt(8)
    r.font.color.rgb = GRAY
    add_field(footer_p, "PAGE")
    for run in footer_p.runs:
        run.font.size = Pt(8)
        run.font.color.rgb = GRAY

    lines = md_path.read_text(encoding="utf-8").split("\n")
    i = 0
    seen_h2 = False
    in_cover = True

    def parse_table(block):
        rows = []
        for ln in block:
            cells = [c.strip() for c in ln.strip().strip("|").split("|")]
            rows.append(cells)
        return rows

    def add_table(rows):
        nonlocal in_cover
        header, body = rows[0], rows[1:]
        ncols = len(header)
        table = doc.add_table(rows=1, cols=ncols)
        table.style = "Table Grid"
        table.alignment = WD_TABLE_ALIGNMENT.CENTER
        table.autofit = True
        cell_margins(table)
        for idx, text in enumerate(header):
            c = table.rows[0].cells[idx]
            c.text = ""
            p = c.paragraphs[0]
            p.paragraph_format.space_after = Pt(0)
            add_inline(p, text, size=9, color=RGBColor(0xFF, 0xFF, 0xFF), bold=True)
            shade(c, GREEN)
        repeat_header(table.rows[0])
        for ridx, row in enumerate(body):
            cells = table.add_row().cells
            for cidx in range(ncols):
                val = row[cidx] if cidx < len(row) else ""
                c = cells[cidx]
                c.text = ""
                p = c.paragraphs[0]
                p.paragraph_format.space_after = Pt(0)
                add_inline(p, val, size=9)
                if ridx % 2 == 1:
                    shade(c, SOFT)
        doc.add_paragraph().paragraph_format.space_after = Pt(2)
        in_cover = False

    while i < len(lines):
        line = lines[i].rstrip()
        stripped = line.strip()

        if not stripped:
            i += 1
            continue

        # tabel
        if stripped.startswith("|") and i + 1 < len(lines) and re.match(r"^\|[\s:|-]+\|$", lines[i + 1].strip()):
            block = []
            while i < len(lines) and lines[i].strip().startswith("|"):
                block.append(lines[i].strip())
                i += 1
            rows = parse_table(block)
            add_table(rows)
            continue

        # heading
        m = re.match(r"^(#{1,4})\s+(.*)$", stripped)
        if m:
            level = len(m.group(1))
            text = m.group(2).strip()
            if level == 1:
                p = doc.add_paragraph()
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                p.paragraph_format.space_before = Pt(24)
                p.paragraph_format.space_after = Pt(4)
                add_inline(p, text, size=20, color=RGBColor(0x0B, 0x3D, 0x1E), bold=True)
            elif level == 2 and in_cover:
                # sub-judul halaman sampul
                p = doc.add_paragraph()
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                p.paragraph_format.space_after = Pt(16)
                add_inline(p, text, size=12, color=GRAY, bold=True)
            elif level == 2:
                if not seen_h2:
                    doc.add_page_break()
                    seen_h2 = True
                p = doc.add_heading(level=1)
                add_inline(p, text, size=16, color=RGBColor(0x0B, 0x3D, 0x1E), bold=True)
                p.paragraph_format.space_before = Pt(2)
            else:
                p = doc.add_heading(level=min(level, 4))
                add_inline(p, text, size=13 if level == 3 else 11.5,
                           color=RGBColor(0x0B, 0x3D, 0x1E), bold=True)
            i += 1
            continue

        # sub-judul markdown (baris ke-2 yang diawali "## " pada heading1)
        if stripped.startswith("## ") and not re.match(r"^#{1,4}\s", stripped):
            # subtitle pada halaman sampul
            p = doc.add_paragraph()
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            p.paragraph_format.space_after = Pt(14)
            add_inline(p, stripped[3:].strip(), size=12, color=GRAY, bold=True)
            i += 1
            continue

        # horizontal rule
        if re.match(r"^(-{3,}|\*{3,})$", stripped):
            p = doc.add_paragraph()
            p.paragraph_format.space_after = Pt(2)
            p.paragraph_format.space_before = Pt(2)
            pPr = p._p.get_or_add_pPr()
            bdr = OxmlElement("w:pBdr")
            bottom = OxmlElement("w:bottom")
            bottom.set(qn("w:val"), "single")
            bottom.set(qn("w:sz"), "6")
            bottom.set(qn("w:space"), "1")
            bottom.set(qn("w:color"), BORDER)
            bdr.append(bottom)
            pPr.append(bdr)
            i += 1
            continue

        # blockquote
        if stripped.startswith(">"):
            text = stripped.lstrip(">").strip()
            p = doc.add_paragraph()
            p.paragraph_format.left_indent = Cm(0.6)
            p.paragraph_format.space_before = Pt(4)
            p.paragraph_format.space_after = Pt(8)
            pPr = p._p.get_or_add_pPr()
            bdr = OxmlElement("w:pBdr")
            left = OxmlElement("w:left")
            left.set(qn("w:val"), "single")
            left.set(qn("w:sz"), "18")
            left.set(qn("w:space"), "8")
            left.set(qn("w:color"), GREEN)
            bdr.append(left)
            pPr.append(bdr)
            add_inline(p, text, size=10, color=RGBColor(0x37, 0x41, 0x51), italic=True)
            i += 1
            continue

        # checklist
        m = re.match(r"^-\s+\[([ xX])\]\s+(.*)$", stripped)
        if m:
            mark = "☒" if m.group(1).lower() == "x" else "☐"
            p = doc.add_paragraph()
            p.paragraph_format.left_indent = Cm(0.75)
            p.paragraph_format.space_after = Pt(3)
            add_inline(p, f"{mark}  {m.group(2)}")
            i += 1
            continue

        # bullet
        m = re.match(r"^[-*]\s+(.*)$", stripped)
        if m:
            p = doc.add_paragraph(style="List Bullet")
            p.paragraph_format.space_after = Pt(3)
            add_inline(p, m.group(1))
            i += 1
            continue

        # numbered
        m = re.match(r"^(\d+)\.\s+(.*)$", stripped)
        if m:
            p = doc.add_paragraph(style="List Number")
            p.paragraph_format.space_after = Pt(3)
            add_inline(p, m.group(2))
            i += 1
            continue

        # paragraf biasa
        p = doc.add_paragraph()
        add_inline(p, stripped)
        i += 1

    # properties
    props = doc.core_properties
    props.title = "Dokumen Permintaan Persetujuan Rancangan - MTs Al-Huda Gondang"
    props.subject = "Blueprint Website MTs Al-Huda Gondang & Aplikasi MADRASAHKU"
    props.author = "Tim Pengembang"
    props.category = "Baseline Persetujuan Mitra v1.0"
    props.comments = "Dikirim untuk persetujuan mitra; ringkasan PRD v1.2, 12 diagram, 41 test case."

    doc.save(out_path)


if __name__ == "__main__":
    src = Path(sys.argv[1])
    dst = Path(sys.argv[2])
    build(src, dst)
    print(f"OK -> {dst} ({dst.stat().st_size / 1024:.1f} KB)")
