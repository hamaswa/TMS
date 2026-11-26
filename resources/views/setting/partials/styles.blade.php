{{-- Shared look for the shop settings pages (list, add, edit). --}}
<style>
    .st-page{min-height:calc(100vh - 65px);padding:26px 0 48px;background:#f4f7fc;direction:rtl}
    .st-shell{width:calc(100% - 40px);margin:auto}
    .st-hero{position:relative;display:flex;align-items:center;gap:22px;min-height:124px;margin-bottom:22px;padding:22px 26px;overflow:hidden;border-radius:20px;background:linear-gradient(100deg,#eef4ff 0%,#dfe9ff 48%,#b9d0ff 100%)}
    .st-hero:before,.st-hero:after{content:"";position:absolute;pointer-events:none;border-radius:50%}
    .st-hero:before{inset-inline-end:-80px;top:-90px;width:520px;height:230px;background:radial-gradient(ellipse at center,rgba(255,255,255,.8),rgba(255,255,255,0) 70%);transform:rotate(-12deg)}
    .st-hero:after{inset-inline-end:120px;bottom:-110px;width:460px;height:200px;background:radial-gradient(ellipse at center,rgba(120,160,255,.45),rgba(120,160,255,0) 70%);transform:rotate(-10deg)}
    .st-hero-icon{position:relative;z-index:1;display:grid;place-items:center;flex:0 0 78px;height:78px;border-radius:50%;background:#e3ecff;color:#1d5fe0;font-size:2.1rem;box-shadow:inset 0 0 0 8px rgba(255,255,255,.55)}
    .st-hero-text{position:relative;z-index:1;flex:1;min-width:0}.st-hero-text h1{margin:0;color:#0f2547;font-size:1.65rem;font-weight:900}.st-hero-text p{margin:6px 0 0;color:#3f5577}
    .st-hero-art{position:relative;z-index:1;display:grid;place-items:center;width:96px;height:96px;border-radius:50%;background:rgba(255,255,255,.55);color:#5b7fc7;font-size:2.4rem}
    .st-hero-action{position:relative;z-index:1;display:inline-flex;align-items:center;gap:8px;min-height:46px;padding:0 20px;border-radius:12px;background:#1769e0;color:#fff!important;font-weight:900;text-decoration:none!important;box-shadow:0 10px 22px rgba(23,105,224,.25)}
    .st-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:22px;align-items:start}
    .st-card{padding:24px 26px;border:1px solid #e3eaf4;border-radius:20px;background:#fff;box-shadow:0 10px 30px rgba(15,37,71,.06)}
    .st-card+.st-card{margin-top:18px}
    .st-card-title{display:flex;align-items:center;gap:12px;margin:0 0 20px;color:#0f172a;font-size:1.2rem;font-weight:900}.st-card-title i{color:#1769e0;font-size:1.35rem}
    .st-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 22px}.st-field.is-wide{grid-column:1/-1}
    .st-field label{display:block;margin:0 0 8px;color:#0f172a;font-weight:800}.st-req{color:#dc2626}
    .st-input{position:relative}.st-input>i{position:absolute;inset-inline-start:16px;top:50%;transform:translateY(-50%);color:#475569;font-size:1rem;pointer-events:none}
    .st-input .form-control{height:50px;padding-inline-start:46px;border:1px solid #d6e0ec;border-radius:12px;background:#fff;color:#0f172a;font-weight:600;box-shadow:none}
    .st-input .form-control:focus{border-color:#7aa7f5;box-shadow:0 0 0 3px rgba(23,105,224,.12)}
    .st-input.is-area>i{top:22px;transform:none}.st-input textarea.form-control{height:auto;min-height:96px;padding-top:13px;resize:vertical}
    .st-count{margin-top:5px;color:#64748b;font-size:.78rem;text-align:left;direction:ltr}
    .st-file{display:flex;align-items:center;gap:12px;min-height:58px;padding:6px;border:1px solid #d6e0ec;border-radius:12px;background:#fff}
    .st-file-thumb{display:grid;flex:0 0 74px;place-items:center;height:46px;overflow:hidden;border-radius:9px;background:#e8f0fd;color:#5b7fc7}.st-file-thumb img{width:100%;height:100%;object-fit:cover}
    .st-file-pick{display:inline-flex;align-items:center;margin:0;padding:9px 16px;border:1px solid #d6e0ec;border-radius:10px;background:#f1f5fb;color:#0f172a;font-weight:800;cursor:pointer}
    .st-file-name{flex:1;min-width:0;overflow:hidden;color:#475569;font-size:.85rem;text-overflow:ellipsis;white-space:nowrap}
    .st-file-view{display:grid;place-items:center;width:44px;height:44px;border-inline-start:1px solid #e3eaf4;color:#1769e0!important;font-size:1.05rem}
    .st-file input[type=file]{position:absolute;width:1px;height:1px;opacity:0}
    .st-check{display:flex;align-items:center;gap:12px;min-height:50px;margin:0;cursor:pointer;color:#0f172a;font-weight:700}.st-check input{width:22px;height:22px;accent-color:#1769e0}.st-check .fa-info-circle{color:#1769e0}
    .st-actions{display:flex;flex-wrap:wrap;gap:14px;margin-top:26px}
    .st-btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:50px;padding:0 24px;border:1px solid #d6e0ec;border-radius:12px;background:#fff;color:#0f172a!important;font-weight:900;text-decoration:none!important}
    .st-btn.is-primary{border-color:#1769e0;background:#1769e0;color:#fff!important;box-shadow:0 10px 22px rgba(23,105,224,.25)}
    .st-side{padding:22px;border:1px solid #e3eaf4;border-radius:20px;background:#fff;box-shadow:0 10px 30px rgba(15,37,71,.06)}
    .st-side-inner{padding:24px 22px;border-radius:16px;background:linear-gradient(180deg,#f1f5fd,#eaf1ff)}
    .st-side-icon{display:grid;place-items:center;width:60px;height:60px;margin:0 auto 16px;border-radius:50%;background:#dbe6ff;box-shadow:inset 0 0 0 7px rgba(255,255,255,.6)}.st-side-icon span{display:grid;place-items:center;width:34px;height:34px;border-radius:50%;background:#1769e0;color:#fff;font-weight:900}
    .st-side h3{margin:0 0 10px;color:#0f172a;font-size:1.2rem;font-weight:900}.st-side p{margin:0;color:#475569;line-height:1.9}
    .st-side ul{margin:18px 0 0;padding:18px 0 0;border-top:1px solid #d5e0f2;list-style:none}.st-side li{display:flex;align-items:center;gap:12px;margin-bottom:12px;color:#1e293b;font-weight:700}.st-side li i{color:#1769e0;font-size:1.15rem}
    .st-side-art{display:grid;place-items:center;height:130px;margin-top:10px;color:#a9c0ee;font-size:4.2rem}
    .st-code{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px;padding:16px 20px;border:1px solid #cfe0f7;border-radius:16px;background:#f1f7ff;color:#164e75}.st-code code{padding:8px 13px;border:1px solid #cfe2f2;border-radius:9px;background:#fff;color:#0f172a;font-size:1rem;font-weight:800}
    .st-profile{display:grid;grid-template-columns:170px minmax(0,1fr);gap:22px;align-items:start}
    .st-logo{display:grid;place-items:center;min-height:150px;overflow:hidden;border:1px dashed #c6d4e6;border-radius:16px;background:#f6f9fe;color:#94a3b8;text-align:center}.st-logo img{max-width:100%;max-height:150px;object-fit:contain}
    .st-tiles{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.st-tile{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1px solid #e3eaf4;border-radius:14px;background:#fbfcff}.st-tile.is-wide{grid-column:1/-1}
    .st-tile>i{display:grid;flex:0 0 36px;place-items:center;height:36px;border-radius:10px;background:#eaf1ff;color:#1769e0}.st-tile small{display:block;margin-bottom:3px;color:#64748b;font-size:.78rem;font-weight:700}.st-tile strong{color:#0f172a;word-break:break-word}
    .st-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:999px;background:#e7f8ee;color:#15803d;font-size:.8rem;font-weight:900}
    .st-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px}.st-card-head .st-card-title{margin:0}
    .st-archive{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 0;border-top:1px solid #edf1f6}.st-archive:first-child{border-top:0}.st-archive-actions{display:flex;align-items:center;gap:8px}.st-archive-actions form{margin:0}
    .st-empty{padding:40px 20px;color:#64748b;text-align:center}.st-empty i{display:block;margin-bottom:12px;color:#b8c5d4;font-size:2.2rem}
    @media(max-width:1100px){.st-grid{grid-template-columns:1fr}}
    @media(max-width:767px){.st-hero{flex-wrap:wrap;padding:18px}.st-hero-art{display:none}.st-fields,.st-tiles,.st-profile{grid-template-columns:1fr}.st-field.is-wide,.st-tile.is-wide{grid-column:auto}.st-card{padding:18px}.st-code{flex-direction:column;align-items:stretch}.st-actions .st-btn,.st-hero-action{width:100%}}
</style>
