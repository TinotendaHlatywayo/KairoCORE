@php
    use Modules\CMS\Services\CmsTemplateService;

    // Contract: $rtKey (unique per block+field), $rtPath (Livewire dot-path under selectedBlockData)
    $rtKey = $rtKey ?? ($rtPath ?? 'rt');
    $rtValue = CmsTemplateService::richText($rtValue ?? '');
    $rtPlaceholder = $rtPlaceholder ?? 'Start typing…';

    $rtFonts = array_keys(CmsTemplateService::RICH_FONTS);
    $rtSizes = [8, 9, 10, 11, 12, 14, 16, 18, 20, 22, 24, 28, 32, 36, 48];
    $rtColors = ['#0f172a', '#1f2937', '#374151', '#6b7280', '#94a3b8', '#cbd5e1', '#e2e8f0', '#ffffff', '#7f1d1d', '#991b1b', '#dc2626', '#ef4444', '#f87171', '#fca5a5', '#7c2d12', '#9a3412', '#ea580c', '#f97316', '#fb923c', '#fdba74', '#b45309', '#d97706', '#f59e0b', '#fbbf24', '#fcd34d', '#fde68a', '#713f12', '#a16207', '#eab308', '#facc15', '#fde047', '#fef08a', '#14532d', '#166534', '#16a34a', '#22c55e', '#4ade80', '#86efac', '#064e3b', '#047857', '#059669', '#10b981', '#34d399', '#6ee7b7', '#0f766e', '#0d9488', '#14b8a6', '#2dd4bf', '#5eead4', '#164e63', '#0e7490', '#06b6d4', '#22d3ee', '#67e8f9', '#1e3a8a', '#1d4ed8', '#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#312e81', '#4338ca', '#6366f1', '#818cf8', '#a5b4fc', '#581c87', '#6b21a8', '#9333ea', '#a855f7', '#c084fc', '#d8b4fe', '#831843', '#9d174d', '#db2777', '#ec4899', '#f472b6', '#f9a8d4'];
    $rtHighlights = ['#fef2f2', '#fee2e2', '#fecaca', '#ffedd5', '#fed7aa', '#fef9c3', '#fde68a', '#fef3c7', '#fde68a', '#fefce8', '#ecfccb', '#d9f99d', '#f0fdf4', '#dcfce7', '#bbf7d0', '#f0fdfa', '#ccfbf1', '#99f6e4', '#ecfeff', '#cffafe', '#a5f3fc', '#eff6ff', '#dbeafe', '#bfdbfe', '#eef2ff', '#e0e7ff', '#c7d2fe', '#faf5ff', '#f3e8ff', '#e9d5ff', '#fdf2f8', '#fce7f3', '#fbcfe8', '#fafafa', '#f4f4f5', '#ffffff'];

    $rtSvgAlign = [
        'left'    => '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h9M3 18h18"/></svg>',
        'center'  => '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M6 12h12M3 18h18"/></svg>',
        'right'   => '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M12 12h9M3 18h18"/></svg>',
        'justify' => '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>',
    ];

    $rtEmoji = [
        'Smileys' => ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🤭','🤫','🤥','😶','😐','😑','😬','🙄','😯','😴','🤤','😪','😵','🤐','🥴','🤢','🤮','🤧','😷','🤒','🤕','🤑','🤠','😈','👿','👹','👺','🤡','💩','👻','💀','☠️','👽','👾','🤖','😺','😸','😹','😻','😼','😽','🙀','😿','😾'],
        'People' => ['👋','🤚','🖐','✋','🖖','👌','🤌','🤏','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','👇','☝️','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏','✍️','💅','🤳','💪','🦾','🦵','🦶','👂','🦻','👃','👀','👁','🧠','🦷','🦴','👅','👄','💋','👤','👥','🗣','🧑','👩','👨','🧒','👦','👧','👶','🧓','👵','👴','👲','👳','🧕','👮','🕵','💂','👷','👸','🤴','🧔','👱','👰','🤵','🤰','🚶','🏃','💃','🕺','👯','🧘','🏌','🏇','🧗','🏄','🏊','🤽','🚣','🚴','🚵','🤾','🤸','🤹','🎅','🤶','🧑‍🎄','🦸','🦹','🧙','🧚','🧛','🧜','🧝','🧞','🧟','💆','💇'],
        'Nature' => ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🙈','🙉','🙊','🐒','🐔','🐧','🐦','🐤','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🐛','🦋','🐌','🐞','🐜','🦟','🦠','🐢','🐍','🦎','🦖','🦕','🐙','🦑','🦞','🦀','🐡','🐠','🐟','🐬','🐳','🐋','🦈','🐊','🌲','🌳','🌴','🌵','🌾','🌿','☘️','🍀','🍁','🍂','🍃','🌸','🌺','🌻','🌞','🌝','🌛','🌜','🌚','🌕','🌖','🌗','🌘','🌑','🌒','🌓','🌔','🌙','🌎','🌍','🌏','🌈','🌊','🌋','🗻','⛰️','🏔️','🏝️','⛱️','🏜️','🪨','🪵','🌋'],
        'Food' => ['🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🥑','🍆','🥔','🥕','🌽','🌶','🥒','🥬','🥦','🧄','🧅','🥜','🍞','🥐','🥖','🥯','🧀','🥚','🍳','🧈','🥞','🧇','🥓','🥩','🍗','🍖','🌭','🍔','🍟','🍕','🥪','🌮','🌯','🧆','🥙','🥫','🍝','🍜','🍲','🍛','🍣','🍱','🥟','🦪','🍤','🍙','🍚','🍘','🍥','🥠','🥮','🍢','🍡','🍧','🍨','🍦','🥧','🍰','🎂','🍮','🍭','🍬','🍫','🍿','🍩','🍪','🌰','🥜','🍯','🥛','🍼','☕','🍵','🧃','🥤','🧋','🍶','🍺','🍻','🥂','🍷','🥃','🍸','🍹','🧉','🍾','🥢','🥄','🍽'],
        'Activities' => ['⚽','🏀','🏈','⚾','🥎','🎾','🏐','🏉','🥏','🎱','🏓','🏸','🏒','🏑','🥍','🏏','🥅','⛳','🏹','🎣','🥊','🥋','🎽','🛹','🎿','🛷','🥌','🎯','🎳','🎮','🎲','🧩','🎪','🎭','🎨','🎬','🎤','🎧','🎼','🎹','🥁','🎷','🎺','🎸','🪕','🎻','♟️','🏆','🥇','🥈','🥉','🏅','🎖','🎗','🎫','🎟','🎠','🎡','🎢','🎰'],
        'Travel' => ['🚗','🚕','🚙','🚌','🚎','🏎','🚓','🚑','🚒','🚐','🛻','🚚','🚛','🚜','🛴','🚲','🛵','🏍','🛺','🚨','🚔','🚍','🚘','🚖','🚡','🚠','🚟','🚃','🚋','🚞','🚝','🚄','🚅','🚈','🚂','🚆','🚇','🚊','🚉','✈️','🛫','🛬','🛩','💺','🛰','🚀','🛸','🚁','🛶','⛵','🚤','🛥','🛳','⛴','🚢','⚓','🗺','🗿','⛺','🏕','🏖','🏛','🏰','🏯','🗼','⛲','⛱'],
        'Objects' => ['⌚','📱','📲','💻','⌨','🖥','🖨','🖱','🖲','🕹','💽','💾','💿','📀','📼','📷','📸','📹','🎥','📽','🎞','📞','☎','📟','📠','📺','📻','🎙','🎚','🎛','🧭','⏱','⏲','⏰','🕰','⌛','⏳','📡','🔋','🔌','💡','🔦','🕯','🗑','🛢','💸','💵','💴','💶','💷','💰','💳','💎','⚖','🧰','🔧','🔨','⚒','🔩','⚙','🔗','📎','📌','📍','📏','📐','✂️','🖊','🖋','✒️','🖌','🖍','📝','✏️','🔍','🔎','🔑','🔒','🔓','🔏','🧾','📋','📅','📆','📇','📈','📉','📊','📁','📂','📃','📄','📑','📜','📌','✉️','📩','📨','📧','💌','📥','📤'],
        'Symbols' => ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','💌','💬','💭','🗯','🔴','🟠','🟡','🟢','🔵','🟣','🟤','⚫','⚪','🔺','🔻','🔸','🔹','🔶','🔷','🔳','🔲','▪️','▫️','◾','◽','➡️','⬅️','⬆️','⬇️','↗️','↘️','↙️','↖️','↕️','↔️','🔄','🔃','♻️','✅','❌','❎','➕','➖','➗','✖️','💯','🔢','🔤','🔡','🔠','🔣','1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣','🔟','⏩','⏪','⏫','⏬','🔆','🔅','📶','📳','📴','♿','🚻','🚾','🚹','🚺','✳️','✴️','❇️','©️','®️','™️','☮️','☯️','🕉','✝️','☪️','☸️','✡️','🔯','🕎','☦️','⛪','🛐','⚛️','🀄','♠️','♥️','♦️','♣️','🎴'],
    ];
