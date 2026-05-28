<template>
  <AuthenticatedLayout>
    <div class="import-container">
      <div class="import-card">
        <div class="card-header">
          <h2><i class='bx bxs-file-import'></i> Import Customers</h2>
          <p>Upload a CSV file to add customers to your database.</p>
        </div>

        <form @submit.prevent="submitImport" class="import-form">
          <div class="upload-area" :class="{ 'dragging': isDragging }" 
               @dragover.prevent="isDragging = true" 
               @dragleave.prevent="isDragging = false"
               @drop.prevent="handleDrop">
            
            <i class='bx bxs-cloud-upload' ></i>
            <p v-if="!form.file">Click to select or drag & drop CSV file</p>
            <p v-else class="file-name">{{ form.file.name }}</p>
            
            <input type="file" @change="handleFileSelect" accept=".csv" ref="fileInput" hidden>
            <button type="button" @click="$refs.fileInput.click()" class="select-btn">Browse Files</button>
          </div>

          <div v-if="form.errors.file" class="error-msg">{{ form.errors.file }}</div>

          <div class="actions">
            <button type="submit" class="import-submit-btn" :disabled="form.processing">
              {{ form.processing ? 'Importing...' : 'Start Import' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from './AuthenticatedLayout.vue'; // Adjust path
import Swal from 'sweetalert2';

const isDragging = ref(false);
const form = useForm({
  file: null,
});

const handleFileSelect = (e) => {
  form.file = e.target.files[0];
};

const handleDrop = (e) => {
  isDragging.value = false;
  form.file = e.dataTransfer.files[0];
};

const submitImport = () => {
  if (!form.file) {
    Swal.fire({ icon: 'error', title: 'Oops...', text: 'Please select a file first!', background: '#1a1a1e', color: '#fff' });
    return;
  }

  form.post('/client-import', {
    onSuccess: () => {
      form.reset();
      Swal.fire({ icon: 'success', title: 'Import Complete', background: '#1a1a1e', color: '#fff' });
    },
  });
};
</script>

<style scoped>
.import-container { display: flex; justify-content: center; padding-top: 50px; }
.import-card { background: #25252b; width: 100%; max-width: 600px; padding: 40px; border-radius: 20px; border: 1px solid #333; }
.card-header h2 { color: #ff2770; display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.card-header p { color: #a0aec0; font-size: 14px; margin-bottom: 30px; }

.upload-area {
  border: 2px dashed #333;
  padding: 40px;
  text-align: center;
  border-radius: 15px;
  background: #1a1a1e;
  transition: 0.3s;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
}
.upload-area.dragging { border-color: #ff2770; background: rgba(255, 39, 112, 0.05); }
.upload-area i { font-size: 50px; color: #ff2770; }
.file-name { color: #00f2ff; font-weight: bold; }

.select-btn { background: #333; color: #fff; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; }
.import-submit-btn {
  width: 100%;
  margin-top: 20px;
  background: #ff2770;
  color: white;
  border: none;
  padding: 15px;
  border-radius: 10px;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s;
}
.import-submit-btn:hover { box-shadow: 0 0 15px rgba(255, 39, 112, 0.4); }
.error-msg { color: #ff2770; font-size: 12px; margin-top: 10px; }
</style>