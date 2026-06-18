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

        <div v-if="paginationLinks && paginationLinks.length > 3" class="pagination-footer">
          <div class="pagination-info">
            Showing <span>{{ paginationMeta?.from || 1 }}</span> to <span>{{ paginationMeta?.to || products.length }}</span> of <span>{{ paginationMeta?.total || products.length }}</span> products
          </div>
          <div class="pagination-buttons">
            <Link
              v-for="(link, index) in paginationLinks"
              :key="index"
              :href="link.url || '#'"
              v-html="link.label"
              class="pagination-link"
              :class="{ 'active': link.active, 'disabled': !link.url }"
              :preserve-scroll="true"
            />
          </div>
        </div>
      </div>

      <div v-if="isModalOpen" class="modal-overlay">
        <div class="modal-content">
          
          <button type="button" @click="isModalOpen = false" class="modal-close-btn" aria-label="Close modal">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>

          <h3>Import Products CSV/Excel</h3>
          <p class="modal-subtitle">Select a file containing your product records.</p>
          
          <form @submit.prevent="handleFileUpload">
            <div class="file-upload-wrapper">
              <div class="upload-icon-container">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="32"
                  height="32"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="17 8 12 3 7 8"/>
                  <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
              </div>

              <p class="upload-text">
                <strong>Click to select</strong> or drag file here
              </p>

              <p class="file-name-hint" v-if="form.file">
                {{ form.file.name }}
              </p>

              <input
                type="file"
                accept=".csv"
                @input="onFileSelected"
                required
              />
            </div>

            <div class="modal-actions-wrapper">
              <button type="button" @click="downloadDemoCSV" class="btn-demo-link">
                download sample csv
              </button>

              <div class="modal-buttons-right">
                <button type="button" @click="isModalOpen = false" class="btn btn-link">
                  Cancel
                </button>
                <button type="submit" :disabled="form.processing" class="btn btn-primary">
                  {{ form.processing ? 'Uploading...' : 'Submit & Import' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

    </VendorLayout>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import VendorLayout from '../VendorLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
  initialProducts: {
    type: [Array, Object],
    default: () => []
  }
});

const isModalOpen = ref(false);
const page = usePage();

const getProductsArray = (data) => {
  if (!data) return [];
  return Array.isArray(data) ? data : (data.data || []);
};

const products = ref(getProductsArray(props.initialProducts));

const paginationLinks = computed(() => props.initialProducts?.links || page.props.initialProducts?.links || null);
const paginationMeta = computed(() => {
  const source = props.initialProducts || page.props.initialProducts;
  if (!source || Array.isArray(source)) return null;
  return {
    from: source.from,
    to: source.to,
    total: source.total
  };
});

