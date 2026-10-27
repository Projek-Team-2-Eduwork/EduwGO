<style>
.home-landing { background: var(--surface); animation: none !important; }
.home-landing .landing-inner { width: min(calc(100% - 48px), 1280px); margin-inline: auto; }
.home-landing .landing-section { padding-block: 96px; background: var(--surface); scroll-margin-top: 104px; }
.home-landing h1 { font-size: clamp(2rem, 4vw, 3.5rem); line-height: 1.15; letter-spacing: -.025em; font-weight: 700; }
.home-landing h2 { font-size: clamp(1.75rem, 2.2vw, 2rem); line-height: 1.3; letter-spacing: -.02em; font-weight: 700; }
.home-landing h3 { font-size: 1rem; line-height: 1.5; font-weight: 600; }
/* Navbar kaca di atas foto hero; saat digulir tetap memakai gaya pill yang sudah ada. */
.navbar-edu--hero { position: fixed; inset-inline: 0; }
.navbar-edu--hero:not(.is-scrolled) { background-color: transparent; }
.navbar-edu--hero:not(.is-scrolled) .navbar-edu-inner { width: 100%; max-width: 100%; height: 100%; margin-top: 0; }
.navbar-edu--hero:not(.is-scrolled) .navbar-edu-bar { height: 100%; padding-inline: clamp(1rem, 3vw, 3rem); border: 0; border-bottom: 1px solid rgb(255 255 255 / .14); border-radius: 0; background: linear-gradient(180deg, rgb(12 20 20 / .58), rgb(12 20 20 / .30)); box-shadow: none; -webkit-backdrop-filter: blur(18px) saturate(135%); backdrop-filter: blur(18px) saturate(135%); }
.navbar-edu--hero:not(.is-scrolled) .brand-logo { --ink: var(--paper); --accent: var(--paper); color: var(--paper); }
.navbar-edu--hero:not(.is-scrolled) .hero-nav-link { color: color-mix(in srgb, var(--paper) 84%, transparent); }
.navbar-edu--hero:not(.is-scrolled) .hero-nav-link:hover,
.navbar-edu--hero:not(.is-scrolled) .hero-nav-link[aria-current='page'] { background-color: color-mix(in srgb, var(--paper) 12%, transparent); color: var(--paper); }
.navbar-edu--hero:not(.is-scrolled) .hero-nav-control { color: var(--paper); background-color: transparent !important; border-color: rgb(255 255 255 / .32) !important; }
.navbar-edu--hero:not(.is-scrolled) .hero-nav-control:hover { background-color: rgb(255 255 255 / .12) !important; }
.navbar-edu--hero:not(.is-scrolled) .hero-nav-control svg { color: inherit; }
.navbar-edu--hero:not(.is-scrolled) button[aria-label='Menu akun'] { color: var(--paper); }
.navbar-edu--hero:not(.is-scrolled) button[aria-label='Menu akun'] > span { color: var(--paper); background-color: rgb(255 255 255 / .16) !important; }
.navbar-edu--hero:not(.is-scrolled) button[aria-controls='mobile-menu'] { color: var(--paper) !important; }
.navbar-edu--hero:not(.is-scrolled) button[aria-controls='mobile-menu']:hover { background-color: rgb(255 255 255 / .12) !important; }
.navbar-edu--hero:not(.is-scrolled) a:focus-visible,
.navbar-edu--hero:not(.is-scrolled) button:focus-visible { outline-color: var(--paper); }
.home-hero { padding: 0; background: var(--surface); }
.home-hero-top { position: relative; display: flex; align-items: center; width: 100%; height: 460px; height: clamp(380px, calc(46svh + 40px), 460px); overflow: hidden; background: var(--photo-ink); }
.home-hero-visual { position: absolute; inset: 0; }
.home-hero-scrim { position: absolute; inset: 0; }
.home-hero-photo { width: 100%; height: 100%; object-fit: cover; object-position: center 60%; }
.home-hero-scrim { background: linear-gradient(180deg, rgb(12 20 20 / .58) 0%, rgb(12 20 20 / .30) 45%, rgb(12 20 20 / .58) 100%); }
.home-hero-copy { position: relative; width: min(calc(100% - 48px), 1280px); margin-inline: auto; padding: 0; text-align: center; }
.home-hero-copy h1 { font-size: clamp(2.25rem, 4.5vw, 4rem); line-height: 1.08; letter-spacing: -.03em; text-wrap: balance; }
.home-hero-copy h1, .home-hero-subtitle { color: var(--paper); text-shadow: 0 1px 2px rgb(0 0 0 / .22); }
.home-hero-subtitle { max-width: 46rem; margin: 14px auto 0; font-size: 1.125rem; line-height: 1.65; text-wrap: balance; }
.home-landing .hero-search { position: relative; translate: none; display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1.3fr) minmax(0, .65fr) minmax(9.5rem, auto); align-items: end; gap: 12px; width: 100%; margin: 10px auto 0; padding: 20px; border: 1px solid var(--line); border-radius: 10px; background: var(--paper); box-shadow: 0 4px 12px rgb(12 20 20 / .05); scroll-margin-top: 104px; color-scheme: light; color: #4B5A58; --surface: #FFFFFF; --ink: #13201F; --ink-muted: #4B5A58; --line: #DCE3E1; --accent: #0F5E5C; --on-accent: #FFFFFF; --accent-hover: color-mix(in srgb, var(--accent) 88%, var(--ink)); }
.home-landing .hero-search > div, .home-landing .hero-search input, .home-landing .hero-search select { min-width: 0; }
.home-landing .hero-search input, .home-landing .hero-search select, .home-landing .hero-search button { min-height: 48px; box-shadow: none; }
.home-landing .hero-search button { min-width: 9.5rem; white-space: nowrap; }
.home-section-head { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; }
.home-text-link { display: inline-flex; align-items: center; min-height: 44px; color: var(--accent); font-size: .875rem; font-weight: 600; text-underline-offset: 4px; }
.home-text-link:hover { text-decoration: underline; }
.home-section-head .home-text-link { flex-shrink: 0; }
.home-type-filters { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0 20px; }
.home-type-filters a { display: inline-flex; align-items: center; min-height: 40px; padding: 6px 18px; border: 1px solid var(--line); border-radius: 20px; color: var(--ink); font-size: .875rem; transition: border-color 160ms ease; }
.home-type-filters a:hover, .home-type-filters .is-selected { border-color: var(--accent); color: var(--accent); }
/* Three columns for a small catalog; four for larger selections. */
.home-motors { display: grid; grid-template-columns: repeat(var(--motor-count), minmax(0, 1fr)); align-items: stretch; gap: 24px; }
.home-motor {
    display: flex;
    flex-direction: column;
    min-width: 0;
    height: 100%;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: var(--surface);
    color: var(--ink-muted);
    transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
}
.home-motor-photo { display: flex; flex-shrink: 0; align-items: center; justify-content: center; aspect-ratio: 4 / 3; background: var(--bg); overflow: hidden; }
.home-motor-photo img { height: 100%; width: 100%; object-fit: cover; object-position: center; transition: transform 160ms ease; }
.home-motor-body { display: flex; flex: 1; flex-direction: column; padding: 14px; }
.home-motor-placeholder { font-size: .875rem; }
.home-motor-type, .home-status, .home-catalog-note { font-size: .875rem; line-height: 1.6; color: var(--ink-muted); }
.home-motor-type { font-size: .75rem; line-height: 1.4; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
.home-motor h3 { min-height: 3em; margin-top: 4px; }
.home-motor h3 a { color: var(--ink); }
.home-motor h3 a:hover { text-decoration: underline; text-underline-offset: 3px; }
.home-status { display: inline-flex; align-items: flex-start; gap: 6px; margin-top: 6px; font-size: .8125rem; line-height: 1.5; }
.home-status-dot { flex-shrink: 0; width: 6px; height: 6px; margin-top: .45em; border-radius: 999px; background: currentColor; }
.home-status.is-available { color: var(--accent); font-weight: 600; }
.home-motor-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: auto; padding-top: 14px; border-top: 1px solid var(--line); }
.home-price { margin-top: 0; }
.home-price strong { color: var(--ink); font-size: 1.125rem; font-weight: 700; }
.home-price span { margin-left: 4px; font-size: .8125rem; }
.home-motor-book { flex-shrink: 0; min-height: 44px; padding-inline: .875rem; font-size: .8125rem; }
.home-catalog-note { margin-top: 16px; }
@media (min-width: 1024px) and (max-width: 1279px) {
    .home-motor-body { padding: 12px; }
    .home-price strong { font-size: 1rem; }
    .home-motor-book { padding-inline: .75rem; }
}
@media (hover: hover) {
    .home-motor:hover { transform: translateY(-2px); border-color: color-mix(in srgb, var(--accent) 32%, var(--line)); box-shadow: 0 10px 28px color-mix(in srgb, var(--ink) 10%, transparent); }
    .home-motor:hover .home-motor-photo img { transform: scale(1.02); }
}
.home-motor:focus-within { border-color: color-mix(in srgb, var(--accent) 32%, var(--line)); box-shadow: 0 10px 28px color-mix(in srgb, var(--ink) 10%, transparent); }
.home-inclusions ul,
.home-rental-terms ul { display: grid; gap: 12px; margin-top: 20px; padding: 0; list-style: none; }
.home-inclusions li,
.home-rental-terms li { display: flex; align-items: center; gap: 12px; color: var(--ink); font-size: .9375rem; line-height: 1.6; }
.home-inclusions svg { width: 24px; height: 24px; flex-shrink: 0; }
.home-rental-terms svg { width: 20px; height: 20px; flex-shrink: 0; color: var(--accent); }
.home-landing .home-information-band { padding-block: 64px; background: var(--bg); }
.home-information-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.home-rental-terms { padding-left: 64px; border-left: 1px solid var(--line); scroll-margin-top: 104px; }
.home-rental-terms .home-text-link { margin-top: 16px; }
.home-branches { margin-top: 20px; border-top: 1px solid var(--line); }
.home-branches article { display: grid; grid-template-columns: 200px minmax(0, 1fr) auto; align-items: center; gap: 16px 32px; min-width: 0; padding-block: 18px; border-bottom: 1px solid var(--line); }
.home-branch-details { min-width: 0; }
.home-branches p { font-size: .875rem; line-height: 1.7; margin-top: 0; overflow-wrap: anywhere; }
.home-branch-details p + p { margin-top: 3px; }
.home-branch-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; margin-top: 0; }
.home-branch-empty { padding-block: 16px; color: var(--ink-muted); }
.home-outline-link { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; border: 1px solid var(--line); border-radius: 6px; padding: 8px 16px; color: var(--ink); font-size: .875rem; font-weight: 600; transition: border-color 160ms ease; }
.home-outline-link:hover { border-color: var(--accent); color: var(--accent); }
.home-steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 32px; margin-top: 20px; }
.home-steps li { display: flex; align-items: start; gap: 12px; }
.home-steps li > span { font-size: 3.5rem; line-height: 1; font-weight: 300; color: inherit; }
.home-landing .home-steps-band { background: #0F5E5C; color: var(--paper); }
.home-steps-band h2, .home-steps-band h3 { color: inherit; }
.home-steps-band .home-steps { margin-top: 40px; gap: 40px; }
.home-steps-band .home-steps li { gap: 20px; }
.home-steps-cta { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 12px 24px; margin-top: 40px; border-radius: 8px; background: var(--paper); color: #0F5E5C; font-weight: 600; transition: background-color 160ms ease; }
.home-steps-cta:hover { background: #DCE3E1; }
.home-steps-band .home-steps-cta:focus-visible { outline-color: var(--paper); }
.home-steps p { margin-top: 4px; font-size: .875rem; line-height: 1.7; }
.home-faq { margin-top: 16px; }
.home-faq details { border-bottom: 1px solid var(--line); }
.home-faq summary { display: flex; align-items: center; justify-content: space-between; gap: 24px; min-height: 56px; padding-block: 14px; cursor: pointer; list-style: none; color: var(--ink); font-size: .9375rem; font-weight: 600; }
.home-faq summary::-webkit-details-marker { display: none; }
.home-faq summary span { font-size: 1.25rem; font-weight: 400; }
.home-faq details[open] summary span { transform: rotate(45deg); }
.home-faq p, .home-faq ul { max-width: 75ch; padding-bottom: 16px; font-size: .875rem; line-height: 1.8; }
.home-faq ul { list-style: disc; padding-left: 20px; }
.home-landing :is(a,summary):focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
.home-landing .home-motor-photo:focus-visible { outline-offset: -3px; }
@keyframes home-fade { from { opacity: 0; } to { opacity: 1; } }
.home-landing .anim-masuk > * { animation: home-fade 250ms ease backwards; }
.home-landing .reveal { animation: none; translate: none !important; }
.home-landing .reveal-enter { animation: reveal-in 250ms ease both; }
@media (max-width: 1023px) {
    .home-landing .hero-search { grid-template-columns: 1.1fr 1.3fr .7fr; }
    .home-landing .hero-search > :last-child { grid-column: 1 / -1; }
    .home-motors { grid-template-columns: repeat(min(2, var(--motor-count)), minmax(0, 1fr)); }
    .home-branches article { grid-template-columns: 160px minmax(0, 1fr) auto; gap: 16px 24px; }
}
@media (max-width: 767px) {
    .home-information-grid { grid-template-columns: 1fr; gap: 40px; }
    .home-rental-terms { padding-left: 0; border-left: 0; border-top: 1px solid var(--line); padding-top: 24px; }
    .home-branches article { grid-template-columns: 1fr; gap: 8px; padding-block: 16px; }
    .home-branch-actions { justify-content: flex-start; margin-top: 4px; }
}
@media (max-width: 639px) {
    .home-landing .landing-inner { width: calc(100% - 32px); }
    .home-hero { padding-top: 0; }
    .home-hero-top { height: 300px; }
    .home-hero-photo { object-fit: cover; object-position: 65% center; }
    .home-hero-copy { width: calc(100% - 32px); padding: 0; }
    .home-landing h1 { font-size: 2rem; }
    .home-hero-subtitle { font-size: .9375rem; margin-top: 10px; }
    .home-landing .hero-search { grid-template-columns: 1fr; width: 100%; margin-top: 12px; padding: 16px; gap: 12px; border-radius: 10px; }
    .home-landing .landing-section { padding-block: 56px; }
    .home-landing h2 { font-size: 1.75rem; }
    .home-section-head { align-items: center; gap: 12px; }
    .home-section-head h2 { font-size: 1.75rem; }
    .home-motors { gap: 16px 12px; }
    .home-landing .home-motors > a { padding: 12px; }
    .home-landing .home-motors > a > div:last-child { flex-direction: column; align-items: stretch; gap: 10px; padding-top: 12px; }
    .home-landing .home-motors > a .btn-primary-edu { width: 100%; justify-content: center; }
    .home-landing .home-motors > a h3 { font-size: .875rem; }
    .home-motor-body { padding: 12px; }
    .home-motor h3 { font-size: .875rem; }
    .home-price strong { font-size: 1rem; }
    .home-price span { font-size: .8125rem; margin-left: 2px; }
    .home-motor-type, .home-status { font-size: .75rem; }
    .home-motor-footer { flex-direction: column; align-items: stretch; gap: 10px; padding-top: 12px; }
    .home-motor-book { width: 100%; }
    .home-rental-terms .home-text-link { margin-top: 8px; }
    .home-steps { grid-template-columns: 1fr; gap: 16px; }
}
@media (prefers-reduced-motion: reduce) {
    .home-landing .reveal, .home-landing .reveal-enter, .home-landing .anim-masuk > * { animation: none !important; opacity: 1 !important; }
    .home-motor:hover { transform: none; }
    .home-motor:hover .home-motor-photo img { transform: none; }
}
</style>
