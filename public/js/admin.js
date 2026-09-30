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

    /* Picture fit & crop: a live preview of the fit, focal point and zoom being chosen */
    all('[data-pic-adjust]').forEach(function (panel) {
        var name = panel.getAttribute('data-pic-adjust');
        var autoFit = panel.getAttribute('data-auto-fit') || 'contain';
        var img = panel.querySelector('[data-pic-preview]');
        var frame = panel.querySelector('[data-pic-frame]');
        var dot = panel.querySelector('[data-pic-dot]');
        var fit = panel.querySelector('[data-pic-fit]');
        var x = panel.querySelector('[data-pic-x]');
        var y = panel.querySelector('[data-pic-y]');
        var zoom = panel.querySelector('[data-pic-zoom]');
        var reset = panel.querySelector('[data-pic-reset]');
        var file = d.querySelector('input[type=file][name="' + name + '"]');

        function out(key, value) {
            var el = panel.querySelector('[data-pic-out="' + key + '"]');
            if (el) { el.textContent = value + '%'; }
        }
        function apply() {
            var origin = x.value + '% ' + y.value + '%';
            img.style.objectFit = fit.value === 'auto' ? autoFit : fit.value;
            img.style.objectPosition = origin;
            img.style.transformOrigin = origin;
            img.style.transform = 'scale(' + (zoom.value / 100) + ')';
            dot.style.left = x.value + '%';
            dot.style.top = y.value + '%';
            out('x', x.value); out('y', y.value); out('zoom', zoom.value);
        }

        [fit, x, y, zoom].forEach(function (control) {
            on(control, 'input', apply);
            on(control, 'change', apply);
        });
        on(frame, 'click', function (e) {
            var box = frame.getBoundingClientRect();
            function pct(value) { return Math.max(0, Math.min(100, Math.round(value))); }
            x.value = pct((e.clientX - box.left) / box.width * 100);
            y.value = pct((e.clientY - box.top) / box.height * 100);
            apply();
        });
        on(reset, 'click', function () {
            fit.value = 'auto'; x.value = 50; y.value = 50; zoom.value = 100;
            apply();
        });
        // A newly chosen file becomes the preview, and reveals the controls.
        on(file, 'change', function () {
            if (!file.files || !file.files[0] || !/^image\//.test(file.files[0].type)) { return; }
            var reader = new FileReader();
            reader.onload = function (e) { img.src = e.target.result; panel.hidden = false; };
            reader.readAsDataURL(file.files[0]);
        });
        apply();
    });

    /* Colour and range settings: keep the read-out in step with the control */
    all('[data-color-input]').forEach(function (input) {
        var box = input.parentNode;
        var readout = box.querySelector('[data-color-readout]');
        var reset = box.querySelector('[data-color-reset]');
        function show() { if (readout) { readout.textContent = input.value; } }
        on(input, 'input', show);
        on(reset, 'click', function () { input.value = reset.getAttribute('data-color-reset'); show(); });
    });
    all('[data-range-input]').forEach(function (input) {
        var readout = input.parentNode.querySelector('[data-range-readout]');
        on(input, 'input', function () {
            if (readout) { readout.textContent = input.value + (readout.getAttribute('data-unit') || ''); }
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
