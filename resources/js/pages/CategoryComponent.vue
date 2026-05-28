<template>
  <AuthenticatedLayout>
    <div class="welcome-banner">
      <div class="header-text">
        <h1>Category Management</h1>
      </div>
      <button @click="openCreateModal" class="add-btn">
        <i class='bx bx-plus'></i> Add Category
      </button>
    </div>

    <div class="table-container">
      <table class="custom-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Category Name</th>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="category_data.length > 0">
            <tr v-for="(category, index) in category_data" :key="category.id">
              <td>#{{ index + 1 }}</td>
              <td class="category-name">{{ category.name }}</td>
              <td>{{ category.description || 'No description provided' }}</td>
              <td>
                <button @click="openEditModal(category)" class="edit-icon" title="Edit">
                  <i class='bx bxs-edit'></i>
                </button>
                <button @click="deleteCategory(category.id)" class="delete-icon" title="Delete">
                  <i class='bx bxs-trash'></i>
                </button>
              </td>
            </tr>
          </template>

          <tr v-else>
            <td colspan="4" class="no-data">
              <i class='bx bx-info-circle'></i> No categories found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="modal-overlay">
      <div class="modal-card">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Edit Category' : 'Create New Category' }}</h3>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>

        <form @submit.prevent="submit">
          <div class="modal-body">
            <div class="form-group">
              <label>Category Name</label>
              <input 
                v-model="form.name" 
                type="text" 
                placeholder="e.g. Electronics"
                required
              >
              <span v-if="form.errors.name" class="error-text">{{ form.errors.name }}</span>
            </div>

            <div class="form-group">
              <label>Description (Optional)</label>
              <textarea 
                v-model="form.description" 
                placeholder="Briefly describe this category..."
              ></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="cancel-btn">Cancel</button>
            <button type="submit" class="save-btn" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : (isEditing ? 'Update Category' : 'Save Category') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from './AuthenticatedLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
  category_data: Array
});

const page = usePage();

// --- 1. Toast Configuration ---
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  background: '#1a1a1e', 
  color: '#fff',
  iconColor: '#00f2ff',
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  }
});

// --- 2. State Management ---
const showModal = ref(false);
const isEditing = ref(false);
const currentEditId = ref(null);

const form = useForm({
  name: '',
  description: '',
});

// --- 3. Modal Handlers ---
const openCreateModal = () => {
  isEditing.value = false;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (category) => {
  isEditing.value = true;
  currentEditId.value = category.id;
  form.clearErrors();
  form.name = category.name;
  form.description = category.description;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.reset();
};

// --- 4. Submit & Toast Logic ---
const submit = () => {
  const options = {
    onSuccess: () => {
      closeModal();
      // Trigger toast only if a success message exists in flash props
      if (page.props.flash.success) {
        Toast.fire({
          icon: 'success',
          title: page.props.flash.success
        });
      }
    },
    onError: () => {
      // Check for server-side flash errors or validation errors
      if (page.props.flash.error) {
        Toast.fire({
          icon: 'error',
          title: page.props.flash.error,
          background: '#7d1212'
        });
      }
    }
  };

  if (isEditing.value) {
    form.put(`/categories/${currentEditId.value}`, options);
  } else {
    form.post('/categories', options);
  }
};

// --- 5. Delete Logic ---
const deleteCategory = (id) => {
  Swal.fire({
    title: 'Are you sure?',
    text: "This category will be permanently removed!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ff2770',
    cancelButtonColor: '#25252b',
    confirmButtonText: 'Yes, delete it!',
    background: '#1a1a1e',
    color: '#fff',
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(`/categories/${id}`, {
        onSuccess: () => {
          if (page.props.flash.success) {
            Toast.fire({
              icon: 'success',
              title: page.props.flash.success
            });
          }
        }
      });
    }
  });
};
</script>

<style scoped>
/* Keeping your high-end Neon styles */
.welcome-banner { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.header-text h1 { font-size: 24px; color: #fff; margin: 0; }
.header-text p { color: #a0aec0; font-size: 14px; }

.add-btn {
  background: #ff2770; color: white; border: none; padding: 10px 20px;
  border-radius: 8px; font-weight: 600; cursor: pointer; display: flex;
  align-items: center; gap: 8px; box-shadow: 0 0 15px rgba(255, 39, 112, 0.4);
}

.table-container { background: #25252b; border-radius: 12px; border: 1px solid #333; padding: 20px; }
.custom-table { width: 100%; border-collapse: collapse; }
.custom-table th { text-align: left; color: #a0aec0; padding: 12px; border-bottom: 1px solid #333; font-size: 13px; }
.custom-table td { padding: 15px 12px; color: #fff; border-bottom: 1px solid #333; font-size: 14px; }

.category-name { color: #ff2770; font-weight: 600; }

/* Action Icons */
.edit-icon, .delete-icon {
  background: transparent; border: none; font-size: 1.2rem; cursor: pointer; transition: 0.3s; padding: 5px 10px;
}
.edit-icon { color: #00f2ff; }
.delete-icon { color: #ff2770; }
.edit-icon:hover { text-shadow: 0 0 8px #00f2ff; transform: scale(1.1); }
.delete-icon:hover { text-shadow: 0 0 8px #ff2770; transform: scale(1.1); }

.no-data { text-align: center; padding: 30px; color: #718096; font-style: italic; }

/* Modal */
.modal-overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0, 0, 0, 0.85); display: flex; justify-content: center;
  align-items: center; z-index: 999;
}
.modal-card {
  background: #25252b; width: 100%; max-width: 500px; border-radius: 15px;
  border: 2px solid #ff2770; box-shadow: 0 0 30px rgba(255, 39, 112, 0.3);
  overflow: hidden; animation: modalScale 0.3s ease;
}
@keyframes modalScale { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.modal-header { padding: 20px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { color: #ff2770; margin: 0; }
.close-btn { background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; }
.modal-body { padding: 25px; }

.form-group { margin-bottom: 20px; }
.form-group label { display: block; color: #a0aec0; font-size: 14px; margin-bottom: 8px; }
.form-group input, .form-group textarea {
  width: 100%; padding: 12px; background: #1a1a1e; border: 1px solid #333;
  border-radius: 8px; color: #fff; outline: none; transition: 0.3s;
}
.form-group input:focus { border-color: #ff2770; }
.error-text { color: #ff2770; font-size: 12px; margin-top: 5px; display: block; }

.modal-footer { padding: 20px; background: #1e1e24; display: flex; justify-content: flex-end; gap: 12px; }
.cancel-btn { background: transparent; border: 1px solid #555; color: #fff; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
.save-btn { background: #ff2770; border: none; color: #fff; padding: 10px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; }
</style>