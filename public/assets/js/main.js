/**
 * SPIV — main.js
 * Interações e animações do sistema
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Animação de entrada nos cards ao scroll ──────────────
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.animate-in');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        elements.forEach(el => {
            el.style.animationPlayState = 'paused';
            observer.observe(el);
        });
    };

    animateOnScroll();

    // ── Highlight do link ativo na navbar ───────────────────
    const currentPath = window.location.pathname;
    document.querySelectorAll('.spiv-navbar .nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.endsWith(href.replace(/^.*\//, '')) && href !== '/') {
            link.classList.add('active');
        }
        if (href === '/' && currentPath === '/') {
            link.classList.add('active');
        }
    });

    // ── Efeito de contagem nos stats ────────────────────────
    const countUp = (el, target, duration = 1200) => {
        let start = 0;
        const step = target / (duration / 16);
        const timer = setInterval(() => {
            start += step;
            if (start >= target) {
                el.textContent = target.toLocaleString('pt-BR');
                clearInterval(timer);
            } else {
                el.textContent = Math.floor(start).toLocaleString('pt-BR');
            }
        }, 16);
    };

    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.dataset.target || el.textContent.replace(/\D/g, ''), 10);
                if (!isNaN(target)) countUp(el, target);
                statsObserver.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.stat-number[data-target]').forEach(el => {
        statsObserver.observe(el);
    });

    // ── Fechar navbar mobile ao clicar no link ──────────────
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        link.addEventListener('click', () => {
            const toggler = document.querySelector('.navbar-toggler');
            const collapse = document.querySelector('.navbar-collapse');
            if (collapse && collapse.classList.contains('show')) {
                toggler && toggler.click();
            }
        });
    });

    // ── Tooltip Bootstrap init ───────────────────────────────
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(el => new bootstrap.Tooltip(el));

});
