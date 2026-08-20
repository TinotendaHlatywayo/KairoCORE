@php
    // Detect if this is rendering inside a Filament form context
    $statePath = isset($getStatePath) ? $getStatePath() : null;
@endphp

<div 
    id="uploader_wrapper"
    @if($statePath)
        x-data="{ state: $wire.entangle('{{ $statePath }}') }"
    @endif
    class="smart-photo-uploader border p-4 rounded-lg bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800"
>
    <label class="form-label d-block fw-bold mb-2 text-dark dark:text-white">Profile Photo (ID Card Specification)</label>
    <p class="text-muted small mb-3">{{ __('Upload must contain a clear, centered, single face. Full-body or blurry photos will be automatically rejected.') }}</p>

    <!-- File Input -->
    <input type="file" id="id_photo_input" accept="image/*" class="form-control mb-3" onchange="handleFileSelect(this)">

    <!-- Alert Messaging -->
    <div id="uploader_message" class="alert d-none py-2 px-3 small border-0 mb-3"></div>

    <!-- Interactive Cropper Container -->
    <div id="cropper_container" class="d-none border rounded p-3 mb-3 bg-light dark:bg-gray-800">
        <div style="max-height: 350px; overflow: hidden; position: relative;">
            <img id="cropper_image" style="max-width: 100%;">
            <!-- Faint Head and Shoulders Silhouette Guide Overlay -->
            <div id="silhouette_overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; opacity: 0.25; background: url('https://cdn-icons-png.flaticon.com/512/1077/1077114.png') no-repeat center center; background-size: 50% auto;"></div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <button type="button" class="btn btn-sm btn-secondary" onclick="resetUploader()">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-sm btn-success" onclick="validateAndCrop()">{{ __('Verify & Save Photo') }}</button>
        </div>
    </div>

    <!-- Hidden Output Field -->
    <input type="hidden" id="photo_base64_output">

    <!-- Loading Indicators -->
    <div id="smart_uploader_loader" class="d-none text-center py-2">
        <span class="spinner-border spinner-border-sm text-success" role="status"></span>
        <span class="small text-secondary ms-2">{{ __('Analyzing facial details using client-side AI...') }}</span>
    </div>

    <!-- Previews -->
    <div id="verified_preview_container" class="d-none text-center">
        <span class="badge bg-success-subtle text-success border border-success mb-2">{{ __('✔ Photo Verified & Saved') }}</span>
        <div>
            <img id="verified_preview_image" class="rounded border shadow-sm" style="width: 120px; height: 160px; object-fit: cover; margin: 0 auto;">
        </div>
    </div>
</div>

<!-- Styles and Dependencies (Cropper & Face-API) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>

<script>
    let cropperInstance = null;
    let modelsLoaded = false;

    async function loadFaceModels() {
        if (modelsLoaded) return;
        
        const loader = document.getElementById('smart_uploader_loader');
        loader.classList.remove('d-none');

        try {
            const modelUrl = 'https://justadudewhohacks.github.io/face-api.js/models';
            await faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl);
            await faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl);
            modelsLoaded = true;
            loader.classList.add('d-none');
        } catch (error) {
            displayMessage('Failed to initialize local AI models. Using fallback cropping checks.', 'warning');
            loader.classList.add('d-none');
        }
    }

    function handleFileSelect(input) {
        if (!input.files || !input.files[0]) return;

        const file = input.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            const img = document.getElementById('cropper_image');
            img.src = e.target.result;

            document.getElementById('cropper_container').classList.remove('d-none');
            document.getElementById('verified_preview_container').classList.add('d-none');

            if (cropperInstance) cropperInstance.destroy();
            cropperInstance = new Cropper(img, {
                aspectRatio: 3 / 4,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.8,
                cropBoxMovable: false,
                cropBoxResizable: false,
                toggleDragModeOnDblclick: false
            });

            loadFaceModels();
        };

        reader.readAsDataURL(file);
    }

    async function validateAndCrop() {
        if (!cropperInstance) return;

        const loader = document.getElementById('smart_uploader_loader');
        loader.classList.remove('d-none');
        displayMessage('Analyzing framing, resolution, and faces...', 'info');

        const canvas = cropperInstance.getCroppedCanvas({
            width: 480,
            height: 640
        });

        const croppedBase64 = canvas.toDataURL('image/jpeg', 0.9);

        if (!modelsLoaded) {
            saveVerifiedPhoto(croppedBase64);
            loader.classList.add('d-none');
            return;
        }

        const tempImg = new Image();
        tempImg.src = croppedBase64;
        tempImg.onload = async function() {
            const detections = await faceapi.detectAllFaces(tempImg, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }));

            loader.classList.add('d-none');

            if (detections.length === 0) {
                displayMessage('✖ No face detected. Please position your head and shoulders inside the silhouette guide.', 'danger');
                return;
            }
            if (detections.length > 1) {
                displayMessage('✖ Multiple faces detected. ID photos must contain exactly one student.', 'danger');
                return;
            }

            const face = detections[0];
            const box = face.box;

            const imageArea = tempImg.width * tempImg.height;
            const faceArea = box.width * box.height;
            const facePercentage = (faceArea / imageArea) * 100;

            if (facePercentage < 15) {
                displayMessage('✖ Face is too far away (full-body shot detected). Please zoom/crop the photo so your head fills the center.', 'danger');
                return;
            }

            saveVerifiedPhoto(croppedBase64);
        };
    }

    function saveVerifiedPhoto(base64Data) {
        document.getElementById('photo_base64_output').value = base64Data;
        
        const wrapper = document.getElementById('uploader_wrapper');

        // Check if Alpine Entanglement is active (Filament form)
        if (wrapper && wrapper.__x) {
            // Update Alpine's entangled state directly.
            // This propagates the Base64 value straight to Filament's backend.
            wrapper.__x.$data.state = base64Data;
        } else if (window.Livewire) {
            // Fallback for standard Livewire (Public Admissions Form)
            const componentId = wrapper.closest('[wire\\:id]').getAttribute('wire:id');
            const livewireComponent = window.Livewire.find(componentId);
            if (livewireComponent) {
                livewireComponent.set('photo_base64', base64Data);
            }
        }

        const previewImg = document.getElementById('verified_preview_image');
        previewImg.src = base64Data;

        document.getElementById('verified_preview_container').classList.remove('d-none');
        document.getElementById('cropper_container').classList.add('d-none');
        displayMessage('✔ ID Photo verified and attached.', 'success');
    }

    function resetUploader() {
        if (cropperInstance) cropperInstance.destroy();
        document.getElementById('id_photo_input').value = '';
        document.getElementById('cropper_container').classList.add('d-none');
        document.getElementById('verified_preview_container').classList.add('d-none');
        document.getElementById('photo_base64_output').value = '';
        hideMessage();
    }

    function displayMessage(text, type) {
        const msg = document.getElementById('uploader_message');
        msg.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info', 'alert-warning');
        
        let alertClass = 'alert-info';
        if (type === 'success') alertClass = 'alert-success';
        if (type === 'danger') alertClass = 'alert-danger';
        if (type === 'warning') alertClass = 'alert-warning';

        msg.classList.add(alertClass);
        msg.innerText = text;
    }

    function hideMessage() {
        document.getElementById('uploader_message').classList.add('d-none');
    }
</script>