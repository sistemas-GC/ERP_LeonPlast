<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Leon Plast ERP</title>
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
                    <i class="fas fa-key text-3xl text-emerald-300"></i>
                </div>
                <h2 class="text-3xl font-bold text-white mb-4 leading-tight">Seguridad y <br>Acceso al <br><span class="text-emerald-300">Sistema</span></h2>
                <p class="text-emerald-100/80 text-sm leading-relaxed">
                    Por políticas de seguridad corporativa, la recuperación o restablecimiento de credenciales se realiza a través de la administración central.
                </p>
            </div>
            
            <div class="relative z-10 flex items-center gap-3 text-emerald-200/60 text-xs font-semibold tracking-widest uppercase">
                <span>Leon Plast</span>
                <span class="w-8 h-[1px] bg-emerald-200/40"></span>
                <span>Soporte TI</span>
            </div>
        </div>

        <!-- Right Side: Info & Actions -->
        <div class="w-full md:w-7/12 glass-panel p-8 sm:p-12 lg:p-16 relative flex flex-col justify-center">
            <div class="max-w-sm mx-auto w-full">
                
                <div class="text-center md:text-left mb-8">
                    <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mb-4 mx-auto md:mx-0 shadow-sm">
                        <i class="fas fa-shield-alt text-xl"></i>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight">¿Olvidaste tu contraseña?</h1>
                    <p class="text-sm text-slate-500 mt-2 font-medium">Sigue estas instrucciones para restablecer tu acceso.</p>
                </div>

                <div class="space-y-4 mb-8">
                    <div class="p-4 bg-emerald-50/80 border border-emerald-200 rounded-2xl">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-user-shield text-emerald-600 mt-0.5"></i>
                            <div>
                                <h4 class="text-xs font-bold text-emerald-900 uppercase tracking-wide">Contactar al Administrador</h4>
                                <p class="text-xs text-emerald-700 mt-1 leading-relaxed">
                                    Solicita el restablecimiento de tu contraseña comunicándote directamente con el Administrador del Sistema o Área de Sistemas.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-2xl text-xs text-slate-600 space-y-2">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-envelope text-slate-400"></i>
                            <span>Correo de soporte: <strong class="text-slate-800">admin@leonplast.com</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-id-card text-slate-400"></i>
                            <span>Ten a la mano tu <strong>código de trabajador</strong> o <strong>usuario</strong>.</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <a href="{{ route('login') }}" class="w-full flex items-center justify-center gap-2 py-3.5 px-4 border border-transparent rounded-xl shadow-lg shadow-primary/30 text-sm font-bold text-white bg-primary hover:bg-primary-dark hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-primary/50 transition-all duration-200">
                        <i class="fas fa-arrow-left mr-1"></i> Volver a Iniciar Sesión
                    </a>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
