@props([
    'name' => 'taskForm.assignee_spec',
    'value' => '{"mode":"self"}',
    'roles' => [],
    'roleMembers' => [],
    'staff' => [],
    'students' => [],
    'levels' => [],
    'sections' => [],
    'placeholder' => 'Search by name, email or ID…',
])

<div
    class="sc-tap"
    x-data="tapPicker(@js($value), @js($roles), @js($roleMembers), @js($staff), @js($students), @js($levels), @js($sections), @js($name))"
>
    {{-- Hidden wire value --}}
    <input type="hidden" :name="field" x-model="specJson" />

    {{-- Mode tabs --}}
    <div class="sc-tap-tabs">
        <button type="button" class="sc-tap-tab" :class="{ 'is-active': mode === 'self' }" @click="setMode('self')">{{ __('Self') }}</button>
        <button type="button" class="sc-tap-tab" :class="{ 'is-active': mode === 'role' }" @click="setMode('role')">{{ __('Role') }}</button>
        <button type="button" class="sc-tap-tab" :class="{ 'is-active': mode === 'staff' }" @click="setMode('staff')">{{ __('Staff') }}</button>
        <button type="button" class="sc-tap-tab" :class="{ 'is-active': mode === 'students' }" @click="setMode('students')">{{ __('Students') }}</button>
    </div>

    {{-- Self --}}
    <p class="sc-tap-hint" x-show="mode === 'self'">{{ __('Assign the task to yourself.') }}</p>

    {{-- Role --}}
    <div class="sc-tap-pane" x-show="mode === 'role'" x-cloak>
        <select class="sc-input" x-model="roleId" @change="sync">
            <option value="">{{ __('Choose a role…') }}</option>
            <template x-for="r in roles" :key="r.id">
                <option :value="r.id" x-text="r.name + ' (' + r.member_count + ')'"></option>
            </template>
        </select>

        <div class="sc-tap-radio-row">
            <label class="sc-field sc-field-check">
                <input type="radio" class="sc-checkbox" value="all" x-model="roleAll" @change="sync" />
                <span>{{ __('Everyone in this role') }}</span>
            </label>
            <label class="sc-field sc-field-check">
                <input type="radio" class="sc-checkbox" value="members" x-model="roleAll" @change="sync" />
                <span>{{ __('Choose specific members') }}</span>
            </label>
        </div>

        <div x-show="! roleAll" x-cloak>
            <input
                type="text"
                class="sc-input"
                :placeholder="placeholder"
                x-model="roleQuery"
                autocomplete="off"
                spellcheck="false"
            />
            <div class="sc-tap-list" x-show="roleResults.length">
                <template x-for="m in roleResults" :key="m.id">
                    <label class="sc-tap-item">
                        <input type="checkbox" class="sc-checkbox" :checked="roleSelected.includes(m.id)" @change="toggleRoleMember(m.id, $event.target.checked)" />
                        <span class="sc-tap-item-name" x-text="m.name"></span>
                        <span class="sc-tap-item-sub" x-text="m.email"></span>
                    </label>
                </template>
                <p class="sc-tap-empty" x-show="! roleResults.length && roleQuery.trim()">{{ __('No members match') }}</p>
            </div>
            <p class="sc-tap-empty" x-show="! roleId">{{ __('Select a role first.') }}</p>
        </div>
    </div>

    {{-- Staff --}}
    <div class="sc-tap-pane" x-show="mode === 'staff'" x-cloak>
        <input
            type="text"
            class="sc-input"
            :placeholder="placeholder"
            x-model="staffQuery"
            autocomplete="off"
            spellcheck="false"
        />
        <div class="sc-tap-list" x-show="staffResults.length">
            <template x-for="s in staffResults" :key="s.id">
                <label class="sc-tap-item">
                    <input type="checkbox" class="sc-checkbox" :checked="staffSelected.includes(s.id)" @change="toggleStaff(s.id, $event.target.checked)" />
                    <span class="sc-tap-item-name" x-text="s.name"></span>
                    <span class="sc-tap-item-sub" x-text="s.email"></span>
                </label>
            </template>
        </div>
        <p class="sc-tap-empty" x-show="! staffResults.length && staffQuery.trim()">{{ __('No staff match') }}</p>
        <p class="sc-tap-hint" x-show="! staffResults.length && ! staffQuery.trim()">{{ __('Search all staff by name or email.') }}</p>
    </div>

    {{-- Students --}}
    <div class="sc-tap-pane" x-show="mode === 'students'" x-cloak>
        <select class="sc-input" x-model="studentScope" @change="studentLevel=''; studentSection=''; studentSelected=[]; sync">
            <option value="school">{{ __('Whole school') }}</option>
            <option value="level">{{ __('Whole level (e.g. all Form 2)') }}</option>
            <option value="class">{{ __('Specific class (e.g. Form 3 A)') }}</option>
            <option value="individuals">{{ __('Specific individuals') }}</option>
        </select>

        <select class="sc-input" x-show="studentScope === 'level'" x-cloak x-model="studentLevel" @change="sync">
            <option value="">{{ __('Choose a level…') }}</option>
            <template x-for="l in levels" :key="l">
                <option :value="l" x-text="l"></option>
            </template>
        </select>

        <select class="sc-input" x-show="studentScope === 'class'" x-cloak x-model="studentSection" @change="sync">
            <option value="">{{ __('Choose a class…') }}</option>
            <template x-for="s in sections" :key="s.id">
                <option :value="s.id" x-text="s.name + (s.level ? ' — ' + s.level : '')"></option>
            </template>
        </select>

        <div x-show="studentScope === 'individuals'" x-cloak>
            <input
                type="text"
                class="sc-input"
                :placeholder="placeholder"
                x-model="studentQuery"
                autocomplete="off"
                spellcheck="false"
            />
            <div class="sc-tap-list" x-show="studentResults.length">
                <template x-for="st in studentResults" :key="st.id">
                    <label class="sc-tap-item">
                        <input type="checkbox" class="sc-checkbox" :checked="studentSelected.includes(st.id)" @change="toggleStudent(st.id, $event.target.checked)" />
                        <span class="sc-tap-item-name" x-text="st.name"></span>
                        <span class="sc-tap-item-sub" x-text="[st.email, st.school_id, st.level, st.section].filter(Boolean).join(' · ')"></span>
                    </label>
                </template>
            </div>
            <p class="sc-tap-empty" x-show="! studentResults.length && studentQuery.trim()">{{ __('No students match') }}</p>
            <p class="sc-tap-hint" x-show="! studentResults.length && ! studentQuery.trim()">{{ __('Search by name, email or school ID.') }}</p>
        </div>
    </div>

    {{-- Summary --}}
    <p class="sc-tap-summary" x-show="summary">
        <x-filament::icon icon="heroicon-o-user-group" class="w-4 h-4" />
        <span x-text="summary"></span>
    </p>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        if (Alpine.data('tapPicker')) return;

        Alpine.data('tapPicker', (initialSpec, roles, roleMembers, staff, students, levels, sections, field) => ({
            field,
            roles,
            roleMembers,
            staff,
            students,
            levels,
            sections,
            mode: 'self',
            specJson: '{"mode":"self"}',
            roleId: '',
            roleAll: true,
            roleQuery: '',
            roleSelected: [],
            staffQuery: '',
            staffSelected: [],
            studentScope: 'school',
            studentLevel: '',
            studentSection: '',
            studentQuery: '',
            studentSelected: [],

            init() {
                let spec = {};
                try { spec = JSON.parse(initialSpec || '{}'); } catch (e) {}
                if (spec && spec.mode) {
                    this.mode = spec.mode;
                    if (spec.mode === 'role') {
                        this.roleId = String(spec.role_id || '');
                        this.roleAll = !!spec.all;
                        this.roleSelected = (spec.member_ids || []).map(String);
                    } else if (spec.mode === 'staff') {
                        this.staffSelected = (spec.staff_ids || []).map(String);
                    } else if (spec.mode === 'students') {
                        this.studentScope = spec.scope || 'school';
                        if (this.studentScope === 'level') this.studentLevel = spec.level || '';
                        if (this.studentScope === 'class') this.studentSection = String(spec.section_id || '');
                        if (this.studentScope === 'individuals') this.studentSelected = (spec.student_ids || []).map(String);
                    }
                }
                this.sync();
            },

            setMode(mode) {
                this.mode = mode;
                this.sync();
            },

            roleMemberOptions() {
                const id = Number(this.roleId);
                return (this.roleMembers[id] || []).map((m) => ({ ...m, id: String(m.id) }));
            },

            get roleResults() {
                const q = this.roleQuery.trim().toLowerCase();
                let list = this.roleMemberOptions();
                if (!this.roleId) return [];
                if (!q) return list;
                return list.filter((m) =>
                    (m.name || '').toLowerCase().includes(q) || (m.email || '').toLowerCase().includes(q)
                );
            },

            toggleRoleMember(id, on) {
                id = String(id);
                this.roleSelected = on
                    ? [...new Set([...this.roleSelected, id])]
                    : this.roleSelected.filter((x) => x !== id);
                this.sync();
            },

            fuzzy(query, name) {
                const q = query.toLowerCase();
                const n = name.toLowerCase();
                if (n === q) return -100;
                if (n.startsWith(q)) return -50;
                let last = -1, gaps = 0;
                for (const ch of q) {
                    const idx = n.indexOf(ch, last + 1);
                    if (idx === -1) return null;
                    gaps += Math.max(0, idx - last - 1);
                    last = idx;
                }
                return gaps + Math.abs(n.length - q.length);
            },

            get staffResults() {
                const q = this.staffQuery.trim();
                if (!q) return this.staff.slice(0, 8);
                return this.staff
                    .map((s) => ({ s, score: this.fuzzy(q, s.name + ' ' + (s.email || '')) }))
                    .filter((x) => x.score !== null)
                    .sort((a, b) => a.score - b.score)
                    .slice(0, 8)
                    .map((x) => x.s);
            },

            toggleStaff(id, on) {
                id = String(id);
                this.staffSelected = on
                    ? [...new Set([...this.staffSelected, id])]
                    : this.staffSelected.filter((x) => x !== id);
                this.sync();
            },

            get studentResults() {
                const q = this.studentQuery.trim();
                if (!q) return this.students.slice(0, 8);
                return this.students
                    .map((s) => ({ s, score: this.fuzzy(q, [s.name, s.email, s.school_id].filter(Boolean).join(' ')) }))
                    .filter((x) => x.score !== null)
                    .sort((a, b) => a.score - b.score)
                    .slice(0, 8)
                    .map((x) => x.s);
            },

            toggleStudent(id, on) {
                id = String(id);
                this.studentSelected = on
                    ? [...new Set([...this.studentSelected, id])]
                    : this.studentSelected.filter((x) => x !== id);
                this.sync();
            },

            spec() {
                if (this.mode === 'role') {
                    return {
                        mode: 'role',
                        role_id: Number(this.roleId) || null,
                        all: this.roleAll,
                        member_ids: this.roleAll ? [] : this.roleSelected.map(Number),
                    };
                }
                if (this.mode === 'staff') {
                    return { mode: 'staff', staff_ids: this.staffSelected.map(Number) };
                }
                if (this.mode === 'students') {
                    const s = { mode: 'students', scope: this.studentScope };
                    if (this.studentScope === 'level') s.level = this.studentLevel;
                    if (this.studentScope === 'class') s.section_id = Number(this.studentSection) || null;
                    if (this.studentScope === 'individuals') s.student_ids = this.studentSelected.map(Number);
                    return s;
                }
                return { mode: 'self' };
            },

            get summary() {
                if (this.mode === 'self') return this.summaryText('yourself', 1);
                if (this.mode === 'role') {
                    const role = this.roles.find((r) => String(r.id) === String(this.roleId));
                    if (!role) return '';
                    if (this.roleAll) return this.summaryText('Everyone in ' + role.name, role.member_count);
                    if (!this.roleSelected.length) return '';
                    const names = this.roleMemberOptions().filter((m) => this.roleSelected.includes(m.id)).map((m) => m.name);
                    return names.slice(0, 3).join(', ') + (names.length > 3 ? '…' : '');
                }
                if (this.mode === 'staff') {
                    if (!this.staffSelected.length) return '';
                    const names = this.staff.filter((s) => this.staffSelected.includes(String(s.id))).map((s) => s.name);
                    return names.slice(0, 3).join(', ') + (names.length > 3 ? '…' : '');
                }
                if (this.mode === 'students') {
                    if (this.studentScope === 'school') return this.summaryText('All students', this.students.length);
                    if (this.studentScope === 'level') return this.studentLevel ? this.summaryText('All students in ' + this.studentLevel, this.students.filter((s) => s.level === this.studentLevel).length) : '';
                    if (this.studentScope === 'class') {
                        const section = this.sections.find((s) => String(s.id) === String(this.studentSection));
                        return section ? this.summaryText('All students in ' + section.name, this.students.filter((s) => s.section === section.name).length) : '';
                    }
                    if (!this.studentSelected.length) return '';
                    const names = this.students.filter((s) => this.studentSelected.includes(String(s.id))).map((s) => s.name);
                    return names.slice(0, 3).join(', ') + (names.length > 3 ? '…' : '');
                }
                return '';
            },

            summaryText(label, count) {
                return label + (count > 1 ? ' (' + count + ')' : '');
            },

            sync() {
                this.specJson = JSON.stringify(this.spec());
                if (this.field && this.field.startsWith('taskForm.')) {
                    $wire.set(this.field, this.specJson);
                }
            },
        }));
    });
</script>