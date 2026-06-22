<template>
  <div class="auth-wrapper">
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>
    
    <div class="auth-card">
      <!-- Left Panel (Visual) -->
      <div class="auth-visual">
        <div class="visual-content">
          <div class="logo-placeholder">
            <div class="logo-icon"></div>
            <span>Inventory Management System </span>
          </div>
          <h2>Manage your business<br>with precision.</h2>
          <p>The ultimate order management system designed to streamline your workflow and accelerate growth.</p>
        </div>
        <div class="visual-overlay"></div>
      </div>

      <!-- Right Panel (Form) -->
      <div class="auth-form-container">
        <div class="form-header">
          <h1>{{ isForgotPasswordMode ? 'Reset Password' : 'Welcome back' }}</h1>
          <p>{{ isForgotPasswordMode ? 'Enter your email to receive a reset link.' : 'Please enter your details to sign in to your account.' }}</p>
        </div>

        <div v-if="statusMessage" class="status-alert">
            {{ statusMessage }}
        </div>

        <form @submit.prevent="submit" class="auth-form">
          <div class="input-group">
            <label>Email address</label>
            <div class="input-wrapper">
              <i class='bx bx-envelope'></i>
              <input 
                v-model="form.email" 
                type="email"  
                :class="{'has-error': shouldShowError('email')}"
                @blur="touched.email = true"
                placeholder="name@company.com"
              >
            </div>
            <span v-if="shouldShowError('email')" class="error-msg">{{ form.errors.email }}</span>
          </div>

         <div v-if="!isForgotPasswordMode" class="input-group">
            <div class="label-row">
              <label>Password</label>
              <button type="button" @click="toggleMode" class="text-link">Forgot password?</button>
            </div>
            <div class="input-wrapper">
              <i class='bx bx-lock-alt'></i>
              
              <input 
                v-model="form.password" 
                :type="showLoginPassword ? 'text' : 'password'"  
                :class="{'has-error': shouldShowError('password')}"
                @blur="touched.password = true"
                placeholder="••••••••"
              >

              <span class="eye-icon-toggle" @click="showLoginPassword = !showLoginPassword">
                <svg v-if="showLoginPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-svg">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M3 3l18 18"></path>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon-svg">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </span>
            </div>
            <span v-if="shouldShowError('password')" class="error-msg">{{ form.errors.password }}</span>
        </div>

          <button type="submit" class="btn-primary" :disabled="form.processing">
            <span v-if="form.processing" class="spinner"></span>
            <span v-else>{{ isForgotPasswordMode ? 'Send Reset Link' : 'Sign in' }}</span>
          </button>
        </form>
        <div v-if="!isForgotPasswordMode" class="auth-divider">
          <span>or continue with</span>
        </div>

        <a v-if="!isForgotPasswordMode" href="/auth/google" class="google-btn">
          <svg class="google-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
          </svg>
          <span>Sign in with Google</span>
        </a>
        <div class="form-footer">
          <p v-if="!isForgotPasswordMode">
            Don't have an account? <Link href="/register" class="text-link accent">Sign up for free</Link>
          </p>
          <p v-else>
            Remember your password? <button type="button" @click="toggleMode" class="text-link accent">Back to sign in</button>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const form = useForm({ email: '', password: '' });
const touched = reactive({ email: false, password: false });
const wasSubmitted = ref(false);
// Add this line with your other refs
const showLoginPassword = ref(false);

const isForgotPasswordMode = ref(false);
const statusMessage = ref('');

const toggleMode = () => {
    isForgotPasswordMode.value = !isForgotPasswordMode.value;
    form.clearErrors();
    wasSubmitted.value = false;
    statusMessage.value = '';
    touched.email = false;
    touched.password = false;
};

const shouldShowError = (field) => form.errors[field] && (touched[field] || wasSubmitted.value);

const submit = () => {
  wasSubmitted.value = true;
  statusMessage.value = '';

  if (isForgotPasswordMode.value) {
      form.post('/forgot-password', {
          onSuccess: () => {
              statusMessage.value = "Password reset email link issued successfully.";
              form.reset('email');
              wasSubmitted.value = false;
          }
      });
  } else {
      form.post('/login', { onFinish: () => form.reset('password') });
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

.auth-wrapper {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #000000;
  font-family: 'Inter', sans-serif;
  overflow: hidden;
  color: #ffffff;
  padding: 20px;
}

/* Ambient Glow Orbs */
.glow-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
  z-index: 0;
  opacity: 0.6;
}
.orb-1 {
  width: 600px;
  height: 600px;
  background: #3b82f6;
  top: -200px;
  left: -100px;
  animation: floatOrb 15s ease-in-out infinite alternate;
}
.orb-2 {
  width: 500px;
  height: 500px;
  background: #8b5cf6;
  bottom: -150px;
  right: -100px;
  animation: floatOrb 20s ease-in-out infinite alternate-reverse;
}

@keyframes floatOrb {
  0% { transform: translate(0, 0); }
  100% { transform: translate(50px, 50px); }
}

.auth-card {
  position: relative;
  z-index: 10;
  display: flex;
  width: 100%;
  max-width: 1080px;
  min-height: 640px;
  background: rgba(20, 20, 22, 0.6);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  box-shadow: 0 40px 80px rgba(0, 0, 0, 0.5);
  overflow: hidden;
}

/* Left Visual Side */
.auth-visual {
  flex: 1;
  position: relative;
  background: url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=1920') center/cover;
  padding: 60px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.visual-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(88, 28, 135, 0.8) 100%);
  z-index: 1;
}

