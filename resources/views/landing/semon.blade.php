<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SEMON 2026 - Portal Sensus Ekonomi</title>

    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Leaflet.js for Boundary Mapping -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Tailwind CSS (via Vite) & Alpine.js -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }

        .leaflet-container {
            background-color: #fff7ed !important;
            /* very soft orange/white bg */
        }

        /* Custom Tooltip Styling */
        .map-tooltip {
            background: rgba(255, 255, 255, 0.98) !important;
            border: 1px solid rgba(249, 115, 22, 0.25) !important;
            border-radius: 12px !important;
            color: #1f2937 !important;
            font-family: 'Inter', sans-serif !important;
            box-shadow: 0 10px 25px -5px rgba(249, 115, 22, 0.1), 0 8px 10px -6px rgba(249, 115, 22, 0.1) !important;
            padding: 10px 14px !important;
        }

        .map-tooltip::before {
            border-top-color: rgba(255, 255, 255, 0.98) !important;
        }

        /* Cinematic Layer Transitions */
        .panel-transition {
            transition: opacity 700ms cubic-bezier(0.25, 1, 0.5, 1),
                transform 700ms cubic-bezier(0.25, 1, 0.5, 1),
                filter 700ms cubic-bezier(0.25, 1, 0.5, 1);
            will-change: opacity, transform, filter;
        }

        .panel-active {
            opacity: 1 !important;
            transform: scale(1) !important;
            filter: blur(0px) !important;
            pointer-events: auto !important;
        }

        .panel-inactive {
            opacity: 0 !important;
            transform: scale(0.95) !important;
            filter: blur(8px) !important;
            pointer-events: none !important;
        }

        /* Remove default focus outline border box on Leaflet path layers */
        path.leaflet-interactive:focus {
            outline: none !important;
        }

        .leaflet-container :focus {
            outline: none !important;
        }

        /* Static Soft Radial Highlight Overlay (No Animation) */
        .static-radial-overlay {
            background: radial-gradient(circle at 50% 50%, rgba(251, 146, 60, 0.08) 0%, rgba(255, 255, 255, 0) 70%);
        }

        /* Floating soft glass bubbles style */
        .hero-bubbles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .bubble-item {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            background: radial-gradient(circle at 30% 30%, rgba(251, 146, 60, 0.45) 0%, rgba(255, 255, 255, 0.05) 50%, rgba(251, 146, 60, 0.15) 100%);
            border: 1.5px solid rgba(255, 255, 255, 0.45);
            box-shadow: inset 0 20px 30px rgba(255, 255, 255, 0.35),
                        inset 15px 0 35px rgba(251, 146, 60, 0.2),
                        inset -15px 0 35px rgba(255, 255, 255, 0.25),
                        inset 0 -20px 30px rgba(0, 0, 0, 0.03),
                        0 20px 40px rgba(251, 146, 60, 0.08);
            will-change: transform, opacity;
        }

        /* GPU friendly floating animations */
        @keyframes float-bubble-1 {
            0% {
                transform: translateY(105vh) translateX(0) scale(0.85);
                opacity: 0;
            }
            8% {
                opacity: 0.25;
            }
            50% {
                transform: translateY(50vh) translateX(30px) scale(1.1);
                opacity: 0.45;
            }
            92% {
                opacity: 0.25;
            }
            100% {
                transform: translateY(-20vh) translateX(-15px) scale(0.85);
                opacity: 0;
            }
        }

        @keyframes float-bubble-2 {
            0% {
                transform: translateY(105vh) translateX(0) scale(1.1);
                opacity: 0;
            }
            12% {
                opacity: 0.3;
            }
            45% {
                transform: translateY(55vh) translateX(-35px) scale(0.85);
                opacity: 0.5;
            }
            88% {
                opacity: 0.25;
            }
            100% {
                transform: translateY(-20vh) translateX(25px) scale(1.15);
                opacity: 0;
            }
        }

        .bubble-1 {
            width: 140px;
            height: 140px;
            left: 5%;
            animation: float-bubble-1 28s infinite linear;
        }
        .bubble-2 {
            width: 90px;
            height: 90px;
            left: 22%;
            animation: float-bubble-2 22s infinite linear;
            animation-delay: -3s;
        }
        .bubble-3 {
            width: 190px;
            height: 190px;
            left: 40%;
            animation: float-bubble-1 35s infinite linear;
            animation-delay: -7s;
        }
        .bubble-4 {
            width: 110px;
            height: 110px;
            left: 58%;
            animation: float-bubble-2 24s infinite linear;
            animation-delay: -11s;
        }
        .bubble-5 {
            width: 160px;
            height: 160px;
            left: 72%;
            animation: float-bubble-1 31s infinite linear;
            animation-delay: -5s;
        }
        .bubble-6 {
            width: 80px;
            height: 80px;
            left: 85%;
            animation: float-bubble-2 20s infinite linear;
            animation-delay: -2s;
        }
        .bubble-7 {
            width: 130px;
            height: 130px;
            left: 15%;
            animation: float-bubble-1 32s infinite linear;
            animation-delay: -15s;
        }
        .bubble-8 {
            width: 220px;
            height: 220px;
            left: 50%;
            animation: float-bubble-2 40s infinite linear;
            animation-delay: -18s;
        }
        .bubble-9 {
            width: 70px;
            height: 70px;
            left: 33%;
            animation: float-bubble-1 18s infinite linear;
            animation-delay: -9s;
        }
        .bubble-10 {
            width: 120px;
            height: 120px;
            left: 65%;
            animation: float-bubble-2 26s infinite linear;
            animation-delay: -14s;
        }
        .bubble-11 {
            width: 100px;
            height: 100px;
            left: 80%;
            animation: float-bubble-1 21s infinite linear;
            animation-delay: -6s;
        }
        .bubble-12 {
            width: 150px;
            height: 150px;
            left: 92%;
            animation: float-bubble-2 33s infinite linear;
            animation-delay: -12s;
        }

        /* Interactive cursor bubble styles */
        .cursor-bubble {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            background: radial-gradient(circle at 30% 30%, rgba(251, 146, 60, 0.5) 0%, rgba(255, 255, 255, 0.1) 60%, rgba(251, 146, 60, 0.15) 100%);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.3),
                        0 4px 8px rgba(251, 146, 60, 0.08);
            animation: cursor-float 1.2s cubic-bezier(0.1, 0.8, 0.3, 1) forwards;
            will-change: transform, opacity;
            z-index: 1;
        }

        @keyframes cursor-float {
            0% {
                transform: translateY(0) scale(0.6);
                opacity: 0;
            }
            15% {
                opacity: 0.75;
                transform: translateY(-10px) scale(1);
            }
            100% {
                transform: translateY(-80px) translateX(var(--drift-x, 0px)) scale(0.4);
                opacity: 0;
            }
        }
    </style>
