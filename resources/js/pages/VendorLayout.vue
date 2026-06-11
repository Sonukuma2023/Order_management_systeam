<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
const page = usePage();
const showProfileModal = ref(false);
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
                background: '#18181b',
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
                <Link href="/vendor/dashboard" class="nav-item" :class="{ 'active': $page.url === '/vendor/dashboard' }">
                    <i class="bx bxs-dashboard"></i>
                    <span>Dashboard</span>
                </Link>

                <Link href="/vendor/products" class="nav-item" :class="{ 'active': $page.url === '/vendor/products' }">
                    <i class="bx bxs-shopping-bag"></i>
                    <span>Products</span>
                </Link>

                <Link href="/vendor/orders" class="nav-item" :class="{ 'active': $page.url === '/vendor/orders' }">
                    <i class="bx bxs-cart"></i>
                    <span>Orders</span>
                </Link>

                <Link href="/vendor/import_product" class="nav-item" :class="{ 'active': $page.url === '/vendor/import_product' }">
                    <i class="bx bx-import"></i>
                    <span>Import Product</span>
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
                    <i class='bx bxs-badge-check'></i> {{ $page.props.auth.user.name }}
                </div>
            </header>

            <section class="content-body">
                <slot />
            </section>
        </main>

        <div v-if="showProfileModal" class="modal-overlay">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Edit Profile</h3>
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
                        <button type="button" class="btn-secondary" @click="closeProfileModal">Cancel</button>
                        <button type="submit" class="btn-primary" :disabled="profileForm.processing">
                            {{ profileForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

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
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar-header h2 {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: -0.5px;
    margin: 0;
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

.menu-section {
    margin-top: 4px;
}
.submenu {
    padding-left: 28px;
    margin-top: 4px;
}
.nav-subitem {
    padding: 10px 16px;
    font-size: 13px;
    color: #71717a;
}
.nav-subitem:hover {
    color: #ffffff;
    background: transparent;
}
.sub-active {
    color: #3b82f6 !important;
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
}

.user-name {
    font-weight: 600;
    font-size: 13px;
    margin: 0;
    color: #ffffff;
}

.user-role {
    font-size: 11px;
    color: #71717a;
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
    justify-content: flex-end;
    align-items: center;
    padding: 0 40px;
    position: sticky;
    top: 0;
    z-index: 50;
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
</style>