{{-- Shared print CSS for finance documents (invoice / receipt / statement).
     Expects: $financeTheme, $h, $t, $m, $tb, $f and optional $bodyFontSize.

     Every document blade must use <body class="style-{{ structure }}"> and wrap
     its content in <div class="doc-page"> so the structural overrides below
     (which are scoped to body.style-*) apply to the header AND the tables. --}}
@php($structure = $financeTheme['structure'] ?? 'classic')
<style>
    @page { margin: 0; }
    body { font-family: {!! $financeTheme['font_family'] !!}; font-size: {{ $bodyFontSize ?? 11 }}px; color: #1f2937; margin: 0; padding: 0; }

    /* Generous, consistent page margins on every side so printed content is
       never clipped at the paper edge. The powered-by line stays pinned to the
       true page bottom because it uses position: fixed. */
    .doc-page { padding: 10mm 12mm; }

    /* ===== Header frame ===== */
    .doc-header { overflow: hidden; margin-bottom: 15px; padding-bottom: 8px; }
    .doc-identity { margin-bottom: 8px; }
    .school-name { font-size: {{ $h['school_name_font_size'] }}px; font-weight: {{ $h['school_name_bold'] ? 'bold' : 'normal' }}; font-style: {{ $h['school_name_italic'] ? 'italic' : 'normal' }}; color: {{ $h['school_name_color'] }}; text-transform: uppercase; line-height: 1.2; }
    .school-motto { font-style: {{ $h['motto_italic'] ? 'italic' : 'normal' }}; color: {{ $h['motto_color'] }}; font-size: {{ $h['motto_font_size'] }}px; margin-top: 3px; }
    .school-contact { font-size: {{ $h['contact_font_size'] }}px; color: {{ $h['contact_color'] }}; margin-top: 3px; line-height: 1.5; }
    .doc-title { font-size: {{ $t['font_size'] }}px; font-weight: {{ $t['bold'] ? 'bold' : 'normal' }}; font-style: {{ $t['italic'] ? 'italic' : 'normal' }}; color: {{ $t['color'] }}; text-transform: uppercase; }
    .doc-title-extra { font-size: 10px; color: {{ $t['color'] }}; margin-top: 3px; line-height: 1.4; }
    .doc-refs { font-size: 10px; color: #4b5563; margin-top: 6px; line-height: 1.6; }

    /* ===== Section / metadata tables ===== */
    .section-title { background: {{ $tb['header_bg'] }}; color: {{ $tb['header_color'] }}; font-weight: {{ $tb['header_bold'] ? 'bold' : 'normal' }}; padding: 4px 6px; font-size: {{ $tb['font_size'] }}px; text-transform: uppercase; }
    .metadata-container { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .metadata-container td { padding: 4px 10px; border: none; vertical-align: top; font-size: {{ $m['font_size'] }}px; color: {{ $m['color'] }}; font-weight: {{ $m['bold'] ? 'bold' : 'normal' }}; font-style: {{ $m['italic'] ? 'italic' : 'normal' }}; }
    .metadata-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .metadata-table td { padding: 3px 10px; border: none; font-size: {{ $m['font_size'] }}px; color: {{ $m['color'] }}; font-weight: {{ $m['bold'] ? 'bold' : 'normal' }}; font-style: {{ $m['italic'] ? 'italic' : 'normal' }}; }
    .label { font-weight: bold; color: #374151; width: 16%; }

    /* ===== Results / breakdown tables ===== */
    .results-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .results-table th { background: {{ $tb['header_bg'] }}; color: {{ $tb['header_color'] }}; padding: 6px 8px; font-size: {{ $tb['font_size'] }}px; font-weight: {{ $tb['header_bold'] ? 'bold' : 'normal' }}; border: 1px solid {{ $tb['header_bg'] }}; text-transform: uppercase; }
    .results-table td { padding: 6px 8px; border: 1px solid #e5e7eb; text-align: center; font-size: {{ $tb['font_size'] }}px; color: {{ $tb['body_color'] }}; }
    .breakdown-table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 15px; }
    .breakdown-table th { background: {{ $tb['header_bg'] }}; color: {{ $tb['header_color'] }}; padding: 5px; font-size: {{ $tb['font_size'] }}px; font-weight: {{ $tb['header_bold'] ? 'bold' : 'normal' }}; border: 1px solid #e5e7eb; text-transform: uppercase; }
    .breakdown-table td { padding: 6px; border: 1px solid #e5e7eb; text-align: center; font-size: {{ $tb['font_size'] }}px; color: {{ $tb['body_color'] }}; }

    /* ===== Instructions ===== */
    .instructions { border: 1px solid #e5e7eb; background: #f9fafb; padding: 8px; margin-bottom: 15px; border-radius: 4px; }

    /* ===== Footer (signatures + QR + extra text + powered-by) ===== */
    .doc-footer { margin-top: 24px; width: 100%; overflow: hidden; min-height: 90px; }
    .signature-line { border-top: 1px solid #9ca3af; text-align: center; padding-top: 3px; font-size: {{ $f['font_size'] }}px; color: {{ $f['color'] }}; }
    .qr-block { width: auto; }
    .qr-caption { font-size: 7px; color: #9ca3af; margin-top: 3px; line-height: 1.2; white-space: nowrap; }
    .doc-extra-text { clear: both; margin-top: 16px; font-size: {{ $f['font_size'] }}px; color: {{ $f['color'] }}; text-align: center; border-top: 1px dashed #ddd; padding-top: 5px; line-height: 1.5; }
    .powered-by { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 7px; color: #9ca3af; padding: 2px 0; }

    /* ============================================================
       STRUCTURAL LAYOUTS
       Scoped to <body class="style-…"> so they also style the tables.
       ============================================================ */

    /* ----- 1. classic — centred identity, right-aligned title, ruled header ----- */
    body.style-classic .doc-header { border-bottom: 2px solid {{ $h['school_name_color'] }}; }
    body.style-classic .doc-identity { text-align: center; }
    body.style-classic .doc-title { text-align: right; }
    body.style-classic .doc-refs { text-align: right; }

    /* ----- 2. modern — left identity, full-width coloured title band ----- */
    body.style-modern .doc-header { border-bottom: 3px solid {{ $h['school_name_color'] }}; }
    body.style-modern .doc-identity { text-align: left; }
    body.style-modern .doc-title { background: {{ $tb['header_bg'] }}; color: {{ $tb['header_color'] }}; padding: 6px 10px; text-align: center; letter-spacing: 1px; }
    body.style-modern .doc-title-extra { text-align: center; color: {{ $tb['header_color'] }}; }
    body.style-modern .doc-refs { text-align: right; }
    body.style-modern .results-table th,
    body.style-modern .breakdown-table th { letter-spacing: 0.5px; }

    /* ----- 3. editorial — serif centred, letter-spaced, thin rules ----- */
    body.style-editorial .doc-header { text-align: center; padding-bottom: 10px; border-top: 1px solid {{ $h['school_name_color'] }}; border-bottom: 1px solid {{ $h['school_name_color'] }}; }
    body.style-editorial .doc-identity { text-align: center; }
    body.style-editorial .doc-title { letter-spacing: 3px; }
    body.style-editorial .doc-title-extra { font-style: italic; }
    body.style-editorial .doc-refs { text-align: center; }
    body.style-editorial .results-table th,
    body.style-editorial .breakdown-table th { background: transparent; color: {{ $tb['header_bg'] }}; border: none; border-bottom: 2px solid {{ $tb['header_bg'] }}; }
    body.style-editorial .results-table td,
    body.style-editorial .breakdown-table td { border-left: none; border-right: none; border-top: none; }

    /* ----- 4. compact — tight single-line header, minimal rules ----- */
    body.style-compact .doc-header { border-bottom: 1px solid #000; }
    body.style-compact .doc-identity { text-align: left; }
    body.style-compact .doc-title { text-align: left; }
    body.style-compact .doc-refs { text-align: left; font-size: 9px; }
    body.style-compact .results-table td,
    body.style-compact .breakdown-table td { padding: 4px 6px; }

    /* ----- 5. crest — framed centred emblem, double border ----- */
    body.style-crest .doc-header { border: 2px double {{ $t['color'] }}; padding: 10px; text-align: center; }
    body.style-crest .doc-identity { text-align: center; }
    body.style-crest .doc-title { letter-spacing: 2px; }
    body.style-crest .doc-title-extra { color: {{ $t['color'] }}; }
    body.style-crest .doc-refs { text-align: center; }

    /* ----- 6. banker — left identity & right refs, full-width navy title bar ----- */
    body.style-banker .doc-header { border-bottom: 3px solid {{ $h['school_name_color'] }}; }
    body.style-banker .doc-identity { float: left; width: 54%; text-align: left; }
    body.style-banker .doc-refs { float: right; width: 42%; text-align: right; font-size: 9px; color: #475569; padding-top: 2px; }
    body.style-banker .doc-refs strong { color: {{ $h['school_name_color'] }}; }
    body.style-banker .doc-title { clear: both; background: {{ $tb['header_bg'] }}; color: #ffffff; padding: 7px 12px; text-align: center; letter-spacing: 2px; font-size: 15px; margin-top: 12px; }
    body.style-banker .doc-title-extra { text-align: center; color: {{ $h['school_name_color'] }}; }
    body.style-banker .section-title { border-radius: 3px; }
    body.style-banker .results-table th,
    body.style-banker .breakdown-table th { letter-spacing: 0.5px; padding: 7px 8px; }
    body.style-banker .results-table td,
    body.style-banker .breakdown-table td { padding: 7px 8px; border-left: none; border-right: none; border-top: none; border-bottom: 1px solid #e2e8f0; }
    body.style-banker .results-table tr.alt td,
    body.style-banker .breakdown-table tr.alt td { background: #f8fafc; }
    body.style-banker .instructions { border: none; border-left: 3px solid {{ $h['school_name_color'] }}; background: #f8fafc; }

    /* ----- 7. scholastic — centred double-ruled serif letterhead, gold accents ----- */
    body.style-scholastic .doc-header { text-align: center; padding: 12px 0; border-top: 3px double {{ $financeTheme['header_color'] }}; border-bottom: 1px solid {{ $t['color'] }}; }
    body.style-scholastic .doc-identity { text-align: center; }
    body.style-scholastic .school-name { letter-spacing: 2px; }
    body.style-scholastic .doc-title { letter-spacing: 4px; }
    body.style-scholastic .doc-title-extra { font-style: italic; }
    body.style-scholastic .doc-refs { text-align: center; color: #4b5563; }
    body.style-scholastic .section-title { letter-spacing: 1px; }
    body.style-scholastic .results-table th,
    body.style-scholastic .breakdown-table th { background: transparent; color: {{ $tb['header_bg'] }}; border: none; border-bottom: 2px solid {{ $tb['header_bg'] }}; letter-spacing: 1px; }
    body.style-scholastic .results-table td,
    body.style-scholastic .breakdown-table td { border-left: none; border-right: none; border-top: none; border-bottom: 1px solid #e5e7eb; }
    body.style-scholastic .instructions { border: 1px solid #e5e7eb; border-top: 3px solid {{ $t['color'] }}; background: #fcfbf7; border-radius: 0; }

    /* ----- 8. swiss — pure grid, thin rules, generous whitespace, monochrome ----- */
    body.style-swiss .doc-header { border-bottom: 1px solid #111827; padding-bottom: 10px; }
    body.style-swiss .doc-identity { text-align: left; }
    body.style-swiss .school-name { text-transform: none; font-weight: 700; letter-spacing: normal; }
    body.style-swiss .school-motto { text-transform: uppercase; font-size: 8px; letter-spacing: 1px; color: #6b7280; }
    body.style-swiss .doc-title { text-align: left; text-transform: none; font-size: 20px; font-weight: 700; letter-spacing: -0.5px; margin-top: 10px; }
    body.style-swiss .doc-title-extra { text-align: left; }
    body.style-swiss .doc-refs { text-align: left; font-size: 9px; color: #6b7280; }
    body.style-swiss .section-title { background: transparent; color: #111827; border-bottom: 1px solid #111827; padding: 4px 0; letter-spacing: 0.5px; }
    body.style-swiss .results-table th,
    body.style-swiss .breakdown-table th { background: transparent; color: #6b7280; border: none; border-bottom: 1px solid #111827; text-align: left; font-size: 8px; letter-spacing: 1px; padding: 6px 4px; }
    body.style-swiss .results-table td,
    body.style-swiss .breakdown-table td { border: none; border-bottom: 1px solid #e5e7eb; padding: 7px 4px; }
    body.style-swiss .instructions { border: none; background: transparent; padding: 0; }
    body.style-swiss .signature-line { border-top-color: #111827; }

    /* ----- 9. boutique — centred soft sage card, warm rounded corners ----- */
    body.style-boutique .doc-header { text-align: center; background: #f4f8f5; border: 1px solid #dde9df; border-radius: 10px; padding: 14px; }
    body.style-boutique .doc-identity { text-align: center; }
    body.style-boutique .doc-title { letter-spacing: 1px; }
    body.style-boutique .doc-title-extra { font-style: italic; }
    body.style-boutique .doc-refs { text-align: center; color: #4b5563; }
    body.style-boutique .section-title { border-radius: 6px; }
    body.style-boutique .results-table th,
    body.style-boutique .breakdown-table th { background: {{ $tb['header_bg'] }}; color: #ffffff; padding: 7px 8px; border-radius: 6px 6px 0 0; }
    body.style-boutique .results-table td,
    body.style-boutique .breakdown-table td { border: 1px solid #e2ede4; padding: 7px 8px; }
    body.style-boutique .results-table tr.alt td,
    body.style-boutique .breakdown-table tr.alt td { background: #f7faf8; }
    body.style-boutique .instructions { border: 1px solid #dde9df; border-radius: 8px; background: #fbfdfb; }

    /* ----- 10. executive — inverted dark navy header block with gold rule ----- */
    body.style-executive .doc-header { background: {{ $financeTheme['header_color'] }}; color: #ffffff; padding: 16px; text-align: center; border-bottom: 3px solid {{ $t['color'] }}; }
    body.style-executive .doc-identity { text-align: center; }
    body.style-executive .school-name { color: #ffffff; letter-spacing: 2px; }
    body.style-executive .school-motto { color: #cbd5e1; }
    body.style-executive .school-contact { color: #94a3b8; }
    body.style-executive .doc-title { color: {{ $t['color'] }}; letter-spacing: 3px; margin-top: 8px; }
    body.style-executive .doc-title-extra { color: #cbd5e1; }
    body.style-executive .doc-refs { text-align: center; color: #cbd5e1; }
    body.style-executive .doc-refs strong { color: {{ $t['color'] }}; }
    body.style-executive .results-table th,
    body.style-executive .breakdown-table th { background: {{ $tb['header_bg'] }}; color: #ffffff; letter-spacing: 1px; padding: 7px 8px; }
    body.style-executive .results-table td,
    body.style-executive .breakdown-table td { padding: 7px 8px; }
    body.style-executive .results-table tr.alt td,
    body.style-executive .breakdown-table tr.alt td { background: #f1f5f9; }
    body.style-executive .instructions { border: 1px solid {{ $financeTheme['header_color'] }}; border-left: 4px solid {{ $financeTheme['header_color'] }}; background: #f8fafc; }
</style>
