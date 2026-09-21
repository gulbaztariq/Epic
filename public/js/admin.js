/* EPIC admin dashboard behaviour. */
(function () {
    'use strict';

    var d = document;
    function all(sel, root) { return Array.prototype.slice.call((root || d).querySelectorAll(sel)); }
    function on(el, ev, fn) { if (el) { el.addEventListener(ev, fn); } }

    /* Sidebar (mobile) */
    all('[data-sidebar-toggle]').forEach(function (btn) {
        on(btn, 'click', function () { d.body.classList.toggle('sidebar-open'); });
    });
    on(d, 'click', function (e) {
        if (d.body.classList.contains('sidebar-open')
            && !e.target.closest('.sidebar')
            && !e.target.closest('[data-sidebar-toggle]')) {
            d.body.classList.remove('sidebar-open');
        }
    });

    /* Delete confirmation */
    all('form[data-confirm]').forEach(function (form) {
        on(form, 'submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
        });
    });

    /* Icon picker preview */
    all('[data-icon-select]').forEach(function (select) {
        var preview = d.querySelector('[data-icon-preview="' + select.getAttribute('data-icon-select') + '"]');
        if (!preview) { return; }
        on(select, 'change', function () {
            var tpl = d.querySelector('[data-icon-svg="' + select.value + '"]');
            preview.innerHTML = tpl ? tpl.innerHTML : '';
        });
    });

    /* Image preview before upload */
    all('input[type=file][data-preview]').forEach(function (input) {
        var target = d.querySelector('[data-preview-for="' + input.getAttribute('data-preview') + '"]');
        on(input, 'change', function () {
            if (!target || !input.files || !input.files[0]) { return; }
            var file = input.files[0];
            if (!/^image\//.test(file.type)) { return; }
            var reader = new FileReader();
            reader.onload = function (e) { target.src = e.target.result; target.style.display = 'block'; };
            reader.readAsDataURL(file);
        });
    });

    /* Auto slug from title (only while the slug field is untouched) */
    var titleInput = d.querySelector('[data-slug-source]');
    var slugInput = d.querySelector('[data-slug-target]');
    if (titleInput && slugInput && slugInput.value === '') {
        var touched = false;
        on(slugInput, 'input', function () { touched = true; });
        on(titleInput, 'input', function () {
            if (touched) { return; }
            slugInput.value = titleInput.value
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        });
    }

    /* Rich text editors (Quill when available, plain textarea otherwise) */
    function initEditors() {
        if (!window.Quill) { return; }

        all('textarea[data-richtext]').forEach(function (textarea) {
            var holder = d.createElement('div');
            holder.className = 'quill-holder';
            textarea.parentNode.insertBefore(holder, textarea.nextSibling);
            textarea.style.display = 'none';

            var quill = new window.Quill(holder, {
                theme: 'snow',
                placeholder: textarea.getAttribute('placeholder') || 'Write here…',
                modules: {
                    toolbar: [
                        [{ header: [2, 3, 4, false] }],
                        ['bold', 'italic', 'underline'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['blockquote', 'link'],
                        ['clean']
                    ]
                }
            });

            quill.root.innerHTML = textarea.value;

            var form = textarea.closest('form');
            if (form) {
                on(form, 'submit', function () {
                    var html = quill.root.innerHTML;
                    textarea.value = (html === '<p><br></p>') ? '' : html;
                });
            }
        });
    }

    if (d.readyState === 'loading') {
        on(d, 'DOMContentLoaded', initEditors);
    } else {
        initEditors();
    }

    /* Copy-to-clipboard for media paths */
    all('[data-copy]').forEach(function (btn) {
        on(btn, 'click', function () {
            var text = btn.getAttribute('data-copy');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function () {
                    var old = btn.textContent;
                    btn.textContent = 'Copied';
                    setTimeout(function () { btn.textContent = old; }, 1400);
                });
            }
        });
    });
})();

/* Analytics chart hover: crosshair + tooltip on the time series. */
(function () {
    'use strict';

    var d = document;
    function all(sel, root) { return Array.prototype.slice.call((root || d).querySelectorAll(sel)); }
    function esc(text) {
        return String(text).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    all('[data-chart]').forEach(function (wrap) {
        var id = wrap.getAttribute('data-chart');
        var payload = d.querySelector('[data-chart-points="' + id + '"]');
        var tooltip = wrap.querySelector('.chart-tooltip');
        var crosshair = wrap.querySelector('.chart-crosshair');
        var points;

        if (!payload || !tooltip) { return; }

        try {
            points = JSON.parse(payload.textContent);
        } catch (e) {
            return;
        }

        function hide() {
            tooltip.hidden = true;
            if (crosshair) { crosshair.style.opacity = 0; }
        }

        all('.chart-hit', wrap).forEach(function (hit) {
            function show() {
                var row = points[parseInt(hit.getAttribute('data-index'), 10)];
                if (!row) { return; }

                var html = '<strong>' + esc(row.label) + '</strong>';
                row.values.forEach(function (value) {
                    html += '<div class="tip-row"><i style="background:' + esc(value.color) + '"></i>'
                        + '<span>' + esc(value.label) + '</span>'
                        + '<b>' + Number(value.value).toLocaleString() + '</b></div>';
                });

                tooltip.innerHTML = html;
                tooltip.hidden = false;

                var wrapBox = wrap.getBoundingClientRect();
                var hitBox = hit.getBoundingClientRect();
                var centre = hitBox.left - wrapBox.left + hitBox.width / 2;
                var half = tooltip.offsetWidth / 2;

                tooltip.style.left = Math.min(Math.max(centre, half + 4), wrapBox.width - half - 4) + 'px';
                tooltip.style.top = Math.max(tooltip.offsetHeight + 8, hitBox.height * 0.45) + 'px';

                if (crosshair) {
                    var x = hit.getAttribute('data-centre');
                    crosshair.setAttribute('x1', x);
                    crosshair.setAttribute('x2', x);
                    crosshair.style.opacity = 1;
                }
            }

            hit.addEventListener('mouseenter', show);
            hit.addEventListener('mousemove', show);
            hit.addEventListener('touchstart', show, { passive: true });
            hit.addEventListener('focus', show);
        });

        wrap.addEventListener('mouseleave', hide);
        wrap.addEventListener('touchend', hide);
    });
})();
