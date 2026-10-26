<style>
.home-landing { background: var(--surface); animation: none !important; }
.home-landing .landing-inner { width: min(calc(100% - 48px), 1280px); margin-inline: auto; }
.home-landing .landing-section { padding-block: 96px; background: var(--surface); scroll-margin-top: 104px; }
.home-landing h1 { font-size: clamp(2rem, 4vw, 3.5rem); line-height: 1.15; letter-spacing: -.025em; font-weight: 700; }
.home-landing h2 { font-size: clamp(1.75rem, 2.2vw, 2rem); line-height: 1.3; letter-spacing: -.02em; font-weight: 700; }
.home-landing h3 { font-size: 1rem; line-height: 1.5; font-weight: 600; }
.home-hero { padding: 24px 0 0; background: var(--surface); }
.home-hero-top { position: relative; display: flex; align-items: end; height: 420px; overflow: hidden; border-radius: 16px; background: var(--photo-ink); }
.home-hero-visual { position: absolute; inset: 0; }
.home-hero-scrim { position: absolute; inset: 0; }
.home-hero-photo { width: 100%; height: 100%; object-fit: cover; object-position: center 60%; }
.home-hero-scrim { background: linear-gradient(90deg, rgb(12 20 20 / .72), rgb(12 20 20 / .18) 65%, rgb(12 20 20 / .02)); }
.home-hero-copy { position: relative; padding: 40px 40px 96px; }
.home-hero-copy h1, .home-hero-subtitle { color: var(--paper); }
.home-hero-subtitle { margin-top: 16px; font-size: 1.125rem; line-height: 1.7; }
.home-landing .hero-search { position: relative; translate: none; display: grid; grid-template-columns: 1.1fr 1.4fr .7fr auto; align-items: end; gap: 16px; width: calc(100% - 64px); margin: -56px auto 0; padding: 24px; border: 1px solid var(--line); border-radius: 12px; background: var(--paper); box-shadow: 0 8px 28px rgb(12 20 20 / .1); scroll-margin-top: 104px; color-scheme: light; color: #4B5A58; --surface: #FFFFFF; --ink: #13201F; --ink-muted: #4B5A58; --line: #DCE3E1; --accent: #0F5E5C; --on-accent: #FFFFFF; --accent-hover: color-mix(in srgb, var(--accent) 88%, var(--ink)); }
.home-landing .hero-search > div, .home-landing .hero-search input, .home-landing .hero-search select { min-width: 0; }
.home-landing .hero-search input, .home-landing .hero-search select, .home-landing .hero-search button { min-height: 48px; box-shadow: none; }
.home-section-head { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; }
.home-text-link { display: inline-flex; align-items: center; min-height: 44px; color: var(--accent); font-size: .875rem; font-weight: 600; text-underline-offset: 4px; }
.home-text-link:hover { text-decoration: underline; }
.home-section-head .home-text-link { flex-shrink: 0; }
.home-type-filters { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0 20px; }
.home-type-filters a { display: inline-flex; align-items: center; min-height: 40px; padding: 6px 18px; border: 1px solid var(--line); border-radius: 20px; color: var(--ink); font-size: .875rem; transition: border-color 160ms ease; }
.home-type-filters a:hover, .home-type-filters .is-selected { border-color: var(--accent); color: var(--accent); }
/* Three columns for a small catalog; four for larger selections. */
.home-motors { display: grid; grid-template-columns: repeat(var(--motor-count), minmax(0, 1fr)); gap: 24px; }
.home-motor { min-width: 0; color: var(--ink-muted); }
.home-motor-photo { display: flex; align-items: center; justify-content: center; aspect-ratio: 4 / 3; background: var(--bg); border-radius: 12px; overflow: hidden; }
.home-motor-photo img { height: 100%; width: 100%; object-fit: cover; object-position: center; transition: transform 160ms ease; }
.home-motor:hover img { transform: scale(1.02); }
.home-motor h3 { margin-top: 12px; }
.home-motor-type, .home-status, .home-catalog-note { font-size: .875rem; line-height: 1.6; color: var(--ink-muted); }
.home-motor-type { margin-top: 2px; }
.home-price { margin-top: 8px; }
.home-price strong { color: var(--ink); font-size: 1.125rem; font-weight: 700; }
.home-price span { margin-left: 4px; font-size: .8125rem; }
.home-status { display: inline-flex; margin-top: 8px; padding: 2px 10px; border-radius: 999px; background: var(--bg); font-size: .8125rem; }
.home-status.is-available { background: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent); font-weight: 600; }
.home-catalog-note { margin-top: 16px; }
.home-inclusions ul { display: flex; flex-wrap: wrap; gap: 24px 56px; margin-top: 20px; }
.home-inclusions li { display: flex; align-items: center; gap: 12px; color: var(--ink); font-size: .9375rem; }
.home-inclusions svg { width: 24px; height: 24px; flex-shrink: 0; }
.home-landing .home-information-band { padding-block: 64px; background: var(--bg); }
.home-information-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 64px; }
.home-rental-terms { scroll-margin-top: 104px; }
.home-rental-terms ul { display: flex; flex-wrap: wrap; gap: 12px 32px; list-style: disc; padding-left: 20px; margin-top: 20px; color: var(--ink); }
.home-rental-terms .home-text-link { margin-top: 16px; }
.home-branches { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px 32px; margin-top: 20px; }
.home-branches article { min-width: 0; padding-block: 16px; border-top: 1px solid var(--line); }
.home-branches p { font-size: .875rem; line-height: 1.7; margin-top: 6px; overflow-wrap: anywhere; }
.home-branch-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
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
@keyframes home-fade { from { opacity: 0; } to { opacity: 1; } }
.home-landing .anim-masuk > * { animation: home-fade 250ms ease backwards; }
.home-landing .reveal { animation: none; translate: none !important; }
.home-landing .reveal-enter { animation: reveal-in 250ms ease both; }
@media (max-width: 1023px) {
    .home-landing .hero-search { grid-template-columns: 1fr 1fr; }
    .home-motors { grid-template-columns: repeat(min(2, var(--motor-count)), minmax(0, 1fr)); }
    .home-branches { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 639px) {
    .home-landing .landing-inner { width: calc(100% - 32px); }
    .home-hero { padding-top: 20px; }
    .home-hero-top { height: 260px; }
    .home-hero-photo { object-fit: cover; object-position: 65% center; }
    .home-hero-copy { padding: 24px 24px 64px; }
    .home-landing h1 { font-size: 1.875rem; }
    .home-hero-subtitle { font-size: .875rem; margin-top: 8px; }
    .home-landing .hero-search { grid-template-columns: 1fr; width: calc(100% - 24px); padding: 16px; gap: 12px; margin-top: -24px; }
    .home-landing .landing-section { padding-block: 56px; }
    .home-landing h2 { font-size: 1.75rem; }
    .home-section-head { align-items: center; gap: 12px; }
    .home-section-head h2 { font-size: 1.75rem; }
    .home-motors { gap: 20px 12px; }
    .home-motor h3 { font-size: .875rem; }
    .home-price strong { font-size: 1rem; }
    .home-price span { font-size: .8125rem; margin-left: 2px; }
    .home-motor-type, .home-status { font-size: .8125rem; }
    .home-inclusions ul { gap: 16px; flex-direction: column; }
    .home-information-grid { grid-template-columns: 1fr; gap: 40px; }
    .home-rental-terms ul { margin-top: 16px; }
    .home-rental-terms .home-text-link { margin-top: 8px; }
    .home-branches, .home-steps { grid-template-columns: 1fr; gap: 16px; }
}
@media (prefers-reduced-motion: reduce) {
    .home-landing .reveal, .home-landing .reveal-enter, .home-landing .anim-masuk > * { animation: none !important; opacity: 1 !important; }
    .home-motor:hover img { transform: none; }
}
</style>
