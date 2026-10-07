/* EPIC Core: edit-screen behaviour. Pictures, files, galleries, icon previews and settings controls. */
(function ($) {
    'use strict';

    var frames = {};

    function openMedia(key, options, onSelect) {
        if (!window.wp || !wp.media) { return; }
        var frame = frames[key];
        if (!frame) {
            frame = frames[key] = wp.media(options);
            frame.on('select', function () { onSelect(frame.state().get('selection')); });
        }
        frame.open();
    }

    /* ---- Picture: choose / remove, and the live fit & crop preview ---------- */
    $('[data-epic-image]').each(function (index) {
        var box = $(this);
        var value = box.find('[data-epic-image-value]');
        var choose = box.find('[data-epic-image-choose]');
        var remove = box.find('[data-epic-image-remove]');
        var panel = box.find('[data-epic-adjust]');
        var img = box.find('[data-epic-preview]');
        var autoFit = panel.data('auto-fit') || 'contain';
        var frameEl = box.find('[data-epic-frame]');
        var dot = box.find('[data-epic-dot]');
        var fit = box.find('[data-epic-fit]');
        var x = box.find('[data-epic-x]');
        var y = box.find('[data-epic-y]');
        var zoom = box.find('[data-epic-zoom]');

        function apply() {
            if (!panel.length) { return; }
            var origin = x.val() + '% ' + y.val() + '%';
            img.css({
                objectFit: fit.val() === 'auto' ? autoFit : fit.val(),
                objectPosition: origin,
                transformOrigin: origin,
                transform: 'scale(' + (zoom.val() / 100) + ')'
            });
            dot.css({ left: x.val() + '%', top: y.val() + '%' });
            box.find('[data-epic-out="x"]').text(x.val() + '%');
            box.find('[data-epic-out="y"]').text(y.val() + '%');
            box.find('[data-epic-out="zoom"]').text(zoom.val() + '%');
        }

        choose.on('click', function () {
            openMedia('image-' + index, { title: 'Choose a picture', library: { type: 'image' }, multiple: false, button: { text: 'Use this picture' } }, function (selection) {
                var item = selection.first().toJSON();
                value.val(item.id);
                img.attr('src', item.url).prop('hidden', false);
                panel.prop('hidden', false);
                remove.prop('hidden', false);
                choose.text('Change picture');
                apply();
            });
        });

        remove.on('click', function () {
            value.val('');
            img.attr('src', '').prop('hidden', true);
            panel.prop('hidden', true);
            remove.prop('hidden', true);
            choose.text('Choose picture');
        });

        panel.find('select, input').on('input change', apply);
        frameEl.on('click', function (e) {
            var rect = this.getBoundingClientRect();
            function pct(v) { return Math.max(0, Math.min(100, Math.round(v))); }
            x.val(pct((e.clientX - rect.left) / rect.width * 100));
            y.val(pct((e.clientY - rect.top) / rect.height * 100));
            apply();
        });
        box.find('[data-epic-reset]').on('click', function () {
            fit.val('auto'); x.val(50); y.val(50); zoom.val(100); apply();
        });
        apply();
    });

    /* ---- File: choose / remove --------------------------------------------- */
    $('[data-epic-file]').each(function (index) {
        var box = $(this);
        var value = box.find('[data-epic-file-value]');
        var current = box.find('[data-epic-file-current]');
        var remove = box.find('[data-epic-file-remove]');

        box.find('[data-epic-file-choose]').on('click', function () {
            openMedia('file-' + index, { title: 'Choose a file', multiple: false, button: { text: 'Use this file' } }, function (selection) {
                var item = selection.first().toJSON();
                value.val(item.id);
                box.find('input[name$="__url]"]').val('');
                current.empty().append($('<a>', { href: item.url, target: '_blank', rel: 'noopener', text: item.filename }));
                remove.prop('hidden', false);
            });
        });

        remove.on('click', function () {
            value.val('');
            box.find('input[name$="__url]"]').val('');
            current.html('<em>No file chosen</em>');
            remove.prop('hidden', true);
        });
    });

    /* ---- Gallery: add several, reorder, remove ------------------------------ */
    $('[data-epic-gallery]').each(function (index) {
        var gallery = $(this);
        var list = gallery.find('[data-epic-gallery-list]');
        var template = $('#epic-gallery-row-template').html();
        var base = gallery.data('name');

        function reindex() {
            list.children('[data-epic-gallery-item]').each(function (i) {
                $(this).find('[data-field]').each(function () {
                    $(this).attr('name', base + '[' + i + '][' + $(this).data('field') + ']');
                });
            });
        }

        list.sortable({ items: '> li', placeholder: 'epic-gallery-placeholder', update: reindex });

        gallery.find('[data-epic-gallery-add]').on('click', function () {
            openMedia('gallery-' + index, { title: 'Choose photos', library: { type: 'image' }, multiple: 'add', button: { text: 'Add to album' } }, function (selection) {
                selection.each(function (attachment) {
                    var item = attachment.toJSON();
                    var row = $(template.replace(/__INDEX__/g, String(list.children().length)));
                    row.find('[data-field="id"]').val(item.id);
                    row.find('[data-epic-gallery-thumb]').attr('src', (item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url));
                    list.append(row);
                });
                reindex();
            });
        });

        gallery.on('click', '[data-epic-gallery-remove]', function () {
            $(this).closest('[data-epic-gallery-item]').remove();
            reindex();
        });

        reindex();
        $('form#post').on('submit', reindex);
    });

    /* ---- Icon picker preview ------------------------------------------------ */
    $('[data-epic-icon]').each(function () {
        var select = $(this);
        var preview = select.siblings('.epic-icon-preview');

        function show() {
            preview.html((window.epicIcons && window.epicIcons[select.val()]) || '');
        }

        select.on('change', show);
        show();
    });

    /* ---- Settings screen: colour and range read-outs ------------------------ */
    $('[data-epic-color]').each(function () {
        var input = $(this);
        var readout = input.siblings('[data-epic-color-readout]');
        input.on('input', function () { readout.text(input.val()); });
        input.siblings('[data-epic-color-reset]').on('click', function () {
            input.val($(this).data('epic-color-reset')).trigger('input');
        });
    });

    $('[data-epic-range]').each(function () {
        var input = $(this);
        var readout = input.siblings('[data-epic-range-readout]');
        input.on('input', function () { readout.text(input.val() + (readout.data('unit') || '')); });
    });

    /* ---- Settings screen: media pickers for logo & favicon ------------------ */
    $('[data-epic-setting-image]').each(function (index) {
        var box = $(this);
        var value = box.find('input[type=hidden]');
        var img = box.find('img');
        var remove = box.find('[data-remove]');

        box.find('[data-choose]').on('click', function () {
            openMedia('setting-' + index, { title: 'Choose a picture', library: { type: 'image' }, multiple: false }, function (selection) {
                var item = selection.first().toJSON();
                value.val(item.id);
                img.attr('src', item.url).prop('hidden', false);
                remove.prop('hidden', false);
            });
        });

        remove.on('click', function () {
            value.val('');
            img.attr('src', '').prop('hidden', true);
            remove.prop('hidden', true);
        });
    });
})(jQuery);
