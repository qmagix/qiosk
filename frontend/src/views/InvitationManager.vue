<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const invitations = ref([])
const isLoading = ref(false)
const showModal = ref(false)
const isEditing = ref(false)
const form = ref({
  id: null,
  code: '',
  is_used: false
})

const fetchInvitations = async () => {
  isLoading.value = true
  try {
    const response = await axios.get('/api/invitations', {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    invitations.value = response.data
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = { id: null, code: '', is_used: false }
  showModal.value = true
}

const openEditModal = (invite) => {
  isEditing.value = true
  form.value = { 
    id: invite.id, 
    code: invite.code, 
    is_used: Boolean(invite.is_used) 
  }
  showModal.value = true
}

const saveInvitation = async () => {
  try {
    const headers = { Authorization: `Bearer ${localStorage.getItem('token')}` }
    
    if (isEditing.value) {
      await axios.put(`/api/invitations/${form.value.id}`, {
        code: form.value.code,
        is_used: form.value.is_used
      }, { headers })
    } else {
      await axios.post('/api/invitations', {
        code: form.value.code // Optional, backend generates if empty
      }, { headers })
    }
    
    showModal.value = false
    await fetchInvitations()
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to save invitation')
  }
}

const deleteInvitation = async (id) => {
  if (!confirm('Are you sure?')) return
  try {
    await axios.delete(`/api/invitations/${id}`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    await fetchInvitations()
  } catch (e) {
    alert('Failed to delete invitation')
  }
}

onMounted(fetchInvitations)
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold">Invitation Codes</h2>
      <button 
        @click="openCreateModal" 
        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
      >
        Create Invitation
      </button>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg w-96">
        <h3 class="text-lg font-bold mb-4">{{ isEditing ? 'Edit Invitation' : 'Create Invitation' }}</h3>
        
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Code</label>
          <input 
            v-model="form.code" 
            type="text" 
            class="w-full border rounded p-2 uppercase" 
            :placeholder="isEditing ? '' : 'Leave empty for random'"
          >
          <p v-if="!isEditing" class="text-xs text-gray-500 mt-1">Leave blank to generate a random code.</p>
        </div>

        <div v-if="isEditing" class="mb-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.is_used" class="rounded text-blue-600">
            <span class="text-sm font-medium">Mark as Used</span>
          </label>
          <p class="text-xs text-gray-500 mt-1">Unchecking this will make the code available again.</p>
        </div>

        <div class="flex justify-end gap-2">
          <button @click="showModal = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">Cancel</button>
          <button @click="saveInvitation" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Save</button>
        </div>
      </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Used By</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="invite in invitations" :key="invite.id">
            <td class="px-6 py-4 whitespace-nowrap font-mono text-lg font-bold text-blue-600 select-all">
              {{ invite.code }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span 
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="invite.is_used ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
              >
                {{ invite.is_used ? 'Used' : 'Available' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ invite.creator?.name || 'Unknown' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ invite.user?.name || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ new Date(invite.created_at).toLocaleDateString() }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button 
                @click="openEditModal(invite)" 
                class="text-blue-600 hover:text-blue-900 mr-3"
              >
                Edit
              </button>
              <button 
                @click="deleteInvitation(invite.id)" 
                class="text-red-600 hover:text-red-900"
              >
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="invitations.length === 0">
            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
              No invitation codes found. Generate one to get started.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
