let cleanupLandingHeader = () => {};

function initializeLandingHeader() {
    cleanupLandingHeader();
    const header = document.querySelector('body.printify-landing > header');
    if (!header) {
        return;
    }

    const updateHeight = () => document.documentElement.style.setProperty(
        '--landing-header-height', `${header.getBoundingClientRect().height}px`,
    );
    updateHeight();
    const observer = typeof window.ResizeObserver === 'function' ? new window.ResizeObserver(updateHeight) : null;
    observer?.observe(header);
    window.addEventListener('resize', updateHeight);
    cleanupLandingHeader = () => {
        observer?.disconnect();
        window.removeEventListener('resize', updateHeight);
        document.documentElement.style.removeProperty('--landing-header-height');
    };
}

let cleanupLandingMotion = () => {};
function initializeLandingMotion() {
    cleanupLandingMotion();
    initializeLandingHeader();

    const sections = document.querySelectorAll('.landing-page [data-landing-reveal], .landing-page [data-landing-heading]');
    const root = document.documentElement;
    window.clearTimeout(window.landingMotionFallback);
    const preference = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (sections.length === 0 || preference.matches || !('IntersectionObserver' in window) || typeof root.animate !== 'function') {
        root.classList.remove('landing-motion-enabled');
        return;
    }

    root.classList.add('landing-motion-enabled');
    const animations = new Set();
    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (!entry.isIntersecting || entry.target.hasAttribute('data-landing-revealed')) {
                continue;
            }

            observer.unobserve(entry.target);
            entry.target.setAttribute('data-landing-revealed', '');

            if (preference.matches || entry.target.matches(':focus-within') || typeof entry.target.animate !== 'function') {
                continue;
            }

            if (!entry.target.hasAttribute('data-landing-heading')) {
                const animation = entry.target.animate(
                    [{ transform: 'translateY(16px)' }, { transform: 'translateY(0)' }],
                    { duration: 1400, delay: 180, fill: 'backwards', easing: 'cubic-bezier(0.22, 0.61, 0.36, 1)' },
                );

                animations.add(animation);
                animation.addEventListener('finish', () => animations.delete(animation), { once: true });

                continue;
            }

            entry.target.querySelectorAll('.landing-heading-word').forEach((word, index) => {
                const wordAnimation = word.animate(
                    [{ opacity: 0, transform: 'translateY(8px)' }, { opacity: 1, transform: 'translateY(0)' }],
                    { duration: 1200, delay: 260 + Math.min(index, 8) * 90, fill: 'backwards', easing: 'cubic-bezier(0.22, 0.61, 0.36, 1)' },
                );
                animations.add(wordAnimation);
                wordAnimation.addEventListener('finish', () => animations.delete(wordAnimation), { once: true });
            });
        }
    }, { threshold: 0.18, rootMargin: '0px 0px -8% 0px' });

    const stop = () => {
        root.classList.remove('landing-motion-enabled');
        observer.disconnect();
        animations.forEach((animation) => animation.cancel());
        animations.clear();
    };

    sections.forEach((section) => {
        if (!section.hasAttribute('data-landing-revealed')) {
            observer.observe(section);
        }
    });
    preference.addEventListener('change', stop);

    cleanupLandingMotion = () => {
        stop();
        preference.removeEventListener('change', stop);
    };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeLandingMotion, { once: true });
} else {
    initializeLandingMotion();
}

document.addEventListener('livewire:navigating', () => {
    cleanupLandingHeader();
    cleanupLandingMotion();
});
document.addEventListener('livewire:navigated', initializeLandingMotion);