@endphp

@once
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ CmsTemplateService::richFontsUrl() }}">
    <style>
        .wcm-rt {
            border: 1px solid var(--sc-border, #dfe3ef);
            border-radius: 0.625rem;
            background: #fff;
            overflow: visible;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .wcm-rt:focus-within {
            border-color: var(--sc-primary, #5b4fe9);
            box-shadow: 0 0 0 3px rgba(91, 79, 233, 0.15);
        }
        .wcm-rt-toolbar {
            display: flex; flex-wrap: wrap; align-items: center; gap: 2px;
            padding: 5px 6px;
            background: var(--sc-canvas, #eef0f7);
            border-bottom: 1px solid var(--sc-border, #dfe3ef);
        }
        .wcm-rt-btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 28px !important; height: 28px;
            padding: 0 0.45rem; border-radius: 6px;
            font-size: 0.75rem; font-weight: 700; line-height: 1;
            color: var(--sc-text-muted, #5c6478); background: transparent; border: 1px solid transparent;
            cursor: pointer; transition: background 0.12s ease, color 0.12s ease;
            user-select: none;
        }
        .wcm-rt-btn:hover { background: #fff; color: var(--sc-primary, #5b4fe9); }
        .wcm-rt-btn.is-active {
            background: var(--sc-primary-light, #efedff);
            color: var(--sc-primary, #5b4fe9);
            border-color: color-mix(in srgb, var(--sc-primary, #5b4fe9) 25%, transparent);
        }
        .wcm-rt-sep { width: 1px; align-self: stretch; margin: 0 4px; background: var(--sc-border, #dfe3ef); }
        .wcm-rt-label { font-size: 0.8rem; }
        .wcm-rt-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            height: 28px; max-width: 130px; min-width: 84px;
            border: 1px solid var(--sc-border, #dfe3ef); border-radius: 6px;
            background-color: #fff;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2364758b'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 7px center;
            background-size: 10px 6px;
            color: var(--sc-text, #0d1220);
            font-size: 0.72rem; font-weight: 600; padding: 0 20px 0 8px; cursor: pointer;
        }
        .wcm-rt-select::-ms-expand { display: none; }
        .wcm-rt-select option { font-family: sans-serif; }
        .wcm-rt-color { position: relative; display: inline-flex; align-items: center; }
        .wcm-rt-color-underline { width: 14px; height: 3px; border-radius: 9999px; margin-top: 1px; }
        .wcm-rt-color-panel {
            position: absolute; top: 32px; left: 0; z-index: 60;
            width: 244px; padding: 8px; border-radius: 10px;
            background: #fff; box-shadow: 0 10px 28px rgba(15, 23, 42, 0.18);
            border: 1px solid var(--sc-border, #dfe3ef);
            display: grid; grid-template-columns: repeat(10, 1fr); gap: 4px;
        }
        .wcm-rt-color-panel input[type="color"] {
            grid-column: span 10; width: 100%; height: 24px; padding: 0; border: 1px solid var(--sc-border, #dfe3ef);
            border-radius: 6px; cursor: pointer; background: transparent;
        }
        .wcm-rt-color-btn {
            width: 100%; aspect-ratio: 1; border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.12); cursor: pointer; padding: 0;
        }
        .wcm-rt-editor {
            min-height: 110px; max-height: 340px; overflow-y: auto;
            padding: 0.625rem 0.75rem;
            font-size: 0.8125rem; line-height: 1.6;
            color: var(--sc-text, #0d1220);
            outline: none;
            overflow-wrap: break-word;
        }
        .wcm-rt-editor:empty::before {
            content: attr(data-placeholder);
            color: var(--sc-text-muted, #9ca3af);
            font-weight: 400;
            pointer-events: none;
        }
        .wcm-rt-editor p, .wcm-rt-editor div { margin: 0 0 0.5em; }
        .wcm-rt-editor p:last-child, .wcm-rt-editor div:last-child { margin-bottom: 0; }
        .wcm-rt-editor ul, .wcm-rt-editor ol { padding-left: 1.4rem; margin: 0 0 0.5em; }
        .wcm-rt-editor a { color: var(--sc-primary, #5b4fe9); text-decoration: underline; }
        .wcm-rt-editor blockquote {
            border-left: 3px solid var(--sc-border, #dfe3ef);
            padding-left: 0.75rem; color: var(--sc-text-muted, #5c6478);
        }
        .wcm-rt-editor sub, .wcm-rt-editor sup { font-size: 0.7em; line-height: 0; }
        .wcm-rt-emoji-wrap { position: relative; display: inline-flex; }
        .wcm-rt-emoji {
            position: absolute; top: calc(100% + 4px); right: 0; z-index: 60;
            width: 324px; max-width: 82vw;
            background: #fff; border: 1px solid var(--sc-border, #dfe3ef);
            border-radius: 10px; overflow: hidden;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.2);
        }
        .wcm-rt-emoji-tabs {
            display: flex; gap: 2px; padding: 4px 6px 0;
            background: var(--sc-canvas, #eef0f7); border-bottom: 1px solid var(--sc-border, #dfe3ef);
            overflow-x: auto;
        }
        .wcm-rt-emoji-tab {
            flex: 1 0 auto; white-space: nowrap;
            font-size: 0.6rem; font-weight: 700;
            color: var(--sc-text-muted, #5c6478); background: transparent; border: 0;
            padding: 5px 6px; border-radius: 6px 6px 0 0; cursor: pointer;
            transition: background 0.12s ease, color 0.12s ease;
        }
        .wcm-rt-emoji-tab:hover { color: var(--sc-primary, #5b4fe9); }
        .wcm-rt-emoji-tab.is-active { background: #fff; color: var(--sc-primary, #5b4fe9); }
        .wcm-rt-emoji-grid {
            display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px;
            padding: 6px; max-height: 176px; overflow-y: auto; background: #fff;
        }
        .wcm-rt-emoji-grid button {
            min-height: 30px !important; height: 30px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; border-radius: 6px; background: transparent; border: none;
            cursor: pointer; transition: background 0.12s ease;
        }
        .wcm-rt-emoji-grid button:hover { background: var(--sc-canvas, #eef0f7); }
        .wcm-rt-model { display: none !important; }
    </style>
    @script
        <script>
            (function () {
            function registerRichText() {
                if (window.__richTextFactory) return;
                var factory = function (initial, path, placeholder) {
                    return {
                        value: initial,
                path: path,
                placeholder: placeholder,
                emojiTab: 'Smileys',
                syncTimer: null,
                savedRange: null,

                init() {
                    this.$refs.editor.setAttribute('data-placeholder', this.placeholder);
                    this.$refs.editor.addEventListener('paste', (e) => {
                        e.preventDefault();
                        const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                        document.execCommand('insertText', false, text);
                    });
                    document.addEventListener('selectionchange', () => {
                        this.saveRange();
                        this.refreshToolbar();
                    });
                    this.syncNow();
                },

                onInput() {
                    this.value = this.$refs.editor.innerHTML;
                    this.scheduleSync();
                    this.refreshToolbar();
                },

                scheduleSync() {
                    clearTimeout(this.syncTimer);
                    this.syncTimer = setTimeout(() => this.syncNow(), 400);
                },

                syncNow() {
                    clearTimeout(this.syncTimer);
                    this.$refs.model.value = this.value;
                    this.$refs.model.dispatchEvent(new Event('input', { bubbles: true }));
                },

                saveRange() {
                    const sel = window.getSelection();
                    if (sel.rangeCount && sel.anchorNode && this.$refs.editor.contains(sel.anchorNode)) {
                        this.savedRange = sel.getRangeAt(0).cloneRange();
                    }
                },

                restoreSelection() {
                    if (this.savedRange && this.$refs.editor.contains(this.savedRange.startContainer)) {
                        const sel = window.getSelection();
                        sel.removeAllRanges();
                        sel.addRange(this.savedRange);
                    }
                },

                exec(cmd, val) {
                    this.restoreSelection();
                    this.$refs.editor.focus();
                    document.execCommand('styleWithCSS', false, true);
                    document.execCommand(cmd, false, val || null);
                    this.normalize();
                    this.onInput();
                    this.syncNow();
                },

                setFontFamily(fam) {
                    this.restoreSelection();
                    this.$refs.editor.focus();
                    document.execCommand('styleWithCSS', false, true);
                    document.execCommand('fontName', false, fam);
                    this.normalize();
                    this.onInput();
                    this.syncNow();
                },

                setFontSize(px) {
                    const ed = this.$refs.editor;
                    this.restoreSelection();
                    ed.focus();
                    document.execCommand('styleWithCSS', false, true);
                    document.execCommand('fontSize', false, '7');
                    ed.querySelectorAll('font[size="7"]').forEach((el) => {
                        const span = document.createElement('span');
                        span.style.fontSize = px + 'px';
                        while (el.firstChild) span.appendChild(el.firstChild);
                        el.replaceWith(span);
                    });
                    ed.querySelectorAll('span').forEach((el) => {
                        if ((el.style.fontSize || '').toLowerCase().indexOf('xxx-large') !== -1) {
                            el.style.fontSize = px + 'px';
                        }
                    });
                    this.normalize();
                    this.onInput();
                    this.syncNow();
                },

                setColor(color, bg) {
                    this.restoreSelection();
                    this.$refs.editor.focus();
                    document.execCommand('styleWithCSS', false, true);
                    document.execCommand(bg ? 'hiliteColor' : 'foreColor', false, color);
                    this.normalize();
                    this.onInput();
                    this.syncNow();
                },

                normalize() {
                    const ed = this.$refs.editor;
                    ed.querySelectorAll('font').forEach((el) => {
                        const span = document.createElement('span');
                        const face = el.getAttribute('face');
                        if (face) span.style.fontFamily = face;
                        const color = el.getAttribute('color');
                        if (color) span.style.color = color;
                        const size = el.getAttribute('size');
                        if (size && /^[1-7]$/.test(size)) {
                            span.style.fontSize = ({1: 'xx-small', 2: 'small', 3: 'medium', 4: 'large', 5: 'x-large', 6: 'xx-large', 7: 'xxx-large'})[size];
                        }
                        if (el.style && el.style.fontSize) {
                            span.style.fontSize = el.style.fontSize;
                        }
                        while (el.firstChild) span.appendChild(el.firstChild);
                        el.replaceWith(span);
                    });
                },

                insertLink() {
                    const url = window.prompt('Paste a link (https://…).');
                    if (url == null || url.trim() === '') return;
                    this.exec('createLink', url.trim());
                },

                unlink() {
                    this.exec('unlink');
                },

                insertEmoji(emoji) {
                    this.restoreSelection();
                    this.$refs.editor.focus();
                    document.execCommand('insertText', false, emoji);
                    this.onInput();
                    this.syncNow();
                },

                qcs(cmd) {
                    try { return !!document.queryCommandState(cmd); } catch (e) { return false; }
                },

                refreshToolbar() {
                    const map = {
                        bold: 'bold',
                        italic: 'italic',
                        underline: 'underline',
                        strikethrough: 'strikeThrough',
                        bullet: 'insertUnorderedList',
                        ordered: 'insertOrderedList',
                        sup: 'superscript',
                        sub: 'subscript',
                        alignLeft: 'justifyLeft',
                        alignCenter: 'justifyCenter',
                        alignRight: 'justifyRight',
                        alignJustify: 'justifyFull',
                        quote: 'formatBlock',
                    };
                    this.$root.querySelectorAll('.wcm-rt-btn[data-cmd]').forEach((btn) => {
                        const c = map[btn.getAttribute('data-cmd')];
                        if (c) btn.classList.toggle('is-active', this.qcs(c));
                    });
                },
                    };
                };
                window.__richTextFactory = factory;
                window.richText = factory;
                if (window.Alpine && typeof window.Alpine.data === 'function') {
                    try { window.Alpine.data('richText', factory); } catch (e) {}
                }
            }

            if (window.Alpine && typeof window.Alpine.data === 'function') {
                registerRichText();
            } else {
                document.addEventListener('alpine:init', registerRichText);
            }
        })();
        </script>
    @endscript
@endonce

<div class="wcm-rt" wire:key="wcm-rt-{{ $rtKey }}"
     x-data="richText(@js($rtValue), '{{ $rtPath }}', @js($rtPlaceholder))"
     x-on:focusout.debounce.200ms="syncNow()">

    <div class="wcm-rt-toolbar" role="toolbar" aria-label="Formatting">
        <button type="button" class="wcm-rt-btn" title="Undo" x-on:mousedown.prevent x-on:click="exec('undo')">{{ __('↶') }}</button>
        <button type="button" class="wcm-rt-btn" title="Redo" x-on:mousedown.prevent x-on:click="exec('redo')">{{ __('↷') }}</button>
        <span class="wcm-rt-sep"></span>

        <button type="button" class="wcm-rt-btn" data-cmd="bold" title="Bold (Ctrl+B)" x-on:mousedown.prevent x-on:click="exec('bold')"><b>{{ __('B') }}</b></button>
        <button type="button" class="wcm-rt-btn" data-cmd="italic" title="Italic (Ctrl+I)" x-on:mousedown.prevent x-on:click="exec('italic')"><i>{{ __('I') }}</i></button>
        <button type="button" class="wcm-rt-btn" data-cmd="underline" title="Underline (Ctrl+U)" x-on:mousedown.prevent x-on:click="exec('underline')"><u>{{ __('U') }}</u></button>
        <button type="button" class="wcm-rt-btn" data-cmd="strikethrough" title="Strikethrough" x-on:mousedown.prevent x-on:click="exec('strikeThrough')"><s>{{ __('S') }}</s></button>
        <span class="wcm-rt-sep"></span>

        <button type="button" class="wcm-rt-btn" data-cmd="sub" title="Subscript" x-on:mousedown.prevent x-on:click="exec('subscript')"><span class="wcm-rt-label">{{ __('X') }}</span><sub>{{ __('2') }}</sub></button>
        <button type="button" class="wcm-rt-btn" data-cmd="sup" title="Superscript" x-on:mousedown.prevent x-on:click="exec('superscript')"><span class="wcm-rt-label">{{ __('X') }}</span><sup>{{ __('2') }}</sup></button>
        <span class="wcm-rt-sep"></span>

        <button type="button" class="wcm-rt-btn" data-cmd="alignLeft" title="Align left" x-on:mousedown.prevent x-on:click="exec('justifyLeft')">{!! $rtSvgAlign['left'] !!}</button>
        <button type="button" class="wcm-rt-btn" data-cmd="alignCenter" title="Align centre" x-on:mousedown.prevent x-on:click="exec('justifyCenter')">{!! $rtSvgAlign['center'] !!}</button>
        <button type="button" class="wcm-rt-btn" data-cmd="alignRight" title="Align right" x-on:mousedown.prevent x-on:click="exec('justifyRight')">{!! $rtSvgAlign['right'] !!}</button>
        <button type="button" class="wcm-rt-btn" data-cmd="alignJustify" title="Justify" x-on:mousedown.prevent x-on:click="exec('justifyFull')">{!! $rtSvgAlign['justify'] !!}</button>
        <span class="wcm-rt-sep"></span>

        <button type="button" class="wcm-rt-btn" data-cmd="bullet" title="Bulleted list" x-on:mousedown.prevent x-on:click="exec('insertUnorderedList')">{{ __('• List') }}</button>
        <button type="button" class="wcm-rt-btn" data-cmd="ordered" title="Numbered list" x-on:mousedown.prevent x-on:click="exec('insertOrderedList')">{{ __('1. List') }}</button>
        <span class="wcm-rt-sep"></span>

        <button type="button" class="wcm-rt-btn" data-cmd="quote" title="Blockquote" x-on:mousedown.prevent x-on:click="exec('formatBlock', 'blockquote')">{{ __('❝') }}</button>
        <button type="button" class="wcm-rt-btn" title="Insert link" x-on:mousedown.prevent x-on:click="insertLink()">{{ __('🔗') }}</button>
        <button type="button" class="wcm-rt-btn" title="Remove link" x-on:mousedown.prevent x-on:click="unlink()">{{ __('⛓️') }}</button>
        <button type="button" class="wcm-rt-btn" title="Clear formatting" x-on:mousedown.prevent x-on:click="exec('removeFormat')"><span class="wcm-rt-label" style="text-decoration: line-through;">{{ __('Aa') }}</span></button>
        <span class="wcm-rt-sep"></span>

        <select class="wcm-rt-select" title="Font family" x-on:change="setFontFamily($event.target.value); $event.target.selectedIndex = 0;">
            <option value="">{{ __('Font') }}</option>
            @foreach($rtFonts as $font)
                <option value="{{ $font }}" style="font-family: '{{ $font }}', sans-serif;">{{ $font }}</option>
            @endforeach
        </select>

        <select class="wcm-rt-select" title="Font size" x-on:change="setFontSize($event.target.value); $event.target.selectedIndex = 0;">
            <option value="">{{ __('Size') }}</option>
            @foreach($rtSizes as $size)
                <option value="{{ $size }}">{{ $size }}px</option>
            @endforeach
        </select>
        <span class="wcm-rt-sep"></span>

        <div class="wcm-rt-color" x-data="{ open: false }" x-on:click.outside="open = false">
            <button type="button" class="wcm-rt-btn" title="Text colour" x-on:mousedown.prevent x-on:click="open = !open">
                <span class="wcm-rt-label">{{ __('A') }}</span><span class="wcm-rt-color-underline" style="background: #e11d48;"></span>
            </button>
            <div class="wcm-rt-color-panel" x-show="open" x-cloak>
                <input type="color" value="#111827" title="Custom colour" x-on:change="setColor($event.target.value); open = false;">
                @foreach($rtColors as $color)
                    <button type="button" class="wcm-rt-color-btn" style="background: {{ $color }};" title="{{ $color }}" x-on:mousedown.prevent x-on:click="setColor('{{ $color }}'); open = false;"></button>
                @endforeach
            </div>
        </div>

        <div class="wcm-rt-color" x-data="{ open: false }" x-on:click.outside="open = false">
            <button type="button" class="wcm-rt-btn" title="Highlight colour" x-on:mousedown.prevent x-on:click="open = !open">
                <span class="wcm-rt-label" style="background: #fef08a;">{{ __('A') }}</span><span class="wcm-rt-color-underline" style="background: #fde047;"></span>
            </button>
            <div class="wcm-rt-color-panel" x-show="open" x-cloak>
                <input type="color" value="#fef08a" title="Custom highlight" x-on:change="setColor($event.target.value, true); open = false;">
                @foreach($rtHighlights as $color)
                    <button type="button" class="wcm-rt-color-btn" style="background: {{ $color }};" title="{{ $color }}" x-on:mousedown.prevent x-on:click="setColor('{{ $color }}', true); open = false;"></button>
                @endforeach
            </div>
        </div>
        <span class="wcm-rt-sep"></span>

        <div class="wcm-rt-emoji-wrap" x-data="{ open: false, tab: 'Smileys' }" x-on:click.outside="open = false">
            <button type="button" class="wcm-rt-btn" title="Insert emoji" x-on:mousedown.prevent x-on:click="open = !open">{{ __('😊') }}</button>
            <div class="wcm-rt-emoji" x-show="open" x-cloak>
                <div class="wcm-rt-emoji-tabs">
                    @foreach($rtEmoji as $cat => $emojis)
                        <button type="button" class="wcm-rt-emoji-tab" :class="tab === '{{ $cat }}' ? 'is-active' : ''" x-on:click="tab = '{{ $cat }}'">{{ $cat }}</button>
                    @endforeach
                </div>
                @foreach($rtEmoji as $cat => $emojis)
                    <div class="wcm-rt-emoji-grid" x-show="tab === '{{ $cat }}'">
                        @foreach($emojis as $emoji)
                            <button type="button" x-on:mousedown.prevent x-on:click="insertEmoji('{{ $emoji }}'); open = false" title="{{ $emoji }}">{{ $emoji }}</button>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="wcm-rt-editor" contenteditable="true" x-ref="editor" wire:ignore
         @input="onInput()" @keyup="refreshToolbar()" @mouseup="refreshToolbar()">{!! $rtValue !!}</div>

    <input type="text" x-ref="model" class="wcm-rt-model" wire:model.live.debounce.400ms="selectedBlockData.{{ $rtPath }}">
</div>