watch(
  () => page.props.initialProducts,
  (newProducts) => {
    if (newProducts) {
      products.value = getProductsArray(newProducts);
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

// Generates dynamic CSV template targeting required spreadsheet formats
const downloadDemoCSV = () => {
  const headers = ['sku_code', 'name', 'image', 'category', 'price', 'description', 'quantity', 'status'];
  const sampleRow = ['PRD001', 'Sample Product', 'https://example.com/image.jpg', 'Electronics', '99.99', 'Product Description', '10', 'active'];
  
  const csvContent = [headers.join(','), sampleRow.join(',')].join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  
  link.setAttribute('href', url);
  link.setAttribute('download', 'sample_products.csv');
  link.style.visibility = 'hidden';
  
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const handleFileUpload = () => {
  form.post('/vendor/products/import', {
    preserveScroll: true,
    onSuccess: () => {
      isModalOpen.value = false;
      form.reset();
      
      Swal.fire({
        title: 'Imported Successfully!',
        text: 'Your products have been processed and loaded into the management table.',
        icon: 'success',
        confirmButtonColor: '#008060',
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
  product.isSyncing = true;
  try {
    const { data } = await axios.post(
      `/vendor/products/${product.id}/sync-shopify`
    );

    if (data.success) {
      product.move_to_shopify = 1;
      
      Swal.fire({
        title: 'Synced!',
        text: `${product.name} synced to Shopify dashboard successfully!`,
        icon: 'success',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true
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
.product-management { padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background-color: #f6f6f7; min-height: 100vh; color: #202223; }
.header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; max-width: 1200px; margin-left: auto; margin-right: auto; }
h1 { font-size: 1.5rem; font-weight: 600; margin: 0; }
.btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s ease; border: 1px solid transparent; outline: none; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-primary { background-color: #008060; color: #ffffff; box-shadow: 0 1px 0 rgba(0, 0, 0, 0.05); }
.btn-primary:hover:not(:disabled) { background-color: #006e52; }
.btn-secondary { background-color: #ffffff; border-color: #babfc3; color: #202223; box-shadow: 0 1px 0 rgba(0, 0, 0, 0.05); }
.btn-secondary:hover:not(:disabled) { background-color: #f6f6f7; border-color: #8c9196; }
.btn-link { background: transparent; color: #6d7175; text-decoration: none; border: none; cursor: pointer; font-size: 0.875rem; }
.btn-link:hover:not(:disabled) { color: #202223; text-decoration: underline; }
.file-upload-wrapper { margin: 1.5rem 0; position: relative; border: 2px dashed #b4ccd2; background-color: #f9fafb; border-radius: 0.75rem; padding: 2.5rem 1.5rem; text-align: center; cursor: pointer; }
.file-upload-wrapper:hover { border-color: #008060; background-color: #f1f8f5; }
.file-upload-wrapper input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
.upload-icon-container { color: #6d7175; margin-bottom: 0.75rem; }
.upload-text { font-size: 0.9rem; color: #202223; margin: 0; }
.upload-text strong { color: #008060; }
.file-name-hint { margin-top: 0.5rem; font-size: 0.85rem; color: #008060; font-weight: bold; }
.table-container { background: #ffffff; border-radius: 0.75rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); overflow: hidden; max-width: 1200px; margin: 0 auto; border: 1px solid #e1e3e5; }
.product-table { width: 100%; border-collapse: collapse; text-align: left; }
.product-table th { background-color: #f9fafb; color: #6d7175; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 1rem 1.5rem; border-bottom: 1px solid #e1e3e5; }
.product-table td { padding: 1.25rem 1.5rem; border-bottom: 1px solid #e1e3e5; font-size: 0.9rem; color: #202223 !important; background-color: #ffffff; }
.status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.75rem; font-weight: 500; }
.status-synced { background-color: #e3f1df; color: #0b4b2c; }
.status-pending { background-color: #fff4e5; color: #8a5a00; }
.empty-state { padding: 3rem; text-align: center; color: #6d7175; }

/* Modal Architecture Styles */
.modal-overlay { position: fixed; z-index: 1000; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(32, 34, 35, 0.6); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; }
.modal-content { position: relative; background: #ffffff; padding: 2.5rem 2rem 2rem 2rem; border-radius: 1rem; width: 520px; max-width: 90vw; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
.modal-content h3 { font-size: 1.25rem; font-weight: 600; margin-top: 0; margin-bottom: 0.5rem; color: #202223; }
.modal-subtitle { color: #6d7175; font-size: 0.9rem; margin-bottom: 1.5rem; }

/* Absolute Top Right "X" Button Positioning Layout */
.modal-close-btn { position: absolute; top: 1.25rem; right: 1.25rem; background: transparent; border: none; color: #6d7175; cursor: pointer; padding: 4px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
.modal-close-btn:hover { background-color: #f6f6f7; color: #202223; }

/* Bottom Modal Action Layout Placements */
.modal-actions-wrapper { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
.modal-buttons-right { display: flex; gap: 0.75rem; align-items: center; }

/* Left-Aligned Red Border Box Link */
.btn-demo-link { background: #34e608ab; border: 1px solid #ff0000; color: black; padding: 0.45rem 0.75rem; cursor: pointer; font-size: 0.85rem; border-radius: 4px; font-weight: 500; transition: background-color 0.2s; text-transform: lowercase; }
.btn-demo-link:hover { background-color: #34e608ab; }

/* Pagination Styling Rules */
.pagination-footer { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: #ffffff; border-top: 1px solid #e1e3e5; flex-wrap: wrap; gap: 1rem; }
.pagination-info { font-size: 0.875rem; color: #6d7175; }
.pagination-info span { font-weight: 600; color: #202223; }
.pagination-buttons { display: flex; gap: 0.25rem; }
.pagination-link { display: inline-flex; align-items: center; justify-content: center; min-width: 2.25rem; height: 2.25rem; padding: 0 0.5rem; font-size: 0.875rem; border: 1px solid #babfc3; border-radius: 0.375rem; color: #202223; text-decoration: none; background: #ffffff; transition: all 0.2s; }
.pagination-link:hover:not(.disabled):not(.active) { background: #f6f6f7; border-color: #8c9196; }
.pagination-link.active { background: #008060; border-color: #008060; color: #ffffff; font-weight: 600; pointer-events: none; }
.pagination-link.disabled { color: #babfc3; border-color: #e1e3e5; cursor: not-allowed; pointer-events: none; }
</style>