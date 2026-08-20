<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes" />
  <title>{{ __('Student Application · Lyceum') }}</title>
  <!-- Tailwind + fonts + flatpickr + confetti -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            // bespoke palette: deep indigo, soft violet, warm amber, fresh mint
            lyceum: {
              50: '#f5f3ff',
              100: '#ede9fe',
              200: '#ddd6fe',
              300: '#c4b5fd',
              400: '#a78bfa',
              500: '#8b5cf6',
              600: '#7c3aed',
              700: '#6d28d9',
              800: '#5b21b6',
              900: '#4c1d95',
            },
            amber: {
              50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d', 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706'
            },
            mint: {
              50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 300: '#6ee7b7', 400: '#34d399', 500: '#10b981'
            }
          }
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; overflow: hidden; }
    body { font-family: 'Inter', sans-serif; background: #f5f3ff; }

    .admission-studio-wrapper {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      /* unified background: deep indigo-to-violet gradient with soft glow */
      background: radial-gradient(circle at 30% 40%, #4c1d95 0%, #5b21b6 40%, #7c3aed 80%, #6d28d9 100%);
      background-size: 200% 200%;
      animation: ambient-shift 20s ease-in-out infinite alternate;
      position: relative;
    }
    @keyframes ambient-shift {
      0% { background-position: 0% 0%; }
      100% { background-position: 100% 100%; }
    }

    /* floating blurry blobs – softer, harmonious */
    .blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(90px);
      opacity: 0.35;
      pointer-events: none;
      z-index: 0;
    }
    .blob-1 { width: 500px; height: 500px; background: #fcd34d; top: -150px; left: -180px; opacity: 0.2; }
    .blob-2 { width: 600px; height: 600px; background: #a78bfa; bottom: -200px; right: -150px; opacity: 0.25; }
    .blob-3 { width: 350px; height: 350px; background: #34d399; top: 30%; right: -100px; opacity: 0.15; }

    /* glass card – lighter, high contrast */
    .light-glass-card {
      backdrop-filter: blur(28px) saturate(200%);
      -webkit-backdrop-filter: blur(28px) saturate(200%);
      background: rgba(255, 255, 255, 0.75);
      border: 1px solid rgba(255, 255, 255, 0.4);
      box-shadow: 0 40px 90px rgba(0, 0, 0, 0.25);
      border-radius: 36px;
      width: 100%;
      position: relative;
      z-index: 2;
    }

    /* inputs – clean, tactile */
    .studio-input-field, .studio-select-field {
      height: 56px;
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.92) !important;
      border: 1.5px solid rgba(139, 92, 246, 0.25);
      color: #1e1b4b !important;
      font-size: 15px;
      font-weight: 500;
      padding-left: 48px;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      width: 100%;
      box-shadow: 0 4px 14px rgba(91, 33, 182, 0.04);
    }
    .studio-input-field:focus, .studio-select-field:focus {
      border-color: #7c3aed;
      background: rgba(255, 255, 255, 1) !important;
      box-shadow: 0 0 0 5px rgba(124, 58, 237, 0.12), 0 8px 28px rgba(124, 58, 237, 0.06);
      transform: scale(1.01);
      outline: none;
    }
    .input-wrapper {
      position: relative;
      width: 100%;
    }
    .input-wrapper .field-icon {
      position: absolute;
      top: 19px;
      left: 18px;
      color: #6d28d9;
      font-size: 17px;
      line-height: 1;
      transition: color 0.3s ease;
      pointer-events: none;
      opacity: 0.6;
    }
    .studio-input-field:focus ~ .field-icon,
    .studio-select-field:focus ~ .field-icon {
      color: #7c3aed;
      opacity: 1;
    }

    /* error state */
    .input-error {
      border-color: #f43f5e !important;
      background: rgba(244, 63, 94, 0.06) !important;
      box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.12) !important;
    }
    .error-message {
      font-size: 11px;
      font-weight: 600;
      color: #f43f5e;
      margin-top: 5px;
      padding-left: 14px;
      display: flex;
      align-items: center;
      gap: 5px;
      animation: fadeSlide 0.2s ease-out;
    }
    @keyframes fadeSlide {
      0% { opacity: 0; transform: translateY(-5px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    /* animated gradient border – uses brand colours */
    .glow-border-wrap {
      position: relative;
      border-radius: 38px;
      padding: 3px;
      background: linear-gradient(135deg, rgba(124, 58, 237, 0.6), rgba(251, 191, 36, 0.5), rgba(52, 211, 153, 0.5), rgba(124, 58, 237, 0.6));
      background-size: 300% 300%;
      animation: gradient-spin 10s ease-in-out infinite;
      max-width: 46rem;
      width: 100%;
      z-index: 2;
    }
    @keyframes gradient-spin {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    /* morph button */
    .morph-btn {
      transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      min-width: 150px;
    }
    .morph-btn.loading {
      border-radius: 40px;
      background: #6d28d9 !important;
      box-shadow: none !important;
      pointer-events: none;
    }

    .no-scroll { overflow: hidden; height: 100vh; width: 100vw; }

    /* brand typography */
    .brand-name {
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 700;
      font-size: 2.2rem;
      letter-spacing: -0.02em;
      background: linear-gradient(135deg, #4c1d95, #7c3aed, #f59e0b);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .brand-sub {
      font-size: 0.8rem;
      font-weight: 500;
      color: #4c1d95;
      opacity: 0.8;
    }
    .step-badge {
      font-size: 0.65rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      color: #6d28d9;
      background: rgba(124, 58, 237, 0.08);
      padding: 0.25rem 1rem;
      border-radius: 40px;
      border: 1px solid rgba(124, 58, 237, 0.15);
    }
  </style>
</head>
<body class="no-scroll m-0 p-0 antialiased">
  <div class="admission-studio-wrapper" 
       x-data="admissionApp()" 
       x-init="init()"
       @mousemove="handleMouseMove($event)"
       @mouseleave="resetTilt()"
       :style="`transform: perspective(1200px) rotateX(${tiltX}deg) rotateY(${tiltY}deg); transition: transform 0.12s ease-out;`"
       style="transform-style: preserve-3d;">

    <!-- blurry blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <!-- main card -->
    <div class="glow-border-wrap" x-ref="appCard">
      <div class="light-glass-card p-7 sm:p-10 relative overflow-hidden flex flex-col" style="min-height: 500px;">

        <!-- mouse glow (violet-tinted) -->
        <div class="pointer-events-none absolute inset-0 opacity-40 transition-opacity duration-300"
             :style="`background: radial-gradient(460px circle at ${mouseGlowX}px ${mouseGlowY}px, rgba(124,58,237,0.12), transparent 80%);`"></div>

        <!-- ====== SUCCESS STATE ====== -->
        <div x-show="submitted" x-transition.opacity.duration.500ms class="text-center py-6 flex flex-col justify-center items-center h-full my-auto">
          <div class="mb-6 flex h-28 w-28 items-center justify-center rounded-full bg-mint-100 border-2 border-mint-300 shadow-[0_0_50px_rgba(52,211,153,0.2)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-mint-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-2">{{ __('Application Submitted!') }}</h3>
          <p class="text-sm text-slate-600 max-w-sm mx-auto leading-relaxed">{{ __('Your student record is securely saved. Use the ID below to track progress.') }}</p>
          <div class="mt-8 bg-white/80 backdrop-blur-sm border border-slate-200 rounded-2xl p-6 w-full max-w-sm shadow-sm mx-auto">
            <span class="text-[10px] font-black uppercase text-violet-600 tracking-wider block mb-1">{{ __('Student Reference') }}</span>
            <span class="text-xl font-bold font-mono text-slate-900 block tracking-widest" x-text="trackingNumber"></span>
            <button @click="copyTracking()" class="mt-4 w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-xs font-bold text-white rounded-xl transition flex items-center justify-center space-x-1.5">
              <span x-text="copied ? '✓ Copied' : '📋 Copy ID'"></span>
            </button>
          </div>
          <a href="#" class="mt-8 text-xs font-black uppercase tracking-wider text-slate-400 hover:text-slate-700 transition">{{ __('← Back to Home') }}</a>
        </div>

        <!-- ====== FORM ACTIVE ====== -->
        <div x-show="!submitted" class="flex flex-col h-full justify-between">
          <!-- header with brand – bigger, exact school name -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200/50 pb-4 mb-5 gap-1">
            <div>
              <h1 class="brand-name">{{ __('Schoolcore ERP') }}</h1>
              <p class="brand-sub text-sm">{{ __('Secure your future with us') }}</p>
            </div>
            <div class="flex items-center gap-3 mt-1 sm:mt-0">
              <span class="step-badge">{{ __('Admission Studio') }}</span>
              <div class="flex items-center space-x-2">
                <span :class="step >= 1 ? 'bg-violet-600 shadow-[0_0_14px_rgba(124,58,237,0.5)]' : 'bg-slate-300'" class="w-3.5 h-3.5 rounded-full transition-all"></span>
                <span :class="step >= 2 ? 'bg-violet-600 shadow-[0_0_14px_rgba(124,58,237,0.5)]' : 'bg-slate-200'" class="w-14 h-0.5 rounded-full block"></span>
                <span :class="step >= 2 ? 'bg-violet-600 shadow-[0_0_14px_rgba(124,58,237,0.5)]' : 'bg-slate-300'" class="w-3.5 h-3.5 rounded-full transition-all"></span>
              </div>
            </div>
          </div>

          <!-- ====== STEP 1 ====== -->
          <div x-show="step === 1" x-transition.opacity.duration.300ms class="space-y-5">
            <div class="flex items-center space-x-2 text-xs font-black uppercase tracking-wider text-violet-700">
              <span>{{ __('👤') }}</span> <span>{{ __('Student details') }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div class="input-wrapper">
                <span class="field-icon">{{ __('👤') }}</span>
                <input type="text" wire:model="first_name" x-model="firstName" placeholder="First name" 
                       class="studio-input-field" :class="errors.firstName ? 'input-error' : ''" @input="errors.firstName = false">
                <div class="error-message" x-show="errors.firstName" x-text="errors.firstName"></div>
              </div>
              <div class="input-wrapper">
                <span class="field-icon">{{ __('👤') }}</span>
                <input type="text" wire:model="last_name" x-model="lastName" placeholder="Last name" 
                       class="studio-input-field" :class="errors.lastName ? 'input-error' : ''" @input="errors.lastName = false">
                <div class="error-message" x-show="errors.lastName" x-text="errors.lastName"></div>
              </div>
              <div class="input-wrapper">
                <span class="field-icon">{{ __('⚧') }}</span>
                <select wire:model="gender" x-model="gender" class="studio-select-field" :class="errors.gender ? 'input-error' : ''" @change="errors.gender = false">
                  <option value="" disabled selected>{{ __('Gender') }}</option>
                  <option value="male">{{ __('Male') }}</option>
                  <option value="female">{{ __('Female') }}</option>
                  <option value="other">{{ __('Other') }}</option>
                </select>
                <div class="error-message" x-show="errors.gender" x-text="errors.gender"></div>
              </div>
              <div class="input-wrapper" x-init="flatpickr($refs.dob, { dateFormat: 'Y-m-d', maxDate: 'today' })">
                <span class="field-icon">{{ __('🎂') }}</span>
                <input x-ref="dob" type="text" wire:model="date_of_birth" x-model="dob" placeholder="Date of birth" 
                       class="studio-input-field" :class="errors.dob ? 'input-error' : ''" @input="errors.dob = false">
                <div class="error-message" x-show="errors.dob" x-text="errors.dob"></div>
              </div>
              <div class="input-wrapper col-span-1 md:col-span-2">
                <span class="field-icon">{{ __('🎓') }}</span>
                <select wire:model="course_id" x-model="courseId" class="studio-select-field" :class="errors.courseId ? 'input-error' : ''" @change="errors.courseId = false">
                    <option value="" disabled selected>{{ __('Select grade / level') }}</option>
                  @foreach ($courses as $courseId => $courseName)
                    <option value="{{ $courseId }}">{{ $courseName }}</option>
                  @endforeach
                </select>
                <div class="error-message" x-show="errors.courseId" x-text="errors.courseId"></div>
              </div>
            </div>
            <div class="flex justify-end pt-3 border-t border-slate-200/50">
              <button type="button" @click="validateStep1()" 
                      class="px-8 py-3.5 text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-md transition bg-violet-600 hover:bg-violet-500 shadow-lg hover:shadow-violet-200/30">
                <span>{{ __('Next') }}</span> <span class="ml-1">{{ __('→') }}</span>
              </button>
            </div>
          </div>

          <!-- ====== STEP 2 ====== -->
          <div x-show="step === 2" x-transition.opacity.duration.300ms class="space-y-5">
            <div class="flex items-center space-x-2 text-xs font-black uppercase tracking-wider text-violet-700">
              <span>{{ __('👨‍👩‍👧') }}</span> <span>{{ __('Guardian details') }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div class="input-wrapper col-span-1 md:col-span-2">
                <span class="field-icon">{{ __('👤') }}</span>
                <input type="text" wire:model="parent_name" x-model="parentName" placeholder="Full name" 
                       class="studio-input-field" :class="errors.parentName ? 'input-error' : ''" @input="errors.parentName = false">
                <div class="error-message" x-show="errors.parentName" x-text="errors.parentName"></div>
              </div>
              <div class="input-wrapper">
                <span class="field-icon">{{ __('📧') }}</span>
                <input type="email" wire:model="parent_email" x-model="parentEmail" placeholder="Email address" 
                       class="studio-input-field" :class="errors.parentEmail ? 'input-error' : ''" @input="errors.parentEmail = false">
                <div class="error-message" x-show="errors.parentEmail" x-text="errors.parentEmail"></div>
              </div>
              <div class="input-wrapper">
                <span class="field-icon">{{ __('📱') }}</span>
                <input type="text" wire:model="parent_phone" x-model="parentPhone" placeholder="Phone number" 
                       class="studio-input-field" :class="errors.parentPhone ? 'input-error' : ''" @input="errors.parentPhone = false">
                <div class="error-message" x-show="errors.parentPhone" x-text="errors.parentPhone"></div>
              </div>
            </div>
            <div class="flex justify-between items-center pt-3 border-t border-slate-200/50">
              <button type="button" @click="step = 1; clearErrors()" class="px-5 py-2.5 text-xs font-black uppercase tracking-wider text-slate-500 hover:text-slate-800 transition">{{ __('← Back') }}</button>
              <button type="button" @click="validateStep2()" 
                      class="morph-btn px-8 py-3.5 text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-md transition flex items-center justify-center gap-2 bg-gradient-to-r from-violet-600 to-amber-500 hover:from-violet-500 hover:to-amber-400 shadow-lg"
                      :class="{'loading': submitting}">
                <span x-show="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin inline-block"></span>
                <span x-text="submitLabel"></span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function admissionApp() {
      return {
        firstName: '', lastName: '', gender: '', dob: '', courseId: '',
        parentName: '', parentEmail: '', parentPhone: '',
        step: 1,
        submitted: false,
        submitting: false,
        submitLabel: 'Submit',
        trackingNumber: 'LYC-2026-9B3E',
        copied: false,
        tiltX: 0, tiltY: 0, mouseGlowX: 0, mouseGlowY: 0,

        errors: {
          firstName: false, lastName: false, gender: false, dob: false, courseId: false,
          parentName: false, parentEmail: false, parentPhone: false
        },

        validateStep1() {
          let valid = true;
          if (!this.firstName.trim()) { this.errors.firstName = 'First name is required'; valid = false; }
          if (!this.lastName.trim()) { this.errors.lastName = 'Last name is required'; valid = false; }
          if (!this.gender) { this.errors.gender = 'Please select a gender'; valid = false; }
          if (!this.dob.trim()) { this.errors.dob = 'Date of birth is required'; valid = false; }
          if (!this.courseId) { this.errors.courseId = 'Please select a grade'; valid = false; }
          if (valid) { this.clearErrors(); this.step = 2; } 
          else { this.$el.querySelector('.input-error')?.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
        },

        validateStep2() {
          let valid = true;
          if (!this.parentName.trim()) { this.errors.parentName = 'Guardian name is required'; valid = false; }
          const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRe.test(this.parentEmail)) { this.errors.parentEmail = 'Valid email is required'; valid = false; }
          if (!this.parentPhone.trim()) { this.errors.parentPhone = 'Phone number is required'; valid = false; }
          if (valid) { this.clearErrors(); this.startSubmission(); } 
          else { this.$el.querySelector('.input-error')?.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
        },

        clearErrors() { Object.keys(this.errors).forEach(k => this.errors[k] = false); },

        async startSubmission() {
            this.submitting = true;
          this.submitLabel = 'Verifying';
          setTimeout(() => { this.submitLabel = 'Syncing'; }, 500);
          setTimeout(() => { this.submitLabel = 'Finalizing'; }, 1000);
          setTimeout(async () => {
            try {
              await this.$wire.submit();
              this.trackingNumber = this.$wire.generatedTrackingNumber;
              this.submitted = true;
              this.triggerConfetti();
            } catch (error) {
              this.errors.parentPhone = 'We could not submit the application. Please check the details and try again.';
            } finally {
            this.submitting = false;
              this.submitLabel = 'Submit';
            }
          }, 1500);
        },

        triggerConfetti() {
          if (typeof confetti !== 'undefined') {
            confetti({ particleCount: 200, spread: 100, origin: { y: 0.5 }, colors: ['#8b5cf6','#fbbf24','#34d399','#f472b6','#60a5fa'] });
          }
        },

        copyTracking() {
          navigator.clipboard?.writeText(this.trackingNumber);
          this.copied = true;
          setTimeout(() => { this.copied = false; }, 2000);
        },

        handleMouseMove(e) {
          const card = this.$refs.appCard;
          if (!card) return;
          const rect = card.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;
          this.mouseGlowX = x;
          this.mouseGlowY = y;
          const cx = rect.width / 2, cy = rect.height / 2;
          this.tiltX = ((y - cy) / cy) * -2.6;
          this.tiltY = ((x - cx) / cx) * 2.6;
        },
        resetTilt() { this.tiltX = 0; this.tiltY = 0; },

        init() {
          document.documentElement.style.overflow = 'hidden';
          document.body.style.overflow = 'hidden';
        }
      }
    }
  </script>
</body>
</html>
