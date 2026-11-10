<?php

/**
 * Shared list-page design system (dashboard-matched). Included by the portal's
 * list views (invoices, payments, projects, domains, tickets, clients, accounts,
 * proposals, billing) so they share one set of styles.
 *
 * Usage: wrap the page in <div class="msd-page">, give the header <div class="msd-head">
 * (with .msd-title / .msd-sub / .msd-actions), and give data tables class "msd-table".
 */

\defined('_JEXEC') or die;
?>
<style>
.msd-page { background:#f6f7f9; padding:22px; border-radius:12px; color:#1f2733; font-size:14px; line-height:1.45; -webkit-font-smoothing:antialiased; }
.msd-page a { color:#2563eb; text-decoration:none; }
.msd-page a:hover { text-decoration:underline; }
.msd-page h1,.msd-page h2,.msd-page h3 { margin:0; }
.msd-page .msd-muted { color:#6b7280; }

/* header + fading title rule */
.msd-head { position:relative; display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding-bottom:12px; margin-bottom:0; flex-wrap:wrap; }
.msd-head::after { content:""; position:absolute; left:0; bottom:0; height:1px; width:78%; background:linear-gradient(90deg,#2563eb 0%,#2563eb 20%,rgba(37,99,235,0) 100%); }
.msd-page .msd-title { flex:1 1 auto; min-width:0; font-size:24px; font-weight:700; letter-spacing:-.01em; border:0!important; padding-bottom:0!important; background-image:none!important; }
.msd-page .msd-title::after { content:none!important; }
.msd-sub { margin:8px 0 10px; color:#6b7280; font-size:14px; }
.msd-actions { display:flex; gap:8px; flex-wrap:wrap; flex-shrink:0; }
.msd-btn { display:inline-flex; align-items:center; gap:6px; background:#2563eb; color:#fff!important; padding:8px 14px; border-radius:8px; font-weight:600; font-size:13px; white-space:nowrap; }
.msd-btn:hover { background:#1d4fd7; text-decoration:none!important; }
.msd-btn--ghost { background:#fff; color:#374151!important; border:1px solid #d7dbe3; }
.msd-btn--ghost:hover { background:#f3f4f6; }

/* surfaces */
.msd-panel { background:#fff; border:1px solid #e6e8ee; border-radius:10px; box-shadow:0 1px 2px rgba(16,24,40,.05); padding:16px 18px; }
.msd-scroll { overflow-x:auto; }
.msd-section { margin-top:20px; }
.msd-panel__head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:6px; }
.msd-panel__head h2 { font-size:16px; font-weight:600; }
.msd-viewall { font-size:13px; font-weight:500; white-space:nowrap; }
.msd-2col { display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:start; }
@media (max-width:820px){ .msd-page .msd-2col{ grid-template-columns:1fr; } }
.msd-list__empty { color:#6b7280; padding:12px 2px; }

/* summary tiles */
.msd-bsum { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
@media (max-width:820px){ .msd-page .msd-bsum{ grid-template-columns:1fr; gap:10px; } }
.msd-bsum__card { display:flex; align-items:center; gap:12px; background:#fff; border:1px solid #e6e8ee; border-radius:10px; box-shadow:0 1px 2px rgba(16,24,40,.05); padding:16px 18px; }
.msd-bsum__v { font-size:22px; font-weight:700; letter-spacing:-.01em; font-variant-numeric:tabular-nums; line-height:1.1; }
.msd-bsum__v.is-red{ color:#dc2626; }
.msd-bsum__l { color:#6b7280; font-size:13px; margin-top:2px; }
.msd-ico { width:38px; height:38px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
.msd-ico svg{ width:19px; height:19px; }
.msd-ico--blue{ background:#e8f0fe; color:#2563eb; }
.msd-ico--red{ background:#fdecec; color:#dc2626; }
.msd-ico--green{ background:#e7f6ed; color:#16a34a; }
.msd-ico--amber{ background:#fff4e5; color:#d97706; }

/* tables */
.msd-page table.msd-table { width:100%; border-collapse:collapse; font-size:13.5px; background:#fff; margin:0; }
.msd-page table.msd-table thead th { text-align:left; font-size:11px; font-weight:600; letter-spacing:.05em; text-transform:uppercase; color:#9ca3af; padding:10px 12px; border-bottom:1px solid #eef0f4; white-space:nowrap; }
.msd-page table.msd-table td { padding:12px; border-bottom:1px solid #f4f5f8; vertical-align:middle; color:#1f2733; }
.msd-page table.msd-table tbody tr:last-child td { border-bottom:0; }
.msd-page table.msd-table tbody tr:hover { background:#fafbfc; }
.msd-page table.msd-table .num { font-variant-numeric:tabular-nums; white-space:nowrap; }
.msd-page table.msd-table .r { text-align:right; }
.msd-page .ms-inv-title { font-weight:600; display:block; }
.msd-page .ms-sub { color:#9ca3af; font-size:12px; }
.msd-page .ms-due-date { font-variant-numeric:tabular-nums; }
.msd-page .ms-row-link { color:#1f2733!important; }
.msd-page .ms-row-link:hover { color:#2563eb!important; }
.msd-relred{ color:#dc2626; font-size:11px; }

/* status dot + label (projects / domains) — covers both msd markup and the
   MothershipHelper::statusDot() output classes. */
.msd-status, .msd-page .ms-status { display:inline-flex; align-items:center; gap:6px; color:#6b7280; font-size:13px; }
.msd-dot, .msd-page .ms-status-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; display:inline-block; }
.msd-page .ms-status-label { color:#6b7280; }
.msd-page .ms-status-cell { white-space:nowrap; }
.msd-legend-inline { display:flex; flex-wrap:wrap; gap:16px; margin:0 0 14px; color:#6b7280; font-size:13px; }
.msd-legend-inline span.i { display:inline-flex; align-items:center; gap:6px; }
.msd-page .ms-proj-url { font-size:12px; margin-top:2px; }
.msd-page .ms-proj-url a { color:#6b7280; }

/* chips */
.msd-chip { display:inline-block; padding:2px 9px; border-radius:100px; font-size:12px; font-weight:600; white-space:nowrap; }
.msd-chip--blue{ background:#e8f0fe; color:#1a56db; }
.msd-chip--green{ background:#e7f6ed; color:#137333; }
.msd-chip--amber{ background:#fff4e5; color:#b45309; }
.msd-chip--purple{ background:#f3e8ff; color:#7e22ce; }
.msd-chip--red{ background:#fdecec; color:#c81e1e; }
.msd-chip--gray{ background:#eef0f2; color:#5a626c; }

/* action buttons */
.msd-acts { display:flex; gap:6px; flex-wrap:wrap; margin:0; padding:0; list-style:none; }
.msd-act-btn { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:6px; font-size:12px; font-weight:600; border:1px solid #d7dbe3; color:#374151!important; white-space:nowrap; }
.msd-act-btn svg { width:13px; height:13px; }
.msd-act-btn:hover { background:#f3f4f6; text-decoration:none!important; }
.msd-act-btn--pay { background:#2563eb; color:#fff!important; border-color:#2563eb; }
.msd-act-btn--pay:hover { background:#1d4fd7; }
.msd-act-btn--cancel { color:#c81e1e!important; border-color:#f0c2c2; }
.msd-act-btn--cancel:hover { background:#fdecec; }

.msd-page .payment-list { margin:6px 0 0; padding:0; }
.msd-page .payment-list li { list-style:none; white-space:nowrap; }
.msd-page .payment-list a { font-size:12px; }

/* DataTables controls */
.msd-page .dt-search input, .msd-page .dt-length select, .msd-page .form-select, .msd-page .form-control, .msd-page select { border:1px solid #d7dbe3; border-radius:8px; padding:6px 10px; font-size:13px; background:#fff; color:#1f2733; }
.msd-page .dt-info { color:#6b7280; font-size:13px; }
.msd-page .pagination { margin-bottom:0; }
.msd-page .pagination .page-link { color:#374151; border:1px solid #e6e8ee; font-size:13px; }
.msd-page .pagination .page-link:hover { background:#f3f4f6; }
.msd-page .pagination .page-item.active .page-link { background:#2563eb; border-color:#2563eb; color:#fff; }
.msd-page .dt-container { margin-top:4px; }
/* DataTables manages its own responsive column-collapse — don't also wrap it in
   an overflow-scroll box, or the table expands and scrolls sideways instead. */
.msd-page .msd-scroll:has(.dt-container) { overflow:visible; }
.msd-filters { margin:0 0 12px; display:flex; flex-wrap:wrap; align-items:center; gap:14px; }

/* legends */
.msd-legends { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:20px; }
@media (max-width:820px){ .msd-page .msd-legends{ grid-template-columns:1fr; } }
.msd-legend h3 { margin:0 0 10px; font-size:13px; font-weight:600; color:#374151; }
.msd-legend ul { margin:0; padding:0; list-style:none; }
.msd-legend li { font-size:13px; color:#6b7280; padding:5px 0; display:flex; gap:8px; align-items:baseline; }
.msd-legend li .msd-chip { flex-shrink:0; }

/* misc content bits (billing "payment details" etc.) */
.msd-subhead { font-size:13px; font-weight:600; color:#374151; margin:0 0 8px; }
.msd-remit { color:#374151; line-height:1.6; }
.msd-rate { font-size:22px; font-weight:700; letter-spacing:-.01em; }
.msd-overdue-chip { display:inline-block; margin-left:6px; padding:1px 8px; border-radius:100px; font-size:11px; font-weight:600; background:#fdecec; color:#c81e1e; }

/* mobile: drop the gray outer frame entirely and let the white panels go
   edge-to-edge, so we're not paying for wrapper + panel + cell padding at once. */
@media (max-width:640px){
    .msd-page { padding:0; border-radius:0; background:transparent; }
    .msd-panel, .msd-bsum__card { border-radius:0; border-left:0; border-right:0; padding:14px; }
    .msd-head { padding:14px 14px 12px; margin-bottom:0; }
    .msd-page .msd-title { font-size:20px; }
    .msd-page table.msd-table td { padding:10px 8px; }
    .msd-page table.msd-table thead th { padding:8px; }
    .msd-legends { margin:0; }
    .msd-bsum { gap:0; }
    .msd-section { margin-top:14px; }

    /* DataTables control bar: stack length/search/info/paging on narrow screens */
    .msd-page .dt-layout-row { flex-direction:column; align-items:stretch; gap:10px; }
    .msd-page .dt-layout-cell { justify-content:flex-start; text-align:left; margin:0!important; }
    .msd-page .dt-search, .msd-page .dt-length { width:100%; }
    .msd-page .dt-search input { width:100%; box-sizing:border-box; }
    .msd-page .dt-paging .pagination { flex-wrap:wrap; margin:0; }

    /* Custom filter dropdowns stack full width */
    .msd-filters { flex-direction:column; align-items:stretch; gap:10px; }
    .msd-filters label { width:100%; justify-content:space-between; }
    .msd-filters select { flex:1; min-width:0; }
}
</style>
