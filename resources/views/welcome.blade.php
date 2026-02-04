<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Chatify - Connect Seamlessly. Chat Effortlessly. The next generation of messaging.">

        <title>{{ config('app.name', 'Chatify') }} - Connect Seamlessly</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Custom Styles & Animations */
            :root {
                --primary: #7c3aed;
                --secondary: #06b6d4;
                --dark: #0f172a;
            }
            
            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--dark);
                overflow-x: hidden;
            }

            h1, h2, h3, h4, h5, h6 {
                font-family: 'Outfit', sans-serif;
            }

            /* Mesh Gradient Background */
            .mesh-bg {
                background-color: #0f172a;
                background-image: 
                    radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                    radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                    radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                z-index: -1;
            }

            .orb {
                position: absolute;
                border-radius: 50%;
                filter: blur(80px);
                opacity: 0.6;
                animation: float 10s infinite ease-in-out;
            }

            .orb-1 {
                top: -10%;
                left: -10%;
                width: 50vw;
                height: 50vw;
                background: radial-gradient(circle, rgba(124, 58, 237, 0.4) 0%, rgba(124, 58, 237, 0) 70%);
                animation-delay: 0s;
            }

            .orb-2 {
                bottom: -10%;
                right: -10%;
                width: 40vw;
                height: 40vw;
                background: radial-gradient(circle, rgba(6, 182, 212, 0.4) 0%, rgba(6, 182, 212, 0) 70%);
                animation-delay: -5s;
            }

            @keyframes float {
                0%, 100% { transform: translate(0, 0); }
                50% { transform: translate(30px, 50px); }
            }

            /* Glassmorphism */
            .glass {
                background: rgba(255, 255, 255, 0.03);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.05);
            }

            .glass-hover:hover {
                background: rgba(255, 255, 255, 0.08);
                border-color: rgba(255, 255, 255, 0.1);
                transform: translateY(-2px);
            }

            /* Preloader */
            #preloader {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: #0f172a;
                z-index: 9999;
                display: flex;
                justify-content: center;
                align-items: center;
                transition: opacity 0.5s ease-out, visibility 0.5s;
            }

            .loader {
                width: 48px;
                height: 48px;
                border: 3px solid #FFF;
                border-radius: 50%;
                display: inline-block;
                position: relative;
                box-sizing: border-box;
                animation: rotation 1s linear infinite;
            } 
            .loader::after {
                content: '';  
                box-sizing: border-box;
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
                width: 40px;
                height: 40px;
                border-radius: 50%;
                border: 3px solid;
                border-color: #7c3aed transparent;
            }

            @keyframes rotation {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            /* Text Gradients */
            .text-gradient {
                background: linear-gradient(to right, #c4b5fd, #67e8f9);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
        </style>
    </head>
    <body class="antialiased text-gray-200">

        <!-- Preloader -->
        <div id="preloader">
            <div class="flex flex-col items-center gap-4">
                <span class="loader"></span>
                <span class="text-sm font-medium tracking-widest text-gray-400 uppercase animate-pulse">Loading Experience</span>
            </div>
        </div>

        <!-- Background Elements -->
        <div class="mesh-bg"></div>
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>

        <!-- Header -->
        <header class="fixed w-full top-0 z-50 transition-all duration-300" id="navbar">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-violet-600 to-cyan-500 flex items-center justify-center shadow-lg group-hover:shadow-violet-500/50 transition-all duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <span class="font-bold text-2xl tracking-tight text-white group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:from-violet-400 group-hover:to-cyan-300 transition-all duration-300">Chatify</span>
                        </a>
                    </div>

                    <!-- Desktop Nav -->
                    <nav class="hidden md:flex items-center space-x-8">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-gray-300 hover:text-white transition-colors duration-200 font-medium">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="text-gray-300 hover:text-white transition-colors duration-200 font-medium">Log in</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-full bg-white/10 hover:bg-white/20 border border-white/10 backdrop-blur-sm text-white font-medium transition-all duration-300 hover:shadow-lg hover:shadow-violet-500/20">
                                        Get Started
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </nav>

                    <!-- Mobile Menu Button -->
                    <div class="md:hidden flex items-center">
                        <button class="text-gray-300 hover:text-white focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <main>
            <section class="relative min-h-screen flex items-center pt-20 overflow-hidden">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full">
                    <div class="text-center max-w-4xl mx-auto">
                        <div class="inline-flex items-center px-4 py-2 rounded-full border border-violet-500/30 bg-violet-500/10 text-violet-300 text-sm font-medium mb-8 backdrop-blur-md animate-fade-in-up">
                            <span class="flex h-2 w-2 rounded-full bg-violet-400 mr-2 animate-pulse"></span>
                            New Features Available
                        </div>
                        
                        <h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-8 leading-tight animate-fade-in-up" style="animation-delay: 0.1s;">
                            Connect Seamlessly. <br>
                            <span class="text-gradient">Chat Effortlessly.</span>
                        </h1>
                        
                        <p class="mt-4 text-xl text-gray-400 mb-10 max-w-2xl mx-auto leading-relaxed animate-fade-in-up" style="animation-delay: 0.2s;">
                            Experience the next generation of messaging with real-time delivery, secure encryption, and a beautiful interface designed for you.
                        </p>
                        
                        <div class="flex flex-col sm:flex-row gap-4 justify-center animate-fade-in-up" style="animation-delay: 0.3s;">
                            <a href="{{ route('register') }}" class="px-8 py-4 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-bold text-lg shadow-lg shadow-violet-600/30 hover:shadow-violet-600/50 hover:scale-105 transition-all duration-300">
                                Start Chatting Now
                            </a>
                            <a href="#features" class="px-8 py-4 rounded-full glass text-white font-semibold text-lg hover:bg-white/10 transition-all duration-300">
                                Learn More
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section id="features" class="py-24 relative z-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-5xl font-bold mb-4">Why Chatify?</h2>
                        <p class="text-gray-400 text-lg">Built for speed, security, and simplicity.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Feature 1 -->
                        <div class="glass rounded-3xl p-8 glass-hover transition-all duration-300">
                            <div class="w-14 h-14 rounded-2xl bg-violet-500/20 flex items-center justify-center mb-6 text-violet-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-3">Lightning Fast</h3>
                            <p class="text-gray-400 leading-relaxed">
                                Real-time message delivery ensures you never miss a beat. Experience zero latency communication.
                            </p>
                        </div>

                        <!-- Feature 2 -->
                        <div class="glass rounded-3xl p-8 glass-hover transition-all duration-300">
                            <div class="w-14 h-14 rounded-2xl bg-cyan-500/20 flex items-center justify-center mb-6 text-cyan-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-3">Secure & Private</h3>
                            <p class="text-gray-400 leading-relaxed">
                                End-to-end encryption keeps your conversations private. Your data is yours alone.
                            </p>
                        </div>

                        <!-- Feature 3 -->
                        <div class="glass rounded-3xl p-8 glass-hover transition-all duration-300">
                            <div class="w-14 h-14 rounded-2xl bg-pink-500/20 flex items-center justify-center mb-6 text-pink-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-3">Express Yourself</h3>
                            <p class="text-gray-400 leading-relaxed">
                                Share photos, videos, and react with emojis. Make your conversations come alive.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-white/5 bg-black/20 backdrop-blur-md pt-16 pb-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                    <div class="col-span-1 md:col-span-2">
                        <div class="flex items-center gap-2 mb-6">
                             <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-violet-600 to-cyan-500 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <span class="font-bold text-xl text-white">Chatify</span>
                        </div>
                        <p class="text-gray-400 mb-6 max-w-sm">
                            Connecting people through seamless, secure, and beautiful messaging experiences. Join us today.
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-white mb-4">Product</h4>
                        <ul class="space-y-3 text-gray-400">
                            <li><a href="#" class="hover:text-violet-400 transition-colors">Features</a></li>
                            <li><a href="#" class="hover:text-violet-400 transition-colors">Security</a></li>
                            <li><a href="#" class="hover:text-violet-400 transition-colors">Download</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-white mb-4">Company</h4>
                        <ul class="space-y-3 text-gray-400">
                            <li><a href="#" class="hover:text-violet-400 transition-colors">About</a></li>
                            <li><a href="#" class="hover:text-violet-400 transition-colors">Careers</a></li>
                            <li><a href="#" class="hover:text-violet-400 transition-colors">Contact</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="border-t border-white/5 pt-8 flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-500 text-sm">© {{ date('Y') }} Chatify. All rights reserved.</p>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <a href="#" class="text-gray-500 hover:text-white transition-colors">
                            <span class="sr-only">Twitter</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" /></svg>
                        </a>
                        <a href="#" class="text-gray-500 hover:text-white transition-colors">
                            <span class="sr-only">GitHub</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                        </a>
                    </div>
                </div>
            </div>
        </footer>

        <script>
            // Preloader Logic
            window.addEventListener('load', function() {
                const preloader = document.getElementById('preloader');
                setTimeout(() => {
                    preloader.style.opacity = '0';
                    setTimeout(() => {
                        preloader.style.visibility = 'hidden';
                    }, 500);
                }, 1000); // 1.5s total display time minimum
            });

            // Navbar Scroll Effect
            window.addEventListener('scroll', function() {
                const navbar = document.getElementById('navbar');
                if (window.scrollY > 20) {
                    navbar.classList.add('bg-black/40', 'backdrop-blur-md', 'border-b', 'border-white/5');
                } else {
                    navbar.classList.remove('bg-black/40', 'backdrop-blur-md', 'border-b', 'border-white/5');
                }
            });
            
            // Fade In Animation for elements
            const observerOptions = {
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in-up');
                        entry.target.style.opacity = 1;
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.animate-on-scroll').forEach(el => {
                el.style.opacity = 0;
                observer.observe(el);
            });
        </script>

        <style>
            .animate-fade-in-up {
                animation: fadeInUp 0.8s ease-out forwards;
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    </body>
</html>
