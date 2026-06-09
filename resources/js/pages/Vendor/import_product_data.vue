<template>
  <div class="product-management">
    <VendorLayout> 
      <div class="header-section">
        <h1>Product Import Management</h1>
        <button @click="isModalOpen = true" class="btn btn-primary">
          Import Products
        </button>
      </div>

      <div class="table-container">
        <table v-if="products.length > 0" class="product-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Product Name</th>
              <th>Price</th>
              <th>Shopify Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in products" :key="product.id">
              <td class="cell-text">{{ product.id }}</td>
              <td class="cell-text"><strong>{{ product.name }}</strong></td>
              <td class="cell-text">${{ parseFloat(product.price).toFixed(2) }}</td>
              <td>
                <span :class="['status-badge', product.move_to_shopify === 1 ? 'status-synced' : 'status-pending']">
                  {{ product.move_to_shopify === 1 ? 'Synced to Shopify' : 'Not Syncing' }}
                </span>
              </td>
              <td>
                <button 
                  @click="moveToShopify(product)" 
                  :disabled="product.move_to_shopify === 1 || product.isSyncing"
                  class="btn btn-secondary"
                >
                  {{ product.isSyncing ? 'Moving...' : 'Move to Shopify' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-else class="empty-state">
          <p>No products imported yet. Click "Import Products" to start.</p>
        </div>
      </div>

      <div v-if="isModalOpen" class="modal-overlay"     >
        <div class="modal-content">
          <h3>Import Products CSV/Excel</h3>
          <p class="modal-subtitle">Select a file containing your product records.</p>
          
          <form @submit.prevent="handleFileUpload">
            <div class="file-upload-wrapper">
              <div class="upload-icon-container">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              </div>
              <p class="upload-text"><strong>Click to select</strong> or drag file here</p>
              <p class="file-name-hint" v-if="form.file">{{ form.file.name }}</p>
              
              <input 
                type="file" 
                accept=".csv" 
                @input="onFileSelected"
                required 
              />
            </div>

            <div class="modal-actions">
              <button type="button" @click="isModalOpen = false" class="btn btn-link">
                Cancel
              </button>
              <button type="submit" :disabled="form.processing" class="btn btn-primary">
                {{ form.processing ? 'Uploading...' : 'Submit & Import' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </VendorLayout>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import VendorLayout from '../VendorLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
  initialProducts: {
    type: Array,
    default: () => []
  }
});

const isModalOpen = ref(false);
const page = usePage();

// FIX 1: Map products to a mutable ref array instead of a strict read-only computed property
const products = ref([...props.initialProducts]);

// FIX 2: Watch for page property mutations from the Inertia sheet framework to update cleanly
watch(
  () => page.props.initialProducts,
  (newProducts) => {
    if (newProducts) {
      products.value = [...newProducts];
    }
  },
  { deep: true, immediate: true }
);

const form = useForm({
  file: null,
});

const onFileSelected = (event) => {
  form.file = event.target.files[0];
};

const handleFileUpload = () => {
  form.post('/vendor/products/import', {
    preserveScroll: true,
    onSuccess: () => {
      // Close the modal and reset form values
      isModalOpen.value = false;
      form.reset();
      
      // Beautiful SweetAlert Success Popup
      Swal.fire({
        title: 'Imported Successfully!',
        text: 'Your products have been processed and loaded into the management table.',
        icon: 'success',
        confirmButtonColor: '#008060', // Matches your Shopify green button
        confirmButtonText: 'Great, thanks!'
      });
    },
    onError: (errors) => {
      alert('Error parsing uploaded file.');
      console.error(errors);
    }
  });
};

const moveToShopify = async (product) => {
  // Safe mutation now that elements live within standard ref arrays
  product.isSyncing = true;

  try {
    const { data } = await axios.post(
      `/vendor/products/${product.id}/sync-shopify`
    );

    if (data.success) {
      product.move_to_shopify = 1;
      
      // SweetAlert Success Notification
      Swal.fire({
        title: 'Synced!',
        text: `${product.name} synced to Shopify dashboard successfully!`,
        icon: 'success',
        toast: true,               // Makes it an elegant corner notification
        position: 'top-end',       // Places it at the top right corner
        showConfirmButton: false,  // Removes the button for a cleaner look
        timer: 3500,               // Automatically closes after 3.5 seconds
        timerProgressBar: true     // Adds a visual countdown bar
      });
    }
  } catch (error) {
    console.error(error);
    alert('Failed to update Shopify catalog status.');
  } finally {
    product.isSyncing = false;
  }
};
</script>

<style scoped>
.product-management { padding: 2.5rem; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background-color: #f6f6f7; min-height: 100vh; color: #202223; }
.header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; max-width: 1200px; margin-left: auto; margin-right: auto; }
h1 { font-size: 1.5rem; font-weight: 600; margin: 0; }
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s ease; border: 1px solid transparent; outline: none; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-primary { background-color: #008060; color: #ffffff; box-shadow: 0 1px 0 rgba(0, 0, 0, 0.05); }
.btn-primary:hover:not(:disabled) { background-color: #006e52; }
.btn-secondary { background-color: #ffffff; border-color: #babfc3; color: #202223; box-shadow: 0 1px 0 rgba(0, 0, 0, 0.05); }
.btn-secondary:hover:not(:disabled) { background-color: #f6f6f7; border-color: #8c9196; }
.btn-link { background: transparent; color: #6d7175; }
.btn-link:hover:not(:disabled) { color: #202223; text-decoration: underline; }
.file-upload-wrapper { margin: 1.5rem 0; position: relative; border: 2px dashed #b4ccd2; background-color: #f9fafb; border-radius: 0.75rem; padding: 2rem 1.5rem; text-align: center; cursor: pointer; }
.file-upload-wrapper:hover { border-color: #008060; background-color: #f1f8f5; }
.file-upload-wrapper input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
.upload-icon-container { color: #6d7175; margin-bottom: 0.75rem; }
.upload-text { font-size: 0.9rem; color: #202223; margin: 0; }
.upload-text strong { color: #008060; }
.file-name-hint { margin-top: 0.5rem; font-size: 0.85rem; color: #008060; font-weight: bold; }
.table-container { background: #ffffff; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); overflow: hidden; max-width: 1200px; margin: 0 auto; border: 1px solid #e1e3e5; }
.product-table { width: 100%; border-collapse: collapse; text-align: left; }
.product-table th {
  background-color: #f9fafb;
  color: #6d7175;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e1e3e5;
}
.product-table td {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e1e3e5;
  font-size: 0.9rem;
  color: #202223 !important; /* Forces text to show up in Shopify charcoal grey instead of transparent/white */
  background-color: #ffffff;
}
.status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.75rem; font-weight: 500; }
.status-synced { background-color: #e3f1df; color: #0b4b2c; }
.status-pending { background-color: #fff4e5; color: #8a5a00; }
.modal-overlay { position: fixed; z-index: 1000; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(32, 34, 35, 0.6); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; }
.modal-content { background: #ffffff; padding: 2rem; border-radius: 1rem; width: 440px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
.modal-content h3 { font-size: 1.25rem; font-weight: 600; margin-top: 0; margin-bottom: 0.5rem; }
.modal-subtitle { color: #6d7175; font-size: 0.9rem; margin-bottom: 1.5rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.75}
</style>