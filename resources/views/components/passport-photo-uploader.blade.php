@php
    $wireMethod = $wireMethod ?? 'savePhoto';
    $aspectOptions = $aspectOptions ?? [
        ['value' => 0.75, 'label' => 'Passport 3:4'],
        ['value' => 0.8, 'label' => 'Passport 4:5'],
        ['value' => 1.0, 'label' => 'Square 1:1'],
    ];
    $defaultAspect = $defaultAspect ?? 0.75;
    $currentPhoto = $currentPhoto ?? null;
    $placeholder = $placeholder ?? null;
    $hasPhoto = $hasPhoto ?? false;
    $label = $label ?? __('Profile Photo');
    $hint = $hint ?? __('Upload a clear, centered, single face. Full-body, selfie or blurry photos are rejected automatically.');
@endphp

<div
    x-data="passportPhotoUploader({
        wireMethod: @js($wireMethod),
        aspectOptions: @js($aspectOptions),
        defaultAspect: @js($defaultAspect),
        hasPhoto: @js($hasPhoto),
        currentPhoto: @js($currentPhoto),
        placeholder: @js($placeholder),
    })"
    class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-950/40"
>
    <div class="flex items-start gap-4">
        <div class="shrink-0">
            <div class="flex h-24 w-20 items-center justify-center overflow-hidden rounded-lg ring-2 ring-slate-200 dark:ring-slate-700">
                <template x-if="!preview && currentPhoto">
                    <img :src="currentPhoto" alt="{{ __('Current photo') }}" class="h-full w-full object-cover">
                </template>
                <template x-if="!preview && !currentPhoto">
                    <template x-if="placeholder">
                        <img :src="placeholder" alt="{{ __('Default photo') }}" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!placeholder">
                        <div class="flex h-full w-full items-center justify-center bg-slate-100 dark:bg-slate-800">
                            <x-heroicon-o-user class="h-8 w-8 text-slate-300 dark:text-slate-600"/>
                        </div>
                    </template>
                </template>
                <template x-if="preview">
                    <img :src="preview" alt="{{ __('Preview') }}" class="h-full w-full object-cover">
                </template>
            </div>
        </div>

        <div class="min-w-0 flex-1 space-y-3">
            <div>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $label }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ $hint }}</p>
            </div>

            <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                @change="onSelect($event)"
                class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-600 file:px-3 file:py-1.5 file:text-[10px] file:font-bold file:text-white dark:text-slate-300"
            />

            <template x-if="error">
                <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 dark:border-rose-900 dark:bg-rose-950/30">
                    <p class="text-xs font-bold text-rose-700 dark:text-rose-300" x-text="error"></p>
                </div>
            </template>

            {{-- Crop + validation modal --}}
            <div x-show="stage === 'crop'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
                <div class="w-full max-w-2xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Adjust Photo') }}</h3>
                        <button type="button" @click="closeCropper()" class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300">
                            <x-heroicon-o-x-mark class="h-5 w-5"/>
                        </button>
                    </div>
                    <div class="p-4">
                        <div class="relative overflow-hidden rounded-lg bg-slate-900" style="max-height: 380px;">
                            <img x-ref="cropImg" alt="{{ __('Photo to crop') }}" class="max-w-full">
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ __('Aspect Ratio') }}</span>
                            <template x-for="option in aspectOptions" :key="option.value">
                                <button
                                    type="button"
                                    @click="setAspect(option.value)"
                                    class="rounded-md px-2.5 py-1 text-[11px] font-bold"
                                    :class="aspect === option.value
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                                    x-text="option.label"
                                ></button>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-4 py-3 dark:border-slate-800">
                        <button type="button" @click="closeCropper()" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="button"
                            @click="validateAndSave()"
                            :disabled="validating"
                            class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span x-show="validating" class="h-3 w-3 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            <span x-text="validating ? '{{ __('Validating…') }}' : '{{ __('Verify & Save') }}'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <template x-if="stage === 'review'">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900 dark:bg-emerald-950/30">
                    <p class="text-xs font-bold text-emerald-700 dark:text-emerald-300">{{ __('Photo verified') }}</p>
                    <p class="mt-0.5 text-[11px] text-emerald-600 dark:text-emerald-400">{{ __('Single clear face detected. Ready to save.') }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="upload()"
                            :disabled="uploading"
                            class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span x-show="uploading" class="h-3 w-3 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            <span x-text="uploading ? '{{ __('Uploading…') }}' : '{{ __('Save Photo') }}'"></span>
                        </button>
                        <button type="button" @click="reset()" class="text-[11px] font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            </template>

            <p x-show="processing" x-cloak class="text-[11px] text-indigo-500">{{ __('Processing image…') }}</p>
        </div>
    </div>
</div>

@script
<script>
    if (! window.passportPhotoUploaderRegistered) {
        window.passportPhotoUploaderRegistered = true;

        window.__passportPhotoUploaderFactory = (config) => ({
            wireMethod: config.wireMethod || 'savePhoto',
            aspectOptions: config.aspectOptions || [{ value: 0.75, label: 'Passport 3:4' }],
            defaultAspect: config.defaultAspect || 0.75,
            hasPhoto: config.hasPhoto || false,
            preview: null,
            stage: 'idle',
            aspect: null,
            cropper: null,
            processing: false,
            validating: false,
            uploading: false,
            error: null,
            dataUrl: null,
            currentPhoto: config.currentPhoto || null,
            placeholder: config.placeholder || null,
            faceApiReady: false,

            init() {
                this.aspect = this.defaultAspect;
                if (! this.currentPhoto && this.$root) {
                    this.currentPhoto = this.$root.querySelector('img')?.src ?? null;
                }
            },

            onSelect(event) {
                const file = event.target.files[0];
                if (! file) return;

                this.error = null;
                this.stage = 'idle';
                this.dataUrl = null;

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.openCropper(e.target.result);
                };
                reader.readAsDataURL(file);
            },

            openCropper(dataUrl) {
                this.loadDependencies().then(() => {
                    this.stage = 'crop';
                    this.preview = dataUrl;
                    this.processing = true;

                    requestAnimationFrame(() => {
                        const img = this.$refs.cropImg;
                        img.src = dataUrl;
                        img.onload = () => {
                            this.processing = false;
                            if (this.cropper) this.cropper.destroy();
                            this.cropper = new Cropper(img, {
                                aspectRatio: this.aspect,
                                viewMode: 1,
                                dragMode: 'move',
                                autoCropArea: 0.75,
                                cropBoxMovable: false,
                                cropBoxResizable: false,
                                toggleDragModeOnDblclick: false,
                            });
                        };
                    });
                }).catch(() => {
                    this.error = @js(__('Unable to load the photo editor. Please check your internet connection and try again.'));
                });
            },

            setAspect(value) {
                this.aspect = value;
                if (this.cropper) this.cropper.setAspectRatio(value);
            },

            closeCropper() {
                this.stage = 'idle';
                if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
                this.error = null;
            },

            async loadDependencies() {
                if (window.Cropper && window.faceapi) return;
                if (window.__photoDepsLoading) return window.__photoDepsLoading;

                window.__photoDepsLoading = new Promise((resolve, reject) => {
                    const styles = document.createElement('link');
                    styles.rel = 'stylesheet';
                    styles.href = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css';
                    document.head.appendChild(styles);

                    const cropperJs = document.createElement('script');
                    cropperJs.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js';
                    const faceApi = document.createElement('script');
                    faceApi.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js';

                    let loaded = 0;
                    const check = () => {
                        loaded++;
                        if (loaded === 2) resolve();
                    };
                    cropperJs.onload = check;
                    faceApi.onload = check;
                    cropperJs.onerror = () => reject(new Error('cropper'));
                    faceApi.onerror = () => reject(new Error('face-api'));

                    document.head.appendChild(cropperJs);
                    document.head.appendChild(faceApi);
                });

                return window.__photoDepsLoading;
            },

            async validateAndSave() {
                if (! this.cropper) return;

                this.validating = true;
                this.error = null;

                try {
                    const canvas = this.cropper.getCroppedCanvas({
                        width: 480,
                        height: 640,
                        imageSmoothingQuality: 'high',
                    });
                    const croppedBase64 = canvas.toDataURL('image/jpeg', 0.9);

                    await this.ensureFaceModels();

                    if (window.faceapi && this.faceApiReady) {
                        const check = await this.validateFace(croppedBase64);
                        if (! check.ok) {
                            this.error = check.message;
                            this.validating = false;
                            return;
                        }
                    }

                    this.dataUrl = croppedBase64;
                    this.preview = croppedBase64;
                    this.stage = 'review';
                    this.closeCropper();
                } catch (e) {
                    this.error = @js(__('The photo could not be validated. Please try a clearer image.'));
                } finally {
                    this.validating = false;
                }
            },

            async ensureFaceModels() {
                if (! window.faceapi || this.faceApiReady) return;

                try {
                    const modelUrl = 'https://justadudewhohacks.github.io/face-api.js/models';
                    await faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl);
                    await faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl);
                    this.faceApiReady = true;
                } catch (e) {
                    this.faceApiReady = false;
                }
            },

            async validateFace(dataUrl) {
                const img = new Image();
                await new Promise((resolve) => { img.onload = resolve; img.src = dataUrl; });

                const detections = await faceapi.detectAllFaces(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }));

                if (detections.length === 0) {
                    return { ok: false, message: @js(__('No face detected. Please position your head and shoulders inside the frame.')) };
                }
                if (detections.length > 1) {
                    return { ok: false, message: @js(__('Multiple faces detected. ID photos must contain exactly one person.')) };
                }

                const box = detections[0].box;
                const faceArea = box.width * box.height;
                const imageArea = img.width * img.height;
                const facePercentage = (faceArea / imageArea) * 100;

                if (facePercentage < 14) {
                    return { ok: false, message: @js(__('The face is too small (a full-body or group shot was detected). Please crop so your head fills the center.')), };
                }
                if (facePercentage > 60) {
                    return { ok: false, message: @js(__('The face fills too much of the frame (a selfie was detected). Please step back so your head and shoulders are visible.')), };
                }

                return { ok: true, message: null };
            },

            upload() {
                if (! this.dataUrl || this.uploading) return;
                this.uploading = true;
                $wire[this.wireMethod](this.dataUrl)
                    .then(() => { this.reset(); this.uploading = false; })
                    .catch(() => { this.uploading = false; });
            },

            reset() {
                this.preview = null;
                this.stage = 'idle';
                this.dataUrl = null;
                this.error = null;
                if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
                const input = this.$root.querySelector('input[type="file"]');
                if (input) input.value = '';
            },
        });

        const registerPhotoUploader = () => {
            if (window.Alpine && typeof window.Alpine.data === 'function') {
                try { window.Alpine.data('passportPhotoUploader', window.__passportPhotoUploaderFactory); } catch (e) {}
            }
        };

        if (window.Alpine && typeof window.Alpine.data === 'function') {
            registerPhotoUploader();
        } else {
            document.addEventListener('alpine:init', registerPhotoUploader);
        }
    }
</script>
@endscript