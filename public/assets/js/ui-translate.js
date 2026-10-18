/*
 * English mode for text inserted by scripts (confirm dialogs, offline messages, alerts).
 * The server already translates rendered pages; this only handles text added later.
 * Uses the dictionary in <script type="application/json" id="tms-ui-dictionary">.
 */
(function () {
    var source = document.getElementById('tms-ui-dictionary');
    if (!source) return;

    var dictionary = {};
    try { dictionary = JSON.parse(source.textContent) || {}; } catch (e) { return; }

    var URDU = '\u0600-\u06FF\u0750-\u077F\uFB50-\uFDFF\uFE70-\uFEFF';
    var RUN = new RegExp('[' + URDU + '](?:[' + URDU + '\\s\u200C\u200F]*[' + URDU + '])?', 'g');
    var HAS_URDU = new RegExp('[' + URDU + ']');
    var MAX_WORDS = 8;
    var PUNCTUATION = { '۔': '.', '،': ',', '؟': '?', '٪': '%' };
    var SKIP = { SCRIPT: 1, STYLE: 1, TEXTAREA: 1, INPUT: 1, SELECT: 0, OPTION: 0 };

    function translateRun(run) {
        var normalized = run.replace(/\s+/g, ' ');
        if (dictionary[normalized]) return dictionary[normalized];
        var words = normalized.split(' '), output = [], i = 0;
        while (i < words.length) {
            var matched = false;
            for (var length = Math.min(MAX_WORDS, words.length - i); length > 0; length--) {
                var phrase = words.slice(i, i + length).join(' ');
                if (dictionary[phrase]) { output.push(dictionary[phrase]); i += length; matched = true; break; }
            }
            if (!matched) { output.push(words[i].replace(/[۔،؟٪]/g, function (c) { return PUNCTUATION[c]; })); i++; }
        }
        return output.join(' ');
    }

    function translate(text) {
        return typeof text === 'string' && HAS_URDU.test(text) ? text.replace(RUN, translateRun) : text;
    }

    function skipped(element) {
        return !element || SKIP[element.tagName] || (element.closest && element.closest('[translate="no"]'));
    }

    function translateNode(node) {
        if (node.nodeType === 3) {
            if (!skipped(node.parentElement) && HAS_URDU.test(node.nodeValue)) {
                var translated = translate(node.nodeValue);
                if (translated !== node.nodeValue) node.nodeValue = translated;
            }
            return;
        }
        if (node.nodeType !== 1 || skipped(node)) return;
        ['placeholder', 'title', 'aria-label'].forEach(function (name) {
            var value = node.getAttribute(name);
            if (value && HAS_URDU.test(value)) node.setAttribute(name, translate(value));
        });
        for (var child = node.firstChild; child; child = child.nextSibling) translateNode(child);
    }

    new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'characterData') translateNode(mutation.target);
            else if (mutation.type === 'attributes') translateNode(mutation.target);
            else mutation.addedNodes.forEach(translateNode);
        });
    }).observe(document.documentElement, {
        childList: true, subtree: true, characterData: true,
        attributes: true, attributeFilter: ['placeholder', 'title', 'aria-label']
    });

    // Native dialogs opened by scripts.
    ['alert', 'confirm', 'prompt'].forEach(function (name) {
        var original = window[name];
        if (typeof original !== 'function') return;
        window[name] = function (message) {
            var args = Array.prototype.slice.call(arguments);
            args[0] = translate(message);
            return original.apply(window, args);
        };
    });

    window.tmsTranslate = translate;
})();