.visual-content {
  position: relative;
  z-index: 2;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.logo-placeholder {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -0.5px;
  color: #fff;
}
.logo-icon {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  border-radius: 8px;
}

.visual-content h2 {
  margin-top: auto;
  font-size: 42px;
  font-weight: 700;
  line-height: 1.1;
  letter-spacing: -1px;
  margin-bottom: 24px;
  color: #ffffff;
}

.visual-content p {
  font-size: 16px;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.7);
  max-width: 80%;
}

/* Right Form Side */
.auth-form-container {
  flex: 1;
  padding: 60px 80px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: #0f0f11;
}

.form-header {
  margin-bottom: 40px;
}
.form-header h1 {
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -0.5px;
  margin-bottom: 12px;
  color: #fff;
}
.form-header p {
  font-size: 15px;
  color: #8b8b93;
  line-height: 1.5;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.input-group label {
  font-size: 14px;
  font-weight: 500;
  color: #e4e4e7;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper i {
  position: absolute;
  left: 16px;
  font-size: 20px;
  color: #71717a;
  pointer-events: none;
  transition: color 0.2s;
}

.input-wrapper input {
  width: 100%;
  height: 48px;
  background: #18181b;
  border: 1px solid #27272a;
  border-radius: 12px;
  padding: 0 16px 0 44px;
  color: #fff;
  font-size: 15px;
  font-family: inherit;
  transition: all 0.2s ease;
}

.input-wrapper input::placeholder {
  color: #52525b;
}

.input-wrapper input:hover {
  border-color: #3f3f46;
}

.input-wrapper input:focus {
  outline: none;
  border-color: #3b82f6;
  background: #141417;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}
.input-wrapper input:focus + i,
.input-wrapper input:focus ~ i {
  color: #3b82f6;
}

.has-error {
  border-color: #ef4444 !important;
}
.error-msg {
  color: #ef4444;
  font-size: 13px;
  margin-top: 4px;
}

.btn-primary {
  height: 48px;
  background: #ffffff;
  color: #000000;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-primary:hover {
  background: #f4f4f5;
  transform: translateY(-1px);
}
.btn-primary:active {
  transform: translateY(0);
}
.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.text-link {
  background: none;
  border: none;
  padding: 0;
  font-family: inherit;
  font-size: 14px;
  font-weight: 500;
  color: #a1a1aa;
  cursor: pointer;
  text-decoration: none;
  transition: color 0.2s;
}
.text-link:hover {
  color: #ffffff;
}
.text-link.accent {
  color: #ffffff;
}
.text-link.accent:hover {
  text-decoration: underline;
}

.form-footer {
  margin-top: 32px;
  text-align: center;
}
.form-footer p {
  font-size: 14px;
  color: #a1a1aa;
}

.status-alert {
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.2);
  color: #4ade80;
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 14px;
  margin-bottom: 24px;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(0,0,0,0.1);
  border-top-color: #000;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 1024px) {
  .auth-card {
    flex-direction: column;
    max-width: 500px;
    min-height: auto;
  }
  .auth-visual {
    display: none;
  }
  .auth-form-container {
    padding: 48px 40px;
    border-radius: 24px;
  }
}
@media (max-width: 640px) {
  .auth-form-container {
    padding: 32px 24px;
  }
}
/* Ensure the wrapper serves as a relative positioning anchor */
.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}

/* Add padding to the right so typing text doesn't slide under the eye */
.input-wrapper input {
    width: 100%;
    padding-right: 40px; 
}

/* Position the eye wrapper perfectly to the right side of the input box */
.eye-icon-toggle {
    position: absolute;
    right: 14px;      
    cursor: pointer;  
    user-select: none; 
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;       
    height: 20px;
    z-index: 2; /* Ensures it stays on top of the input field */
}

/* Clean vector styling matching your dark layout */
.eye-icon-svg {
    width: 100%;       
    height: 100%;
    color: #6b7280;   /* Slate/gray resting state color */
    transition: color 0.15s ease-in-out;
}

/* Turns bright white on hover against your dark UI */
.eye-icon-toggle:hover .eye-icon-svg {
    color: #ffffff;   
}

/* Premium Divider Component */
.auth-divider {
  display: flex;
  align-items: center;
  text-align: center;
  margin: 1.5rem 0;
  color: #a0a0a0;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
}

.auth-divider::before, 
.auth-divider::after {
  content: '';
  flex: 1;
  /* Soft semi-transparent borders blend beautifully on dark backgrounds */
  border-bottom: 1px solid rgba(255, 255, 255, 0.12); 
}

.auth-divider:not(:empty)::before { margin-right: 1rem; }
.auth-divider:not(:empty)::after { margin-left: 1rem; }

/* Modern Minimalist Google Button */
.google-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  width: 100%;
  padding: 0.8rem;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  background-color: transparent; /* Changed to dark theme ghost style */
  color: #ffffff;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.95rem;
  transition: background-color 0.2s ease, border-color 0.2s ease;
  box-sizing: border-box;
}

.google-btn:hover {
  background-color: rgba(255, 255, 255, 0.07);
  border-color: rgba(255, 255, 255, 0.3);
}

/* Perfect alignment sizing for the SVG asset */
.google-icon {
  width: 18px;
  height: 18px;
  display: block;
}


</style>