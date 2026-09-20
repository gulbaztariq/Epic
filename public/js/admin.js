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
