<template>
  <div class="dashboard-container">
    <aside class="sidebar">
      <div class="logo-section">
        <div class="logo-icon">G</div>
        
        <h2>Admin<span>panel</span></h2>
      </div>

      <nav class="nav-menu">
        <Link href="/dashboard" :class="{ 'active': $page.url === '/dashboard' }">
          <i class='bx bxs-dashboard'></i> <span>Dashboard</span>
        </Link>
        <Link href="/products" :class="{ 'active': $page.url.startsWith('/products') }">
          <i class='bx bxs-shopping-bag'></i> <span>Products</span>
        </Link>
        <Link href="/orders">
          <i class='bx bxs-cart'></i> <span>Orders</span>
        </Link>
        <Link href="/category">
          <i class='bx bxs-category'></i> <span>Category</span>
        </Link>
        <a href="#" @click.prevent="openImportModal" :class="{ 'active': showImportModal }">
          <i class='bx bxs-user-plus'></i> <span>Client import</span> 
        </a>

        <Link href="/customer">
          <i className='bx bxs-user'></i> <span>Customer</span>
        </Link>
        
      </nav>
      <div v-if="showImportModal" class="modal-overlay">
  <div class="modal-card import-modal">
    <div class="modal-header">
      <h3><i class='bx bxs-file-import'></i> Import Customers</h3>
      <button @click="closeImportModal" class="close-btn">&times;</button>
    </div>

    <form @submit.prevent="submitImport">
      <div class="modal-body">
        <p class="modal-subtitle">Upload a CSV file to add customers to your database.</p>
        
        <div class="upload-area" :class="{ 'dragging': isDragging }" 
             @dragover.prevent="isDragging = true" 
             @dragleave.prevent="isDragging = false"
             @drop.prevent="handleDrop">
          
          <i class='bx bxs-cloud-upload'></i>
          <p v-if="!importForm.file">Click or drag & drop CSV file</p>
          <p v-else class="file-name">{{ importForm.file.name }}</p>
          
          <input type="file" @change="handleFileSelect" accept=".csv" ref="fileInput" hidden>
          <button type="button" @click="$refs.fileInput.click()" class="select-btn">Browse Files</button>
        </div>
        <span v-if="importForm.errors.file" class="error-text">{{ importForm.errors.file }}</span>
      </div>

      <div class="modal-footer">
        <button type="button" @click="closeImportModal" class="cancel-btn">Cancel</button>
        <button type="submit" class="save-btn" :disabled="importForm.processing">
          {{ importForm.processing ? 'Importing...' : 'Start Import' }}
        </button>
      </div>
    </form>
  </div>
</div>

      

<div class="user-profile" @click="openProfileModal" style="cursor: pointer;">
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
    class="logout-btn" 
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
          <input type="text" placeholder="Search data...">
        </div>
        <div class="top-actions">
          <i class='bx bxs-bell'></i>
          <div class="avatar"></div>
        </div>
      </header>

      <div class="content-body">
        <slot />
      </div>
    </main>
    <div v-if="showProfileModal" class="modal-overlay">
      <div class="modal-card profile-modal">
        <div class="modal-header">
          <h3>Edit Profile</h3>
          <button @click="closeProfileModal" class="close-btn">&times;</button>
        </div>

        <form @submit.prevent="updateProfile">
          <div class="modal-body">
            <div class="form-group">
              <label>Full Name</label>
              <input v-model="profileForm.name" type="text" placeholder="Your Name">
              <span v-if="profileForm.errors.name" class="error-text">{{ profileForm.errors.name }}</span>
            </div>

            <div class="form-group">
              <label>Email Address</label>
              <input v-model="profileForm.email" type="email" placeholder="email@example.com">
              <span v-if="profileForm.errors.email" class="error-text">{{ profileForm.errors.email }}</span>
            </div>

            <hr class="modal-divider">
            
            <div class="password-section">
              <p class="section-title">Change Password</p>
                    
              <div class="input-group mt-10">
                <input type="password" v-model="profileForm.password" placeholder="New Password" />
                <span v-if="profileForm.errors.password" class="error-text">{{ profileForm.errors.password }}</span>
              </div>

              <div class="input-group">
                <input type="password" v-model="profileForm.password_confirmation" placeholder="Confirm New Password" />
              </div>
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" @click="closeProfileModal" class="cancel-btn">Cancel</button>
            <button type="submit" class="save-btn" :disabled="profileForm.processing">
              {{ profileForm.processing ? 'Updating...' : 'Update Profile' }}
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
const showProfileModal = ref(false);

const profileForm = useForm({
  name: page.props.auth.user.name,
  email: page.props.auth.user.email,
  password: '',
});

const openProfileModal = () => {
  profileForm.clearErrors();
  showProfileModal.value = true;
};

const closeProfileModal = () => {
  showProfileModal.value = false;
  profileForm.reset('password');
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
        background: '#1a1a1e',
        color: '#fff'
      });
    },
  });
};

const showImportModal = ref(false);
const isDragging = ref(false);

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
  importForm.file = e.target.files[0];
};

const handleDrop = (e) => {
  isDragging.value = false;
  importForm.file = e.dataTransfer.files[0];
};

const submitImport = () => {
  if (!importForm.file) {
    return;
  }

  importForm.post('/client-import', {
    preserveScroll: true,
    onSuccess: () => {
      closeImportModal();
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Import Complete!',
        showConfirmButton: false,
        timer: 3000,
        background: '#1a1a1e',
        color: '#fff'
      });
    },
  });
};

</script>

<style scoped>

