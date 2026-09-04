<div class="max-w-[85%] mx-auto py-10 px-2 sm:px-6 lg:px-4">
    @if($submitted)
    <!-- Success State -->
    <div class="bg-white shadow-sm sm:rounded-2xl p-10 text-center border border-gray-100">
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
            <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h2 class="text-3xl font-extrabold text-gray-900 mb-2">Pengajuan Berhasil Dikirim!</h2>
        <p class="text-gray-500 text-lg mb-8 max-w-xl mx-auto">Harap simpan kode pelacakan unik di bawah ini dengan aman. Anda akan membutuhkannya untuk mengecek status atau mengunduh surat terbitan Anda nanti.</p>

        <div class="inline-block bg-gray-50 border-2 border-dashed border-primary-200 rounded-xl px-10 py-6 mb-8">
            <p class="text-sm text-gray-500 uppercase tracking-widest font-bold mb-2">KODE PELACAKAN</p>
            <div class="text-5xl font-mono font-black text-primary-700 tracking-wider">
                {{ $trackingCode }}
            </div>
        </div>

        <div>
            <a href="{{ route('lacak') }}" class="inline-flex items-center px-8 py-3 border border-transparent text-lg font-bold rounded-xl shadow-sm shadow-primary-200 text-white bg-primary-600 hover:bg-primary-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Lacak Surat Sekarang
            </a>
        </div>
    </div>
    @else
    <!-- NEW Form Header matching the UI Design -->
    <div class="text-center mb-10">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-3">Ajukan Surat Baru</h1>
        <p class="text-gray-500 text-lg">Lengkapi langkah berikut untuk mengajukan surat baru</p>
    </div>

    <style>
        /* Customizing Filament's Native Wizard to match the wireframe */

        /* Add white background and pill shape strictly to the Stepper Header */
        .fi-sc-wizard-header {

            border: 1px solid #f3f4f6 !important;
            border-radius: 1rem !important;
            padding: 1.5rem 2rem !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important;

            justify-content: space-between !important;
            max-width: 90%;
            margin-left: auto;
            margin-right: auto;
            position: relative;
            gap: 0 !important;
        }

        /* Remove the static background line */
        .fi-sc-wizard-header::before {
            display: none !important;
        }

        /* Draw a reactive line from each step to the next */
        .fi-sc-wizard-header-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 1.25rem;
            /* Aligns with the vertical center of the circle icons */
            left: 50%;
            width: 100%;
            height: 2px;
            background-color: #e5e7eb;
            /* Inactive gray line */
            z-index: 1;
            transition: background-color 0.3s ease;
        }

        /* When a step is completed, the line connecting to the NEXT step turns primary */
        .fi-sc-wizard-header-step.fi-completed:not(:last-child)::after {
            background-color: var(--color-primary-600) !important;
        }

        /* Hide Filament's default separators between steps */
        .fi-sc-wizard-header-step-separator {
            display: none !important;
        }

        /* Make sure the steps sit above the line */
        .fi-sc-wizard-header-step {
            position: relative;
            z-index: 2;
            flex: 1;
            min-width: 200px;
            /* Gives each step enough breathing room */
        }

        /* Give the button a solid white background so it 'cuts' the line behind it */
        .fi-sc-wizard-header-step-btn {

            position: relative;
            z-index: 3;
            flex-direction: column !important;
            align-items: center !important;
            /* Forces icon and text to center horizontally */
            justify-content: center !important;
            gap: 0.5rem !important;
            margin: 0 auto;
            padding: 0 1rem !important;
            /* Gives space around the label so the line cuts cleanly */
            width: max-content !important;
        }

        /* Restyle the active and inactive states to match Primary colors */
        .fi-sc-wizard-header-step.fi-active .fi-sc-wizard-header-step-icon-ctn {
            background-color: var(--color-primary-600) !important;
            color: white !important;
        }

        .fi-sc-wizard-header-step.fi-active .fi-sc-wizard-header-step-number {
            color: white !important;
        }

        .fi-sc-wizard-header-step.fi-completed .fi-sc-wizard-header-step-icon-ctn {
            background-color: var(--color-secondary-500) !important;
            color: var(--color-white) !important;
        }

        .fi-sc-wizard-header-step:not(.fi-active):not(.fi-completed) .fi-sc-wizard-header-step-icon-ctn {
            background-color: var(--color-gray-200) !important;
            /* gray-200 */
            color: var(--color-neutral-500) !important;
            /* gray-500 */
        }

        .fi-sc-wizard-header-step:not(.fi-active):not(.fi-completed) .fi-sc-wizard-header-step-label{

            /* gray-200 */
            color: var(--color-neutral-400) !important;
            /* gray-500 */
        }

        /* Style the label text underneath */
        .fi-sc-wizard-header-step-text {

            text-align: center !important;
            width: 100% !important;
            vertical-align: top;
        }

        .fi-sc-wizard-header-step.fi-completed .fi-sc-wizard-header-step-label {
            color: gray !important;
        }

        .fi-sc-wizard-header-step.fi-completed .fi-sc-wizard-header-step-description {
            color: var(--color-neutral-500) !important;
        }

        .fi-sc-wizard-header-step-label {
            font-size: 1 rem !important;
            /* text-xs */
            font-weight: 700 !important;
            text-align: center !important;
            max-height: 1lh;

            justify-self: center !important;
            justify-content: baseline !important;
            display: block !important;
        }

        .fi-sc-wizard-header-step-description {
            min-height: 2lh;
            font-weight: 400 !important;
            text-align: center !important;
            max-width: 150px;
            vertical-align: top;
            display: flex;
            justify-self: center !important;
            justify-content: center !important;
        }

        /* Prevent step height collapse during Livewire morphs */
        .fi-sc-wizard-step.fi-active {
            min-height: 400px;
        }
        #guest-pengajuan-wrapper {
            min-height: 500px;
        }
    </style>

    <!-- Filament Form Render -->
    <!-- Notice we removed the extra white bg and borders here, letting Filament's Wizard card shine natively -->
    <div class="w-full" id="guest-pengajuan-wrapper" wire:key="guest-pengajuan-wrapper">
        <form wire:submit.prevent="submit" id="guest-pengajuan-form">
            {{ $this->form }}
        </form>
    </div>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            let savedScrollY = null;
            let isNavigatingWizard = false;

            // Detect when user clicks step navigation ("Lanjutkan", "Kembali", or wizard step headers)
            document.addEventListener('click', (e) => {
                const stepBtn = e.target.closest('.fi-sc-wizard-header-step-btn');
                const footerNav = e.target.closest('.fi-sc-wizard-footer');
                if (stepBtn || footerNav) {
                    isNavigatingWizard = true;
                }
            }, true);

            // Intercept Livewire commits on guest-pengajuan to lock scroll position during intra-step updates
            Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                const formEl = document.getElementById('guest-pengajuan-form');
                if (formEl && (!isNavigatingWizard)) {
                    savedScrollY = window.scrollY;
                }

                succeed(() => {
                    if (!isNavigatingWizard && savedScrollY !== null && savedScrollY > 0) {
                        const targetScroll = savedScrollY;
                        requestAnimationFrame(() => {
                            window.scrollTo({ top: targetScroll, behavior: 'instant' });
                        });
                        setTimeout(() => {
                            window.scrollTo({ top: targetScroll, behavior: 'instant' });
                        }, 50);
                        setTimeout(() => {
                            window.scrollTo({ top: targetScroll, behavior: 'instant' });
                        }, 150);
                    }
                    isNavigatingWizard = false;
                    savedScrollY = null;
                });

                fail(() => {
                    isNavigatingWizard = false;
                    savedScrollY = null;
                });
            });
        });
    </script>
</div>
