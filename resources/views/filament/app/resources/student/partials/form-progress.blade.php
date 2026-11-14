@include('filament.app.resources.student.partials.create-styles')

<div class="std-form-progress" x-data="studentFormProgress">
    <div class="std-form-progress-top">
        <span>{{ __('Required details') }}</span>
        <b><span x-text="done">0</span> / <span x-text="total">0</span> · <span x-text="percent()">0</span>%</b>
    </div>
    <div class="std-form-progress-track" role="progressbar" x-bind:aria-valuenow="percent()" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Required fields completed') }}">
        <i x-bind:style="`width: ${percent()}%`"></i>
    </div>
    <p>{{ __('Complete the required fields to create the student. Required fields are marked with an asterisk.') }}</p>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('studentFormProgress', () => ({
            total: 0,
            done: 0,
            observer: null,
            init() {
                this.$nextTick(() => this.bind());

                const form = this.$root.closest('form');

                if (form) {
                    this.observer = new MutationObserver(() => this.refresh());
                    this.observer.observe(form, { subtree: true, childList: true, attributes: true });
                }
            },
            bind() {
                const form = this.$root.closest('form');

                if (!form) {
                    return;
                }

                form.addEventListener('input', () => this.refresh());
                form.addEventListener('change', () => this.refresh());
                this.refresh();
            },
            refresh() {
                const form = this.$root.closest('form');

                if (!form) {
                    return;
                }

                const fields = Array.from(form.querySelectorAll('[required]')).filter((field) => {
                    if (field.disabled || field.type === 'hidden') {
                        return false;
                    }

                    return !field.closest('[style*="display: none"], [hidden]');
                });

                this.total = fields.length;
                this.done = fields.filter((field) => {
                    if (field.type === 'checkbox' || field.type === 'radio') {
                        return field.checked;
                    }

                    return String(field.value ?? '').trim() !== '';
                }).length;
            },
            percent() {
                return this.total > 0 ? Math.round((this.done / this.total) * 100) : 0;
            },
        }));
    });

    (() => {
        const bindErrorJump = () => {
            if (window.__studentCreateErrorJumpBound || !window.Livewire) {
                return;
            }

            window.__studentCreateErrorJumpBound = true;

            Livewire.hook('morph.updated', () => {
                if (!document.body.matches('[data-page="student-create"]')) {
                    return;
                }

                const firstError = document.querySelector('.fi-fo-field-wrp-error-message');

                if (!firstError) {
                    return;
                }

                const panel = firstError.closest('[role="tabpanel"]');

                if (panel && !panel.classList.contains('fi-active')) {
                    panel.dispatchEvent(new CustomEvent('expand', { bubbles: true }));
                }

                window.requestAnimationFrame(() => {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            });
        };

        document.addEventListener('livewire:init', bindErrorJump);

        if (window.Livewire) {
            bindErrorJump();
        }
    })();
</script>
