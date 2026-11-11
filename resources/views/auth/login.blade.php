<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Leon Plast</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#059669',
                        'primary-dark': '#047857',
                        'primary-light': '#10b981',
                    },
                    animation: {
                        'blob': 'blob 7s infinite',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .input-group:focus-within label {
            color: #059669;
        }
        .input-group:focus-within i {
            color: #059669;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased relative overflow-hidden flex items-center justify-center min-h-screen">

    <!-- Animated Background Blobs -->
    <div class="absolute top-0 -left-4 w-72 h-72 bg-emerald-300 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-blob"></div>
    <div class="absolute top-0 -right-4 w-72 h-72 bg-teal-300 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-green-300 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-blob animation-delay-4000"></div>

    <div class="w-full max-w-4xl flex shadow-2xl rounded-3xl overflow-hidden relative z-10 m-4">
        
        <!-- Left Side: Branding/Image -->
        <div class="hidden md:flex md:w-5/12 bg-gradient-to-br from-slate-900 via-emerald-900 to-primary-dark p-12 flex-col justify-between relative overflow-hidden">
            <!-- Decorative overlay -->
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20"></div>
            <div class="absolute -bottom-24 -right-24 w-64 h-64 border-[30px] border-emerald-500/20 rounded-full"></div>
            <div class="absolute -top-10 -left-10 w-40 h-40 border-[20px] border-emerald-500/20 rounded-full"></div>
            
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20 mb-8 shadow-lg">
                    <i class="fas fa-industry text-3xl text-emerald-300"></i>
                </div>
                <h2 class="text-3xl font-bold text-white mb-4 leading-tight">Gestión <br>Inteligente de <br><span class="text-emerald-300">Producción</span></h2>
                <p class="text-emerald-100/80 text-sm leading-relaxed">
                    Control absoluto sobre tus inventarios, procesos y trazabilidad industrial. Todo en un solo lugar.
                </p>
            </div>
            
            <div class="relative z-10 flex items-center gap-3 text-emerald-200/60 text-xs font-semibold tracking-widest uppercase">
                <span>Leon Plast</span>
                <span class="w-8 h-[1px] bg-emerald-200/40"></span>
                <span>ERP</span>
            </div>
        </div>

        <!-- Right Side: Form -->
        <div class="w-full md:w-7/12 glass-panel p-8 sm:p-12 lg:p-16 relative">
            <div class="max-w-sm mx-auto">
                <div class="text-center md:text-left mb-10">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight">Bienvenido de nuevo</h1>
                    <p class="text-sm text-slate-500 mt-2 font-medium">Ingresa tus credenciales para acceder al sistema.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg text-sm font-medium shadow-sm animate-pulse">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" class="space-y-6">
                    @csrf

                    <div class="input-group">
                        <label for="nombre_usuario" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 transition-colors">Usuario o Correo</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-user text-slate-400 transition-colors"></i>
                            </div>
                            <input id="nombre_usuario" name="nombre_usuario" type="text" autocomplete="username" required value="{{ old('nombre_usuario') }}"
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary focus:bg-white transition-all shadow-sm placeholder-slate-300" placeholder="Ej. admin o admin@leonplast.com">
                        </div>
                    </div>

                    <div class="input-group">
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider transition-colors">Contraseña</label>
                            <a href="{{ route('password.request') }}" class="text-[11px] font-bold text-primary hover:text-primary-dark transition-colors">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-slate-400 transition-colors"></i>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary focus:bg-white transition-all shadow-sm placeholder-slate-300" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center mt-2">
                        <div class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input id="remember" name="remember" type="checkbox" class="w-4 h-4 text-primary bg-slate-100 border-slate-300 rounded focus:ring-primary focus:ring-2 transition-all cursor-pointer">
                            </div>
                            <div class="ml-2 text-sm">
                                <label for="remember" class="font-medium text-slate-600 cursor-pointer select-none">Mantener sesión iniciada</label>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-3.5 px-4 border border-transparent rounded-xl shadow-lg shadow-primary/30 text-sm font-bold text-white bg-primary hover:bg-primary-dark hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-primary/50 transition-all duration-200">
                            Ingresar al Sistema <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>