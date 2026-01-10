<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RADTik - MikroTik WiFi Hotspot Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        // Theme toggle function
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('radtik-theme', newTheme);
        }

        // Initialize theme on load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('radtik-theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        });
    </script>

    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animate-fadeIn {
            animation: fadeIn 1s ease-out forwards;
        }

        .delay-100 {
            animation-delay: 0.1s;
        }

        .delay-200 {
            animation-delay: 0.2s;
        }

        .delay-300 {
            animation-delay: 0.3s;
        }

        .delay-400 {
            animation-delay: 0.4s;
        }

        .opacity-0 {
            opacity: 0;
        }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #0EA5E9 0%, #06B6D4 50%, #3B82F6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Feature card hover effect */
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body class="min-h-screen bg-base-100">

    <!-- Navigation -->
    <div class="navbar bg-base-100 shadow-lg sticky top-0 z-50 backdrop-blur-lg bg-opacity-90">
        <div class="navbar-start">
            <a href="/"
                class="btn btn-ghost normal-case text-5xl md:text-6xl font-bold gradient-text flex items-center gap-4 py-2 px-4">
                <img src="{{ asset('logo_color.png') }}" alt="RADTik Logo"
                    class="h-24 w-24 aspect-square object-contain" style="max-width:96px;max-height:96px;" />
            </a>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1 text-base">
                <li><a href="#features" class="hover:text-primary">Features</a></li>
                <li><a href="#how-it-works" class="hover:text-primary">How It Works</a></li>
                <li><a href="#pricing" class="hover:text-primary">Pricing</a></li>
                <li><a href="#contact" class="hover:text-primary">Contact</a></li>
            </ul>
        </div>
        <div class="navbar-end gap-2">
            <!-- Theme Toggle -->
            <button onclick="toggleTheme()" class="btn btn-ghost btn-circle">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>

            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm md:btn-md">Dashboard</a>
            @else
                <a href="{{ route('tyro-login.login') }}" class="btn btn-ghost btn-sm md:btn-md">Login</a>
                <a href="{{ route('tyro-login.register') }}" class="btn btn-primary btn-sm md:btn-md">Sign Up</a>
            @endauth

            <!-- Mobile Menu -->
            <div class="dropdown dropdown-end lg:hidden">
                <label tabindex="0" class="btn btn-ghost btn-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </label>
                <ul tabindex="0"
                    class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero min-h-[90vh] bg-base-200 relative overflow-hidden">
        <div class="absolute inset-0 opacity-5">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
        </div>

        <div class="hero-content text-center relative z-10">
            <div class="max-w-4xl">
                <h1 class="text-5xl md:text-7xl font-bold mb-6 opacity-0 animate-fadeInUp">
                    Powerful MikroTik <br>
                    <span class="gradient-text">WiFi Hotspot Management</span>
                </h1>
                <p class="text-xl md:text-2xl mb-8 opacity-0 animate-fadeInUp delay-100 max-w-3xl mx-auto">
                    Streamline your WiFi hotspot business with comprehensive router management, voucher generation,
                    billing automation, and multi-tenant administration.
                </p>
                <div class="flex flex-wrap gap-4 justify-center opacity-0 animate-fadeInUp delay-200">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg gap-2 shadow-xl">
                            Go to Dashboard
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('tyro-login.register') }}" class="btn btn-primary btn-lg gap-2 shadow-xl">
                            Get Started Free
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    @endauth
                    <a href="#features" class="btn btn-outline btn-lg gap-2">
                        Learn More
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </a>
                </div>

                <!-- Stats -->
                <div
                    class="stats stats-vertical md:stats-horizontal shadow-xl mt-16 opacity-0 animate-fadeInUp delay-300 bg-base-100">
                    <div class="stat">
                        <div class="stat-figure text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="stat-title">Routers Managed</div>
                        <div class="stat-value text-primary">1000+</div>
                        <div class="stat-desc">Across all customers</div>
                    </div>

                    <div class="stat">
                        <div class="stat-figure text-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="stat-title">Vouchers Generated</div>
                        <div class="stat-value text-secondary">50K+</div>
                        <div class="stat-desc">Per month</div>
                    </div>

                    <div class="stat">
                        <div class="stat-figure text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="stat-title">Active Users</div>
                        <div class="stat-value text-accent">10K+</div>
                        <div class="stat-desc">Connected daily</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Preview Section -->
    <section class="py-20 px-4 bg-base-100">
        <div class="container mx-auto max-w-7xl">
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Powerful Dashboard at Your Fingertips</h2>
                <p class="text-xl text-base-content/70 max-w-2xl mx-auto">
                    Intuitive interface designed for efficiency and ease of use
                </p>
            </div>

            <!-- Screenshot Container -->
            <div class="relative">
                <div class="absolute inset-0 bg-gradient-to-r from-primary to-secondary opacity-20 blur-3xl"></div>
                <div class="relative">
                    <div class="mockup-browser border border-base-300 shadow-2xl bg-base-200">
                        <div class="mockup-browser-toolbar">
                            <div class="input border border-base-300">https://radtik.app/dashboard</div>
                        </div>
                        <div class="bg-base-100 flex justify-center px-4 py-16">
                            <!-- Dashboard Screenshot Placeholder -->
                            <div class="w-full relative">
                                <img src="{{ asset('dashboard.png') }}" alt="RADTik Dashboard Screenshot"
                                    class="w-full h-auto rounded-lg shadow-xl"
                                    onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'bg-base-200 rounded-lg p-20 text-center\'><svg xmlns=\'http://www.w3.org/2000/svg\' class=\'h-24 w-24 mx-auto mb-4 text-base-content/30\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\' /></svg><p class=\'text-base-content/50 text-lg\'>Dashboard Screenshot</p><p class=\'text-base-content/30 text-sm mt-2\'>Place your screenshot at: /public/storage/dashboard-screenshot.png</p></div>';" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Highlights Below Screenshot -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
                <div class="text-center p-6 bg-base-200 rounded-lg">
                    <div class="text-3xl font-bold text-primary mb-2">⚡</div>
                    <h4 class="font-bold mb-2">Lightning Fast</h4>
                    <p class="text-sm text-base-content/70">Real-time updates and instant response</p>
                </div>
                <div class="text-center p-6 bg-base-200 rounded-lg">
                    <div class="text-3xl font-bold text-secondary mb-2">📊</div>
                    <h4 class="font-bold mb-2">Comprehensive Analytics</h4>
                    <p class="text-sm text-base-content/70">All metrics at a glance</p>
                </div>
                <div class="text-center p-6 bg-base-200 rounded-lg">
                    <div class="text-3xl font-bold text-accent mb-2">🎨</div>
                    <h4 class="font-bold mb-2">Beautiful UI</h4>
                    <p class="text-sm text-base-content/70">Modern design with dark mode support</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 px-4 bg-base-100">
        <div class="container mx-auto max-w-7xl">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Powerful Features</h2>
                <p class="text-xl text-base-content/70 max-w-2xl mx-auto">
                    Everything you need to manage your WiFi hotspot business efficiently
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="card bg-base-200 shadow-xl feature-card">
                    <div class="card-body">
                        <div class="w-16 h-16 bg-primary rounded-lg flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-primary-content"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                        </div>
                        <h3 class="card-title text-2xl">MikroTik Router Management</h3>
                        <p class="text-base-content/70">
                            Centrally manage multiple MikroTik routers with real-time monitoring, configuration updates,
                            and remote control capabilities.
                        </p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="card bg-base-200 shadow-xl feature-card">
                    <div class="card-body">
                        <div class="w-16 h-16 bg-secondary rounded-lg flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-secondary-content"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                        </div>
                        <h3 class="card-title text-2xl">Voucher Generation</h3>
                        <p class="text-base-content/70">
                            Generate bulk WiFi vouchers with customizable validity periods, bandwidth limits, and
                            pricing. Perfect for cafes, hotels, and public spaces.
                        </p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="card bg-base-200 shadow-xl feature-card">
                    <div class="card-body">
                        <div class="w-16 h-16 bg-accent rounded-lg flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-accent-content"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="card-title text-2xl">Automated Billing</h3>
                        <p class="text-base-content/70">
                            Integrated payment processing with multiple gateways, automated invoicing, and subscription
                            management for hassle-free revenue collection.
                        </p>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="card bg-base-200 shadow-xl feature-card">
                    <div class="card-body">
                        <div class="w-16 h-16 bg-info rounded-lg flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-info-content"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="card-title text-2xl">Multi-Tenant Support</h3>
                        <p class="text-base-content/70">
                            Perfect for resellers and ISPs. Manage multiple customers with isolated environments, custom
                            branding, and role-based access control.
                        </p>
                    </div>
                </div>

                <!-- Feature 5 -->
                <div class="card bg-base-200 shadow-xl feature-card">
                    <div class="card-body">
                        <div class="w-16 h-16 bg-success rounded-lg flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-success-content"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="card-title text-2xl">Real-Time Analytics</h3>
                        <p class="text-base-content/70">
                            Comprehensive dashboards with usage statistics, revenue reports, user analytics, and
                            performance monitoring for data-driven decisions.
                        </p>
                    </div>
                </div>

                <!-- Feature 6 -->
                <div class="card bg-base-200 shadow-xl feature-card">
                    <div class="card-body">
                        <div class="w-16 h-16 bg-warning rounded-lg flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-warning-content"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h3 class="card-title text-2xl">Support System</h3>
                        <p class="text-base-content/70">
                            Built-in ticketing system with knowledgebase, documentation, and priority support to keep
                            your operations running smoothly.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-20 px-4 bg-base-200">
        <div class="container mx-auto max-w-7xl">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4">How It Works</h2>
                <p class="text-xl text-base-content/70 max-w-2xl mx-auto">
                    Get started in minutes with our simple setup process
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Step 1 -->
                <div class="text-center">
                    <div class="relative">
                        <div
                            class="w-20 h-20 bg-primary rounded-full flex items-center justify-center mx-auto mb-4 text-3xl font-bold text-primary-content">
                            1
                        </div>
                        <div class="hidden md:block absolute top-10 left-full w-full h-0.5 bg-primary/30"></div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Sign Up</h3>
                    <p class="text-base-content/70">Create your account in seconds. No credit card required.</p>
                </div>

                <!-- Step 2 -->
                <div class="text-center">
                    <div class="relative">
                        <div
                            class="w-20 h-20 bg-secondary rounded-full flex items-center justify-center mx-auto mb-4 text-3xl font-bold text-secondary-content">
                            2
                        </div>
                        <div class="hidden md:block absolute top-10 left-full w-full h-0.5 bg-secondary/30"></div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Connect Routers</h3>
                    <p class="text-base-content/70">Add your MikroTik routers with simple API configuration.</p>
                </div>

                <!-- Step 3 -->
                <div class="text-center">
                    <div class="relative">
                        <div
                            class="w-20 h-20 bg-accent rounded-full flex items-center justify-center mx-auto mb-4 text-3xl font-bold text-accent-content">
                            3
                        </div>
                        <div class="hidden md:block absolute top-10 left-full w-full h-0.5 bg-accent/30"></div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Generate Vouchers</h3>
                    <p class="text-base-content/70">Create and customize your WiFi access vouchers.</p>
                </div>

                <!-- Step 4 -->
                <div class="text-center">
                    <div
                        class="w-20 h-20 bg-success rounded-full flex items-center justify-center mx-auto mb-4 text-3xl font-bold text-success-content">
                        4
                    </div>
                    <h3 class="text-xl font-bold mb-2">Start Earning</h3>
                    <p class="text-base-content/70">Sell vouchers and manage your WiFi business effortlessly.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 px-4 bg-base-100">
        <div class="container mx-auto max-w-7xl">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Simple, Transparent Pricing</h2>
                <p class="text-xl text-base-content/70 max-w-2xl mx-auto">
                    Choose the plan that fits your business needs
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 max-w-6xl mx-auto">
                @forelse ($packages as $package)
                    @php($isPopular = $package->id == 2)
                    <div
                        class="card bg-base-200 shadow-xl {{ $isPopular ? 'scale-105 border-4 border-primary bg-primary text-primary-content' : '' }}">
                        @if ($isPopular)
                            <div class="badge badge-secondary absolute top-4 right-4">POPULAR</div>
                        @endif
                        <div class="card-body">
                            <h3 class="card-title text-2xl mb-2">{{ $package->name }}</h3>
                            <div class="mb-4">
                                @if ($package->price_monthly > 0)
                                    <span class="text-4xl font-bold">@userCurrency($package->price_monthly)</span>
                                    <span
                                        class="{{ $isPopular ? 'opacity-70' : 'text-base-content/70' }}">/month</span>
                                @else
                                    <span class="text-4xl font-bold">Free</span>
                                @endif
                            </div>

                            @if ($package->description)
                                <p class="text-sm {{ $isPopular ? 'opacity-80' : 'text-base-content/70' }} mb-4">
                                    {{ $package->description }}
                                </p>
                            @endif

                            <ul class="space-y-3 mb-6">
                                <li class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 {{ $isPopular ? '' : 'text-success' }}" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $package->max_routers ?? '∞' }}
                                    {{ $package->max_routers == 1 ? 'Router' : 'Routers' }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 {{ $isPopular ? '' : 'text-success' }}" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $package->max_users ?? 'Unlimited' }}
                                    {{ $package->max_users == 1 ? 'User' : 'Users' }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 {{ $isPopular ? '' : 'text-success' }}" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $package->max_zones ?? 'Unlimited' }}
                                    {{ $package->max_zones == 1 ? 'Zone' : 'Zones' }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 {{ $isPopular ? '' : 'text-success' }}" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $package->max_vouchers_per_router ?? 'Unlimited' }} Vouchers/Router
                                </li>
                                @if ($package->billing_cycle)
                                    <li class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 {{ $isPopular ? '' : 'text-success' }}"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        {{ ucfirst($package->billing_cycle) }} Billing
                                    </li>
                                @endif
                            </ul>
                            <a href="{{ route('tyro-login.register') }}"
                                class="btn {{ $isPopular ? 'btn-secondary' : 'btn-outline' }} btn-block">
                                Get Started
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <p class="text-xl text-base-content/70">No packages available at the moment.</p>
                        <a href="{{ route('tyro-login.register') }}" class="btn btn-primary mt-4">Sign Up for
                            Updates</a>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-4 bg-gradient-to-br from-primary to-secondary text-primary-content">
        <div class="container mx-auto max-w-4xl text-center">
            <h2 class="text-4xl md:text-5xl font-bold mb-6">Ready to Transform Your WiFi Business?</h2>
            <p class="text-xl mb-8 opacity-90">
                Join thousands of businesses already using RADTik to manage their WiFi hotspot operations.
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="{{ route('tyro-login.register') }}"
                    class="btn btn-lg bg-base-100 text-base-content hover:scale-105 shadow-xl">
                    Start Free Trial
                </a>
                <a href="#contact"
                    class="btn btn-lg btn-outline border-2 border-base-100 text-base-100 hover:bg-base-100 hover:text-base-content">
                    Schedule Demo
                </a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 px-4 bg-base-100">
        <div class="container mx-auto max-w-5xl">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4">Get In Touch</h2>
                <p class="text-xl text-base-content/70">
                    Have questions? We'd love to hear from you.
                </p>
            </div>

            <div class="card bg-base-200 shadow-2xl">
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success mb-6 shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6"
                                fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    <form action="{{ route('contact.store') }}" method="POST" class="space-y-6">
                        @csrf

                        {{-- Name and Email Row --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control w-full">
                                <label class="label pb-2">
                                    <span class="label-text font-semibold">Name <span
                                            class="text-error">*</span></span>
                                </label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                    placeholder="Your full name"
                                    class="input input-bordered w-full focus:input-primary @error('name') input-error @enderror"
                                    required />
                                @error('name')
                                    <label class="label pt-2">
                                        <span class="label-text-alt text-error text-sm">{{ $message }}</span>
                                    </label>
                                @enderror
                            </div>

                            <div class="form-control w-full">
                                <label class="label pb-2">
                                    <span class="label-text font-semibold">Email <span
                                            class="text-error">*</span></span>
                                </label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                    placeholder="your@email.com"
                                    class="input input-bordered w-full focus:input-primary @error('email') input-error @enderror"
                                    required />
                                @error('email')
                                    <label class="label pt-2">
                                        <span class="label-text-alt text-error text-sm">{{ $message }}</span>
                                    </label>
                                @enderror
                            </div>
                        </div>

                        {{-- WhatsApp and Subject Row --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control w-full">
                                <label class="label pb-2">
                                    <span class="label-text font-semibold">WhatsApp Number</span>
                                </label>
                                <input type="text" name="whatsapp" value="{{ old('whatsapp') }}"
                                    placeholder="+1 (234) 567-8900"
                                    class="input input-bordered w-full focus:input-primary @error('whatsapp') input-error @enderror" />
                                @error('whatsapp')
                                    <label class="label pt-2">
                                        <span class="label-text-alt text-error text-sm">{{ $message }}</span>
                                    </label>
                                @enderror
                            </div>

                            <div class="form-control w-full">
                                <label class="label pb-2">
                                    <span class="label-text font-semibold">Subject <span
                                            class="text-error">*</span></span>
                                </label>
                                <input type="text" name="subject" value="{{ old('subject') }}"
                                    placeholder="What's this about?"
                                    class="input input-bordered w-full focus:input-primary @error('subject') input-error @enderror"
                                    required />
                                @error('subject')
                                    <label class="label pt-2">
                                        <span class="label-text-alt text-error text-sm">{{ $message }}</span>
                                    </label>
                                @enderror
                            </div>
                        </div>

                        {{-- Message Row --}}
                        <div class="form-control w-full">
                            <label class="label pb-2">
                                <span class="label-text font-semibold">Message <span
                                        class="text-error">*</span></span>
                            </label>
                            <textarea name="message"
                                class="textarea textarea-bordered w-full h-32 focus:textarea-primary @error('message') textarea-error @enderror"
                                placeholder="Tell us more about your inquiry..." required>{{ old('message') }}</textarea>
                            @error('message')
                                <label class="label pt-2">
                                    <span class="label-text-alt text-error text-sm">{{ $message }}</span>
                                </label>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <div class="pt-2">
                            <button type="submit"
                                class="btn btn-primary btn-lg w-full gap-2 text-lg shadow-lg hover:shadow-xl">
                                Send Message
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/8801303705753" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp"
        class="whatsapp-float">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="32" height="32"
            aria-hidden="true">
            <path fill="currentColor"
                d="M380.9 97.1C339-10.7 210.9-32 122.1 39.1 52.3 95.4 31.8 186.8 66.2 264L32 368l107.1-33.7C214.7 370.7 307.6 352 364.9 282.2c71.1-88.8 49.8-216.9-16-255.1zM224 336c-23.7 0-47-6-67.4-17.4l-4.8-2.7-63.9 20.1 20.9-61-2.9-5C85.6 249.7 80 226.3 80 202.6 80 137.2 137.2 80 202.6 80c24.5 0 47.8 7.3 67.6 21.1 19.1 13.3 33.7 31.6 42.3 53.2 8.4 20.9 10.6 43.7 6.3 66.3-4.6 24.1-16.7 46-34.5 63.8C265.8 323.3 245 334.6 224 336zm85.2-93.5c-3.7-1.9-22-10.8-25.4-12-3.4-1.2-5.9-1.9-8.5 1.9-2.5 3.7-9.8 12-12 14.5-2.2 2.4-4.5 2.7-8.2.9-3.7-1.9-15.7-5.8-29.8-18.6-11-9.8-18.4-21.9-20.6-25.6-2.2-3.7-.2-5.7 1.6-7.5 1.6-1.6 3.7-4.5 5.5-6.7 1.9-2.2 2.5-3.7 3.7-6.1 1.2-2.4.6-4.5-.3-6.4-.9-1.9-8.5-20.5-11.7-28.1-3.1-7.4-6.3-6.4-8.5-6.5-2.2-.1-4.5-.1-6.9-.1s-6.4.9-9.8 4.5-12.9 12.6-12.9 30.8 13.2 35.7 15 38.2c1.9 2.5 26 39.8 63.1 55.8 23.4 10.1 32.5 11 44.2 9.3 7.1-1.1 22-9 25.1-17.6 3.1-8.6 3.1-16 2.2-17.6-.8-1.6-3.4-2.5-7.1-4.4z" />
        </svg>
    </a>

    <style>
        .whatsapp-float {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 2147483647;
            width: 64px;
            height: 64px;
            border-radius: 50% !important;
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4), 0 8px 24px rgba(0, 0, 0, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            overflow: visible;
        }

        .whatsapp-float::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: inherit;
            animation: whatsapp-pulse 2s ease-out infinite;
            opacity: 0;
        }

        .whatsapp-float:hover {
            transform: scale(1.1) translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 211, 102, 0.5), 0 12px 32px rgba(0, 0, 0, 0.2);
        }

        .whatsapp-float:active {
            transform: scale(1.05);
        }

        @keyframes whatsapp-pulse {
            0% {
                transform: scale(1);
                opacity: 0.6;
            }

            50% {
                transform: scale(1.3);
                opacity: 0;
            }

            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        @media (max-width: 640px) {
            .whatsapp-float {
                width: 56px;
                height: 56px;
                bottom: 16px;
                right: 16px;
            }

            .whatsapp-float svg {
                width: 28px;
                height: 28px;
            }
        }
    </style>

    <!-- Footer -->
    <footer class="footer footer-center p-10 bg-base-200 text-base-content">
        <div>
            <div class="flex items-center gap-2 text-2xl font-bold gradient-text">
                <img src="{{ asset('logo_color.png') }}" alt="RADTik Logo" class="h-8 w-8 object-contain" />
            </div>
            <p class="font-medium">Professional MikroTik WiFi Hotspot Management</p>
        </div>
        <div>
            <div class="grid grid-flow-col gap-4">
                <a class="link link-hover">About</a>
                <a class="link link-hover">Features</a>
                <a class="link link-hover">Pricing</a>
                <a class="link link-hover">Documentation</a>
                <a class="link link-hover">Support</a>
            </div>
        </div>
        <div>
            <div class="grid grid-flow-col gap-4">
                <a class="btn btn-ghost btn-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" />
                    </svg>
                </a>
                <a class="btn btn-ghost btn-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                    </svg>
                </a>
                <a class="btn btn-ghost btn-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z" />
                    </svg>
                </a>
            </div>
        </div>
        <div>
            <p>Copyright © {{ date('Y') }} RADTik - All rights reserved</p>
        </div>
    </footer>

</body>

</html>
