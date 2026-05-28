<script setup>
import { ref } from 'vue';
import { Head, useForm, usePage, router, Link } from '@inertiajs/vue3';
import VendorLayout from '../VendorLayout.vue';
import Swal from 'sweetalert2';
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

// 1. Updated 'products' prop definition to handle the Laravel pagination metadata object
const props = defineProps({
    products: Object,
    category: Array,
    product_count: Number,
});

const showModal = ref(false);
const isEditing = ref(false);
const currentProductId = ref(null);

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
    currentProductId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (product) => {
    isEditing.value = true;
    currentProductId.value = product.id;
    
    form.name = product.name;
    form.sku_code = product.sku_code;
    form.category = product.category;
    form.price = product.price;
    form.quantity = product.quantity;
    form.status = product.status;
    form.description = product.description;
    form.image = null; 
    
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.post(`/vendor/products/update/${currentProductId.value}`, {
            forceFormData: true,
            onSuccess: () => {
                closeModal();
                // Check for flash message from your backend redirect
                const successMsg = page.props.flash?.success || 'Product updated successfully!';
                Toast.fire({
                    icon: 'success',
                    title: successMsg
                });
            },
        });
    } else {
        form.post('/vendor/products/store', {
            forceFormData: true,
            onSuccess: () => {
                closeModal();
                // Check for flash message from your backend redirect
                const successMsg = page.props.flash?.success || 'Product created successfully!';
                Toast.fire({
                    icon: 'success',
                    title: successMsg
                });
            },
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const handleImage = (e) => {
    form.image = e.target.files[0];
};

const deleteProduct = (id) => {
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
            router.delete(`/vendor/products/${id}`, {
                onSuccess: () => {
                    const successMsg = page.props.flash?.success || 'Product deleted successfully!';
                    Toast.fire({
                        icon: 'success',
                        title: successMsg
                    });
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Vendor Products" />

    <VendorLayout>
        <div class="page-container">
            <div class="header-section">
                <h1>My Products</h1>
                <button @click="openCreateModal" class="add-btn">
                    <i class='bx bx-plus'></i> Add Product
                </button>
            </div>

            <!-- Modal Section -->
            <div v-if="showModal" class="modal-overlay">
                <div class="modal-box">
                    <div class="modal-header">
                        <h2>{{ isEditing ? 'Edit Product' : 'Create Product' }}</h2>
                        <button class="close-btn" @click="closeModal">×</button>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" v-model="form.name">
                            <div v-if="form.errors.name" class="error">{{ form.errors.name }}</div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>SKU Code</label>
                                <input type="text" v-model="form.sku_code">
                                <div v-if="form.errors.sku_code" class="error">{{ form.errors.sku_code }}</div>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select v-model="form.category">
                                    <option value="" disabled>Select a Category</option>
                                    <option v-for="cat in category" :key="cat.id" :value="cat.name">
                                        {{ cat.name }}
                                    </option>
                                </select>
                                <span v-if="form.errors.category" class="error">
                                    {{ form.errors.category }}
                                </span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Price</label>
                                <input type="number" v-model="form.price">
                                <span v-if="form.errors.price" class="error">{{ form.errors.price }}</span>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" v-model="form.quantity">
                                <span v-if="form.errors.quantity" class="error">{{ form.errors.quantity }}</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select v-model="form.status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <span v-if="form.errors.status" class="error">{{ form.errors.status }}</span>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea v-model="form.description" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Image {{ isEditing ? '(Leave blank to keep current)' : '' }}</label>
                            <input type="file" @change="handleImage">
                        </div>

                        <button type="submit" class="submit-btn" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update Product' : 'Create Product') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table Section -->
            <div class="demo">
                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 2. Loop through products.data wrapper containing current paginated records -->
                            <tr v-for="product in products?.data" :key="product.id">
                                <td>
                                    <img v-if="product.image" :src="'/asset/product/images/' + product.image" class="img-preview" />
                                    <div v-else class="img-placeholder"></div>
                                </td>
                                <td>{{ product.name }}</td>
                                <td>${{ product.price }}</td>
                                <td>{{ product.category }}</td>
                                <td>{{ product.quantity }}</td>
                                <td><span class="badge" :class="product.status">{{ product.status }}</span></td>
                                <td>
                                    <!-- EDIT BUTTON -->
                                    <button @click="openEditModal(product)" class="edit-icon">
                                        <i class='bx bxs-edit'></i>
                                    </button>
                                    <button @click="deleteProduct(product.id)" class="delete-icon">
                                        <i class='bx bxs-trash'></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!products?.data || products.data.length === 0">
                                <td colspan="6" style="text-align: center; color: #888;">No products found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 3. Safe pagination tracking container referencing standard pagination keys -->
                <div class="pagination-container" v-if="products?.links && products.links.length > 3">
                    <div class="pagination-info">
                        Showing {{ products.from || 0 }} to {{ products.to || 0 }} of {{ products.total || 0 }}
                    </div>
                    <div class="pagination-links">
                        <Link 
                            v-for="(link, k) in products.links" 
                            :key="k"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="page-link"
                            :class="{ 'active': link.active, 'disabled': !link.url }"
                        />
                    </div>
                </div>
            </div>
        </div>
    </VendorLayout>
</template>

<style scoped>
.form-row { display: flex; gap: 15px; }
.form-row .form-group { flex: 1; }
.error { color: #ff4d4d; font-size: 12px; margin-top: 4px; }
.img-preview { width: 40px; height: 40px; object-fit: cover; border-radius: 5px; }

.page-container { color: white; }
.header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.add-btn { background: #ff2770; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: 600; }

.table-card { background: #1a1a1e; border-radius: 12px; padding: 20px; border: 1px solid #333; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; text-align: left; }
th { color: #888; font-size: 13px; padding-bottom: 15px; border-bottom: 1px solid #333; text-transform: uppercase; }
td { padding: 15px 0; border-bottom: 1px solid #222; }

.img-placeholder { width: 40px; height: 40px; background: #333; border-radius: 5px; }
.badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; text-transform: capitalize; }
.badge.active { background: rgba(0, 255, 127, 0.1); color: #00ff7f; }
.badge.inactive { background: rgba(255, 255, 255, 0.1); color: #ccc; }

.edit-icon { color: #3498db; background: none; border: none; margin-right: 10px; cursor: pointer; font-size: 18px; }
.delete-icon { color: #e74c3c; background: none; border: none; cursor: pointer; font-size: 18px; }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); display: flex; justify-content: center; align-items: center; z-index: 999; }
.modal-box { background: #1a1a1e; width: 100%; max-width: 500px; padding: 25px; border-radius: 12px; border: 1px solid #333; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.close-btn { background: none; border: none; color: #888; font-size: 50px; cursor: pointer; }

.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 6px; font-size: 14px; color: #ccc; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #333; background: #0f0f12; color: white; outline: none; }
.form-group input:focus { border-color: #ff2770; }

.submit-btn { width: 100%; padding: 12px; background: #ff2770; border: none; color: white; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.3s; }
.submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* 4. Custom Dark-Themed Styles for Pagination Components */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding-top: 15px;
}
.pagination-info {
    color: #888;
    font-size: 14px;
}
.pagination-links {
    display: flex;
    gap: 6px;
}
.page-link {
    padding: 8px 14px;
    border: 1px solid #333;
    background: #1a1a1e;
    text-decoration: none;
    color: #ccc;
    font-size: 13px;
    border-radius: 6px;
    transition: all 0.2s ease;
}
.page-link.active {
    background-color: #ff2770;
    color: white;
    border-color: #ff2770;
}
.page-link.disabled {
    color: #555;
    pointer-events: none;
    background-color: #0f0f12;
    border-color: #222;
}
.page-link:hover:not(.disabled):not(.active) {
    background-color: #2d2d35;
    color: #fff;
    border-color: #444;
}
</style>