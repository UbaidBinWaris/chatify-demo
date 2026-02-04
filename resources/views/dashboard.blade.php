<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Welcome Section -->
            <div class="glass rounded-3xl p-8 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-64 h-64 bg-violet-600/20 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-white mb-2">Welcome back, {{ Auth::user()->name }}! 👋</h2>
                    <p class="text-gray-400 text-lg">Ready to connect with your friends and colleagues today?</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Main Action Card -->
                <div class="md:col-span-2 glass rounded-3xl p-8 flex flex-col justify-between relative overflow-hidden group hover:border-violet-500/30 transition-colors duration-300">
                    <div class="absolute inset-0 bg-gradient-to-br from-violet-600/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-start justify-between mb-8">
                            <div>
                                <h3 class="text-2xl font-bold text-white mb-2">Launch Messenger</h3>
                                <p class="text-gray-400 max-w-md">Access your chats, groups, and contacts in our real-time messaging interface.</p>
                            </div>
                            <div class="p-3 bg-violet-600/20 rounded-xl text-violet-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                        </div>
                        
                        <a href="{{ url('/chatify') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-bold text-sm shadow-lg shadow-violet-600/30 hover:shadow-violet-600/50 hover:scale-[1.02] transition-all duration-300">
                            <span>Open Chatify</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Stats / Info -->
                <div class="glass rounded-3xl p-8 flex flex-col justify-center relative overflow-hidden group hover:border-cyan-500/30 transition-colors duration-300">
                    <div class="absolute inset-0 bg-gradient-to-br from-cyan-600/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    
                    <div class="relative z-10 text-center">
                        <div class="w-16 h-16 mx-auto bg-cyan-500/20 rounded-full flex items-center justify-center text-cyan-400 mb-4 text-2xl">
                            🚀
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Did You Know?</h3>
                        <p class="text-gray-400 text-sm mb-6">You can now create group chats and share files instantly with your team.</p>
                        <div class="text-cyan-400 text-sm font-medium">Explore Features &rarr;</div>
                    </div>
                </div>
            </div>

            <!-- Guides Section -->
            <div class="glass rounded-3xl p-8">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <span class="p-1 bg-white/10 rounded-lg">📚</span> Quick Guide
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-4 rounded-2xl bg-white/5 border border-white/5 hover:border-white/10 transition-colors">
                        <h4 class="font-bold text-white mb-2 flex items-center gap-2">
                            <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Start a New Chat
                        </h4>
                        <p class="text-sm text-gray-400">Search for users in the messenger sidebar and click their name to start a conversation instantly.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/5 border border-white/5 hover:border-white/10 transition-colors">
                        <h4 class="font-bold text-white mb-2 flex items-center gap-2">
                            <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            Create a Group
                        </h4>
                        <p class="text-sm text-gray-400">Go to the "Groups" tab and click "Create New Group" to get your team together in one place.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
