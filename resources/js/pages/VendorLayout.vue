<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
const page = usePage();
const showProfileModal = ref(false);
// const showReports = ref(false);
const showReports = ref(page.url.startsWith('/vendor/reports'));

const profileForm = useForm({
    name: page.props.auth.user.name,
    email: page.props.auth.user.email,
    password: '',
    password_confirmation: '',
});

const openProfileModal = () => {
    profileForm.clearErrors();
    profileForm.name = page.props.auth.user.name;
    profileForm.email = page.props.auth.user.email;
    showProfileModal.value = true;
};

const closeProfileModal = () => {
    showProfileModal.value = false;
    profileForm.reset('password', 'password_confirmation');
};

const updateProfile = () => {
    profileForm.put('/vendor/profile/update', {
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
</script>

<template>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
    <div class="sidebar-header">
        <h2>Vendor Panel</h2>
    </div>

    <nav class="sidebar-nav">
        <Link href="/vendor/dashboard" class="nav-item">
            <i class="bx bxs-dashboard"></i>
            <span>Dashboard</span>
        </Link>

        <Link href="/vendor/products" class="nav-item">
            <i class="bx bxs-shopping-bag"></i>
            <span>Products</span>
        </Link>

        <Link href="/vendor/orders" class="nav-item">
            <i class="bx bxs-cart"></i>
            <span>Orders</span>
        </Link>

        <!-- Reports Menu -->
        <div class="menu-section">
                    <div 
                        class="nav-item reports-toggle" 
                        :class="{ 'active': $page.url.startsWith('/vendor/reports') }"
                        @click="showReports = !showReports"
                    >
                        <div style="display:flex; align-items:center; gap:10px;">
                            <i class="bx bxs-report"></i>
                            <span>Reports</span>
                        </div>
                        <i :class="showReports ? 'bx bx-chevron-up' : 'bx bx-chevron-down'"></i>
                    </div>

                    <div v-if="showReports" class="submenu">
                        <Link
                            href="/vendor/reports/products"
                            class="nav-item nav-subitem"
                            :class="{ 'sub-active': $page.url === '/vendor/reports/products' }"
                            @click="handleSubitemClick"
                        >
                            Product Report
                        </Link>
                    </div>
                </div>
    </nav>

    <div class="user-profile" @click="openProfileModal">
        <div class="user-avatar">
            <i class="bx bxs-user-circle"></i>
        </div>

        <div class="user-info">
            <p class="user-name">
                {{ $page.props.auth.user.name }}
            </p>
            <p class="user-role">
                Vendor Account
            </p>
        </div>

        <Link
            href="/logout"
            method="post"
            as="button"
            class="profile-logout"
            @click.stop
        >
            <i class="bx bx-log-out"></i>
        </Link>
    </div>
</aside>

        <main class="main-content">
            <header class="top-nav">
                <div class="top-user-name">
                    <i class='bx bxs-user-badge'></i> {{ $page.props.auth.user.name }}
                </div>
            </header>

            <section class="content-body">
                <slot />
            </section>
        </main>

        <div v-if="showProfileModal" class="modal-overlay">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Edit Vendor Profile</h3>
                    <button class="close-x" @click="closeProfileModal">&times;</button>
                </div>

                <form @submit.prevent="updateProfile" class="modal-form">
                    <div class="input-group">
                        <label>Business Name</label>
                        <input type="text" v-model="profileForm.name" placeholder="Enter name" />
                        <span v-if="profileForm.errors.name" class="err-msg">{{ profileForm.errors.name }}</span>
                    </div>

                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" v-model="profileForm.email" placeholder="Enter email" />
                        <span v-if="profileForm.errors.email" class="err-msg">{{ profileForm.errors.email }}</span>
                    </div>

                    <div class="password-section">
                        <p class="section-title">Change Password</p>
                        
                        <div class="input-group mt-10">
                            <input type="password" v-model="profileForm.password" placeholder="New Password" />
                            <span v-if="profileForm.errors.password" class="err-msg">{{ profileForm.errors.password }}</span>
                        </div>

                        <div class="input-group">
                            <input type="password" v-model="profileForm.password_confirmation" placeholder="Confirm New Password" />
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" @click="closeProfileModal">Cancel</button>
                        <button type="submit" class="btn-primary" :disabled="profileForm.processing">
                            {{ profileForm.processing ? 'Updating...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Layout Styles */
.dashboard-wrapper {
    display: flex;
    min-height: 100vh;
    background: #0f0f12;
    color: white;
    font-family: 'Inter', sans-serif;
}

.sidebar {
    width: 260px;
    background: #1a1a1e;
    border-right: 1px solid #333;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid #333;
}

.sidebar-header h2 {
    color: #ff2770;
    font-size: 1.5rem;
}

.sidebar-nav {
    padding: 20px;
    flex-grow: 1;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    margin-bottom: 10px;
    background: #222;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: 0.3s;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.nav-item:hover {
    background: #ff2770;
}

.logout-btn-nav {
    margin-top: 20px;
    background: #2a1b1b;
    color: #ff4d4d;
}

/* User Profile Sidebar Widget */
.user-profile {
    padding: 15px;
    background: #222;
    margin: 10px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: 0.2s;
    border: 1px solid transparent;
}

.user-profile:hover {
    border-color: #ff2770;
    background: #2a2a30;
}

.user-avatar i {
    font-size: 40px;
    color: #ff2770;
}

.user-info {
    flex-grow: 1;
}

.user-name {
    font-weight: bold;
    font-size: 0.9rem;
    margin: 0;
}

.user-role {
    font-size: 0.75rem;
    color: #888;
    margin: 0;
}

.profile-logout {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 1.2rem;
}

.profile-logout:hover {
    color: #ff4d4d;
}

/* Main Content Area */
.main-content {
    flex: 1;
}

.top-nav {
    height: 70px;
    background: #1a1a1e;
    border-bottom: 1px solid #333;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 30px;
}

.top-user-name {
    color: #ff2770;
    font-weight: 600;
}

.content-body {
    padding: 30px;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(4px);
}

.modal-card {
    background: #1a1a1e;
    width: 100%;
    max-width: 450px;
    border-radius: 15px;
    border: 1px solid #333;
    overflow: hidden;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    padding: 20px;
    background: #222;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #333;
}

.modal-header h3 {
    margin: 0;
    color: #ff2770;
}

.close-x {
    background: none;
    border: none;
    color: #888;
    font-size: 28px;
    cursor: pointer;
}

.modal-form {
    padding: 20px;
}

.input-group {
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
}

.input-group label {
    font-size: 0.85rem;
    margin-bottom: 5px;
    color: #aaa;
}

.input-group input {
    background: #0f0f12;
    border: 1px solid #444;
    padding: 12px;
    border-radius: 8px;
    color: white;
    outline: none;
    transition: 0.2s;
}

.input-group input:focus {
    border-color: #ff2770;
    box-shadow: 0 0 0 2px rgba(255, 39, 112, 0.2);
}

.password-section {
    margin-top: 20px;
    padding: 15px;
    background: #222;
    border-radius: 8px;
}

.section-title {
    font-weight: 600;
    margin-bottom: 2px;
}

.mt-10 { margin-top: 10px; }

.err-msg {
    color: #ff4d4d;
    font-size: 0.75rem;
    margin-top: 4px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 20px;
}

.btn-secondary {
    background: #333;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
}

.btn-primary {
    background: #ff2770;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>