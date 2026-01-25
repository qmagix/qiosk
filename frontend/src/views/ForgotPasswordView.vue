<script setup>
import { ref } from 'vue'
import axios from 'axios'

const email = ref('')
const error = ref('')
const success = ref(false)
const isLoading = ref(false)

const handleSubmit = async () => {
  error.value = ''
  isLoading.value = true

  try {
    await axios.post('/api/forgot-password', {
      email: email.value
    })
    success.value = true
  } catch (e) {
    console.error(e)
    error.value = e.response?.data?.message || 'Failed to send reset email. Please try again.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded shadow-md w-96">
      <h1 class="text-2xl font-bold mb-6 text-center">Forgot Password</h1>

      <div v-if="success" class="text-center">
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
          <p class="font-medium">Check your email</p>
          <p class="text-sm mt-2">If an account exists with this email, you will receive a password reset link.</p>
        </div>
        <router-link to="/login" class="text-blue-500 hover:text-blue-800 text-sm">
          Back to Login
        </router-link>
      </div>

      <form v-else @submit.prevent="handleSubmit">
        <p class="text-gray-600 text-sm mb-4">
          Enter your email address and we'll send you a link to reset your password.
        </p>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
            Email
          </label>
          <input
            v-model="email"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            id="email"
            type="email"
            placeholder="Enter your email"
            required
            :disabled="isLoading"
          >
        </div>

        <p v-if="error" class="text-red-500 text-xs italic mb-4">{{ error }}</p>

        <div class="flex items-center justify-between flex-col gap-4">
          <button
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full disabled:opacity-50 disabled:cursor-not-allowed"
            type="submit"
            :disabled="isLoading"
          >
            {{ isLoading ? 'Sending...' : 'Send Reset Link' }}
          </button>
          <router-link to="/login" class="text-blue-500 hover:text-blue-800 text-sm">
            Back to Login
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>
