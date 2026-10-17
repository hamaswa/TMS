<style>
    .xp-page { padding: 28px 0 48px; }
    .xp-card { border: 1px solid #e3ebf5; border-radius: 22px; background: #fff; box-shadow: 0 18px 45px rgba(30, 64, 103, .08); }
    .xp-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; padding: 28px 30px 22px; }
    .xp-title { margin: 0; color: #102a52; font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800; }
    .xp-subtitle { margin: 4px 0 0; color: #7185a2; font-size: .94rem; }
    .xp-header-actions { display: flex; flex-wrap: wrap; gap: 10px; }
    .xp-add { display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-height: 48px; padding: 9px 20px; border: 0; border-radius: 12px; background: linear-gradient(135deg, #1670ef, #0d5bdb); box-shadow: 0 9px 22px rgba(22, 112, 239, .23); color: #fff; font-weight: 800; transition: transform .18s ease, box-shadow .18s ease; }
    .xp-add:hover, .xp-add:focus { transform: translateY(-1px); box-shadow: 0 12px 27px rgba(22, 112, 239, .3); color: #fff; }
    .xp-add.is-secondary { border: 1px solid #cfe0f6; background: #f2f7ff; box-shadow: none; color: #1769df; }
    .xp-add.is-secondary:hover, .xp-add.is-secondary:focus { background: #e6f0ff; color: #1769df; }

    .xp-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; padding: 0 30px 22px; }
    .xp-stat { display: flex; align-items: center; gap: 14px; padding: 16px 18px; border: 1px solid #e3ebf5; border-radius: 16px; background: #fbfdff; }
    .xp-stat-icon { display: grid; place-items: center; flex: 0 0 46px; width: 46px; height: 46px; border-radius: 13px; font-size: 18px; }
    .xp-stat-icon.blue { background: #eaf3ff; color: #1769e0; }
    .xp-stat-icon.orange { background: #fff3dd; color: #d17d08; }
    .xp-stat-icon.green { background: #e7f7ef; color: #148052; }
    .xp-stat small { display: block; color: #7185a2; font-size: .8rem; font-weight: 700; }
    .xp-stat strong { display: block; color: #102a52; font-size: 1.25rem; font-weight: 800; }

    .xp-toolbar { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 14px; padding: 0 30px 22px; }
    .xp-filter { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px; }
    .xp-filter label { display: block; margin-bottom: 4px; color: #4f6480; font-size: .8rem; font-weight: 700; }
    .xp-filter .form-control, .xp-search .form-control { min-height: 46px; border: 1px solid #d9e4f1; border-radius: 12px; background: #fbfdff; color: #213a5d; box-shadow: none; }
    .xp-filter .form-control:focus, .xp-search .form-control:focus { border-color: #4b94f3; background: #fff; box-shadow: 0 0 0 3px rgba(22, 112, 239, .1); }
    .xp-filter .btn { min-height: 46px; border-radius: 12px; font-weight: 700; }
    .xp-search { position: relative; width: min(100%, 320px); }
    .xp-search i { position: absolute; top: 50%; left: 16px; color: #8394ab; transform: translateY(-50%); pointer-events: none; }
    .xp-search .form-control { padding-left: 44px; }

    .xp-section-title { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 0 30px 12px; }
    .xp-section-title h2 { margin: 0; color: #102a52; font-size: 1.15rem; font-weight: 800; }
    .xp-table-shell { margin: 0 30px 24px; overflow-x: auto; border: 1px solid #e0e9f3; border-radius: 16px; background: #fff; }
    .xp-table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
    .xp-table thead th { padding: 14px 18px; border: 0; border-bottom: 1px solid #dfe8f2; background: #f4f8fd; color: #4f6480; font-size: .85rem; font-weight: 800; white-space: nowrap; }
    .xp-table tbody td { padding: 14px 18px; border: 0; border-bottom: 1px solid #e8eef5; color: #183354; vertical-align: middle; }
    .xp-table tbody tr:hover { background: #f9fbfe; }
    .xp-table tfoot td { padding: 14px 18px; border-top: 1px solid #dfe8f2; background: #f4f8fd; color: #102a52; font-weight: 800; }
    .xp-amount { font-family: Arial, sans-serif; font-weight: 800; white-space: nowrap; }
    .xp-date { color: #5b6d84; font-family: Arial, sans-serif; white-space: nowrap; }
    .xp-empty { padding: 36px 18px !important; color: #7185a2 !important; text-align: center; }
    .xp-empty i { display: block; margin-bottom: 8px; color: #aab9cb; font-size: 1.8rem; }
    .xp-actions { display: inline-flex; gap: 6px; }
    .xp-icon-btn { display: inline-grid; place-items: center; width: 38px; height: 38px; padding: 0; border: 1px solid #dce6f1; border-radius: 10px; background: #f6f9fd; color: #1d3c62; }
    .xp-icon-btn:hover, .xp-icon-btn:focus { border-color: #bcd4f4; background: #eaf3ff; color: #1769df; }
    .xp-icon-btn.is-danger { border-color: #f0cbd0; background: #fff7f8; color: #c23b48; }

    .xp-pagination { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin: -8px 30px 24px; }
    .xp-pagination .pagination { margin: 0; }
    .xp-pagination .page-link { min-width: 38px; margin: 0 2px; border-radius: 9px !important; color: #44607f; text-align: center; }
    .xp-pagination .page-item.active .page-link { border-color: #1769df; background: #1769df; color: #fff; }

    .xp-modal .modal-content { border: 0; border-radius: 18px; text-align: right; }
    .xp-modal .modal-header { align-items: center; border-bottom-color: #e8eef5; }
    .xp-modal .modal-header .close { margin: -1rem auto -1rem -1rem; }
    .xp-modal .modal-title { color: #102a52; font-weight: 800; }
    .xp-modal label { color: #38516f; font-weight: 700; }
    .xp-modal .modal-footer { justify-content: flex-start; gap: 8px; border-top-color: #e8eef5; }
    .xp-rows { display: grid; gap: 10px; }
    .xp-row { display: grid; grid-template-columns: minmax(0, 1fr) 150px 42px; align-items: end; gap: 10px; }
    .xp-row .form-group { margin-bottom: 0; }
    .xp-row-remove { height: 38px; }
    .xp-add-row { margin-top: 12px; }
    .xp-modal-total { display: flex; justify-content: space-between; margin-top: 14px; padding: 10px 14px; border-radius: 10px; background: #f4f8fd; color: #102a52; font-weight: 800; }

    @media (max-width: 767.98px) {
        .xp-page { padding-top: 16px; }
        .xp-card { border-radius: 16px; }
        .xp-header, .xp-toolbar { flex-direction: column; align-items: stretch; padding-right: 18px; padding-left: 18px; }
        .xp-header-actions .xp-add, .xp-search { width: 100%; }
        .xp-stats { grid-template-columns: 1fr; padding-right: 18px; padding-left: 18px; }
        .xp-section-title { margin-right: 18px; margin-left: 18px; }
        .xp-table-shell { margin-right: 12px; margin-left: 12px; }
        .xp-pagination { justify-content: center; margin-right: 12px; margin-left: 12px; }
        .xp-row { grid-template-columns: minmax(0, 1fr) 110px 42px; }
    }
</style>
