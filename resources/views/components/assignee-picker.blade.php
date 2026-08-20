@props([
    'name' => 'taskForm.assignee_id',
    'selectedId' => '',
    'options' => [],
    'placeholder' => 'Search by name…',
])

<div
    class="sc-assignee"
    x-data="{
        options: @js(collect($options)->map(fn ($n, $id) => ['id' => (string) $id, 'name' => (string) $n])->values()),
        field: @js($name),
        placeholder: @js($placeholder),
        selected: @js((string) $selectedId),
        q: '',
        active: -1,
        open: false,
        init() {
            const o = this.options.find((x) => x.id === this.selected);
            if (o) this.q = o.name;
        },
        fuzzy(query, name) {
            const q = query.toLowerCase();
            const n = name.toLowerCase();
            if (n === q) return -100;
            if (n.startsWith(q)) return -50;
            let last = -1;
            let gaps = 0;
            for (const ch of q) {
                const idx = n.indexOf(ch, last + 1);
                if (idx === -1) return null;
                gaps += Math.max(0, idx - last - 1);
                last = idx;
            }
            return gaps + Math.abs(n.length - q.length);
        },
        get results() {
            const s = this.q.trim();
            const out = [];
            if (!s) {
                if (!this.selected) out.push({ id: '', name: 'Myself' });
                this.options.forEach((o) => out.push(o));
                return out.slice(0, 8);
            }
            this.options
                .map((o) => ({ o, score: this.fuzzy(s, o.name) }))
                .filter((x) => x.score !== null)
                .sort((a, b) => a.score - b.score)
                .slice(0, 8)
                .forEach((x) => out.push(x.o));
            return out;
        },
        move(dir) {
            const len = this.results.length;
            if (!len) return;
            this.active = (this.active + dir + len) % len;
        },
        chooseActive() {
            const r = this.results;
            if (!r.length) return;
            this.choose(r[this.active < 0 ? 0 : this.active]);
        },
        choose(o) {
            this.selected = o.id;
            this.q = o.id ? o.name : '';
            this.active = -1;
            this.open = false;
            $wire.set(this.field, o.id);
        },
        clear() {
            this.selected = '';
            this.q = '';
            this.active = -1;
            $wire.set(this.field, '');
        },
    }"
    @click.outside="open = false"
>
    <div class="sc-assignee-input" @click="open = true">
        <x-filament::icon icon="heroicon-o-user-group" class="w-4 h-4 sc-assignee-icon" />
        <input
            type="text"
            class="sc-assignee-text"
            :value="q"
            :placeholder="placeholder"
            @input="q = $event.target.value; open = true; active = -1"
            @focus="open = true"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter.prevent="chooseActive()"
            @keydown.escape="open = false"
            autocomplete="off"
            spellcheck="false"
        />
        <button type="button" class="sc-assignee-clear" x-show="selected" @click="clear()" title="Assign to myself" aria-label="Clear assignee">{{ __('✕') }}</button>
    </div>
    <div class="sc-assignee-drop" x-show="open && (results.length || q.trim())" x-cloak>
        <template x-for="(o, i) in results" :key="o.id || 'self'">
            <button
                type="button"
                class="sc-assignee-opt"
                :class="{ 'is-active': i === active }"
                @mousedown.prevent="choose(o)"
                @mouseenter="active = i"
                x-text="o.name"
            ></button>
        </template>
        <div class="sc-assignee-empty" x-show="!results.length && q.trim()">{{ __('No matches') }}</div>
    </div>
</div>
