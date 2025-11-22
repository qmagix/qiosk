<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const email = ref('')
const password = ref('')
const error = ref('')
const router = useRouter()

const handleLogin = async () => {
  try {
    const response = await axios.post('/api/login', {
      email: email.value,
      password: password.value
    })
    localStorage.setItem('token', response.data.token)
    axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
    router.push('/admin')
  } catch (e) {
    console.error(e)
    error.value = e.response?.data?.message || 'Invalid credentials'
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded shadow-md w-96">
      <h1 class="text-2xl font-bold mb-6 text-center">EyePub Login</h1>
      <form @submit.prevent="handleLogin">
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
            Email
          </label>
          <input 
            v-model="email"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
            id="email" 
            type="email" 
            placeholder="Email"
            required
          >
        </div>
        <div class="mb-6">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
            Password
          </label>
          <input 
            v-model="password"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" 
            id="password" 
            type="password" 
            placeholder="******************"
            required
          >
          <p v-if="error" class="text-red-500 text-xs italic">{{ error }}</p>
        </div>
        <div class="flex items-center justify-between flex-col gap-4">
          <button 
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" 
            type="submit"
          >
            Sign In
          </button>
          <router-link to="/register" class="text-blue-500 hover:text-blue-800 text-sm">
            Need an account? Register
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>
