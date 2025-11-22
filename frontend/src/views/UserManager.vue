<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

const users = ref([])
const currentUser = ref(null)
const showModal = ref(false)
const isEditing = ref(false)
const form = ref({
  id: null,
  name: '',
  email: '',
  password: '',
  role: 'regular'
})
const error = ref('')

const fetchUsers = async () => {
  try {
    const response = await axios.get('/api/users', {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    users.value = response.data
  } catch (e) {
    console.error(e)
    if (e.response?.status === 403) {
      error.value = 'You are not authorized to view users.'
    }
  }
}

const fetchCurrentUser = async () => {
  try {
    const response = await axios.get('/api/user', {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    currentUser.value = response.data
  } catch (e) {
    console.error(e)
  }
}

const canManageUsers = computed(() => {
  return currentUser.value?.role === 'admin' || currentUser.value?.role === 'superadmin'
})

const isSuperAdmin = computed(() => {
  return currentUser.value?.role === 'superadmin'
})

const openCreateModal = () => {
  isEditing.value = false
  form.value = { id: null, name: '', email: '', password: '', role: 'regular' }
  showModal.value = true
  error.value = ''
}

const openEditModal = (user) => {
  isEditing.value = true
  form.value = { 
    id: user.id, 
    name: user.name, 
    email: user.email, 
    password: '', // Don't fill password
    role: user.role 
  }
  showModal.value = true
  error.value = ''
}

const saveUser = async () => {
  try {
    const headers = { Authorization: `Bearer ${localStorage.getItem('token')}` }
    const payload = { ...form.value }
    if (!payload.password) delete payload.password

    if (isEditing.value) {
      await axios.put(`/api/users/${form.value.id}`, payload, { headers })
    } else {
      await axios.post('/api/users', payload, { headers })
    }
    showModal.value = false
    await fetchUsers()
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to save user'
  }
}

const deleteUser = async (id) => {
  if (!confirm('Are you sure?')) return
  try {
    await axios.delete(`/api/users/${id}`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    await fetchUsers()
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to delete user')
  }
}

onMounted(async () => {
  await fetchCurrentUser()
  if (canManageUsers.value) {
    fetchUsers()
  } else {
    error.value = 'Unauthorized'
  }
})
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold">User Management</h2>
      <button 
        v-if="canManageUsers"
        @click="openCreateModal" 
        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
      >
        Add User
      </button>
    </div>

    <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
      {{ error }}
    </div>

    <div v-if="canManageUsers" class="bg-white shadow overflow-hidden sm:rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="user in users" :key="user.id">
            <td class="px-6 py-4 whitespace-nowrap">{{ user.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ user.email }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="{
                  'bg-purple-100 text-purple-800': user.role === 'superadmin',
                  'bg-blue-100 text-blue-800': user.role === 'admin',
                  'bg-gray-100 text-gray-800': user.role === 'regular'
                }"
              >
                {{ user.role }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button @click="openEditModal(user)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
              <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-xl w-96">
        <h3 class="text-lg font-medium mb-4">{{ isEditing ? 'Edit User' : 'Add User' }}</h3>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700">Name</label>
          <input v-model="form.name" type="text" class="mt-1 block w-full border rounded-md shadow-sm p-2">
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input v-model="form.email" type="email" class="mt-1 block w-full border rounded-md shadow-sm p-2">
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700">Password {{ isEditing ? '(Leave blank to keep)' : '' }}</label>
          <input v-model="form.password" type="password" class="mt-1 block w-full border rounded-md shadow-sm p-2">
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700">Role</label>
          <select v-model="form.role" class="mt-1 block w-full border rounded-md shadow-sm p-2">
            <option value="regular">Regular</option>
            <option value="admin">Admin</option>
            <option v-if="isSuperAdmin" value="superadmin">Superadmin</option>
          </select>
        </div>

        <div class="flex justify-end space-x-2">
          <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">Cancel</button>
          <button @click="saveUser" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save</button>
        </div>
      </div>
    </div>
  </div>
</template>
