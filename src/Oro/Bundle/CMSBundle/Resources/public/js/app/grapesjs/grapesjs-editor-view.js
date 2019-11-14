import $ from 'jquery';
import _ from 'underscore';
import GrapesJS from 'grapesjs';

import BaseView from 'oroui/js/app/views/base/view';
import ModuleManager from 'orocms/js/app/grapesjs/modules/module-manager';
import mediator from 'oroui/js/mediator';
import canvasStyle from 'text-loader!orocms/css/grapesjs/grapesjs-canvas.css';

import 'grapesjs-preset-webpage';
import 'orocms/js/app/grapesjs/plugins/components/grapesjs-components';
import {escapeWrapper} from 'orocms/js/app/grapesjs/plugins/grapesjs-style-isolation';

/**
 * Create GrapesJS content builder
 * @type {*|void}
 */
const GrapesjsEditorView = BaseView.extend({
    /**
     * @inheritDoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'autoRender', 'allow_tags', 'builderOptions', 'builderPlugins', 'currentTheme', 'canvasConfig',
        'contextClass', 'storageManager', 'stylesInputSelector', 'storagePrefix', 'themes',
        'propertiesInputSelector'
    ]),

    /**
     * @inheritDoc
     */
    autoRender: true,

    /**
     * @property {GrapesJS.Instance}
     */
    builder: null,

    /**
     * Allow html tags
     * @property {Object}
     */
    allow_tags: null,

    /**
     * Page context class
     * @property {String}
     */
    contextClass: 'body cms-page',

    /**
     * Main builder options
     * @property {Object}
     */
    builderOptions: {
        fromElement: true,
        height: '2000px',
        avoidInlineStyle: true,

        /**
         * Color picker options
         * @property {Object}
         */
        colorPicker: {
            appendTo: 'body',
            showPalette: false
        }
    },

    /**
     * Storage prefix
     * @property {String}
     */
    storagePrefix: 'gjs-',

    /**
     * Storage options
     * @property {Object}
     */
    storageManager: {
        autosave: false,
        autoload: false
    },

    /**
     * Canvas options
     * @property {Object}
     */
    canvasConfig: {
        canvasCss: canvasStyle
    },

    /**
     * Style manager options
     * @property {Object}
     */
    styleManager: {
        clearProperties: 1
    },

    /**
     * Asset manager settings
     * @property {Object}
     */
    assetManagerConfig: {
        embedAsBase64: 1
    },

    /**
     * Themes list
     * @property {Array}
     */
    themes: [],

    /**
     * Styles input selector
     * @property {String}
     */
    stylesInputSelector: '[data-grapesjs-styles]',

    /**
     * Styles input element
     * @property {Object}
     */
    $stylesInputElement: null,

    /**
     * Properties input selector
     * @property {String}
     */
    propertiesInputSelector: '[data-grapesjs-properties]',

    /**
     * Properties input element
     * @property {Object}
     */
    $propertiesInputElement: null,

    /**
     * @property {String}
     */
    wrapperSelector: '.page-content-editor, .fallback-item-value, .variant-collection',

    /**
     * @property {Array}
     */
    JSONcomponents: [],

    /**
     * @property {jQuery.Element}
     */
    $parent: null,

    /**
     * List of grapesjs plugins
     * @property {Object}
     */
    builderPlugins: {
        'gjs-preset-webpage': {
            aviaryOpts: false,
            filestackOpts: null,
            blocksBasicOpts: {
                flexGrid: 1
            },
            navbarOpts: false,
            countdownOpts: false,
            modalImportContent: function(editor) {
                return editor.getHtml() + '<style>' + editor.getCss() + '</style>';
            }
        },
        'grapesjs-components': {},
        'grapesjs-style-isolation': {}
    },

    events: {
        'wysiwyg:enable': 'enableEditor',
        'wysiwyg:disable': 'disableEditor'
    },

    /**
     * @inheritDoc
     */
    constructor: function GrapesjsEditorView(options) {
        GrapesjsEditorView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     * @param options
     */
    initialize: function(options) {
        this.setCurrentContentAlias();
        this.$parent = this.$el.closest(this.wrapperSelector);
        this.$stylesInputElement = this.$parent.find(this.stylesInputSelector);
        this.$propertiesInputElement = this.$parent.find(this.propertiesInputSelector);

        if (this.allow_tags) {
            this.builderPlugins['grapesjs-components'] = _.extend({},
                this.builderPlugins['grapesjs-components'],
                {
                    allowTags: this.allow_tags
                }
            );
        }

        GrapesjsEditorView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritDoc
     */
    render: function() {
        this.applyComponentsJSON();
        this.initBuilder();
    },

    /**
     * @inheritDoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.disableEditor();

        GrapesjsEditorView.__super__.dispose.call(this);
    },

    /**
     * Set disable editor
     */
    disableEditor: function() {
        if (this.builder) {
            this.builderUndelegateEvents();
            this.builder.destroy();

            this.disposeElements();

            this.builder = null;
        }
    },

    /**
     * Set enable editor
     */
    enableEditor: function() {
        if (!this.builder) {
            this.render();
        }
    },

    disposeElements: function() {
        this.$el.show();
        this.$container.remove();
    },

    /**
     * Resolve editor container
     * @returns {*}
     */
    getContainer: function() {
        const $editor = $('<div class="grapesjs" />');
        $editor.html(escapeWrapper(this.$el.val()));
        this.$el.parent().append($editor);
        this.$container = $editor;

        return $editor.get(0);
    },

    /**
     * Get properties json
     * @returns {Array}
     */
    applyComponentsJSON: function() {
        const value = this.$propertiesInputElement.val();

        if (value) {
            this.JSONcomponents = JSON.parse(value);
        }

        return this.JSONcomponents;
    },

    /**
     * Initialize builder instance
     */
    initBuilder: function() {
        this.builder = GrapesJS.init(_.extend(
            {}
            , {
                avoidInlineStyle: 1,
                container: this.getContainer()
            }
            , this._prepareBuilderOptions()));

        // Ensures all changes to sectors, properties and types are applied.
        this.builder.StyleManager.getSectors().reset(ModuleManager.getModule('style-manager'));

        this.builder.setIsolatedStyle(
            this.$stylesInputElement.val()
        );

        mediator.trigger('grapesjs:created', this.builder);

        if (_.isEmpty(this.JSONcomponents)) {
            this.builder.addComponents(this.JSONcomponents);
        }

        this.builderDelegateEvents();
    },

    /**
     * Add builder event listeners
     */
    builderDelegateEvents: function() {
        this.$el.closest('form').on(
            'keyup' + this.eventNamespace() + ' keypress' + this.eventNamespace()
            , _.bind(function(e) {
                const keyCode = e.keyCode || e.which;
                if (keyCode === 13 && this.$container.get(0).contains(e.target)) {
                    e.preventDefault();
                    return false;
                }
            }, this));

        this.builder.on('load', _.bind(this._onLoadBuilder, this));
        this.builder.on('update', _.bind(this._onUpdatedBuilder, this));
        this.builder.on('component:update', _.debounce(_.bind(this._onComponentUpdatedBuilder, this), 100));
        this.builder.on('changeTheme', _.bind(this._updateTheme, this));

        // Fix reload form when click export to zip dialog
        this.builder.on('run:export-template', _.bind(function() {
            $(this.builder.Modal.getContentEl())
                .find('.gjs-btn-prim').bind('click', _.bind(function(e) {
                    e.preventDefault();
                }, this));
        }, this));
    },

    /**
     * Remove builder event listeners
     */
    builderUndelegateEvents: function() {
        this.$el.closest('form').off(this.eventNamespace());
        mediator.off('dropdown-button:click');

        if (this.builder) {
            this.builder.off();
        }
    },

    /**
     * Get current theme
     * @returns {Object}
     */
    getCurrentTheme: function() {
        return _.find(this.themes, function(theme) {
            return theme.active;
        });
    },

    /**
     * Set active state for button
     * @param panel {String}
     * @param name {String}
     */
    setActiveButton: function(panel, name) {
        this.builder.Commands.run(name);
        const button = this.builder.Panels.getButton(panel, name);

        button.set('active', true);
    },

    setCurrentContentAlias: function() {
        this.form = this.$el.closest('form');
        const contentBlockAliasField = this.form.find('[name="oro_cms_content_block[alias]"]');
        if (contentBlockAliasField.length && contentBlockAliasField.val()) {
            this.builderOptions.contentBlockAlias = contentBlockAliasField.val();
        }
    },

    /**
     * Get editor content
     * @returns {String}
     */
    getEditorContent: function() {
        return this.builder.getIsolatedHtml();
    },

    /**
     * Get editor styles
     * @returns {String}
     */
    getEditorStyles: function() {
        return this.builder.getIsolatedCss();
    },

    /**
     * Get editor components
     * @returns {Object}
     */
    getEditorComponents: function() {
        return JSON.stringify(this.builder.getComponents());
    },

    /**
     * Add wrapper classes for iframe with content
     */
    _addClassForFrameWrapper: function() {
        $(this.builder.Canvas.getFrameEl().contentDocument).find('#wrapper').addClass(this.contextClass);
    },

    /**
     * Onload builder handler
     * @private
     */
    _onLoadBuilder: function() {
        ModuleManager.call('panel-manager', {
            builder: this.builder,
            themes: this.themes
        });

        ModuleManager.call('devices', {
            builder: this.builder
        });

        this.setActiveButton('options', 'sw-visibility');
        this._addClassForFrameWrapper();

        mediator.trigger('grapesjs:loaded', this.builder);
        mediator.trigger('page:afterChange');
    },

    /**
     * Update builder handler
     * @private
     */
    _onUpdatedBuilder: function() {
        this._getCSSBreakpoint();
        mediator.trigger('grapesjs:updated', this.builder);
    },

    /**
     * Update components builder handler
     * @param state
     * @private
     */
    _onComponentUpdatedBuilder: function(state) {
        // TODO: Should be removed after fix behat methods
        this.componentUpdated = false;
        if (!this.componentUpdated) {
            mediator.on('dropdown-button:click', this._onComponentUpdatedBuilder, this);
        }
        this._updateInitialField();
        mediator.trigger('grapesjs:components:updated', state);
        this.componentUpdated = true;
    },

    /**
     * Update theme view in grapes iframe
     * @param selected {String}
     * @private
     */
    _updateTheme: function(selected) {
        if (!_.isUndefined(this.activeTheme) && this.activeTheme.name === selected) {
            return false;
        }

        _.each(this.themes, function(theme) {
            theme.active = theme.name === selected;
        });

        this.activeTheme = _.find(this.themes, function(theme) {
            return theme.active;
        });

        const style = this.builder.Canvas.getFrameEl().contentDocument.head.querySelector('link');

        style.href = this.activeTheme.stylesheet;
    },

    /**
     * Update source textarea and styles
     * @private
     */
    _updateInitialField: function() {
        this.$el.val(this.getEditorContent()).trigger('change');
        this.$stylesInputElement.val(this.getEditorStyles()).trigger('change');
        this.$propertiesInputElement.val(this.getEditorComponents()).trigger('change');
    },

    /**
     * Collect and compare builder options
     * @returns {GrapesjsEditorView.builderOptions|{fromElement}}
     * @private
     */
    _prepareBuilderOptions: function() {
        _.extend(this.builderOptions
            , this._getPlugins()
            , this._getStorageManagerConfig()
            , this._getCanvasConfig()
            , this._getStyleManagerConfig()
            , this._getAssetConfig()
        );

        return this.builderOptions;
    },

    /**
     * Get extended Storage Manager config
     * @returns {{storageManager: (*|void)}}
     * @private
     */
    _getStorageManagerConfig: function() {
        return {
            storageManager: _.extend({}, this.storageManager, {
                id: this.storagePrefix
            })
        };
    },

    /**
     * Get extended Style Manager config
     * @returns {{styleManager: *}}
     * @private
     */
    _getStyleManagerConfig: function() {
        return {
            styleManager: this.styleManager
        };
    },

    /**
     * Get extended Canvas config
     * @returns {{canvasCss: string, canvas: {styles: (*|string)[]}}}
     * @private
     */
    _getCanvasConfig: function() {
        const theme = this.getCurrentTheme();
        return _.extend({}, this.canvasConfig, {
            canvas: {
                styles: [theme.stylesheet]
            },
            protectedCss: []
        });
    },

    /**
     * Get asset manager configuration
     * @returns {*|void}
     * @private
     */
    _getAssetConfig: function() {
        return {
            assetManager: this.assetManagerConfig
        };
    },

    /**
     * Get plugins list with options
     * @returns {{plugins: *, pluginsOpts: (GrapesjsEditorView.builderPlugins|{"gjs-preset-webpage"})}}
     * @private
     */
    _getPlugins: function() {
        return {
            plugins: _.keys(this.builderPlugins),
            pluginsOpts: this.builderPlugins
        };
    }
});

export default GrapesjsEditorView;
