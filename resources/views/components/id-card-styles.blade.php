{{--
    Shared ID card stylesheet.

    Included EXACTLY ONCE per page by the print layout (modules.students
    .id-card-bulk-pdf) and by the designer live preview, so the preview, the
    printed PDF and the PNG export all render through the identical CSS.
--}}
<style>
    .id-card {
        position: relative;
        overflow: hidden;
        box-sizing: border-box;
        margin: 0 auto;
        text-align: left;
        font-family: 'Inter', Helvetica, Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
    }
    .id-card.landscape { width: 480px; height: 300px; }
    .id-card.portrait { width: 300px; height: 480px; }
    .id-card.crop-marks { border: 2px dashed #94a3b8 !important; }
    .id-card .card-background-layer {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
    }
    .id-card .positioned-element {
        position: absolute;
        z-index: 2;
        box-sizing: border-box;
    }

    .card-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
    }

    .card-header-bar {
        position: relative;
        z-index: 1;
        vertical-align: middle;
        padding: 0 10px;
        box-sizing: border-box;
    }
    .card-header-bar > table { width: 100%; }
    .card-header-bar .logo-cell { width: 40px; padding-right: 6px; }
    .logo-box {
        display: block;
        vertical-align: middle;
        text-align: center;
        overflow: hidden;
        box-sizing: border-box;
    }
    .logo-img { width: 100%; height: 100%; object-fit: contain; }
    .logo-letter {
        display: block;
        line-height: 1;
        font-weight: 800;
    }
    .title-cell {
        text-align: left;
        padding-left: 2px;
        vertical-align: middle;
    }
    .school-name {
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    .school-motto {
        font-style: italic;
        margin-top: 1px;
        line-height: 1.15;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    .card-body {
        vertical-align: top;
        box-sizing: border-box;
    }
    .body-grid { border-collapse: collapse; }
    .photo-cell { padding-right: 10px; }
    .student-photo {
        background-color: #f8fafc;
        box-sizing: border-box;
        object-fit: cover;
    }
    .identity-cell { padding-top: 2px; }
    .student-name {
        line-height: 1.15;
        text-transform: uppercase;
    }
    .class-chip {
        display: inline-block;
        margin-top: 5px;
        padding: 3px 9px;
        border-radius: 999px;
        background-color: #eef2ff;
        border: 1px solid #c7d2fe;
        line-height: 1.2;
        white-space: nowrap;
    }
    .header-label-cell {
        width: 86px;
        text-align: right;
        vertical-align: middle;
        padding-left: 8px;
    }
    .header-card-type {
        font-size: 6.5px;
        font-weight: 800;
        letter-spacing: 1px;
        color: inherit;
        text-transform: uppercase;
        opacity: 0.85;
        white-space: nowrap;
    }
    .lower-cell { padding-top: 4px; }
    .lower-cell > table { border-collapse: collapse; }
    .qr-cell { padding-right: 8px; vertical-align: middle; }
    .qr-img {
        border: 1px solid #cbd5e1;
        padding: 1px;
        background: #fff;
        border-radius: 4px;
        box-sizing: border-box;
    }
    .meta-cell { line-height: 1.4; }
    .meta-cell .custom-meta { margin-top: 2px; font-weight: 600; }
    .barcode-wrap {
        margin-top: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 6px;
        text-align: center;
    }
    .barcode-img { max-width: 100%; height: auto; }
    .barcode-caption {
        font-size: 8px;
        letter-spacing: 2px;
        margin-top: 2px;
        font-family: 'Courier New', monospace;
        font-weight: 700;
    }

    .lower-block {
        position: absolute;
        z-index: 3;
        border-top: 1px solid #e2e8f0;
        padding-top: 6px;
    }
    .card-footer-bar {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 4;
        background-color: #f8fafc;
        border-top: 1px solid #cbd5e1;
        box-sizing: border-box;
    }
    .contact-footer {
        text-align: center;
        padding: 0;
        font-weight: 600;
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        box-sizing: border-box;
        white-space: normal;
    }

    .custom-text-line {
        position: absolute;
        white-space: nowrap;
        z-index: 2;
    }

    .powered-seal {
        position: absolute;
        bottom: 4px;
        right: 8px;
        font-size: 5px;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        z-index: 3;
    }
</style>