</head>

<body
    class="h-full overflow-hidden bg-white font-sans text-gray-800 antialiased selection:bg-orange-500 selection:text-white">

    <!-- Single Page App Wrapper -->
    <div id="landing-wrapper" data-target-date="{{ $targetDate }}" x-data="semonLanding" class="relative w-full h-full overflow-hidden select-none">

        <!-- Static Layered Background System (100% stable, no CPU animation loop) -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden z-[-10] bg-gradient-to-br from-orange-50 via-white to-orange-100">
            <div class="absolute inset-0 static-radial-overlay"></div>
            
            <!-- Home Panel Static Tint -->
            <div x-show="currentPanel === 'home'" x-transition class="absolute inset-0 bg-orange-50/10"></div>
            
            <!-- Map Panel Static Tint -->
            <div x-show="currentPanel === 'map'" x-transition class="absolute inset-0 bg-white/5"></div>
            
            <!-- Login Panel Static Tint -->
            <div x-show="currentPanel === 'login'" x-transition class="absolute inset-0 bg-orange-100/10 backdrop-blur-sm"></div>
        </div>

        <!-- ========================================== -->
        <!-- GEOSPATIAL MAP PANEL (Static In-Place Layer)-->
        <!-- ========================================== -->
        <section id="mapPanel" x-show="currentPanel === 'map'" x-cloak
            x-transition:enter="transition-opacity duration-700 ease-out"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-700 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 w-full h-full flex flex-col">

            <!-- Map Top Navbar (UI overlay - z-30) -->
            <div
                class="px-6 py-4 bg-white/95 backdrop-blur-md border-b border-orange-100 flex items-center justify-between z-30 shadow-sm relative">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7">
                        </path>
                    </svg>
                    <span class="font-extrabold text-base tracking-wide text-gray-900">SE-MON GEOSPATIAL MAP</span>
                </div>
                <button @click="currentPanel = 'home'"
                    class="px-4 py-2 text-xs font-bold text-gray-700 bg-white border border-orange-200 hover:text-orange-600 hover:bg-orange-50 rounded-xl transition duration-150 flex items-center gap-1 shadow-sm">
                    <svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali Ke Beranda
                </button>
            </div>

            <!-- Map View Area -->
            <div class="flex-1 relative z-10">
                <!-- Map Container (map - z-0) -->
                <div id="leafletMap" class="w-full h-full relative z-0"></div>

                <!-- Legend Overlay (legend - z-10) -->
                <div
                    class="absolute bottom-6 left-6 p-4 bg-white/95 backdrop-blur-md border border-orange-100 rounded-2xl z-10 text-xs shadow-lg flex flex-col gap-2.5 min-w-[160px] pointer-events-auto">
                    <span class="font-bold text-gray-800 border-b border-orange-100 pb-1.5">Skala Progres</span>
                    <div class="flex items-stretch gap-3 h-32">
                        <!-- Gradient Bar -->
                        <div class="w-4 rounded-md shadow-inner border border-orange-200/50"
                            style="background: linear-gradient(to top, #FFF7ED, #FED7AA, #FB923C, #F97316, #EA580C);"></div>
                        <!-- Labels -->
                        <div class="flex flex-col justify-between text-[10px] text-gray-600 font-semibold py-0.5">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#EA580C]"></span>100% (Selesai)</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#FB923C]"></span>50% (Sedang)</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#FFF7ED] border border-orange-200"></span>0% (Mulai)</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Village Breakdown Panel (modal - z-20) -->
                <div x-show="showKecModal" x-cloak
                    class="absolute inset-x-0 bottom-4 mx-auto w-[92vw] max-w-[420px] max-h-[72dvh] h-auto min-h-0 md:left-auto md:right-6 md:top-6 md:bottom-6 md:inset-x-auto md:mx-0 md:w-96 md:max-w-none md:max-h-none md:h-auto md:min-h-0 bg-white/95 backdrop-blur-lg border border-orange-100 rounded-3xl z-20 shadow-2xl flex flex-col p-4 md:p-6 overflow-hidden pointer-events-auto pb-[env(safe-area-inset-bottom,0px)]"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-8 md:translate-y-0 md:translate-x-8"
                    x-transition:enter-end="opacity-100 translate-y-0 md:translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 md:translate-x-0"
                    x-transition:leave-end="opacity-0 translate-y-8 md:translate-y-0 md:translate-x-8">
                    <div class="sticky top-0 bg-white/95 backdrop-blur-lg z-10 flex items-center justify-between border-b border-orange-100 pb-3 md:pb-4 mb-3 md:mb-4">
                        <div>
                            <span class="text-[10px] font-extrabold text-orange-600 uppercase tracking-widest">Detail
                                Wilayah</span>
                            <h4 class="text-base font-extrabold text-gray-900 mt-0.5" x-text="'Kec. ' + activeKec"></h4>
                        </div>
                        <button @click="showKecModal = false"
                            class="p-1.5 bg-orange-50 border border-orange-200 text-orange-500 hover:text-orange-700 hover:bg-orange-100 rounded-xl transition duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Village breakdown list -->
                    <div class="flex-1 overflow-y-auto space-y-2 md:space-y-3.5 pr-1">
                        <template x-if="kecBreakdown.length === 0">
                            <div class="flex flex-col items-center justify-center text-center h-full gap-2">
                                <span
                                    class="w-6 h-6 border-2 border-orange-500 border-t-transparent rounded-full animate-spin"></span>
                                <span class="text-xs text-gray-500">Memuat breakdown Desa...</span>
                            </div>
                        </template>
                        <template x-for="village in kecBreakdown">
                            <div
                                class="p-2.5 md:p-3 bg-orange-50/60 border border-orange-100/80 rounded-2xl flex flex-col gap-1.5 md:gap-2">
                                <div class="flex items-center justify-between gap-2 min-w-0">
                                    <span class="text-[11px] md:text-xs font-bold text-gray-800 truncate flex-1 min-w-0"
                                        x-text="village.nmdesa"></span>
                                    <span class="text-[11px] md:text-xs font-extrabold text-orange-600 shrink-0"
                                        x-text="Number(village.progress).toFixed(1) + '%'"></span>
                                </div>
                                <div class="w-full bg-orange-100 rounded-full h-1 overflow-hidden">
                                    <div class="h-1 rounded-full bg-orange-500"
                                        :style="'width: ' + Math.min(100, village.progress) + '%'"></div>
                                </div>
                                <div class="flex items-center justify-between text-[9px] md:text-[10px] text-gray-500 font-semibold">
                                    <span
                                        x-text="'Realisasi: ' + Number(village.realisasi).toLocaleString('id-ID')"></span>
                                    <span x-text="'Target: ' + Number(village.target).toLocaleString('id-ID')"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- HOME PANEL (Welcome Panel, Center)         -->
        <!-- ========================================== -->
        <section id="homePanel" x-show="currentPanel === 'home'" x-cloak x-transition:enter="panel-transition"
            x-transition:enter-start="panel-inactive" x-transition:enter-end="panel-active"
            x-transition:leave="panel-transition" x-transition:leave-start="panel-active"
            x-transition:leave-end="panel-inactive"
            class="absolute inset-0 w-full h-full flex flex-col justify-between py-[clamp(0.5rem,2.5vh,2rem)] px-[clamp(1rem,4vw,2.5rem)] md:py-12 md:px-12 overflow-hidden">

            <!-- Floating Hero Bubbles Background Layer -->
            <div class="hero-bubbles">
                <div class="bubble-item bubble-1"></div>
                <div class="bubble-item bubble-2"></div>
                <div class="bubble-item bubble-3"></div>
                <div class="bubble-item bubble-4"></div>
                <div class="bubble-item bubble-5"></div>
                <div class="bubble-item bubble-6"></div>
                <div class="bubble-item bubble-7"></div>
                <div class="bubble-item bubble-8"></div>
                <div class="bubble-item bubble-9"></div>
                <div class="bubble-item bubble-10"></div>
                <div class="bubble-item bubble-11"></div>
                <div class="bubble-item bubble-12"></div>
            </div>

            <!-- Navbar Header -->
            <header class="flex flex-col items-center justify-center relative z-10 pt-2 md:pt-0 flex-shrink-0">
                <div class="flex flex-col items-center gap-1.5 text-center">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-orange-100/80 border border-orange-200/80 rounded-2xl flex items-center justify-center shadow-md">
                        <svg class="w-6 h-6 md:w-7 md:h-7 text-orange-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z">
                            </path>
                        </svg>
                    </div>
                    <span class="font-extrabold text-xl md:text-2xl tracking-widest text-orange-600 mt-1 md:mt-2 flex items-center justify-center gap-1.5">
                        SEMON
                        <span class="text-[10px] md:text-xs font-bold text-orange-750 px-2 py-0.5 bg-orange-100/60 border border-orange-200/60 rounded-md">SE2026</span>
                    </span>
                </div>
            </header>

            <!-- Center Welcome Panel / Stats Area -->
            <main
                class="max-w-4xl mx-auto text-center space-y-[clamp(0.5rem,3vh,2rem)] relative z-10 py-[clamp(0.25rem,2vh,2.5rem)] flex flex-col justify-center flex-grow flex-shrink min-h-0">
                <div class="space-y-[clamp(0.25rem,1.5vh,1rem)]">
                    <span
                        class="inline-flex flex-wrap justify-center text-[10px] font-extrabold text-orange-600 uppercase tracking-[0.2em] bg-orange-100/50 px-3 py-1 border border-orange-200/60 rounded-full leading-normal max-w-[90vw] md:max-w-none text-center">Sistem
                        Monitoring Sensus Ekonomi 2026 BPS Kabupaten Bintan</span>
                    <h1 class="text-[clamp(2rem,8vw,4rem)] font-extrabold text-gray-900 tracking-tight leading-none">
                        Transparansi Data<br><span
                            class="text-transparent bg-clip-text bg-gradient-to-r from-orange-600 to-amber-600">
                            Lapangan Real-time</span>
                    </h1>
                    <p class="text-[clamp(0.8rem,2vw,1rem)] text-gray-600 max-w-xl mx-auto leading-relaxed">SE-MON menyajikan
                        dashboard visual terpusat yang memantau target, realisasi, dan pencacahan rumah tangga di
                        Kabupaten Bintan.</p>
                </div>

                <!-- COUNTDOWN VIEW (Active before June 19) -->
                <div x-show="!isLaunched" x-cloak class="space-y-[clamp(0.25rem,1.5vh,1.5rem)]">
                    <span class="text-[clamp(10px,1.5vw,12px)] text-gray-500 font-extrabold tracking-widest uppercase block">Pendataan Lapangan Sensus Ekonomi 2026 Akan Dimulai Dalam:</span>
                    <div class="grid grid-cols-4 gap-2 md:gap-4 max-w-lg mx-auto w-full px-2">
                        <div class="p-2.5 md:p-4 bg-white/80 border border-orange-100/85 rounded-2xl backdrop-blur-md shadow-md flex flex-col justify-center">
                            <span class="text-xl md:text-3xl font-extrabold text-gray-900 leading-tight" x-text="countdown.days"></span>
                            <span class="block text-[8px] md:text-[10px] text-gray-500 font-bold uppercase mt-0.5 md:mt-1">Hari</span>
                        </div>
                        <div class="p-2.5 md:p-4 bg-white/80 border border-orange-100/85 rounded-2xl backdrop-blur-md shadow-md flex flex-col justify-center">
                            <span class="text-xl md:text-3xl font-extrabold text-gray-900 leading-tight" x-text="countdown.hours"></span>
                            <span class="block text-[8px] md:text-[10px] text-gray-500 font-bold uppercase mt-0.5 md:mt-1">Jam</span>
                        </div>
                        <div class="p-2.5 md:p-4 bg-white/80 border border-orange-100/85 rounded-2xl backdrop-blur-md shadow-md flex flex-col justify-center">
                            <span class="text-xl md:text-3xl font-extrabold text-gray-900 leading-tight" x-text="countdown.minutes"></span>
                            <span class="block text-[8px] md:text-[10px] text-gray-500 font-bold uppercase mt-0.5 md:mt-1">Menit</span>
                        </div>
                        <div class="p-2.5 md:p-4 bg-white/80 border border-orange-100/85 rounded-2xl backdrop-blur-md shadow-md flex flex-col justify-center">
                            <span class="text-xl md:text-3xl font-extrabold text-orange-600 animate-pulse leading-tight" x-text="countdown.seconds"></span>
                            <span class="block text-[8px] md:text-[10px] text-gray-500 font-bold uppercase mt-0.5 md:mt-1">Detik</span>
                        </div>
                    </div>
                </div>

                <!-- LIVE STATS VIEW (Active after June 15) -->
                <div x-show="isLaunched" x-cloak class="space-y-[clamp(0.25rem,1.5vh,1rem)]">
                    <span
                        class="text-[10px] md:text-xs text-orange-600 font-extrabold tracking-widest uppercase flex items-center justify-center gap-1.5 bg-orange-100/50 px-3.5 py-1 md:px-4 md:py-1.5 border border-orange-200/50 rounded-full w-fit mx-auto animate-pulse">
                        <span class="w-2.5 h-2.5 bg-orange-500 rounded-full animate-ping"></span>
                        Pelaksanaan SE2026 Sedang Berlangsung
                    </span>
                    <div class="grid grid-cols-3 gap-2 md:gap-4 max-w-2xl mx-auto w-full px-2">
                        <!-- Target -->
                        <div
                            class="p-2.5 md:p-4 bg-white/80 border border-orange-100/85 rounded-2xl backdrop-blur-md shadow-md flex flex-col justify-center">
                            <span class="text-[9px] md:text-xs text-gray-500 font-bold uppercase">Total Target</span>
                            <span class="text-lg md:text-2xl font-extrabold text-gray-900 mt-0.5 md:mt-1 leading-tight"
                                x-text="Number(stats.total_usaha).toLocaleString('id-ID')">0</span>
                        </div>
                        <!-- Realisasi -->
                        <div
                            class="p-2.5 md:p-4 bg-white/80 border border-orange-100/85 rounded-2xl backdrop-blur-md shadow-md flex flex-col justify-center">
                            <span class="text-[9px] md:text-xs text-gray-500 font-bold uppercase">Realisasi Lapangan</span>
                            <span class="text-lg md:text-2xl font-extrabold text-orange-600 mt-0.5 md:mt-1 leading-tight"
                                x-text="Number(stats.realisasi).toLocaleString('id-ID')">0</span>
                        </div>
                        <!-- Progress % -->
                        <div
                            class="p-2.5 md:p-4 bg-white/80 border border-orange-100/85 rounded-2xl backdrop-blur-md shadow-md flex flex-col justify-center">
                            <span class="text-[9px] md:text-xs text-gray-500 font-bold uppercase">Progres Kerja</span>
                            <span class="text-lg md:text-2xl font-extrabold text-orange-600 mt-0.5 md:mt-1 leading-tight"
                                x-text="Number(stats.progress).toFixed(2) + '%'">0.00%</span>
                        </div>
                    </div>
                    <!-- Empty state notice when progress is empty (Case A) -->
                    <div x-show="stats.realisasi === 0" class="text-[10px] md:text-xs text-orange-800 font-bold bg-orange-50/50 px-3.5 py-1.5 border border-orange-150 rounded-2xl w-fit mx-auto mt-1">
                        Data lapangan belum tersedia
                    </div>
                </div>

                <!-- CTA Action Button -->
                <div class="pt-2 md:pt-4 flex flex-col sm:flex-row items-center justify-center gap-2 md:gap-4 max-w-lg mx-auto w-full px-4 flex-shrink-0">
                    <button @click="currentPanel = 'map'"
                        class="w-full sm:w-auto px-6 py-2.5 md:py-3.5 text-sm font-bold text-gray-700 bg-white border border-orange-200 hover:border-orange-400 hover:bg-orange-50/50 rounded-2xl transition duration-150 flex items-center justify-center gap-2 shadow-md">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Lihat Peta Geospasial Bintan
                    </button>
                    <button @click="currentPanel = 'login'"
                        class="w-full sm:w-auto px-6 py-2.5 md:py-3.5 text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-2xl transition duration-150 flex items-center justify-center gap-2 shadow-lg shadow-orange-600/15">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                            </path>
                        </svg>
                        Login Portal Petugas
                    </button>
                </div>
            </main>

            <!-- BPS Bintan Branding Footer -->
            <footer
                class="flex justify-center text-center text-[11px] text-gray-500 font-bold uppercase tracking-wider relative z-10 border-t border-orange-100/80 pt-3 md:pt-6 flex-shrink-0">
                <span>© 2026 TIM TI Badan Pusat Statistik Kabupaten Bintan</span>
            </footer>
        </section>

        <!-- ========================================== -->
        <!-- LOGIN PANEL (In-Place Cinematic)           -->
        <!-- ========================================== -->
        <section id="loginPanel" x-show="currentPanel === 'login'" x-cloak x-transition:enter="panel-transition"
            x-transition:enter-start="panel-inactive" x-transition:enter-end="panel-active"
            x-transition:leave="panel-transition" x-transition:leave-start="panel-active"
            x-transition:leave-end="panel-inactive"
            class="absolute inset-0 w-full h-full panel-transition flex flex-col justify-between p-6 md:p-12">

            <!-- Navbar Header -->
            <div class="flex items-center justify-between relative z-10 max-w-5xl w-full mx-auto">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                    <span class="font-extrabold text-base tracking-wide text-gray-900">FORM LOGIN</span>
                </div>
                <button @click="currentPanel = 'home'"
                    class="px-4 py-2 text-xs font-bold text-gray-700 bg-white border border-orange-200 hover:text-orange-600 hover:bg-orange-50 rounded-xl transition duration-150 flex items-center gap-1 shadow-sm">
                    <svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali Ke Beranda
                </button>
            </div>

            <!-- Login Center Area -->
            <main
                class="max-w-md w-full mx-auto relative z-10 bg-white/90 border border-orange-100 rounded-3xl p-8 backdrop-blur-md shadow-2xl flex flex-col justify-center my-6">
                <div class="text-center space-y-1 mb-6">
                    <h3 class="text-lg font-extrabold text-gray-900">Form Login</h3>
                    <p class="text-xs text-gray-500">Masukkan email dan password akun Sensus Ekonomi Anda.</p>
                </div>

                <!-- Session Status / Errors -->
                @if ($errors->any())
                    <div
                        class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded-xl flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <!-- Standard Laravel Login POST Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-gray-600 mb-1.5">Alamat Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full text-sm bg-orange-50/30 border border-orange-200 rounded-xl p-2.5 text-gray-800 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400"
                            placeholder="nama.petugas@gmail.com" />
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold text-gray-600">Password</label>
                        </div>
                        <input id="password" type="password" name="password" required
                            class="w-full text-sm bg-orange-50/30 border border-orange-200 rounded-xl p-2.5 text-gray-800 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400"
                            placeholder="••••••••" />
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input id="remember_me" type="checkbox" name="remember"
                            class="w-4 h-4 rounded text-orange-600 bg-orange-50 border-orange-200 focus:ring-orange-500" />
                        <label for="remember_me" class="ms-2 text-xs text-gray-500 font-semibold">Ingat masuk
                            saya</label>
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                        class="w-full py-2.5 bg-orange-600 hover:bg-orange-700 text-sm font-extrabold text-white rounded-xl transition duration-150 shadow-lg shadow-orange-600/10 mt-6 flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                            </path>
                        </svg>
                        Masuk Sekarang
                    </button>
                </form>

                <!-- Advisory Info Box -->
                <div
                    class="mt-5 p-3.5 bg-orange-50/70 border border-orange-100 rounded-2xl flex items-center gap-2.5 text-xs text-orange-850 leading-relaxed font-semibold">
                    <svg class="w-4 h-4 text-orange-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Hubungi admin/PIC BPS Kabupaten Bintan jika mengalami kendala akses sistem.</span>
                </div>
            </main>

            <!-- Bottom Margin alignment -->
            <div class="h-6"></div>
        </section>

    </div>

    <!-- Alpine.js Application Controller -->
    <script>
        function getGradientColor(progress) {
            const p = Math.max(0, Math.min(100, progress));
            const stops = [
                { pct: 0, r: 255, g: 247, b: 237 },    // #FFF7ED
                { pct: 25, r: 254, g: 215, b: 170 },   // #FED7AA
                { pct: 50, r: 251, g: 146, b: 60 },    // #FB923C
                { pct: 75, r: 249, g: 115, b: 22 },    // #F97316
                { pct: 100, r: 234, g: 88, b: 12 }     // #EA580C
            ];

            let lower = stops[0];
            let upper = stops[stops.length - 1];

            for (let i = 0; i < stops.length - 1; i++) {
                if (p >= stops[i].pct && p <= stops[i+1].pct) {
                    lower = stops[i];
                    upper = stops[i+1];
                    break;
                }
            }

            const range = upper.pct - lower.pct;
            const factor = range > 0 ? (p - lower.pct) / range : 0;

            const r = Math.round(lower.r + factor * (upper.r - lower.r));
            const g = Math.round(lower.g + factor * (upper.g - lower.g));
            const b = Math.round(lower.b + factor * (upper.b - lower.b));

            const toHex = (val) => {
                const hex = val.toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            };

            return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('semonLanding', () => ({
                currentPanel: '{{ $errors->any() ? "login" : "home" }}',
                isLaunched: false,
                mapInitialized: false,
                activeKec: null,
                kecBreakdown: [],
                showKecModal: false,

                stats: {
                    total_usaha: 0,
                    realisasi: 0,
                    progress: 0,
                    subsls: 751,
                    pcl: 0,
                    pml: 0
                },

                countdown: {
                    days: '00',
                    hours: '00',
                    minutes: '00',
                    seconds: '00'
                },

                targetDate: null,

                init() {
                    const wrapper = document.getElementById('landing-wrapper');
                    const targetStr = wrapper ? wrapper.getAttribute('data-target-date') : '2026-06-15T00:00:00+07:00';
                    this.targetDate = new Date(targetStr);
                    this.checkLaunch();

                    // Watch currentPanel to trigger Map initialization
                    this.$watch('currentPanel', value => {
                        if (value === 'map') {
                            this.initMap();
                        }
                    });

                    // If initial panel is map
                    if (this.currentPanel === 'map') {
                        this.initMap();
                    }

                    // Setup interactive cursor hover bubble trail
                    this.setupMouseTrail();
                },

                checkLaunch() {
                    const now = new Date();
                    this.isLaunched = now >= this.targetDate;

                    if (this.isLaunched) {
                        this.fetchStats();
                        setInterval(() => this.fetchStats(), 30000);
                    } else {
                        this.updateCountdown();
                        setInterval(() => this.updateCountdown(), 1000);
                    }
                },

                updateCountdown() {
                    const now = new Date();
                    const diff = this.targetDate - now;

                    if (diff <= 0) {
                        this.isLaunched = true;
                        this.fetchStats();
                        return;
                    }

                    const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((diff % (1000 * 60)) / 1000);

                    this.countdown.days = String(d).padStart(2, '0');
                    this.countdown.hours = String(h).padStart(2, '0');
                    this.countdown.minutes = String(m).padStart(2, '0');
                    this.countdown.seconds = String(s).padStart(2, '0');
                },

                fetchStats() {
                    fetch('/api/semon/landing-stats')
                        .then(r => r.json())
                        .then(data => {
                            this.stats = data;
                        })
                        .catch(err => console.error(err));
                },

                initMap() {
                    if (this.mapInitialized) return;
                    this.mapInitialized = true;

                    setTimeout(() => {
                        this.initLeafletMap();
                    }, 300);
                },

                initLeafletMap() {
                    // Create Leaflet Map centered around Bintan with wider viewport coordinates
                    const map = L.map('leafletMap', {
                        center: [0.97, 104.51],
                        zoom: 9,
                        zoomControl: false,
                        attributionControl: false
                    });

                    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                        maxZoom: 20
                    }).addTo(map);

                    L.control.zoom({ position: 'bottomright' }).addTo(map);

                    Promise.all([
                        fetch('/Final_Kec_202512102.geojson').then(res => {
                            if (!res.ok) throw new Error('Failed to load GeoJSON');
                            return res.json();
                        }),
                        fetch('/api/semon/map-progress').then(res => {
                            if (!res.ok) throw new Error('Failed to load map progress');
                            return res.json();
                        })
                    ])
                        .then(([geojson, progressMap]) => {
                            // Safe property join utility supporting both Object dictionaries and sequential JSON arrays
                            const getProgressData = (idkec) => {
                                if (!progressMap) return { target: 0, realisasi: 0, progress: 0.0 };

                                // If progressMap is returned as a JSON array
                                if (Array.isArray(progressMap)) {
                                    return progressMap.find(item => String(item.idkec) === String(idkec)) || { target: 0, realisasi: 0, progress: 0.0 };
                                }

                                // If progressMap is a keyed JSON object
                                return progressMap[idkec] || { target: 0, realisasi: 0, progress: 0.0 };
                            };

                            geojson.features.forEach(feature => {
                                const idkec = feature.properties.idkec;
                                const prog = getProgressData(idkec);
                                feature.properties.target = prog.target || 0;
                                feature.properties.realisasi = prog.realisasi || 0;
                                feature.properties.progress = prog.progress || 0.0;
                            });

                            const geojsonLayer = L.geoJSON(geojson, {
                                style: function (feature) {
                                    const progress = feature.properties.progress || 0;
                                    const fill = getGradientColor(progress);

                                    return {
                                        fillColor: fill,
                                        weight: 1.5,
                                        opacity: 0.9,
                                        color: '#ffffff',
                                        fillOpacity: 0.75
                                    };
                                },
                                onEachFeature: (feature, layer) => {
                                    const props = feature.properties;
                                    const progress = props.progress || 0;
                                    const progressColor = getGradientColor(progress);

                                    layer.bindTooltip(
                                        `<div class='text-xs'>` +
                                        `<div class='font-extrabold text-gray-900 text-sm mb-1 border-b border-gray-100 pb-1'>Kec. ${props.nmkec}</div>` +
                                        `<div class='flex justify-between gap-6 mb-0.5 text-gray-600'><span>Target Usaha:</span><span class='font-bold text-gray-800'>${Number(props.target).toLocaleString('id-ID')}</span></div>` +
                                        `<div class='flex justify-between gap-6 mb-0.5 text-gray-600'><span>Realisasi Lapangan:</span><span class='font-bold text-gray-800'>${Number(props.realisasi).toLocaleString('id-ID')}</span></div>` +
                                        `<div class='flex justify-between gap-6 font-bold mt-1.5 pt-1.5 border-t border-gray-100 text-gray-800'><span>Progress:</span><span style='color: ${progressColor};'>${Number(props.progress).toFixed(2)}%</span></div>` +
                                        `</div>`,
                                        { sticky: true, className: 'map-tooltip' }
                                    );

                                    layer.on({
                                        mouseover: function (e) {
                                            const l = e.target;
                                            l.setStyle({
                                                fillOpacity: 0.9,
                                                weight: 2.5,
                                                color: '#475569'
                                            });
                                            if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
                                                l.bringToFront();
                                            }
                                        },
                                        mouseout: function (e) {
                                            geojsonLayer.resetStyle(e.target);
                                        },
                                        click: (e) => {
                                            this.activeKec = props.nmkec;
                                            this.showKecModal = true;
                                            this.kecBreakdown = [];

                                            fetch(`/api/semon/kecamatan-breakdown/${props.idkec}`)
                                                .then(res => res.json())
                                                .then(data => { this.kecBreakdown = data; })
                                                .catch(err => console.error(err));
                                        }
                                    });
                                }
                            }).addTo(map);
                        })
                        .catch(err => console.error('Error in parallel map loading:', err));
                },

                setupMouseTrail() {
                    const homePanel = document.getElementById('homePanel');
                    if (!homePanel) return;

                    let trailContainer = document.getElementById('bubble-trail-container');
                    if (!trailContainer) {
                        trailContainer = document.createElement('div');
                        trailContainer.id = 'bubble-trail-container';
                        trailContainer.className = 'absolute inset-0 pointer-events-none overflow-hidden z-[1]';
                        homePanel.appendChild(trailContainer);
                    }

                    let lastSpawn = 0;
                    const throttleMs = 50; // Spawn bubble at most every 50ms

                    homePanel.addEventListener('mousemove', (e) => {
                        if (this.currentPanel !== 'home') return;

                        const now = Date.now();
                        if (now - lastSpawn < throttleMs) return;
                        lastSpawn = now;

                        const rect = homePanel.getBoundingClientRect();
                        const x = e.clientX - rect.left;
                        const y = e.clientY - rect.top;

                        const bubble = document.createElement('div');
                        const size = Math.random() * 12 + 6; // 6px to 18px
                        bubble.className = 'cursor-bubble';
                        bubble.style.width = `${size}px`;
                        bubble.style.height = `${size}px`;
                        bubble.style.left = `${x - size / 2}px`;
                        bubble.style.top = `${y - size / 2}px`;

                        const drift = (Math.random() - 0.5) * 50; // -25px to 25px
                        bubble.style.setProperty('--drift-x', `${drift}px`);

                        trailContainer.appendChild(bubble);

                        bubble.addEventListener('animationend', () => {
                            bubble.remove();
                        });
                    });
                }
            }));
        });
    </script>

</body>
</html>