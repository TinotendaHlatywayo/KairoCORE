<div class="space-y-3">
    <!-- Component Heading -->
    <div class="flex items-center justify-between px-1">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ $getLabel() }}
        </span>
        <span class="text-[10px] font-mono px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded-full">
            {{ Micro-Interaction Preview }}
        </span>
    </div>

    <!-- Main Wrapper -->
    <div x-data="{
        state: 'idle', // idle, uploading, completed
        fileName: 'Passport_Photo.jpg',
        fileSize: '1.8 MB',
        showEnding: false,
        
        startUpload() {
            this.state = 'uploading';
            
            // Simulates smooth uploading sequence
            setTimeout(() => {
                this.state = 'completed';
                
                // Triggers high-end creator watermark ending sequence
                setTimeout(() => {
                    this.showEnding = true;
                    setTimeout(() => {
                        this.showEnding = false;
                        this.state = 'idle';
                    }, 2400);
                }, 2000);
            }, 2500);
        }
    }" class="relative w-full bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 shadow-sm overflow-hidden p-6 transition-all duration-300">
        
        <!-- Premium Creator Blackout Sequence -->
        <div x-show="showEnding" 
             x-transition:enter="transition opacity duration-500 ease-out"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition opacity duration-300 ease-in"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center z-50">
            <span class="text-white font-mono text-lg tracking-[0.25em] uppercase font-bold animate-pulse">@CODE.XR</span>
            <span class="text-[9px] text-slate-500 mt-1 tracking-widest uppercase font-mono">{{ Loop Sequence Reset }}</span>
        </div>

        <!-- Component Action Wrapper -->
        <div class="relative bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-100 dark:border-slate-900/60 p-4 min-h-[80px] flex items-center justify-between overflow-hidden transition-all duration-300">
            
            <!-- Orange Progress Backdrop Wave -->
            <div class="absolute inset-y-0 left-0 bg-orange-500 transition-all ease-out duration-[2500ms]"
                 :style="state === 'uploading' ? 'width: 100%' : 'width: 0%'"
                 :class="state === 'completed' ? 'opacity-0' : 'opacity-100'">
            </div>

            <!-- Dark Slate Completion Overlay -->
            <div class="absolute inset-0 bg-slate-900 dark:bg-slate-850 transition-opacity ease-out duration-500 pointer-events-none"
                 :class="state === 'completed' ? 'opacity-100' : 'opacity-0'">
            </div>

            <!-- Left Segment: Info details (moves left & fades during upload) -->
            <div class="flex items-center gap-3 z-10 transition-all duration-500 transform"
                 :class="state !== 'idle' ? 'opacity-0 -translate-x-6 pointer-events-none' : 'opacity-100 translate-x-0'">
                <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-slate-100 dark:border-slate-800 text-slate-500 dark:text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                    </svg>
                </div>
                <div class="text-left">
                    <p class="font-semibold text-slate-700 dark:text-slate-200 text-xs tracking-tight" x-text="fileName"></p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono" x-text="fileSize"></p>
                </div>
            </div>

            <!-- Right Segment: Action Area (Slides to center during progress) -->
            <div class="z-10 flex items-center justify-end transition-all duration-500"
                 :class="state !== 'idle' ? 'w-full justify-center absolute inset-0 px-4' : ''">
                
                <!-- Idle Action Trigger -->
                <button type="button" 
                        x-show="state === 'idle'" 
                        @click="startUpload()"
                        class="bg-orange-500 hover:bg-orange-600 active:scale-[0.97] text-white font-semibold text-xs px-4.5 py-2.5 rounded-lg transition-all duration-150 flex items-center gap-1.5 shadow-sm cursor-pointer select-none">
                    <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    <span>{{ Upload }}</span>
                </button>

                <!-- Dynamic Progress Spinner -->
                <div x-show="state === 'uploading'" 
                     x-transition:enter="transition opacity duration-300"
                     class="flex items-center gap-2 text-white font-semibold">
                    <svg class="animate-spin h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs tracking-wide">{{ Uploading... }}</span>
                </div>

                <!-- Finished Spring State -->
                <div x-show="state === 'completed'" 
                     x-transition:enter="transition transform duration-500 cubic-bezier(0.34, 1.56, 0.64, 1)"
                     x-transition:enter-start="scale-75 opacity-0"
                     x-transition:enter-end="scale-100 opacity-100"
                     class="flex items-center gap-2 text-white font-semibold">
                    <div class="bg-emerald-500 p-1 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.5)] flex items-center justify-center">
                        <svg class="h-3 w-3 text-white stroke-[3.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="text-xs tracking-wide">{{ Completed }}</span>
                </div>
            </div>
        </div>

        <!-- Code Window Display block -->
        <div class="mt-5 bg-[#0B0F19] rounded-xl border border-slate-800/80 shadow-lg overflow-hidden text-left font-mono text-[11px] select-none">
            <!-- Console Header bar -->
            <div class="bg-[#111827] px-4 py-2.5 border-b border-slate-800/80 flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <div class="w-2 h-2 rounded-full bg-rose-500/80"></div>
                    <div class="w-2 h-2 rounded-full bg-amber-500/80"></div>
                    <div class="w-2 h-2 rounded-full bg-emerald-500/80"></div>
                </div>
                <div class="text-slate-400 text-[9px] font-sans">{{ PremiumUpload.tsx }}</div>
                <div class="w-10"></div>
            </div>
            
            <div class="p-4 overflow-x-auto text-slate-300 leading-relaxed max-h-[160px] overflow-y-auto">
                @verbatim
                <pre><code><span class="text-purple-400">{{ import }}</span> { motion, AnimatePresence } <span class="text-purple-400">{{ from }}</span> <span class="text-emerald-400">'framer-motion'</span>{{ ; }}
<span class="text-purple-400">{{ import }}</span> { useState } <span class="text-purple-400">{{ from }}</span> <span class="text-emerald-400">'react'</span>{{ ; }}

<span class="text-purple-400">{{ export default function }}</span> <span class="text-blue-400">{{ PremiumUpload }}</span>() {
  <span class="text-purple-400">{{ const }}</span> [state, setState] = <span class="text-blue-400">{{ useState }}</span>(<span class="text-emerald-400">'idle'</span>);

  <span class="text-purple-400">{{ return }}</span> (
    &lt;<span class="text-rose-400">{{ motion.div }}</span> <span class="text-amber-400">{{ className }}</span>=<span class="text-emerald-400">"relative bg-white rounded-2xl p-6"</span>{{ &gt;
      &lt; }}<span class="text-rose-400">{{ AnimatePresence }}</span>&gt;
        {state === <span class="text-emerald-400">'uploading'</span> && (
          &lt;<span class="text-rose-400">{{ motion.div }}</span>
            <span class="text-amber-400">{{ initial }}</span>={{ width: 0 }}
            <span class="text-amber-400">{{ animate }}</span>={{ width: <span class="text-emerald-400">'100%'</span> }}
            <span class="text-amber-400">{{ transition }}</span>={{ type: <span class="text-emerald-400">'spring'</span>, damping: 25 }}
            <span class="text-amber-400">{{ className }}</span>=<span class="text-emerald-400">"absolute inset-0 bg-orange-500"</span>
          /&gt;
        )}
      &lt;/<span class="text-rose-400">{{ AnimatePresence }}</span>{{ &gt;
    &lt;/ }}<span class="text-rose-400">{{ motion.div }}</span>&gt;
  );
}</code></pre>
                @endverbatim
            </div>
        </div>

    </div>
</div>