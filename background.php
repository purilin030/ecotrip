<div class="fixed inset-0 -z-10 h-full w-full bg-sky-50 overflow-hidden">
    
    <style>
        /* 1. Clouds Drifting Animation */
        @keyframes float-cloud {
            0% { transform: translateX(-200px); }
            100% { transform: translateX(120vw); } /* floating across the entire screen */
        }

        /* 2. Sun Halo Breath */
        @keyframes sun-glow {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.1); opacity: 1; box-shadow: 0 0 50px rgba(253, 224, 71, 0.6); }
        }

        /* 3. Endless Mountain Roll (Core Animation) */
        @keyframes move-waves {
            0% { transform: translate3d(-90px,0,0); }
            100% { transform: translate3d(85px,0,0); }
        }

        /* 4. Falling Leaves Animation (Enhanced Version: Added Rotation and Sway) */
        @keyframes fall-sway {
            0% { top: -10%; transform: translateX(0) rotate(0deg); opacity: 0; }
            10% { opacity: 0.8; }
            50% { transform: translateX(50px) rotate(180deg); }
            90% { opacity: 0.8; }
            100% { top: 110%; transform: translateX(-50px) rotate(360deg); opacity: 0; }
        }

        .cloud { position: absolute; background: white; border-radius: 999px; opacity: 0.8; filter: blur(1px); }
        .wave-layer > use { animation: move-waves 25s cubic-bezier(.55,.5,.45,.5) infinite; }
        
        /* The speed and color of each mountain layer */
        .wave-layer > use:nth-child(1) { animation-duration: 20s; animation-delay: -2s; fill: #86efac; opacity: 0.5; } /* 后山 (浅) */
        .wave-layer > use:nth-child(2) { animation-duration: 15s; animation-delay: -3s; fill: #4ade80; opacity: 0.7; } /* 中山 (中) */
        .wave-layer > use:nth-child(3) { animation-duration: 10s; animation-delay: -4s; fill: #16a34a; opacity: 1; }   /* 前山 (深) */

        /* Fallen Leaves Arrangement  */
        .leaf {
            position: absolute;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2322c55e'%3E%3Cpath d='M21,5c-1.1-0.35-2.3-0.5-3.5-0.5c-0.8,3.4-3.5,6-7,6.8c0.6-2.5,0.2-5-1-7.2C5.3,4.4,2.3,7.6,2,11.7 c-0.1,1.1,0.2,2.2,0.9,3.1C3.8,16,5.3,16.8,7,17c3.4,0.3,6.5-1.9,7.6-5.1c0.9-2.5,0.5-5.3-1-7.6C16.4,4.2,18.8,4.4,21,5z'/%3E%3C/svg%3E");
            background-size: contain; background-repeat: no-repeat;
            z-index: 10; animation: fall-sway linear infinite;
        }
    </style>

    <div class="absolute inset-0 bg-gradient-to-b from-sky-200 via-sky-100 to-white h-[80%]"></div>

    <div class="absolute top-12 right-16 w-24 h-24 bg-yellow-300 rounded-full blur-xl animate-[sun-glow_4s_infinite_ease-in-out]"></div>
    <div class="absolute top-16 right-20 w-16 h-16 bg-yellow-100 rounded-full blur-md opacity-90"></div>

    <div class="cloud w-32 h-10 top-20 left-[-100px]" style="animation: float-cloud 35s linear infinite;"></div>
    <div class="cloud w-48 h-14 top-32 left-[-200px]" style="animation: float-cloud 45s linear infinite; animation-delay: -10s;"></div>
    <div class="cloud w-24 h-8 top-10 left-[-150px]" style="animation: float-cloud 60s linear infinite; animation-delay: -5s; opacity: 0.6;"></div>


    <svg class="absolute bottom-0 left-0 w-full h-[35vh]" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
    viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
        <defs>
            <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z" />
        </defs>
        <g class="wave-layer">
            <use xlink:href="#gentle-wave" x="48" y="0" />
            <use xlink:href="#gentle-wave" x="48" y="3" />
            <use xlink:href="#gentle-wave" x="48" y="5" />
        </g>
    </svg>

    <div class="leaf w-8 h-8 left-[10%]" style="animation-duration: 12s; animation-delay: 0s;"></div>
    <div class="leaf w-6 h-6 left-[30%]" style="animation-duration: 18s; animation-delay: 2s; opacity: 0.6;"></div>
    <div class="leaf w-10 h-10 left-[60%]" style="animation-duration: 14s; animation-delay: 4s;"></div>
    <div class="leaf w-5 h-5 left-[85%]" style="animation-duration: 20s; animation-delay: 1s; opacity: 0.5;"></div>
    <div class="leaf w-7 h-7 left-[45%]" style="animation-duration: 16s; animation-delay: 6s;"></div>

    <div class="absolute top-0 w-full h-24 bg-gradient-to-b from-white/90 to-transparent"></div>

</div>