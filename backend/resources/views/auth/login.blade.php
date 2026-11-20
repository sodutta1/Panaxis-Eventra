<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - EventFlow ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-rose-950 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-4xl bg-slate-900/90 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-700 overflow-hidden grid grid-cols-1 md:grid-cols-12">
        <!-- Left Branding Panel -->
        <div class="md:col-span-5 bg-gradient-to-br from-rose-700 via-rose-800 to-slate-900 p-8 flex flex-col justify-between text-white relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-8">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center font-bold text-2xl shadow-lg">
                        <i class="fa-solid fa-crown text-amber-300"></i>
                    </div>
                    <div>
                        <span class="font-extrabold text-2xl tracking-tight">Event<span class="text-amber-300">Flow</span></span>
                        <span class="block text-xs text-rose-200 tracking-widest uppercase">ERP 2.0</span>
                    </div>
                </div>

                <h2 class="text-2xl font-bold mb-3 leading-snug">Enterprise Event Lifecycle Management</h2>
                <p class="text-sm text-rose-100/80 mb-6">Unified system for luxury weddings, corporate summits, global concert tours, exhibitions, and guest operations.</p>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check text-emerald-400"></i>
                        <span>Multi-Tenant & Multi-Branch Architecture</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check text-emerald-400"></i>
                        <span>Lead CRM, Quotes & Automated Invoicing</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check text-emerald-400"></i>
                        <span>Run-of-Show Schedules & Vendor Tracking</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check text-emerald-400"></i>
                        <span>Live RSVP & Gate QR Check-in System</span>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-4 border-t border-rose-600/50 text-[11px] text-rose-200 relative z-10">
                GrandVibe Events & Productions Pvt. Ltd. &copy; 2026
            </div>
        </div>

        <!-- Right Sign In Panel -->
        <div class="md:col-span-7 p-8 flex flex-col justify-center">
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-white mb-1">Welcome back</h3>
                <p class="text-xs text-slate-400">Sign in to your EventFlow ERP account or choose a demo role below.</p>
            </div>

            @if ($errors->any())
                <div class="bg-rose-500/10 border border-rose-500 text-rose-400 text-xs p-3 rounded-lg mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Email Address</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3 top-3 text-slate-500 text-xs"></i>
                        <input type="email" name="email" value="{{ old('email', 'admin@eventflow.test') }}" required class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1.5">Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-3 text-slate-500 text-xs"></i>
                        <input type="password" name="password" value="password" required class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 transition">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-400">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded bg-slate-800 border-slate-700 text-rose-600 focus:ring-0">
                        <span>Remember session</span>
                    </label>
                    <span class="text-rose-400">Default: password</span>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 text-white font-semibold py-2.5 rounded-lg shadow-lg shadow-rose-900/30 text-sm transition">
                    Sign In
                </button>
            </form>

            <!-- One-Click Demo Logins -->
            <div class="mt-8 pt-6 border-t border-slate-800">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Quick Demo Role Access</span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a href="{{ route('demo.login', 1) }}" class="flex items-center space-x-2 p-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 transition">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                        <div class="truncate">
                            <span class="font-medium block truncate">Super Admin</span>
                            <span class="text-[10px] text-slate-400">Vikramaditya S.</span>
                        </div>
                    </a>

                    <a href="{{ route('demo.login', 2) }}" class="flex items-center space-x-2 p-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 transition">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <div class="truncate">
                            <span class="font-medium block truncate">Event Director</span>
                            <span class="text-[10px] text-slate-400">Priya Sharma</span>
                        </div>
                    </a>

                    <a href="{{ route('demo.login', 3) }}" class="flex items-center space-x-2 p-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 transition">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <div class="truncate">
                            <span class="font-medium block truncate">Sales Lead</span>
                            <span class="text-[10px] text-slate-400">Rajesh Verma</span>
                        </div>
                    </a>

                    <a href="{{ route('demo.login', 4) }}" class="flex items-center space-x-2 p-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 transition">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <div class="truncate">
                            <span class="font-medium block truncate">Finance Lead</span>
                            <span class="text-[10px] text-slate-400">Anita Desai</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

