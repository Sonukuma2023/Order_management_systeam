<template>
  <AuthenticatedLayout>
    <div class="welcome-banner">
      <div class="header-text">
         
      </div>
      <button @click="openCreateModal" class="add-btn">
        <i class='bx bx-plus'></i> Add Product
      </button>
    </div>

    <div class="product-card">
      <div class="card-header">
        <h3>Inventory List</h3>
        <span class="badge">{{ product_data.total }} Total Items</span>
      </div>

      <div class="table-responsive">
        <table class="product-table">
          <thead>
            <tr>
              <th class="col-product">Product</th>
              <th class="col-category">Category</th>
              <th class="col-price">Price</th>
              <th class="col-stock">Stock</th>
              <th class="col-status">Status</th>
              <th class="col-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in product_data.data" :key="product.id">
              <td class="product-cell">
                <div class="product-info">
                  <img
                    :src="product.image ? `/asset/product/images/${product.image}` : '/asset/placeholder.png'"
                    alt=""`
                    class="product-img-thumb"
                  />
                  <span class="product-name" :title="product.name">{{ product.name }}</span>
                </div>
              </td>

              <td><span class="category-text">{{ product.category }}</span></td>
              <td class="price-text">₹{{ parseFloat(product.price).toLocaleString('en-IN') }}</td>
              <td>{{ product.quantity }}</td>

              <td>
                <span class="status-pill" :class="product.status === 'active' ? 'active' : 'inactive'">
                  {{ product.status }}
                </span>
              </td>

              <td class="action-buttons">
                <button @click="openEditModal(product)" class="edit-btn" title="Edit">
                  <i class='bx bx-edit-alt'></i>
                </button>
                <button @click="confirmDelete(product.id)" class="delete-btn" title="Delete">
                  <i class='bx bx-trash'></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination-container" v-if="product_data.links.length > 3">
        <div class="pagination-info">
          Showing {{ product_data.from }} to {{ product_data.to }} of {{ product_data.total }}
        </div>
        <div class="pagination-links">
          <Link 
            v-for="(link, k) in product_data.links" 
            :key="k"
            :href="link.url || '#'"
            v-html="link.label"
            class="page-link"
            :class="{ 'active': link.active, 'disabled': !link.url }"
          />
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-card">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Edit Product' : 'Add New Product' }}</h3>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="submit">
          <div class="modal-body">
            <div class="form-grid">
              <div class="form-group full-width">
                <label>Product Image</label>
                <div class="image-upload-wrapper">
                  <input type="file" @input="form.image = $event.target.files[0]" id="image-input" accept="image/*" />
                  <div class="upload-hint">Format: JPG, PNG (Max 2MB)</div>
                </div>
                <progress v-if="form.progress" :value="form.progress.percentage" max="100">{{ form.progress.percentage }}%</progress>
                <span v-if="form.errors.image" class="error-text">{{ form.errors.image }}</span>
              </div>

              <div class="form-group">
                <label>Product Name</label>
                <input v-model="form.name" type="text" placeholder="Enter product name" >
                <span v-if="form.errors.name" class="error-text">{{ form.errors.name }}</span>
              </div>

              <div class="form-group">
                <label>SKU Code</label>
                <!-- Changed form.sku_code to form.sku to match your controller validation -->
                <input v-model="form.sku_code" type="text" placeholder="e.g. PRD-001" >
                <span v-if="form.errors.sku_code" class="error-text">{{ form.errors.sku_code }}</span>
              </div>
              <div class="form-group">
                <label>Category</label>
                
                <select v-model="form.category" class="form-control">
                  <option value="" disabled>Select a Category</option>
                  <!-- Loop through the category prop passed from the controller -->
                  <option v-for="cat in category" :key="cat.id" :value="cat.name">
                    {{ cat.name }}
                  </option>
                </select>

                <span v-if="form.errors.category" class="error-text">
                  {{ form.errors.category }}
                </span>
              </div>

              <div class="form-group">
                <label>Price (₹)</label>
                <input v-model="form.price" type="number" step="0.01" >
                <span v-if="form.errors.price" class="error-text">{{ form.errors.price }}</span>
              </div>

              <div class="form-group">
                <label>Stock Quantity</label>
                <input v-model="form.quantity" type="number" >
                <span v-if="form.errors.quantity" class="error-text">{{ form.errors.quantity }}</span>
              </div>

              <div class="form-group">
                <label>Status</label>
                <select v-model="form.status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
                <span v-if="form.errors.status" class="error-text">{{ form.errors.status }}</span>
              </div>

              <div class="form-group full-width">
                <label>Description</label>
                <textarea v-model="form.description" rows="3" placeholder="Enter product details..."></textarea>
                <span v-if="form.errors.description" class="error-text">{{ form.errors.description }}</span>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="closeModal" class="cancel-btn">Cancel</button>
            <button type="submit" class="save-btn" :disabled="form.processing">
              {{ isEditing ? 'Update Product' : 'Save Product' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, usePage, Link } from '@inertiajs/vue3'; // Added usePage
import AuthenticatedLayout from './AuthenticatedLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
  product_data: Object,
  category: Array,
});

const page = usePage(); 

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

const showModal = ref(false);
const isEditing = ref(false);
const editId = ref(null);

const form = useForm({
  name: '',
  sku_code: '', 
  category: '',
  price: '',
  quantity: '',
  status: 'active',
  description: '', 
  image: null,    
});

const openCreateModal = () => {
  isEditing.value = false;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (product) => {
  isEditing.value = true;
  editId.value = product.id;
  form.clearErrors();
  
  form.name = product.name;
  form.sku_code = product.sku_code;
  form.category = product.category;
  form.price = product.price;
  form.quantity = product.quantity;
  form.status = product.status;
  form.description = product.description || '';
  form.image =  null; 
  
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.reset();
  form.clearErrors();
};

// --- 2. Corrected Submit with Flash Toast ---
const submit = () => {
  const url = isEditing.value ? `/products/${editId.value}` : '/products';
  
  const options = {
    forceFormData: true,
    onSuccess: () => {
      closeModal();
      if (page.props.flash.success) {
        Toast.fire({
          icon: 'success',
          title: page.props.flash.success
        });
      }
    },
    onError: () => {
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
    form.transform((data) => ({
      ...data,
      _method: 'PUT',
    })).post(url, options);
  } else {
    form.post(url, options);
  }
};

// --- 3. Corrected Delete with Flash Toast ---
const confirmDelete = (id) => {
  Swal.fire({
    title: 'Delete Product?',
    text: "This item will be permanently removed.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ff2770',
    cancelButtonColor: '#2d3748',
    confirmButtonText: 'Yes, Delete',
    background: '#1a1a1e',
    color: '#fff'
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(`/products/${id}`, {
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
/* All your specific styles remain intact here... */
.welcome-banner { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.header-text h1 { font-size: 24px; color: #fff; font-weight: 600; margin: 0; }
.header-text p { color: #718096; font-size: 14px; margin-top: 4px; }

.add-btn {
  background: #ff2770; color: white; border: none; padding: 10px 20px;
  border-radius: 8px; font-weight: 600; cursor: pointer; display: flex;
  align-items: center; gap: 8px; transition: 0.3s;
}
.add-btn:hover { box-shadow: 0 0 15px rgba(255, 39, 112, 0.4); transform: translateY(-2px); }

.product-card { background: #25252b; border-radius: 12px; border: 1px solid #333; padding: 20px; }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.badge { background: rgba(0, 242, 255, 0.1); color: #00f2ff; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; }

.product-table { width: 100%; border-collapse: collapse; table-layout: fixed; }

.col-product { width: 35%; }
.col-category { width: 15%; }
.col-price { width: 15%; }
.col-stock { width: 10%; }
.col-status { width: 12%; }
.col-actions { width: 13%; text-align: center; }

.product-table th { text-align: left; color: #718096; font-size: 12px; text-transform: uppercase; padding: 15px 12px; border-bottom: 1px solid #333; }
.product-table td { padding: 12px; border-bottom: 1px solid #333; color: #fff; vertical-align: middle; }

.product-info { display: flex; align-items: center; gap: 12px; }
.product-img-thumb { width: 42px; height: 42px; object-fit: cover; border-radius: 8px; flex-shrink: 0; border: 1px solid #444; }
.product-name { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.category-text { color: #a0aec0; }
.price-text { font-weight: 600; color: #fff; }
.status-pill { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: capitalize; }
.status-pill.active { background: rgba(0, 255, 127, 0.1); color: #00ff7f; }
.status-pill.inactive { background: rgba(255, 39, 112, 0.1); color: #ff2770; }

.action-buttons { display: flex; justify-content: center; gap: 8px; }
.edit-btn, .delete-btn {
  width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
  border-radius: 6px; border: 1px solid #333; background: #1a1a1e; cursor: pointer; transition: 0.2s;
}
.edit-btn i { color: #00f2ff; }
.delete-btn i { color: #ff2770; }

.pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; }
.pagination-info { color: #718096; font-size: 13px; }
.pagination-links { display: flex; gap: 6px; }
.page-link { padding: 6px 12px; background: #1a1a1e; border: 1px solid #333; color: #fff; border-radius: 4px; text-decoration: none; font-size: 13px; }
.page-link.active { background: #ff2770; border-color: #ff2770; }
.page-link.disabled { opacity: 0.3; cursor: not-allowed; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-card { background: #25252b; border: 2px solid #ff2770; border-radius: 12px; width: 550px; overflow: hidden; }
.modal-header { padding: 20px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; }
.modal-header h3 { color: #ff2770; margin: 0; }
.modal-body { padding: 20px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.full-width { grid-column: span 2; }
.form-group label { display: block; color: #a0aec0; font-size: 13px; margin-bottom: 6px; }
.form-group input, .form-group select { width: 100%; padding: 10px; background: #1a1a1e; border: 1px solid #333; color: #fff; border-radius: 6px; }
.modal-footer { padding: 20px; background: #1e1e24; display: flex; justify-content: flex-end; gap: 10px; }
.cancel-btn { background: none; border: 1px solid #444; color: #fff; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
.save-btn { background: #ff2770; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.error-text { color: #ff2770; font-size: 12px; margin-top: 4px; display: block; }
textarea { width: 100%; background: #1a1a1e; border: 1px solid #333; color: #fff; padding: 10px; border-radius: 8px; resize: none; }
.image-upload-wrapper { border: 1px dashed #444; padding: 15px; border-radius: 8px; background: #1a1a1e; }
.upload-hint { font-size: 11px; color: #718096; margin-top: 5px; }
</style>