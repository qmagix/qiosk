<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="min-h-screen bg-white flex flex-col font-sans text-gray-900">
    <!-- Navigation -->
    <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-xl">E</div>
        <span class="text-2xl font-bold tracking-tight">EyePub</span>
      </div>
      <div class="flex items-center gap-4">
        <RouterLink to="/login" class="text-gray-600 hover:text-blue-600 font-medium">Log in</RouterLink>
        <RouterLink to="/register" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-full font-medium transition">Get Started</RouterLink>
      </div>
    </nav>

    <!-- Hero Section -->
    <header class="container mx-auto px-6 py-16 md:py-24 flex flex-col md:flex-row items-center">
      <div class="md:w-1/2 mb-12 md:mb-0">
        <h1 class="text-5xl md:text-6xl font-extrabold leading-tight mb-6">
          Publishing for <span class="text-blue-600">Eyes</span>.
        </h1>
        <p class="text-xl text-gray-600 mb-8 leading-relaxed">
          Turn any screen into a powerful digital signage display. 
          Manage content remotely, schedule playlists, and engage your audience instantly.
        </p>
        <div class="flex flex-col sm:flex-row gap-4">
          <RouterLink to="/register" class="bg-blue-600 hover:bg-blue-700 text-white text-center px-8 py-4 rounded-full text-lg font-bold transition shadow-lg hover:shadow-xl transform hover:-translate-y-1">
            Start Publishing Free
          </RouterLink>
          <RouterLink to="/login" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-center px-8 py-4 rounded-full text-lg font-bold transition">
            View Demo
          </RouterLink>
        </div>
      </div>
      <div class="md:w-1/2 relative">
        <!-- Abstract visual representation of a screen -->
        <div class="relative z-10 bg-gray-900 rounded-2xl shadow-2xl p-2 transform rotate-2 hover:rotate-0 transition duration-500">
          <div class="bg-gray-800 rounded-xl overflow-hidden aspect-video relative">
            <div class="absolute inset-0 flex items-center justify-center">
               <span class="text-gray-500 text-6xl opacity-20 font-bold">4K</span>
            </div>
            <!-- Simulated UI -->
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6">
              <div class="h-4 w-3/4 bg-gray-600 rounded mb-2"></div>
              <div class="h-3 w-1/2 bg-gray-700 rounded"></div>
            </div>
          </div>
        </div>
        <div class="absolute -top-10 -right-10 w-64 h-64 bg-blue-100 rounded-full filter blur-3xl opacity-50 z-0"></div>
        <div class="absolute -bottom-10 -left-10 w-64 h-64 bg-purple-100 rounded-full filter blur-3xl opacity-50 z-0"></div>
      </div>
    </header>

    <!-- Features Section -->
    <section class="bg-gray-50 py-20">
      <div class="container mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold mb-4">Why Choose EyePub?</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">Everything you need to manage a network of screens, from a single kiosk to a global fleet.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-12">
          <!-- Feature 1 -->
          <div class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-6">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold mb-3">Manage Anywhere</h3>
            <p class="text-gray-600">Control your screens from any device, anywhere in the world. Push updates instantly via the cloud.</p>
          </div>

          <!-- Feature 2 -->
          <div class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center mb-6">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold mb-3">Offline First</h3>
            <p class="text-gray-600">Internet down? No problem. EyePub caches content locally so your screens never go dark.</p>
          </div>

          <!-- Feature 3 -->
          <div class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-md transition">
            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-6">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold mb-3">4K Ready</h3>
            <p class="text-gray-600">Crystal clear playback for images and videos. Optimized for high-resolution displays.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20">
      <div class="container mx-auto px-6">
        <div class="bg-blue-600 rounded-3xl p-12 text-center text-white relative overflow-hidden">
          <div class="relative z-10">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to transform your screens?</h2>
            <p class="text-blue-100 text-lg mb-8 max-w-2xl mx-auto">Join thousands of businesses using EyePub to captivate their audience.</p>
            <RouterLink to="/register" class="inline-block bg-white text-blue-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-gray-100 transition">
              Get Started for Free
            </RouterLink>
          </div>
          <!-- Decorative circles -->
          <div class="absolute top-0 left-0 w-64 h-64 bg-white opacity-10 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
          <div class="absolute bottom-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full translate-x-1/2 translate-y-1/2"></div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12 mt-auto">
      <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <div class="flex items-center gap-2 mb-4 md:mb-0">
            <div class="w-6 h-6 bg-gray-700 rounded flex items-center justify-center text-white font-bold text-xs">E</div>
            <span class="text-white font-bold text-lg">EyePub</span>
          </div>
          <div class="text-sm">
            &copy; {{ new Date().getFullYear() }} EyePub. All rights reserved.
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
