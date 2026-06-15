<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      
      <div class="brand-panel">
        <div class="brand-header">
          <div class="brand-logo">
            <div class="logo-box"></div>
            <span class="logo-text">OrderSync</span>
          </div>
        </div>
        
        <div class="brand-content">
          <h1 class="brand-title">Manage your business with precision.</h1>
          <p class="brand-subtitle">
            The ultimate order management system designed to streamline your workflow and accelerate growth.
          </p>
        </div>
      </div>

      <div class="form-panel">
        <div class="form-container">
          <h2 class="form-title">Reset password</h2>
          <p class="form-subtitle">Please enter your new details to update your account password.</p>
          
          <form @submit.prevent="submit" class="auth-form">
            
            <div class="input-group" :class="{ 'has-error': form.errors.email }">
              <label class="input-label">Email address</label>
              <div class="input-wrapper">
                <i class='bx bx-envelope input-icon'></i>
                <input 
                  v-model="form.email" 
                  type="email" 
                  placeholder="name@company.com"
                  autocomplete="email"
                >
              </div>
              <span v-if="form.errors.email" class="error-text">{{ form.errors.email }}</span>
            </div>

            <div class="input-group" :class="{ 'has-error': form.errors.password }">
              <label class="input-label">New Password</label>
              <div class="input-wrapper">
                <i class='bx bx-lock-alt input-icon'></i>
                <input 
                  v-model="form.password" 
                  type="password" 
                  placeholder="••••••••"
                  autocomplete="new-password"
                >
              </div>
              <span v-if="form.errors.password" class="error-text">{{ form.errors.password }}</span>
            </div>

            <div class="input-group" :class="{ 'has-error': form.errors.password_confirmation }">
              <label class="input-label">Confirm Password</label>
              <div class="input-wrapper">
                <i class='bx bx-lock-alt input-icon'></i>
                <input 
                  v-model="form.password_confirmation" 
                  type="password" 
                  placeholder="••••••••"
                  autocomplete="new-password"
                >
              </div>
              <span v-if="form.errors.password_confirmation" class="error-text">{{ form.errors.password_confirmation }}</span>
            </div>

            <button type="submit" class="submit-btn" :disabled="form.processing">
              {{ form.processing ? 'Updating...' : 'Update Password' }}
            </button>
          </form>

          <div class="form-footer">
            <span class="footer-text">Remember your password? </span>
            <a href="/login" class="footer-link">Sign in</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

/* Main Page Layout Wrapper */
.auth-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: radial-gradient(circle at top right, #101223, #07070a);
  font-family: 'Inter', sans-serif;
  padding: 20px;
  box-sizing: border-box;
}

/* Central Card Structure */
.auth-card {
  display: flex;
  width: 100%;
  max-width: 1024px;
  min-height: 640px;
  background-color: #0d0e12;
  border: 1px solid #1f222f;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
}

/* Left Brand Panel with Abstract Blueprint Texture Background */
.brand-panel {
  width: 50%;
  padding: 48px;
  background: 
    linear-gradient(to bottom, rgba(74, 21, 131, 0.4), rgba(13, 14, 18, 0.95)),
    url('https://images.unsplash.com/photo-1634017839464-5c339ebe3cb4?q=80&w=1000&auto=format&fit=crop') no-repeat center;
  background-size: cover;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-sizing: border-box;
  border-right: 1px solid #1f222f;
}

.brand-logo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-box {
  width: 28px;
  height: 28px;
  background: linear-gradient(135deg, #6366f1, #a855f7);
  border-radius: 8px;
}

.logo-text {
  font-size: 22px;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: -0.5px;
}

.brand-content {
  margin-bottom: 24px;
}

.brand-title {
  font-size: 36px;
  font-weight: 700;
  color: #ffffff;
  line-height: 1.25;
  margin-bottom: 16px;
  letter-spacing: -1px;
}

.brand-subtitle {
  font-size: 15px;
  color: #94a3b8;
  line-height: 1.6;
  max-width: 400px;
}

/* Right Form Panel Styling */
.form-panel {
  width: 50%;
  background-color: #0d0e12;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 48px;
  box-sizing: border-box;
}

.form-container {
  width: 100%;
  max-width: 400px;
}

.form-title {
  font-size: 32px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 8px;
  letter-spacing: -0.5px;
}

.form-subtitle {
  font-size: 14px;
  color: #94a3b8;
  margin-bottom: 32px;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Input Group Formatting */
.input-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.input-label {
  font-size: 14px;
  font-weight: 500;
  color: #e2e8f0;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 14px;
  font-size: 18px;
  color: #64748b;
  pointer-events: none;
}

.input-wrapper input {
  width: 100%;
  height: 48px;
  background-color: #161822;
  border: 1px solid #272a3d;
  border-radius: 10px;
  padding: 0 16px 0 44px;
  color: #ffffff;
  font-size: 15px;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.input-wrapper input:focus {
  outline: none;
  border-color: #6366f1;
  background-color: #1a1d2a;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
}

.input-wrapper input::placeholder {
  color: #475569;
}

/* Action Button Details */
.submit-btn {
  width: 100%;
  height: 48px;
  background-color: #ffffff;
  color: #0d0e12;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-top: 12px;
}

.submit-btn:hover:not(:disabled) {
  background-color: #f1f5f9;
  transform: translateY(-1px);
}

.submit-btn:active:not(:disabled) {
  transform: translateY(0);
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Errors & Bottom Links */
.error-text {
  font-size: 13px;
  color: #f43f5e;
  margin-top: 2px;
}

.input-group.has-error input {
  border-color: #f43f5e;
}

.input-group.has-error input:focus {
  box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.15);
}

.form-footer {
  margin-top: 32px;
  text-align: center;
  font-size: 14px;
}

.footer-text {
  color: #64748b;
}

.footer-link {
  color: #ffffff;
  text-decoration: none;
  font-weight: 500;
  margin-left: 4px;
  transition: color 0.2s ease;
}

.footer-link:hover {
  text-decoration: underline;
  color: #6366f1;
}

/* Responsive adjustment for small monitors or mobile layouts */
@media (max-width: 768px) {
  .auth-card {
    flex-direction: column;
    min-height: auto;
  }
  .brand-panel {
    width: 100%;
    padding: 32px;
    min-height: 220px;
    border-right: none;
    border-bottom: 1px solid #1f222f;
  }
  .form-panel {
    width: 100%;
    padding: 32px;
  }
}
</style>