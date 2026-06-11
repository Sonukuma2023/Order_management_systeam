<template>
  <div class="dashboard-wrapper">
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo-icon">O</div>
        <h2>Admin<span>Sync</span></h2>
      </div>

      <nav class="sidebar-nav">
        <Link href="/dashboard" class="nav-item" :class="{ 'active': $page.url === '/dashboard' }">
          <i class='bx bxs-dashboard'></i> <span>Dashboard</span>
        </Link>
        <Link href="/products" class="nav-item" :class="{ 'active': $page.url.startsWith('/products') }">
          <i class='bx bxs-shopping-bag'></i> <span>Products</span>
        </Link>
        <Link href="/orders" class="nav-item" :class="{ 'active': $page.url.startsWith('/orders') }">
          <i class='bx bxs-cart'></i> <span>Orders</span>
        </Link>
        <Link href="/category" class="nav-item" :class="{ 'active': $page.url.startsWith('/category') }">
          <i class='bx bxs-category'></i> <span>Category</span>
        </Link>
        <a href="#" @click.prevent="openImportModal" class="nav-item" :class="{ 'active': showImportModal }">
          <i class='bx bxs-user-plus'></i> <span>Client Import</span> 
        </a>
        <Link href="/customer" class="nav-item" :class="{ 'active': $page.url.startsWith('/customer') }">
          <i class='bx bxs-user'></i> <span>Customers</span>
        </Link>
      </nav>

      <div class="user-profile" @click="openProfileModal">
        <div class="user-avatar">
          <i class='bx bxs-user-circle'></i>
        </div>

        <div class="user-info">
          <p class="user-name">{{ $page.props.auth.user.name }}</p>
          <p class="user-role">Administrator</p>
        </div>

        <Link 
          href="/logout" 
          method="post" 
          as="button" 
          type="button"
          class="profile-logout" 
          @click.stop
        >
          <i class='bx bx-log-out'></i>
        </Link> 
      </div>
    </aside>

    <main class="main-content">
      <header class="top-nav">
        <div class="search-bar">
          <i class='bx bx-search'></i>
          <input type="text" placeholder="Search across store...">
        </div>
        <div class="top-user-name">
          <i class='bx bxs-badge-check'></i> {{ $page.props.auth.user.name }}
        </div>
      </header>

      <div class="content-body">
        <slot />
      </div>
    </main>

    <!-- Client Import Modal -->
    <div v-if="showImportModal" class="modal-overlay">
      <div class="modal-card import-modal">
        <div class="modal-header">
          <h3><i class='bx bx-import'></i> Import Customers</h3>
          <button @click="closeImportModal" class="close-x">&times;</button>
        </div>

        <form @submit.prevent="submitImport" class="modal-form">
  <div class="modal-body text-center">
    <p class="modal-subtitle">Upload a CSV file to seamlessly import customers into your database.</p>
    
    <div class="upload-area" :class="{ 'dragging': isDragging }" 
         @dragover.prevent="isDragging = true" 
         @dragleave.prevent="isDragging = false"
         @drop.prevent="handleDrop">
      
      <div class="upload-icon-circle"><i class='bx bx-cloud-upload'></i></div>
      <p v-if="!importForm.file">Drag & drop your CSV file here, or click to browse</p>
      <p v-else class="file-name">{{ importForm.file.name }}</p>
      
      <input type="file" @change="handleFileSelect" accept=".csv" ref="fileInput" hidden>
      <button type="button" @click="$refs.fileInput.click()" class="btn-secondary mt-10">Select File</button>
    </div>
    <span v-if="importForm.errors.file" class="err-msg mt-10">{{ importForm.errors.file }}</span>
  </div>

  <div class="modal-actions-wrapper">
    <button type="button" @click="downloadDemoCSV" class="btn-demo-link">
      download sample csv
    </button>

    <div class="modal-buttons-right">
      <button type="button" @click="closeImportModal" class="btn-secondary">Cancel</button>
      <button type="submit" class="btn-primary" :disabled="importForm.processing || !importForm.file">
        {{ importForm.processing ? 'Importing...' : 'Start Import' }}
      </button>
    </div>
  </div>
