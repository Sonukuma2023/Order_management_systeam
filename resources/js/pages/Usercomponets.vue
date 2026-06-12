<template>
  <AuthenticatedLayout>
    <div class="user-list-container">
      <div class="table-actions">
        <button @click="openCreateModal" class="add-user-btn">
          <i class='bx bx-plus'></i> Add New Customer
        </button>
      </div>

      <div class="table-wrapper">
        <table class="user-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Address</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users.data" :key="user.id">
              <td>
                <div class="user-info-cell">
                  <div class="user-initial">{{ user.name.charAt(0) }}</div>
                  <span>{{ user.name }}</span>
                </div>
              </td>
              <td>{{ user.email }}</td>
              <td>{{ user.phone || 'N/A' }}</td>
              <td>{{ user.address || 'N/A' }}</td>
              <td class="actions-cell">
                <button @click="openEditModal(user)" class="edit-icon-btn">
                  <i class='bx bxs-edit'></i>
                </button>
                <button @click="deleteUser(user.id)" class="delete-icon-btn">
                  <i class='bx bxs-trash'></i>
                </button>
              </td>
            </tr>
            <tr v-if="users.data.length === 0">
              <td colspan="5" style="text-align: center; color: #a0aec0;">No customers found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="users.links && users.links.length > 3" class="pagination-container">
        <div class="pagination-info">
          Showing {{ users.from }} to {{ users.to }} of {{ users.total }} entries
        </div>
        <div class="pagination-links">
          <button 
            v-for="(link, index) in users.links" 
            :key="index"
            @click="changePage(link.url)"
            :disabled="!link.url || link.active"
            :class="['pagination-btn', { 'active': link.active, 'disabled-btn': !link.url }]"
            v-html="link.label"
          />
        </div>
      </div>
    </div>

    <div v-if="showEditModal" class="modal-overlay">
      <div class="modal-card">
        <div class="modal-header">
          <h3><i class='bx bxs-user-detail'></i> Edit Customer Details</h3>
          <button @click="closeEditModal" class="close-btn">&times;</button>
        </div>

        <form @submit.prevent="submitUpdate">
          <div class="modal-body">
            <div class="input-field">
              <label>Name</label>
              <input v-model="editForm.name" type="text" :class="['custom-input', { 'error-border': editForm.errors.name }]">
              <span v-if="editForm.errors.name" class="error-text">{{ editForm.errors.name }}</span>
            </div>

            <div class="input-field">
              <label>Phone</label>
              <input v-model="editForm.phone" type="text" :class="['custom-input', { 'error-border': editForm.errors.phone }]">
              <span v-if="editForm.errors.phone" class="error-text">{{ editForm.errors.phone }}</span>
            </div>

            <div class="input-field">
              <label>Address</label>
              <textarea v-model="editForm.address" :class="['custom-input', { 'error-border': editForm.errors.address }]" rows="3"></textarea>
              <span v-if="editForm.errors.address" class="error-text">{{ editForm.errors.address }}</span>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeEditModal" class="cancel-btn">Cancel</button>
            <button type="submit" class="save-btn" :disabled="editForm.processing">
              {{ editForm.processing ? 'Updating...' : 'Update Customer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showCreateModal" class="modal-overlay">
      <div class="modal-card">
        <div class="modal-header">
          <h3><i class='bx bxs-user-plus'></i> Add New Customer</h3>
          <button @click="closeCreateModal" class="close-btn">&times;</button>
        </div>

        <form @submit.prevent="submitCreate">
          <div class="modal-body">
            <div class="input-field">
              <label>Full Name</label>
              <input v-model="createForm.name" type="text" :class="['custom-input', { 'error-border': createForm.errors.name }]" placeholder="e.g. Rahul Chib">
              <span v-if="createForm.errors.name" class="error-text">{{ createForm.errors.name }}</span>
            </div>
            
            <div class="input-field">
              <label>Email Address</label>
              <input v-model="createForm.email" type="email" :class="['custom-input', { 'error-border': createForm.errors.email }]" placeholder="customer@example.com">
              <span v-if="createForm.errors.email" class="error-text">{{ createForm.errors.email }}</span>
            </div>

            <div class="input-field">
              <label>Phone</label>
              <input v-model="createForm.phone" type="text" :class="['custom-input', { 'error-border': createForm.errors.phone }]" placeholder="Enter phone number">
              <span v-if="createForm.errors.phone" class="error-text">{{ createForm.errors.phone }}</span>
            </div>

            <div class="input-field">
              <label>Address</label>
              <textarea v-model="createForm.address" :class="['custom-input', { 'error-border': createForm.errors.address }]" placeholder="Enter full address"></textarea>
              <span v-if="createForm.errors.address" class="error-text">{{ createForm.errors.address }}</span>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeCreateModal" class="cancel-btn">Cancel</button>
            <button type="submit" class="save-btn" :disabled="createForm.processing">
              {{ createForm.processing ? 'Creating...' : 'Create User' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from './AuthenticatedLayout.vue';
import Swal from 'sweetalert2';

// CHANGED: Accept users prop as an Object instead of Array because pagination passes metadata properties
const props = defineProps({
  users: Object
});

const showEditModal = ref(false);
const showCreateModal = ref(false);

const editForm = useForm({
  id: null,
  name: '',
  phone: '',
  address: '',
});

const createForm = useForm({
  name: '',
  email: '',
  phone: '',
  address: '',
});

// ADDED: Navigation handling via Inertia router to hit target links without full refresh page locks
const changePage = (url) => {
  if (url) {
    router.visit(url, {
      preserveScroll: true,
      preserveState: true
    });
  }
};

const openEditModal = (user) => {
  editForm.clearErrors();
  editForm.id = user.id;
  editForm.name = user.name;
  editForm.phone = user.phone;
  editForm.address = user.address;
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editForm.reset();
};

const submitUpdate = () => {
  editForm.put(`/users/${editForm.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      closeEditModal();
      Swal.fire({
        icon: 'success',
        title: 'Updated Successfully',
        background: '#1a1a1e',
        color: '#fff',
        confirmButtonColor: '#ff2770'
      });
    },
  });
};

const openCreateModal = () => {
  createForm.reset();
  createForm.clearErrors();
  showCreateModal.value = true;
};

const closeCreateModal = () => {
  showCreateModal.value = false;
};

const submitCreate = () => {
  createForm.post('/users', {
    preserveScroll: true,
    onSuccess: () => {
      closeCreateModal();
      Swal.fire({
        icon: 'success',
        title: 'User Created!',
        background: '#1a1a1e',
        color: '#fff',
        confirmButtonColor: '#ff2770'
      });
    },
  });
};

const deleteUser = (id) => {
  Swal.fire({
    title: 'Are you sure?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ff2770',
    background: '#1a1a1e',
    color: '#fff'
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(`/users/${id}`, { preserveScroll: true });
    }
  });
};
</script>

<style scoped>
/* Previous component styling rules remain unchanged */
.error-text { color: #ff2770; font-size: 12px; margin-top: 5px; display: block; }
.error-border { border-color: #ff2770 !important; }
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); display: flex; justify-content: center; align-items: center; z-index: 9999; backdrop-filter: blur(5px); }
.modal-card { background: #1a1a1e; width: 90%; max-width: 550px; border-radius: 12px; border: 1px solid #ff2770; overflow: hidden; box-shadow: 0 0 20px rgba(255, 39, 112, 0.2); }
.modal-header { padding: 15px 25px; background: #25252b; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
.modal-header h3 { color: #ff2770; margin: 0; font-size: 18px; }
.close-btn { background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; }
.modal-body { padding: 25px; }
.input-field { margin-bottom: 20px; }
.input-field label { display: block; color: #a0aec0; margin-bottom: 8px; font-size: 14px; }
.custom-input { width: 100%; padding: 12px; background: #25252b; border: 1px solid #444; color: #fff; border-radius: 8px; outline: none; }
.custom-input:focus { border-color: #ff2770; }
.modal-footer { padding: 15px 25px; background: #25252b; display: flex; justify-content: flex-end; gap: 10px; }
.user-info-cell { display: flex; align-items: center; gap: 10px; }
.user-initial { width: 30px; height: 30px; background: #ff2770; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
.cancel-btn { padding: 10px 20px; background: transparent; color: #fff; border: 1px solid #444; border-radius: 8px; cursor: pointer; }
.save-btn { padding: 10px 25px; background: #ff2770; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
.user-list-container { padding: 20px; }
.table-wrapper { background: #25252b; border-radius: 12px; overflow: hidden; border: 1px solid #333; }
.user-table { width: 100%; border-collapse: collapse; color: #fff; }
.user-table th { background: #1a1a1e; padding: 15px; color: #ff2770; text-align: left; }
.user-table td { padding: 8px; border-bottom: 1px solid #333; }
.edit-icon-btn { color: #00f2ff; background: none; border: none; cursor: pointer; margin-right: 10px; }
.delete-icon-btn { color: #ff2770; background: none; border: none; cursor: pointer; }
.add-user-btn { background: #ff2770; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
.table-actions { display: flex; justify-content: flex-end; margin-bottom: 20px; width: 100%; }
.add-user-btn:hover { box-shadow: 0 0 15px rgba(255, 39, 112, 0.4); transform: translateY(-2px); }

/* ADDED: New Pagination Component Styles to fit uniform theme design rules */
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding: 10px 5px;
}

.pagination-info {
  color: #a0aec0;
  font-size: 14px;
}

.pagination-links {
  display: flex;
  gap: 5px;
}

.pagination-btn {
  padding: 8px 14px;
  background: #25252b;
  border: 1px solid #333;
  color: #fff;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(.disabled-btn):not(.active) {
  border-color: #ff2770;
  color: #ff2770;
}

.pagination-btn.active {
  background: #ff2770;
  border-color: #ff2770;
  color: white;
  font-weight: bold;
  cursor: default;
}

.pagination-btn.disabled-btn {
  color: #555;
  cursor: not-allowed;
  opacity: 0.5;
}
</style>