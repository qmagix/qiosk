<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const email = ref('')
const token = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const success = ref(false)
const isLoading = ref(false)

onMounted(() => {
  // Get token from route params and email from query string
  token.value = route.params.token || ''
  email.value = route.query.email || ''

  if (!token.value || !email.value) {
    error.value = 'Invalid reset link. Please request a new password reset.'
  }
})

const handleSubmit = async () => {
  error.value = ''

  if (password.value !== passwordConfirmation.value) {
    error.value = 'Passwords do not match.'
    return
  }

  if (password.value.length < 8) {
    error.value = 'Password must be at least 8 characters.'
    return
  }

  isLoading.value = true

  try {
    await axios.post('/api/reset-password', {
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    })
    success.value = true
  } catch (e) {
    console.error(e)
    error.value = e.response?.data?.message || 'Failed to reset password. Please try again.'
  } finally {
    isLoading.value = false
  }
}

const goToLogin = () => {
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded shadow-md w-96">
      <h1 class="text-2xl font-bold mb-6 text-center">Reset Password</h1>

      <div v-if="success" class="text-center">
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
          <p class="font-medium">Password Reset Successful</p>
          <p class="text-sm mt-2">Your password has been updated. You can now log in with your new password.</p>
        </div>
        <button
          @click="goToLogin"
          class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
        >
          Go to Login
        </button>
      </div>

      <form v-else @submit.prevent="handleSubmit">
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
            Email
          </label>
          <input
            v-model="email"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100"
            id="email"
            type="email"
            readonly
          >
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
            New Password
          </label>
          <input
            v-model="password"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            id="password"
            type="password"
            placeholder="Enter new password"
            required
            :disabled="isLoading || (!token && !email)"
          >
        </div>

        <div class="mb-6">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="password_confirmation">
            Confirm Password
          </label>
          <input
            v-model="passwordConfirmation"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            id="password_confirmation"
            type="password"
            placeholder="Confirm new password"
            required
            :disabled="isLoading || (!token && !email)"
          >
        </div>

        <p v-if="error" class="text-red-500 text-xs italic mb-4">{{ error }}</p>

        <div class="flex items-center justify-between flex-col gap-4">
          <button
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full disabled:opacity-50 disabled:cursor-not-allowed"
            type="submit"
            :disabled="isLoading || !token || !email"
          >
            {{ isLoading ? 'Resetting...' : 'Reset Password' }}
          </button>
          <router-link to="/login" class="text-blue-500 hover:text-blue-800 text-sm">
            Back to Login
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>
