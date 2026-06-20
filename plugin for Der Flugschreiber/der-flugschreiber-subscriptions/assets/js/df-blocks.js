(function (blocks, element, components, blockEditor, i18n) {
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var RangeControl = components.RangeControl;
    var ToggleControl = components.ToggleControl;
    var __ = i18n.__;

    function inspector(controls) {
        return el(InspectorControls, {}, el(PanelBody, { title: __('Settings', 'der-flugschreiber-subscriptions') }, controls));
    }

    blocks.registerBlockType('df-subscriptions/all-issues', {
        apiVersion: 2,
        title: __('DF All Issues', 'der-flugschreiber-subscriptions'),
        icon: 'book-alt',
        category: 'widgets',
        edit: function (props) {
            return el('div', { className: 'df-block-placeholder' },
                inspector([
                    el(TextControl, { key: 'title', label: __('Title', 'der-flugschreiber-subscriptions'), value: props.attributes.title, onChange: function (value) { props.setAttributes({ title: value }); } }),
                    el(RangeControl, { key: 'initial', label: __('Initial items', 'der-flugschreiber-subscriptions'), min: 1, max: 50, value: props.attributes.initial, onChange: function (value) { props.setAttributes({ initial: value }); } }),
                    el(RangeControl, { key: 'step', label: __('Load more step', 'der-flugschreiber-subscriptions'), min: 1, max: 50, value: props.attributes.step, onChange: function (value) { props.setAttributes({ step: value }); } })
                ]),
                el('strong', {}, props.attributes.title || __('All Issues', 'der-flugschreiber-subscriptions'))
            );
        },
        save: function () { return null; }
    });

    blocks.registerBlockType('df-subscriptions/all-articles', {
        apiVersion: 2,
        title: __('DF All Articles', 'der-flugschreiber-subscriptions'),
        icon: 'media-document',
        category: 'widgets',
        edit: function (props) {
            return el('div', { className: 'df-block-placeholder' },
                inspector([
                    el(TextControl, { key: 'title', label: __('Title', 'der-flugschreiber-subscriptions'), value: props.attributes.title, onChange: function (value) { props.setAttributes({ title: value }); } }),
                    el(RangeControl, { key: 'initial', label: __('Initial items', 'der-flugschreiber-subscriptions'), min: 1, max: 50, value: props.attributes.initial, onChange: function (value) { props.setAttributes({ initial: value }); } }),
                    el(RangeControl, { key: 'step', label: __('Load more step', 'der-flugschreiber-subscriptions'), min: 1, max: 50, value: props.attributes.step, onChange: function (value) { props.setAttributes({ step: value }); } }),
                    el(TextControl, { key: 'magazine', type: 'number', label: __('Magazine ID', 'der-flugschreiber-subscriptions'), value: props.attributes.magazine, onChange: function (value) { props.setAttributes({ magazine: parseInt(value, 10) || 0 }); } })
                ]),
                el('strong', {}, props.attributes.title || __('All Articles', 'der-flugschreiber-subscriptions'))
            );
        },
        save: function () { return null; }
    });

    blocks.registerBlockType('df-subscriptions/account', {
        apiVersion: 2,
        title: __('DF Subscriber Account', 'der-flugschreiber-subscriptions'),
        icon: 'admin-users',
        category: 'widgets',
        edit: function () {
            return el('div', { className: 'df-block-placeholder' }, el('strong', {}, __('Subscriber account', 'der-flugschreiber-subscriptions')));
        },
        save: function () { return null; }
    });

    blocks.registerBlockType('df-subscriptions/article', {
        apiVersion: 2,
        title: __('DF Article Page', 'der-flugschreiber-subscriptions'),
        icon: 'welcome-write-blog',
        category: 'widgets',
        edit: function (props) {
            return el('div', { className: 'df-block-placeholder' },
                inspector([
                    el(TextControl, { key: 'article', type: 'number', label: __('Article ID', 'der-flugschreiber-subscriptions'), value: props.attributes.article, onChange: function (value) { props.setAttributes({ article: parseInt(value, 10) || 0 }); } }),
                    el(ToggleControl, { key: 'showBack', label: __('Show back link', 'der-flugschreiber-subscriptions'), checked: props.attributes.showBack, onChange: function (value) { props.setAttributes({ showBack: value }); } })
                ]),
                el('strong', {}, __('Magazine article page', 'der-flugschreiber-subscriptions'))
            );
        },
        save: function () { return null; }
    });
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n);