</form>
      </div>
    </div>

    <!-- Edit Profile Modal -->
    <div v-if="showProfileModal" class="modal-overlay">
      <div class="modal-card">
        <div class="modal-header">
          <h3>Edit Profile</h3>
          <button @click="closeProfileModal" class="close-x">&times;</button>
        </div>

        <form @submit.prevent="updateProfile" class="modal-form">
          <div class="input-group">
            <label>Full Name</label>
            <input v-model="profileForm.name" type="text" placeholder="Your Name">
            <span v-if="profileForm.errors.name" class="err-msg">{{ profileForm.errors.name }}</span>
          </div>

          <div class="input-group">
            <label>Email Address</label>
            <input v-model="profileForm.email" type="email" placeholder="email@company.com">
            <span v-if="profileForm.errors.email" class="err-msg">{{ profileForm.errors.email }}</span>
          </div>
          
          <div class="password-section">
            <p class="section-title">Security</p>
                  
            <div class="input-group mt-10">
              <input type="password" v-model="profileForm.password" placeholder="New Password" />
              <span v-if="profileForm.errors.password" class="err-msg">{{ profileForm.errors.password }}</span>
            </div>

            <div class="input-group">
              <input type="password" v-model="profileForm.password_confirmation" placeholder="Confirm New Password" />
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeProfileModal" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="profileForm.processing">
              {{ profileForm.processing ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const page = usePage();

// ==========================================
// 1. Profile Editing Module States & Logic
// ==========================================
const showProfileModal = ref(false);

const profileForm = useForm({
  name: page.props.auth.user.name,
  email: page.props.auth.user.email,
  password: '',
  password_confirmation: '',
});

const openProfileModal = () => {
  profileForm.clearErrors();
  showProfileModal.value = true;
};

const closeProfileModal = () => {
  showProfileModal.value = false;
  profileForm.reset('password', 'password_confirmation');
};

const updateProfile = () => {
  profileForm.put('/profile/update', {
    preserveScroll: true,
    onSuccess: () => {
      closeProfileModal();
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Profile updated!',
        showConfirmButton: false,
        timer: 3000,
        background: '#18181b',
        color: '#fff'
      });
    },
  });
};

// ==========================================
// 2. CSV Import Module States & Logic
// ==========================================
const showImportModal = ref(false);
const isDragging = ref(false);
const fileInput = ref(null); // Reference to trigger the hidden file input element

const importForm = useForm({
  file: null,
});

const openImportModal = () => {
  importForm.clearErrors();
  showImportModal.value = true;
};

const closeImportModal = () => {
  showImportModal.value = false;
  importForm.reset();
};

const handleFileSelect = (e) => {
  if (e.target.files && e.target.files.length > 0) {
    importForm.file = e.target.files[0];
    importForm.clearErrors('file');
  }
};

// Completed Drag and Drop tracking logic
const handleDrop = (e) => {
  isDragging.value = false;
  
  if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
    const droppedFile = e.dataTransfer.files[0];
    
    // Validate that the dropped file is actually a CSV
    if (droppedFile.type === 'text/csv' || droppedFile.name.endsWith('.csv')) {
      importForm.file = droppedFile;
      importForm.clearErrors('file');
    } else {
      importForm.setError('file', 'Please drop a valid CSV file format.');
    }
  }
};

