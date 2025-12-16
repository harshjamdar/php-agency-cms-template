<!-- Cost Estimator -->
<section id="estimator" class="py-20 bg-slate-950 relative overflow-hidden">
    <!-- Glow effect -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full max-w-4xl bg-primary/5 blur-3xl rounded-full pointer-events-none"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-12 fade-in-up">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/10 text-secondary mb-4 border border-secondary/20">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16" y2="18"></line><path d="M16 10h.01"></path><path d="M12 10h.01"></path><path d="M8 10h.01"></path><path d="M12 14h.01"></path><path d="M8 14h.01"></path><path d="M12 18h.01"></path><path d="M8 18h.01"></path></svg>
                <span class="text-sm font-semibold">Get Instant Quote</span>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Project Cost Estimator</h2>
            <p class="text-gray-400">Select your project type and complexity to get a rough estimate.</p>
        </div>

        <div class="bg-slate-900/80 backdrop-blur-sm border border-slate-800 rounded-2xl p-6 md:p-8 shadow-2xl fade-in-up">
            <!-- Step 1: Service Type -->
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-400 mb-3">1. Choose Service</label>
                <div class="grid grid-cols-3 gap-3">
                    <button class="service-btn py-3 px-4 rounded-xl text-sm md:text-base font-semibold transition-all border bg-primary text-white border-primary shadow-[0_0_15px_rgba(139,92,246,0.3)] hover:scale-105" data-service="web">Website</button>
                    <button class="service-btn py-3 px-4 rounded-xl text-sm md:text-base font-semibold transition-all border bg-slate-800 text-gray-300 border-slate-700 hover:bg-slate-700 hover:border-slate-600" data-service="app">Mobile App</button>
                    <button class="service-btn py-3 px-4 rounded-xl text-sm md:text-base font-semibold transition-all border bg-slate-800 text-gray-300 border-slate-700 hover:bg-slate-700 hover:border-slate-600" data-service="marketing">Marketing</button>
                </div>
            </div>

            <!-- Step 2: Complexity -->
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-400 mb-3">2. Select Complexity</label>
                <div class="grid grid-cols-3 gap-3">
                    <button class="complexity-btn py-3 px-4 rounded-xl text-sm md:text-base font-semibold transition-all border bg-secondary text-white border-secondary shadow-[0_0_15px_rgba(6,182,212,0.3)] hover:scale-105" data-complexity="basic">Basic</button>
                    <button class="complexity-btn py-3 px-4 rounded-xl text-sm md:text-base font-semibold transition-all border bg-slate-800 text-gray-300 border-slate-700 hover:bg-slate-700 hover:border-slate-600" data-complexity="standard">Standard</button>
                    <button class="complexity-btn py-3 px-4 rounded-xl text-sm md:text-base font-semibold transition-all border bg-slate-800 text-gray-300 border-slate-700 hover:bg-slate-700 hover:border-slate-600" data-complexity="premium">Premium</button>
                </div>
            </div>

            <!-- Result -->
            <div class="bg-slate-950 rounded-xl p-6 border border-slate-800">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                    <div>
                        <p class="text-sm text-gray-400 mb-1">Estimated Cost Range</p>
                        <p id="price-display" class="text-3xl font-bold text-white">₹15,000 - ₹25,000</p>
                    </div>
                    <a href="#contact" class="bg-white text-black px-6 py-2 rounded-lg font-bold hover:bg-gray-200 transition-colors shadow-lg hover:shadow-white/20">
                        Book Consultation
                    </a>
                </div>
                
                <div class="border-t border-slate-800 pt-4">
                    <p class="text-sm font-medium text-gray-400 mb-3">Included Features:</p>
                    <ul id="features-list" class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                        <!-- Features populated by JS -->
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>