<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EventFlow ERP') - Event Management ERP</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fdf4f5',
                            100: '#fbe8eb',
                            200: '#f7d5db',
                            500: '#e11d48',
                            600: '#be123c',
                            700: '#9f1239',
                            800: '#881337',
                            900: '#4c0519',
                        },
                        navy: {
                            800: '#0f172a',
                            900: '#020617',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased font-sans flex h-screen overflow-hidden" x-data="{ sidebarOpen: true, mobileMenuOpen: false }">

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-slate-900 text-slate-300 flex flex-col transition-all duration-300 ease-in-out shrink-0 z-30 shadow-xl hidden md:flex">
        <!-- Logo -->
        <div class="h-16 flex items-center justify-between px-4 border-b border-slate-800">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 overflow-hidden">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 to-amber-500 flex items-center justify-center text-white font-bold text-xl shadow-lg shrink-0">
                    <i class="fa-solid fa-crown text-base"></i>
                </div>
                <div x-show="sidebarOpen" class="transition-opacity duration-200">
                    <span class="font-extrabold text-lg text-white tracking-tight">Event<span class="text-rose-500">Flow</span></span>
                    <span class="block text-[10px] text-slate-400 font-medium uppercase tracking-wider">Enterprise ERP</span>
                </div>
            </a>
            <button @click="sidebarOpen = !sidebarOpen" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                <i :class="sidebarOpen ? 'fa-angles-left' : 'fa-angles-right'" class="fa-solid text-sm"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 text-sm custom-scrollbar">
            <!-- Core -->
            <div x-show="sidebarOpen" class="px-3 pt-2 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Main</div>
            <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-chart-pie w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Dashboard</span>
            </a>

            <a href="{{ route('events.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('events.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-calendar-check w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3 flex-1">Events</span>
                <span x-show="sidebarOpen" class="bg-rose-950 text-rose-300 text-xs px-2 py-0.5 rounded-full font-bold">7</span>
            </a>

            <!-- CRM -->
            <div x-show="sidebarOpen" class="px-3 pt-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">CRM & Sales</div>
            <a href="{{ route('leads.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('leads.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-funnel-dollar w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Leads & Enquiries</span>
            </a>
            <a href="{{ route('clients.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('clients.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-users w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Clients</span>
            </a>
            <a href="{{ route('quotations.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('quotations.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-file-invoice w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Quotations</span>
            </a>

            <!-- Operations -->
            <div x-show="sidebarOpen" class="px-3 pt-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Operations</div>
            <a href="{{ route('venues.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('venues.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-hotel w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Venues</span>
            </a>
            <a href="{{ route('vendors.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('vendors.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-handshake w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Vendors & Partners</span>
            </a>
            <a href="{{ route('inventory.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('inventory.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-boxes-stacked w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Inventory & Assets</span>
            </a>
            <a href="{{ route('guests.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('guests.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-envelope-open-text w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Guests & RSVPs</span>
            </a>
            <a href="{{ route('tickets.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('tickets.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-qrcode w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Ticketing & QR Gate</span>
            </a>

            <!-- Finance -->
            <div x-show="sidebarOpen" class="px-3 pt-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Finance & Billing</div>
            <a href="{{ route('invoices.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('invoices.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-file-invoice-dollar w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Invoices & GST</span>
            </a>
            <a href="{{ route('expenses.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('expenses.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-receipt w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Event Expenses</span>
            </a>
            <a href="{{ route('reports.index') }}" class="flex items-center px-3 py-2.5 rounded-lg {{ request()->routeIs('reports.*') ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition">
                <i class="fa-solid fa-chart-line w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Business Reports</span>
            </a>

            <!-- Portals -->
            <div x-show="sidebarOpen" class="px-3 pt-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Portals</div>
            <a href="{{ route('portal.client') }}" class="flex items-center px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition">
                <i class="fa-solid fa-user-tie w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Client Portal View</span>
            </a>
            <a href="{{ route('portal.vendor') }}" class="flex items-center px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition">
                <i class="fa-solid fa-truck-ramp-box w-6 text-center text-base"></i>
                <span x-show="sidebarOpen" class="ml-3">Vendor Portal View</span>
            </a>
        </nav>

        <!-- Current User Profile Footer -->
        <div class="p-3 border-t border-slate-800 bg-slate-950/50">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-slate-700 flex items-center justify-center font-bold text-white uppercase shrink-0">
                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                </div>
                <div x-show="sidebarOpen" class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="text-[11px] text-rose-400 font-medium truncate capitalize">{{ str_replace('_', ' ', auth()->user()->role ?? 'Admin') }}</p>
                </div>
                <form x-show="sidebarOpen" action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" title="Logout" class="text-slate-400 hover:text-red-400 p-1.5 rounded-lg hover:bg-slate-800 transition">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Top Navbar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-6 z-20 shrink-0">
            <div class="flex items-center space-x-3">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-slate-600 hover:text-slate-900 p-2">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <div class="hidden sm:flex items-center space-x-2 text-xs bg-slate-100 px-3 py-1.5 rounded-full border border-slate-200">
                    <i class="fa-solid fa-location-dot text-rose-600"></i>
                    <span class="font-medium text-slate-700">Mumbai Corporate HQ</span>
                    <span class="text-slate-300">|</span>
                    <span class="text-slate-500">INR (₹)</span>
                </div>
            </div>

            <!-- Top Search & Quick Actions -->
            <div class="flex items-center space-x-3 md:space-x-4">
                <!-- Demo Quick Switcher -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-2 text-xs font-medium bg-rose-50 hover:bg-rose-100 text-rose-700 px-3 py-1.5 rounded-lg border border-rose-200 transition">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span class="hidden sm:inline">Switch Demo Role</span>
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50">
                        <div class="px-3 py-1 text-[11px] font-bold uppercase text-slate-400">Switch User Session</div>
                        <a href="{{ route('demo.login', 1) }}" class="flex items-center px-3 py-2 text-xs hover:bg-slate-50 text-slate-700">
                            <span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span>
                            <div class="flex-1 font-medium">Vikramaditya S. <span class="text-slate-400 text-[10px]">(Super Admin)</span></div>
                        </a>
                        <a href="{{ route('demo.login', 2) }}" class="flex items-center px-3 py-2 text-xs hover:bg-slate-50 text-slate-700">
                            <span class="w-2 h-2 rounded-full bg-blue-500 mr-2"></span>
                            <div class="flex-1 font-medium">Priya Sharma <span class="text-slate-400 text-[10px]">(Event Manager)</span></div>
                        </a>
                        <a href="{{ route('demo.login', 3) }}" class="flex items-center px-3 py-2 text-xs hover:bg-slate-50 text-slate-700">
                            <span class="w-2 h-2 rounded-full bg-amber-500 mr-2"></span>
                            <div class="flex-1 font-medium">Rajesh Verma <span class="text-slate-400 text-[10px]">(Sales Exec)</span></div>
                        </a>
                        <a href="{{ route('demo.login', 4) }}" class="flex items-center px-3 py-2 text-xs hover:bg-slate-50 text-slate-700">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                            <div class="flex-1 font-medium">Anita Desai <span class="text-slate-400 text-[10px]">(Accountant)</span></div>
                        </a>
                        <a href="{{ route('demo.login', 5) }}" class="flex items-center px-3 py-2 text-xs hover:bg-slate-50 text-slate-700">
                            <span class="w-2 h-2 rounded-full bg-purple-500 mr-2"></span>
                            <div class="flex-1 font-medium">Rahul Oberoi <span class="text-slate-400 text-[10px]">(Client Portal)</span></div>
                        </a>
                    </div>
                </div>

                <a href="{{ route('events.create') }}" class="inline-flex items-center space-x-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-sm transition">
                    <i class="fa-solid fa-plus"></i>
                    <span class="hidden sm:inline">New Event</span>
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 mx-6 mt-4 rounded-r-lg shadow-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                    <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mx-6 mt-4 rounded-r-lg shadow-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg"></i>
                    <p class="text-sm font-medium text-amber-800">{{ session('warning') }}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 mx-6 mt-4 rounded-r-lg shadow-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-circle-xmark text-rose-600 text-lg"></i>
                    <p class="text-sm font-medium text-rose-800">{{ session('error') }}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        <!-- Main Body Content -->
        <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-slate-50">
            @yield('content')
        </main>
    </div>

    <!-- Mobile Drawer -->
    <div x-show="mobileMenuOpen" @click.away="mobileMenuOpen = false" x-cloak class="fixed inset-0 z-40 bg-black/60 md:hidden flex">
        <div class="w-64 bg-slate-900 text-white h-full p-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <span class="font-extrabold text-lg text-white">Event<span class="text-rose-500">Flow</span></span>
                    <button @click="mobileMenuOpen = false" class="text-slate-400 p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <div class="py-4 space-y-2 text-sm">
                    <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Dashboard</a>
                    <a href="{{ route('events.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Events</a>
                    <a href="{{ route('leads.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Leads</a>
                    <a href="{{ route('clients.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Clients</a>
                    <a href="{{ route('venues.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Venues</a>
                    <a href="{{ route('vendors.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Vendors</a>
                    <a href="{{ route('quotations.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Quotations</a>
                    <a href="{{ route('invoices.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Invoices</a>
                    <a href="{{ route('expenses.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Expenses</a>
                    <a href="{{ route('inventory.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Inventory</a>
                    <a href="{{ route('guests.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Guests & RSVPs</a>
                    <a href="{{ route('tickets.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Tickets & QR</a>
                    <a href="{{ route('reports.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">Reports</a>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 py-2 rounded-lg font-medium text-xs">Logout</button>
            </form>
        </div>
    </div>

    @yield('scripts')
</body>
</html>

