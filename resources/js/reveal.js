export function initReveal(root = document, viewport = window) {
    root.querySelectorAll('.anim-masuk > *').forEach(element => {
        if (element.getBoundingClientRect().top >= viewport.innerHeight) element.classList.add('load-skip');
    });
    const items = [...root.querySelectorAll('.reveal')];
    if (!items.length) return;
    const motion = viewport.matchMedia('(prefers-reduced-motion: reduce)');
    const finish = element => {
        element.classList.remove('reveal-pending', 'reveal-enter');
        element.classList.add('is-revealed');
    };
    // Above-fold descendants delegate their entrance to the production load wrapper.
    items.filter(element => {
        const bounds = element.getBoundingClientRect();
        return bounds.top < viewport.innerHeight && bounds.bottom > 0;
    }).forEach(finish);
    const pending = items.filter(element => !element.classList.contains('is-revealed'));
    if (!pending.length) return;
    if (motion.matches) {
        items.forEach(finish);
        return;
    }
    let observer;
    root.addEventListener('focusin', event => {
        const element = event.target.closest?.('.reveal, .hero-search');
        if (element) {
            observer?.unobserve(element);
            finish(element);
        }
    });
    root.addEventListener('animationend', event => {
        if (event.animationName === 'reveal-in' && event.target.classList?.contains('reveal')) finish(event.target);
    });
    motion.addEventListener('change', event => {
        if (event.matches) {
            observer?.disconnect();
            items.forEach(finish);
        }
    });
    if (viewport.CSS?.supports('animation-timeline: view()') && viewport.CSS.supports('animation-range: entry 0% entry 100%')) {
        return;
    }
    if (!viewport.IntersectionObserver) return;
    observer = new viewport.IntersectionObserver(entries => {
        entries.forEach(({ target, isIntersecting }) => {
            if (!isIntersecting) return;
            target.classList.remove('reveal-pending');
            target.classList.add('reveal-enter');
            observer.unobserve(target);
        });
    }, { threshold: .12 });
    pending.forEach(element => {
        element.classList.add('reveal-pending');
        observer.observe(element);
    });
}