// Generates and downloads the dynamic format template directly using your required keys
const downloadDemoCSV = () => {
  const headers = ['First Name', 'Last Name', 'Email', 'Phone', 'Accepts', 'Marketing', 'Tags'];
  const sampleRow = ['Laravel', 'developer', 'test1@gmail.com ', '4525252552', '', '', ''];
  
  const csvContent = [headers.join(','), sampleRow.join(',')].join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  
  link.setAttribute('href', url);
  link.setAttribute('download', 'sample_import_format.csv');
  link.style.visibility = 'hidden';
  
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

// Handles file uploads via Inertia form instances
const submitImport = () => {
  if (!importForm.file) return;

  importForm.post('/vendor/products/import', { // Change this string endpoint path if your router endpoint differs
    preserveScroll: true,
    onSuccess: () => {
      closeImportModal();
      Swal.fire({
        title: 'Imported Successfully!',
        text: 'Your products have been processed and uploaded successfully.',
        icon: 'success',
        confirmButtonColor: '#008060'
      });
    },
    onError: (errors) => {
      console.error('File parsing errors encountered:', errors);
    }
  });
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

/* Layout Styles */
.dashboard-wrapper {
    display: flex;
    min-height: 100vh;
    background: #000000;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
}

/* Sidebar */
.sidebar {
    width: 260px;
    background: #0a0a0a;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.logo-icon {
    width: 32px;
    height: 32px;
    background: #ffffff;
    color: #000000;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 18px;
}

.sidebar-header h2 {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: -0.5px;
    margin: 0;
}
.sidebar-header h2 span {
    color: #3b82f6;
}

.sidebar-nav {
    padding: 20px 16px;
    flex-grow: 1;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    margin-bottom: 4px;
    background: transparent;
    color: #a1a1aa;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s ease;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
}

.nav-item i {
    font-size: 18px;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #ffffff;
}
.nav-item.active {
    background: #18181b;
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

/* User Profile Sidebar Widget */
.user-profile {
    padding: 16px;
    background: #0f0f11;
    margin: 16px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.user-profile:hover {
    border-color: rgba(255, 255, 255, 0.15);
    background: #18181b;
}

.user-avatar i {
    font-size: 36px;
    color: #ffffff;
}

.user-info {
    flex-grow: 1;
    overflow: hidden;
}

.user-name {
    font-weight: 600;
    font-size: 13px;
    margin: 0;
    color: #ffffff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    font-size: 11px;
    color: #3b82f6;
    margin: 0;
    margin-top: 2px;
}

.profile-logout {
    background: none;
    border: none;
    color: #52525b;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 4px;
    transition: color 0.2s;
}

.profile-logout:hover {
    color: #ef4444;
}

/* Main Content Area */
.main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.top-nav {
    height: 72px;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 40px;
    position: sticky;
    top: 0;
    z-index: 50;
}

.search-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #0f0f11;
    border: 1px solid rgba(255, 255, 255, 0.08);
    padding: 8px 16px;
    border-radius: 8px;
    width: 320px;
    transition: all 0.2s;
}

.search-bar:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.search-bar i {
    color: #71717a;
    font-size: 18px;
}

.search-bar input {
    background: transparent;
    border: none;
    color: #ffffff;
    outline: none;
    width: 100%;
    font-size: 13px;
    font-family: 'Inter', sans-serif;
}
.search-bar input::placeholder {
    color: #71717a;
}

.top-user-name {
    color: #e4e4e7;
    font-weight: 500;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.top-user-name i {
    color: #3b82f6;
    font-size: 18px;
}

.content-body {
    padding: 40px;
    flex-grow: 1;
    overflow-y: auto;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(8px);
}

.modal-card {
    background: #0f0f11;
    width: 100%;
    max-width: 480px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    overflow: hidden;
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.import-modal {
    max-width: 540px;
}

@keyframes slideUp {
    from { transform: translateY(20px) scale(0.95); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}

.modal-header {
    padding: 24px 32px;
    background: #0f0f11;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.modal-header h3 {
    margin: 0;
    color: #ffffff;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.modal-header h3 i {
    color: #3b82f6;
    font-size: 20px;
}

.close-x {
    background: none;
    border: none;
    color: #71717a;
    font-size: 24px;
    cursor: pointer;
    line-height: 1;
    transition: color 0.2s;
}
.close-x:hover {
    color: #ffffff;
}

.modal-form {
    padding: 32px;
}

.input-group {
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
}

.input-group label {
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 8px;
    color: #a1a1aa;
}

.input-group input {
    background: #18181b;
    border: 1px solid #27272a;
    padding: 12px 16px;
    border-radius: 10px;
    color: #ffffff;
    font-size: 14px;
    outline: none;
    transition: all 0.2s ease;
}

.input-group input:hover {
    border-color: #3f3f46;
}

.input-group input:focus {
    border-color: #3b82f6;
    background: #141417;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}

.password-section {
    margin-top: 32px;
    padding: 24px;
    background: #18181b;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.section-title {
    font-weight: 600;
    font-size: 14px;
    color: #ffffff;
    margin-bottom: 16px;
}

.mt-10 { margin-top: 10px; }

.err-msg {
    color: #ef4444;
    font-size: 12px;
    margin-top: 6px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 32px;
}

.btn-secondary {
    background: #27272a;
    color: #ffffff;
    border: none;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-secondary:hover {
    background: #3f3f46;
}

.btn-primary {
    background: #ffffff;
    color: #000000;
    border: none;
    padding: 10px 24px;
    font-size: 14px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-primary:hover {
    background: #f4f4f5;
    transform: translateY(-1px);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Upload Area specific styles */
.text-center { text-align: center; }
.modal-subtitle {
    color: #a1a1aa;
    font-size: 14px;
    margin-bottom: 24px;
    line-height: 1.5;
}

.upload-area {
    border: 2px dashed #27272a;
    padding: 40px 24px;
    background: #18181b;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
}

.upload-area.dragging {
    border-color: #3b82f6;
    background: rgba(59, 130, 246, 0.05);
}

.upload-icon-circle {
    width: 56px;
    height: 56px;
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.upload-area p {
    color: #e4e4e7;
    font-size: 14px;
    font-weight: 500;
    margin: 0;
}
.upload-area .file-name {
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 13px;
}
/* Split layout: download button on left, action buttons on right */
.modal-actions-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  padding: 0 1rem; /* Adjust padding matching your frame bounds */
}

.modal-buttons-right {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

/* Red outline sample button styling */
.btn-demo-link {
  background: none;
  border: 1px solid #ff0000;
  color: #ff0000;
  padding: 0.45rem 0.75rem;
  cursor: pointer;
  font-size: 0.85rem;
  border-radius: 4px;
  font-weight: 500;
  transition: background-color 0.2s;
  text-transform: lowercase;
}

.btn-demo-link:hover {
  background-color: rgba(255, 0, 0, 0.05);
}

.mt-10 {
  margin-top: 10px;
}
</style>