.modal-overlay {
  position: fixed;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.8);
  display: flex; justify-content: center; align-items: center;
  z-index: 9999;
}
.profile-modal {
  width: 400px;
  background: #25252b;
  border: 1px solid #ff2770;
  border-radius: 15px;
  box-shadow: 0 0 20px rgba(255, 39, 112, 0.2);
}
.modal-header {
  padding: 20px;
  border-bottom: 1px solid #333;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.modal-header h3 { color: #ff2770; margin: 0; font-size: 18px; }

.modal-body { padding: 20px; }

.modal-divider { border: 0; border-top: 1px solid #333; margin: 20px 0; }
.section-hint { font-size: 11px; color: #718096; margin-bottom: 10px; }

.form-group { display: flex;
  flex-direction: column;
  align-items: flex-start; /* Forces children (labels and inputs) to align left */
  text-align: left;        /* Ensures text resets to left alignment */
  margin-bottom: 15px; }
.form-group label { display: block;
  width: 100%;
  text-align: left;
  margin-bottom: 5px;      /* Spacing between label and input */
  font-weight: 500; }
.form-group input {
  width: 100%;
  background: #1a1a1e;
  border: 1px solid #333;
  padding: 10px;
  border-radius: 8px;
  color: white;
  outline: none;
}
.form-group input:focus { border-color: #ff2770; }

.modal-footer {
  padding: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.save-btn {
  background: #ff2770;
  color: white;
  border: none;
  padding: 8px 20px;
  border-radius: 8px;
  cursor: pointer;
}

.cancel-btn {
  background: transparent;
  color: #a0aec0;
  border: 1px solid #333;
  padding: 8px 20px;
  border-radius: 8px;
  cursor: pointer;
}

.error-text { color: #ff2770; font-size: 11px; margin-top: 5px; display: block;}
.dashboard-container {
  display: flex;
  min-height: 100vh;
  background: #1a1a1e; /* Match your dark theme */
  color: #fff;
}

/* Sidebar Styling */
.sidebar {
  width: 260px;
  background: #25252b;
  border-right: 1px solid #ff2770;
  display: flex;
  flex-direction: column;
  position: fixed;
  height: 100vh;
}

.logo-section {
  padding: 30px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo-icon {
  background: #ff2770;
  width: 35px;
  height: 35px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 8px;
  font-weight: bold;
}

.logo-section h2 span { color: #ff2770; }

.nav-menu {
  flex: 1;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.nav-menu a {
  text-decoration: none;
  color: #a0aec0;
  padding: 12px 15px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: 0.3s;
}

.nav-menu a i { font-size: 20px; }
.nav-menu a:hover, .nav-menu a.active {
  background: rgba(255, 39, 112, 0.1);
  color: #ff2770;
}

/* Main Content area */
.main-content {
  flex: 1;
  margin-left: 260px;
  display: flex;
  flex-direction: column;
}

.top-nav {
  height: 70px;
  background: #25252b;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 30px;
  border-bottom: 1px solid #333;
}

.search-bar {
  background: #1a1a1e;
  padding: 8px 15px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-bar input {
  background: transparent;
  border: none;
  color: #fff;
  outline: none;
}

.content-body {
  padding: 30px;
  overflow-y: auto;
}

.user-profile {
  padding: 15px;
  background: #1a1a1e;
  margin: 20px;
  border-radius: 12px;
  display: flex;
  align-items: center; 
  gap: 15px; /* Increased gap slightly for breathing room */
  border: 1px solid transparent;
  transition: 0.3s;
  width: auto; /* Ensures it expands nicely, or set a solid width like 250px */
}

.user-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0; /* Ensures text truncation works if name is long */
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 2px; /* Add space between name and role */
}

.user-role {
  font-size: 11px;
  color: #ff2770;
  margin: 0;
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 5px;
  color: #ff2770;
  font-weight: 500;
  font-size: 14px;
  text-decoration: none;
}

/* Container for the whole modal */
.import-modal {
  width: 500px;
  background: #25252b;
  border-radius: 15px;
  border: 1px solid #333;
  overflow: hidden;
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid #333;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  color: #ff2770;
  font-size: 18px;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Modal Body spacing */
.modal-body {
  padding: 30px 20px;
  text-align: center;
}

.modal-subtitle {
  color: #fff;
  font-weight: 500;
  margin-bottom: 20px;
}

/* Upload Area Fixes */
.upload-area {
  border: 2px dashed #444;
  padding: 40px 20px;
  background: #1a1a1e;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
}

/* Modal Footer alignment */
.modal-footer {
  padding: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  border-top: 1px solid #333;
}

.cancel-btn {
  padding: 10px 25px;
  border-radius: 8px;
  background: transparent;
  border: 1px solid #444;
  color: #fff;
  cursor: pointer;
}

.save-btn {
  padding: 10px 25px;
  border-radius: 8px;
  background: #ff2770;
  border: none;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
}
.password-section {
    text-align: left; /* Ensure labels/titles align left */
    margin-top: 10px;
}

.section-title {
    font-size: 14px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 15px;
    text-align:  left; /* Matches your screenshot "Change Password" header */
}

.input-group {
    margin-bottom: 12px;
}

/* Ensure the password inputs match the name/email inputs */
.input-group input {
    width: 100%;
    background: #1a1a1e;
    border: 1px solid #333;
    padding: 10px;
    border-radius: 8px;
    color: white;
    outline: none;
    transition: border-color 0.2s;
}

.input-group input:focus {
    border-color: #ff2770;
}

.mt-10 {
    margin-top: 10px;
}

/* Style for the validation error message below password */
.err-msg {
    color: #ff2770;
    font-size: 11px;
    margin-top: 5px;
    display: block;
}
.user-avatar i {
    font-size: 40px;
    color: #ff2770;
}
</style>