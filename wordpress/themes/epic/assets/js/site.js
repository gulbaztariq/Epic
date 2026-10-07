/* EPIC public website behaviour. Vanilla JS, no build step required. */
(function () {
    'use strict';

    var d = document;

    function on(el, evt, fn) { if (el) { el.addEventListener(evt, fn); } }
    function all(sel, root) { return Array.prototype.slice.call((root || d).querySelectorAll(sel)); }

    /* ---- Sticky header shadow ------------------------------------------- */
    var header = d.querySelector('.site-header');
    var toTop = d.querySelector('.back-to-top');

    function onScroll() {
        var y = window.pageYOffset || d.documentElement.scrollTop;
        if (header) { header.classList.toggle('is-stuck', y > 8); }
        if (toTop) { toTop.classList.toggle('is-visible', y > 600); }
    }
    on(window, 'scroll', onScroll);
    onScroll();

    on(toTop, 'click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* ---- Desktop dropdowns (click support for touch screens) ------------- */
    all('.nav-list > li.has-drop > .nav-link').forEach(function (link) {
        on(link, 'click', function (e) {
            var parent = link.parentNode;
            // Let plain links work on desktop; toggle when the caret is tapped.
            if (window.matchMedia('(hover: none)').matches) {
                e.preventDefault();
                var open = parent.classList.contains('is-open');
                all('.nav-list > li').forEach(function (li) { li.classList.remove('is-open'); });
                parent.classList.toggle('is-open', !open);
            }
        });
    });

    on(d, 'click', function (e) {
        if (!e.target.closest('.primary-nav')) {
            all('.nav-list > li').forEach(function (li) { li.classList.remove('is-open'); });
        }
    });

    /* ---- Mobile navigation ---------------------------------------------- */
    var mobileNav = d.querySelector('.mobile-nav');
    var backdrop = d.querySelector('.nav-backdrop');

    function closeNav() {
        if (mobileNav) { mobileNav.classList.remove('is-open'); }
        if (backdrop) { backdrop.classList.remove('is-open'); }
        d.body.classList.remove('nav-open');
    }

    function openNav() {
        if (mobileNav) { mobileNav.classList.add('is-open'); }
        if (backdrop) { backdrop.classList.add('is-open'); }
        d.body.classList.add('nav-open');
    }

    all('[data-nav-open]').forEach(function (btn) { on(btn, 'click', openNav); });
    all('[data-nav-close]').forEach(function (btn) { on(btn, 'click', closeNav); });
    on(backdrop, 'click', closeNav);

    all('.mobile-nav .m-parent').forEach(function (btn) {
        on(btn, 'click', function () { btn.parentNode.classList.toggle('is-open'); });
    });

    /* ---- Search panel ---------------------------------------------------- */
    var searchPanel = d.querySelector('.search-panel');

    all('[data-search-open]').forEach(function (btn) {
        on(btn, 'click', function () {
            if (!searchPanel) { return; }
            searchPanel.classList.add('is-open');
            var input = searchPanel.querySelector('input');
            if (input) { setTimeout(function () { input.focus(); }, 60); }
        });
    });

    on(searchPanel, 'click', function (e) {
        if (e.target === searchPanel) { searchPanel.classList.remove('is-open'); }
    });

    on(d, 'keydown', function (e) {
        if (e.key === 'Escape') {
            if (searchPanel) { searchPanel.classList.remove('is-open'); }
            closeNav();
        }
    });

    /* ---- Accordions ------------------------------------------------------ */
    all('.accordion-trigger').forEach(function (btn) {
        on(btn, 'click', function () {
            var item = btn.closest('.accordion-item');
            var accordion = btn.closest('.accordion');
            var isOpen = item.classList.contains('is-open');
            if (accordion && accordion.hasAttribute('data-single')) {
                all('.accordion-item', accordion).forEach(function (i) { i.classList.remove('is-open'); });
            }
            item.classList.toggle('is-open', !isOpen);
            btn.setAttribute('aria-expanded', String(!isOpen));
        });
    });

    /* ---- Reveal on scroll ------------------------------------------------ */
    var revealables = all('.reveal');
    if (revealables.length && 'IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -60px 0px', threshold: 0.05 });
        revealables.forEach(function (el) { io.observe(el); });
    } else {
        revealables.forEach(function (el) { el.classList.add('is-visible'); });
    }

    /* ---- Gallery lightbox ------------------------------------------------ */
    var lightbox = null;

    function buildLightbox() {
        lightbox = d.createElement('div');
        lightbox.className = 'lightbox';
        lightbox.innerHTML = '<button class="lightbox-close" aria-label="Close">&times;</button><img alt="">';
        d.body.appendChild(lightbox);
        on(lightbox, 'click', function (e) {
            if (e.target !== lightbox.querySelector('img')) { lightbox.classList.remove('is-open'); }
        });
    }

    all('[data-lightbox]').forEach(function (el) {
        on(el, 'click', function (e) {
            e.preventDefault();
            if (!lightbox) { buildLightbox(); }
            lightbox.querySelector('img').src = el.getAttribute('data-lightbox') || el.querySelector('img').src;
            lightbox.classList.add('is-open');
        });
    });
})();
