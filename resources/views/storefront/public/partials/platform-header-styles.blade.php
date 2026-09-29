.tms-nav {
    position: sticky;
    top: 0;
    z-index: 50;
    background: rgba(255, 255, 255, .96);
    border-bottom: 1px solid #e4edf5;
    box-shadow: 0 8px 30px rgba(11, 36, 71, .06);
    backdrop-filter: blur(14px)
}

.tms-nav .shell {
    width: min(1500px, calc(100% - 36px));
    min-height: 76px;
    display: grid;
    grid-template-columns: minmax(220px, auto) minmax(0, 1fr) auto;
    align-items: center;
    gap: clamp(16px, 2.2vw, 34px);
    direction: ltr
}

.tms-brand {
    min-height: 52px;
    display: flex;
    align-items: center;
    gap: 11px;
    color: var(--navy, #0b2447);
    text-decoration: none;
    direction: ltr
}

.tms-mark {
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: linear-gradient(145deg, var(--green, #0e8f68), #16b887);
    color: #fff;
    box-shadow: 0 9px 20px rgba(14, 143, 104, .2)
}

.tms-mark svg {
    width: 23px;
    height: 23px
}

.tms-wordmark strong {
    display: block;
    font: 900 1.35rem/1 Arial, sans-serif
}

.tms-wordmark small {
    display: block;
    margin-top: 5px;
    color: var(--muted, #64748b);
    font: 600 .63rem/1 Arial, sans-serif
}

.nav-center,
.nav-actions {
    display: flex;
    align-items: center
}

.nav-center {
    min-width: 0;
    justify-content: center;
    gap: clamp(2px, .65vw, 12px)
}

.nav-center a {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    padding: 8px clamp(7px, .7vw, 12px);
    color: #334a68;
    text-decoration: none;
    font-weight: 800;
    font-size: .87rem;
    gap: 5px;
    white-space: nowrap;
    font-family: Inter, Arial, sans-serif;
    line-height: 1.35
}

.nav-center a [lang="ur"] {
    font-family: "Noto Nastaliq Urdu", "Noto Sans Arabic", Tahoma, sans-serif;
    line-height: 2
}

.nav-actions {
    justify-content: flex-end;
    gap: 9px;
    flex-wrap: nowrap;
    white-space: nowrap
}

.tms-nav .locale-switch {
    color: #334a68
}

.nav-login,
.nav-demo {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 11px;
    padding: 8px 15px;
    text-decoration: none;
    font-weight: 900;
    white-space: nowrap;
    line-height: 1.65
}

.nav-login {
    border: 1px solid #a8c4b9;
    color: var(--dark-green, #087254)
}

.nav-demo {
    background: var(--blue, #2477ff);
    color: #fff;
    box-shadow: 0 9px 22px rgba(36, 119, 255, .2)
}

@media(max-width:1050px) {
    .tms-nav .shell {
        grid-template-columns: minmax(0, 1fr) auto
    }

    .nav-center {
        display: none
    }
}

@media(max-width:620px) {
    .tms-nav .shell {
        width: min(100% - 24px, 1500px);
        min-height: 68px;
        gap: 10px
    }

    .tms-wordmark small,
    .nav-actions .locale-switch,
    .nav-login {
        display: none
    }

    .tms-nav.is-login .nav-login {
        display: inline-flex;
        padding: 7px 10px
    }

    .nav-demo {
        padding: 8px 11px
    }
}
