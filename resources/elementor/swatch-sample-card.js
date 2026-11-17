/**
 * Gutenberg's sample card under a swatch tab's switch, drawn from the tab's settings (keys named by the
 * placeholder's data attributes) and redrawn after every editor command, so it follows the controls.
 */
(function () {
    'use strict';

    var SAMPLE = '.fct-swatch-sample';
    var queued = false;
    // The value picked on each sample row, by sample and row, so a redraw keeps it.
    var picked = {};

    function strings() {
        var data = window.fluentCartElementor && window.fluentCartElementor.i18n;

        return (data && data.swatchSample) || {};
    }

    function format(text, value) {
        return String(text || '%s').replace('%s', value);
    }

    // The same sample products as Gutenberg's preview, with the same sold-out values (Rust, XL, 48 pods).
    function sampleRows(type) {
        var s = strings();
        var colors = s.colors || ['Navy', 'Mustard', 'Sage', 'Rust', 'Sand', 'Plum'];
        var hexes = ['#2f3e63', '#c9a23a', '#6f9a7a', '#b45a3c', '#d9c7a7', '#6b3f5e'];

        if (type === 'attribute') {
            return [
                {name: s.color || 'Color', options: colors.map(function (value, index) {
                    return {value: value, hex: hexes[index], soldOut: index === 3};
                })},
                {name: s.size || 'Size', options: ['S', 'M', 'L', 'XL'].map(function (value) {
                    return {value: value, soldOut: value === 'XL'};
                })},
            ];
        }

        return [{name: '', options: ['6', '12', '24', '48'].map(function (count) {
            return {value: format(s.podsCount, count), soldOut: count === '48'};
        }).concat({value: s.sampler || 'Sampler'})}];
    }

    // The deepest panel view holding the sample is its own control view; its container has the settings.
    function settingsFor(node) {
        var panel = window.elementor && window.elementor.getPanelView && window.elementor.getPanelView();
        var view = panel && panel.getCurrentPageView && panel.getCurrentPageView();
        var found = null;

        function walk(current) {
            if (!current || !current.el || !current.el.contains(node)) {
                return;
            }
            if (current.container && current.container.settings) {
                found = current.container.settings;
            }
            if (current.children && typeof current.children.each === 'function') {
                current.children.each(walk);
            }
        }

        walk(view);

        return found;
    }

    // Classic's slider saves {size}, a layout's number saves the number; the renderer keeps it within 1–12.
    // A cleared count falls back to the control's default (3 attribute, 4 list in Classic, the layout's own
    // otherwise); Classic reads a typed 0 as cleared too, a layout reads it as one option.
    function readCount(settings, key, all, isClassic) {
        if (all === 'yes') {
            return 0;
        }

        var value = settings.get(key);
        var size = value && typeof value === 'object' ? value.size : value;
        var control = settings.controls && settings.controls[key];
        var fallback = control && control.default;

        fallback = parseInt(fallback && typeof fallback === 'object' ? fallback.size : fallback, 10) || 1;

        if (size === '' || size === null || size === undefined || (isClassic && !parseInt(size, 10))) {
            size = fallback;
        }

        return Math.max(1, Math.min(12, parseInt(size, 10) || 1));
    }

    // The tab's settings, as the block's own preview props.
    function readSettings(settings, type, prefix, suffix) {
        var get = function (name) {
            return settings.get(prefix + name + suffix);
        };
        var soldOut = get('sold_out_display') || 'normal';

        if (type === 'attribute') {
            return {
                enabled: get('advanced_enabled') !== '',
                maxVisible: readCount(settings, prefix + 'max_visible' + suffix, get('max_visible_all'), suffix === ''),
                showName: get('show_group_name') === 'yes',
                rowName: '',
                showSelected: get('show_label') === 'yes',
                selectedBelow: get('selected_position') === 'below',
                soldOut: soldOut,
            };
        }

        var ownSelected = get('simple_show_selected');

        return {
            enabled: get('simple_enabled') !== '',
            maxVisible: readCount(settings, prefix + 'simple_max_visible' + suffix, get('simple_max_visible_all'), suffix === ''),
            showName: get('simple_show_label') === 'yes',
            rowName: get('simple_label') || '',
            // Left on Same as Attribute Products, it follows the attribute tab, as the card does.
            showSelected: ownSelected === 'yes' || (ownSelected !== 'no' && get('show_label') === 'yes'),
            selectedBelow: get('simple_selected_position') === 'below',
            soldOut: soldOut,
        };
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);

        if (className) {
            node.className = className;
        }
        if (text !== undefined) {
            node.textContent = text;
        }

        return node;
    }

    function svg(markup) {
        var wrap = document.createElement('div');
        wrap.innerHTML = markup;

        return wrap.firstChild;
    }

    function teeIcon(fill) {
        return svg('<svg width="44" height="44" viewBox="0 0 120 120" aria-hidden="true"><path d="M40 18 L28 22 L8 42 L20 56 L30 48 L30 104 L90 104 L90 48 L100 56 L112 42 L92 22 L80 18 Q60 32 40 18 Z" fill="' + fill + '"/></svg>');
    }

    function podsIcon() {
        return svg('<svg width="44" height="44" viewBox="0 0 120 120" aria-hidden="true"><rect x="22" y="34" width="76" height="60" rx="8" fill="#8a5a3b"/><rect x="22" y="34" width="76" height="12" rx="6" fill="#5b3a25"/><circle cx="44" cy="70" r="9" fill="#f5efe6"/><circle cx="76" cy="70" r="9" fill="#f5efe6"/></svg>');
    }

    function draw(node) {
        var settings = settingsFor(node);

        if (!settings) {
            return;
        }
        // A typed value updates the settings before any command runs, so the card listens to them too.
        if (!settings.fctSwatchSample && typeof settings.on === 'function') {
            settings.fctSwatchSample = true;
            settings.on('change', schedule);
        }

        var type = node.getAttribute('data-type') === 'list' ? 'list' : 'attribute';
        var prefix = node.getAttribute('data-prefix') || '';
        var suffix = node.getAttribute('data-suffix') || '';
        // Per widget (its settings model) and tab, so two widgets keep their own picks.
        var key = (settings.cid || '') + '|' + type + '|' + prefix + '|' + suffix;
        var props = readSettings(settings, type, prefix, suffix);
        var state = JSON.stringify(props) + JSON.stringify(picked[key] || {});

        // Every command redraws; an unchanged card is left alone, so a focused option keeps its focus.
        if (node.getAttribute('data-drawn') === state) {
            return;
        }

        var s = strings();
        var isAttribute = type === 'attribute';
        var limit = props.maxVisible === 0 ? Infinity : props.maxVisible;
        var crossed = function (option) {
            return props.soldOut === 'cross' && !!option.soldOut;
        };
        var rows = sampleRows(type).map(function (row, rowIndex) {
            var options = props.soldOut === 'hide' ? row.options.filter(function (option) {
                return !option.soldOut;
            }) : row.options;
            var visible = options.slice(0, limit);
            var choice = (picked[key] || {})[rowIndex];
            var current = visible.filter(function (option) {
                return option.value === choice && !crossed(option);
            })[0] || visible.filter(function (option) {
                return !crossed(option);
            })[0] || visible[0];

            return {name: row.name, visible: visible, current: current, hidden: options.length - visible.length};
        });

        var card = el('div', 'fct-swatch-sample-card');
        card.setAttribute('role', 'group');
        card.setAttribute('aria-label', s.cardPreview || 'Card preview');

        var image = el('div', 'fct-swatch-sample-image is-' + type);
        image.setAttribute('aria-hidden', 'true');
        image.appendChild(isAttribute ? teeIcon(props.enabled ? rows[0].current.hex : '#d6d3cd') : podsIcon());
        card.appendChild(image);

        var details = el('div', 'fct-swatch-sample-details');
        details.appendChild(el('span', 'fct-swatch-sample-name', isAttribute ? (s.tee || 'Classic Tee') : (s.pods || 'Espresso Pods')));

        if (!props.enabled) {
            details.appendChild(el('span', 'fct-swatch-sample-off', s.off || 'Off: no options on the card.'));
        } else {
            rows.forEach(function (row, rowIndex) {
                var name = isAttribute ? row.name : (props.rowName || s.option || 'Option');
                var title = [props.showName ? name : '', props.showSelected && !props.selectedBelow ? row.current.value : ''].filter(Boolean).join(' · ');
                var rowNode = el('div', 'fct-swatch-sample-row');

                if (title) {
                    var titleNode = el('span', 'fct-swatch-sample-title', title);
                    titleNode.setAttribute('aria-hidden', 'true');
                    rowNode.appendChild(titleNode);
                }

                var group = el('div', 'fct-swatch-sample-options');
                group.setAttribute('role', 'radiogroup');
                group.setAttribute('aria-label', name);

                var pick = function (option, focus) {
                    picked[key] = picked[key] || {};
                    picked[key][rowIndex] = option.value;
                    node.removeAttribute('data-drawn');
                    draw(node);
                    if (focus) {
                        var again = node.querySelectorAll('.fct-swatch-sample-options')[rowIndex];
                        var checked = again && again.querySelector('[aria-checked="true"]');
                        if (checked) {
                            checked.focus();
                        }
                    }
                };

                row.visible.forEach(function (option, index) {
                    var button = el('button', 'fct-swatch-sample-option' + (option.hex ? ' is-dot' : '') + (crossed(option) ? ' is-sold-out' : ''), option.hex ? undefined : option.value);
                    // A crossed-out option is never the checked one, so focus cannot land on it.
                    var isCurrent = row.current === option && !crossed(option);
                    button.type = 'button';
                    button.setAttribute('role', 'radio');
                    button.setAttribute('aria-checked', isCurrent ? 'true' : 'false');
                    button.tabIndex = isCurrent ? 0 : -1;
                    if (option.hex) {
                        button.style.background = option.hex;
                    }
                    if (crossed(option)) {
                        button.setAttribute('aria-label', format(s.soldOut, option.value));
                        button.setAttribute('aria-disabled', 'true');
                    } else if (option.hex) {
                        button.setAttribute('aria-label', option.value);
                    }
                    button.addEventListener('click', function () {
                        if (!crossed(option)) {
                            pick(option, false);
                        }
                    });
                    // Arrow keys move through the row as in a radio group; crossed-out samples are skipped.
                    button.addEventListener('keydown', function (event) {
                        var step = {ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1}[event.key];
                        var count = row.visible.length;
                        var next = null;

                        if (event.key === 'Home' || event.key === 'End') {
                            step = event.key === 'Home' ? 1 : -1;
                            next = event.key === 'Home' ? 0 : count - 1;
                        } else if (step) {
                            next = (index + step + count) % count;
                        }
                        for (var tries = 0; next !== null && crossed(row.visible[next]) && tries < count; tries++) {
                            next = (next + step + count) % count;
                        }
                        if (next === null || crossed(row.visible[next])) {
                            return;
                        }
                        event.preventDefault();
                        pick(row.visible[next], true);
                    });
                    group.appendChild(button);
                });

                if (row.hidden > 0) {
                    var more = el('span', 'fct-swatch-sample-more');
                    var shown = el('span', '', '+' + row.hidden);
                    shown.setAttribute('aria-hidden', 'true');
                    more.appendChild(shown);
                    more.appendChild(el('span', 'elementor-screen-only', format(s.more, row.hidden)));
                    group.appendChild(more);
                }

                rowNode.appendChild(group);
                details.appendChild(rowNode);
            });

            if (props.showSelected && props.selectedBelow) {
                var below = el('span', 'fct-swatch-sample-selected', format(s.selected, rows.map(function (row) {
                    return row.current.value;
                }).join(' · ')));
                below.setAttribute('aria-hidden', 'true');
                details.appendChild(below);
            }
        }

        card.appendChild(details);
        node.textContent = '';
        node.appendChild(card);
        node.setAttribute('data-drawn', state);
    }

    function run() {
        queued = false;

        var nodes = document.querySelectorAll(SAMPLE);

        for (var i = 0; i < nodes.length; i++) {
            draw(nodes[i]);
        }
    }

    function schedule() {
        if (queued) {
            return;
        }

        queued = true;

        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(run);
        } else {
            window.setTimeout(run, 16);
        }
    }

    // Only a mutation that brings in a sample needs a pass; the editor's other churn does not.
    function addsSample(mutations) {
        for (var m = 0; m < mutations.length; m++) {
            var added = mutations[m].addedNodes;

            for (var n = 0; n < added.length; n++) {
                var node = added[n];

                if (node.nodeType === 1 && ((node.matches && node.matches(SAMPLE)) || (node.querySelector && node.querySelector(SAMPLE)))) {
                    return true;
                }
            }
        }

        return false;
    }

    if (window.MutationObserver) {
        new window.MutationObserver(function (mutations) {
            if (addsSample(mutations)) {
                schedule();
            }
        }).observe(document.documentElement, {
            childList: true,
            subtree: true,
        });
    }

    // A changed setting, undo and a reset all run as editor commands.
    if (window.$e && window.$e.commands && typeof window.$e.commands.on === 'function') {
        window.$e.commands.on('run:after', schedule);
    }

    schedule();
    document.addEventListener('DOMContentLoaded', schedule);
}());
