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
            <span>Inventory Management System</span>
          </div>
          <h2>Join us today and<br>scale your business.</h2>
          <p>Create an account to manage your orders, track inventory, and get a faster checkout experience.</p>
        </div>
        <div class="visual-overlay"></div>status
      </div>

      <!-- Right Panel (Form) -->
      <div class="auth-form-container register-container">
        <div class="form-header">
          <h1>Create an account</h1>
          <p>Please fill in your details below to get started.</p>
        </div>

        <form @submit.prevent="submit" class="auth-form">
          <div class="input-group">
            <label>Full Name</label>
            <div class="input-wrapper">
              <i class='bx bx-user'></i>
             <input
                v-model="form.name"
                type="text"
                @input="form.name = form.name.replace(/[^a-zA-Z\s]/g, '')"
                :class="{ 'has-error': shouldShowError('name') }"
                @blur="touched.name = true"
                placeholder="John Doe"
              />
            </div>
            <span v-if="shouldShowError('name')" class="error-msg">{{ form.errors.name }}</span>
          </div>

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

          <div class="input-group">
            <label>Phone Number</label>
            <div class="input-wrapper">
              <i class='bx bx-phone'></i>
              <input
                v-model="form.phone"
                type="text"
                inputmode="numeric"
                maxlength="10"
                @input="form.phone = form.phone.replace(/\D/g, '').slice(0, 10)"
                :class="{ 'has-error': shouldShowError('phone') }"
                @blur="touched.phone = true"
                placeholder="Enter 10-digit phone number"
              />
            </div>
            <span v-if="shouldShowError('phone')" class="error-msg">{{ form.errors.phone }}</span>
          </div>

          <div class="input-group">
            <label>Password</label>
            <div class="input-wrapper">
              <i class='bx bx-lock-alt'></i>
              <input 
                v-model="form.password" 
                type="password"  
                :class="{'has-error': shouldShowError('password')}"
                @blur="touched.password = true"
                placeholder="••••••••"
              >
            </div>
            <span v-if="shouldShowError('password')" class="error-msg">{{ form.errors.password }}</span>
          </div>

          <div class="input-group">
            <label>Address</label>
            <div class="input-wrapper">
              <i class='bx bx-map'></i>
              <input 
                v-model="form.address" 
                type="text"  
                :class="{'has-error': shouldShowError('address')}"
                @blur="touched.address = true"
                placeholder="123 Business St, City"
              >
            </div>
            <span v-if="shouldShowError('address')" class="error-msg">{{ form.errors.address }}</span>
          </div>

          <button type="submit" class="btn-primary" :disabled="form.processing">
            <span v-if="form.processing" class="spinner"></span>
            <span v-else>Register Now</span>
          </button>
        </form>

        <div class="form-footer">
          <p>
            Already have an account? <Link href="/login" class="text-link accent">Sign in instead</Link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const form = useForm({
  name: '',
  email: '',
  password: '',
  phone: '',
  address: '',
});

const touched = reactive({
  name: false,
  email: false,
  password: false,
  phone: false,
  address: false,
});

const wasSubmitted = ref(false);

const shouldShowError = (field) => {
  return form.errors[field] && (touched[field] || wasSubmitted.value);
};

const submit = () => {
  wasSubmitted.value = true;
  form.post('/register', {
    onFinish: () => form.reset('password'),
    onSuccess: () => wasSubmitted.value = false,
  });
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
  min-height: 720px; /* Slightly taller for register fields */
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
  background: url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=1920') center/cover;
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
  overflow-y: auto;
}

/* Custom Scrollbar for form side */
.auth-form-container::-webkit-scrollbar {
  width: 6px;
}
.auth-form-container::-webkit-scrollbar-track {
  background: transparent;
}
.auth-form-container::-webkit-scrollbar-thumb {
  background: #3f3f46;
  border-radius: 10px;
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
  gap: 20px;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
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
  margin-top: 12px;
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
</style>