<template>
  <div class="auth-body">
    <div class="container">
      <div class="curved-shape"></div>
      
      <div class="form-box">
        <!-- Dynamic Header Title -->
        <h2 class="title">{{ isForgotPasswordMode ? 'Reset Password' : 'Login' }}</h2>
        
        <!-- Status success indicator -->
        <div v-if="statusMessage" class="status-success-alert">
            {{ statusMessage }}
        </div>

        <form @submit.prevent="submit">
          <!-- EMAIL INPUT (Active across both modes) -->
          <div class="input-box">
            <input 
              v-model="form.email" 
              type="email"  
              :class="{'input-error': shouldShowError('email')}"
              @blur="touched.email = true"
               
            >
            <label>Email Address</label>
            <i class='bx bxs-user'></i>
          </div>
          <span v-if="shouldShowError('email')" class="error-text">{{ form.errors.email }}</span>

          <!-- PASSWORD INPUT BLOCK (Hidden while using Forgot Password system view) -->
          <div v-if="!isForgotPasswordMode">
            <div class="input-box">
              <input 
                v-model="form.password" 
                type="password"  
                :class="{'input-error': shouldShowError('password')}"
                @blur="touched.password = true"
                 
              >
              <label>Password</label>
              <i class='bx bxs-lock-alt'></i>
            </div>
            <span v-if="shouldShowError('password')" class="error-text">{{ form.errors.password }}</span>
            
            <!-- Forgot Password trigger button option wrapper -->
            <div class="forgot-link-wrapper">
               <button type="button" @click="toggleMode" class="ghost-link-btn">Forgot Password?</button>
            </div>
          </div>

          <!-- DYNAMIC ACTION CTA BUTTON -->
          <button type="submit" class="btn" :disabled="form.processing">
            <span v-if="form.processing">Authenticating...</span>
            <span v-else>{{ isForgotPasswordMode ? 'Send Reset Link' : 'Login' }}</span>
          </button>

          <!-- BOTTOM INTERPOLATING LINK LINKS -->
          <div class="footer-link">
            <p v-if="!isForgotPasswordMode">
              Don't have an account? <Link href="/register">Sign Up</Link>
            </p>
            <p v-else>
              Remembered your password? <button type="button" @click="toggleMode" class="inline-toggle-btn">Back to Login</button>
            </p>
          </div>
        </form>
      </div>

      <div class="info-content">
        <h2>WELCOME BACK!</h2>
        <p>We are happy to have you with us again. If you need anything, we are here to help.</p>
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

// View Mode Toggle tracking definitions
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
      // POST out to backend forgot-password handler engine
      form.post('/forgot-password', {
          onSuccess: () => {
              statusMessage.value = "Password reset email link issued successfully.";
              form.reset('email');
              wasSubmitted.value = false;
          }
      });
  } else {
      // Execute original login pipeline routine
      form.post('/login', { onFinish: () => form.reset('password') });
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

.auth-body {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: #25252b;
  font-family: 'Poppins', sans-serif;
}

.container {
  position: relative;
  width: 850px;
  height: 500px;
  background: #25252b;
  border: 2px solid #ff2770;
  box-shadow: 0 0 25px #ff2770;
  overflow: hidden;
  display: flex;
}

.curved-shape {
  position: absolute;
  right: 0;
  top: 0;
  height: 600px;
  width: 500px;
  background: linear-gradient(45deg, #25252b, #ff2770);
  transform: rotate(10deg) skewY(40deg);
  transform-origin: bottom right;
  z-index: 1;
}

.form-box {
  width: 50%;
  padding: 0 60px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  z-index: 2;
}

.title { font-size: 32px; color: #fff; text-align: center; margin-bottom: 20px; }

.input-box {
  position: relative;
  width: 100%;
  height: 50px;
  margin-top: 25px;
  border-bottom: 2px solid #fff;
}

.input-box input {
  width: 100%;
  height: 100%;
  background: transparent;
  border: none;
  outline: none;
  color: #fff;
  font-size: 16px;
}

.input-box label {
  position: absolute;
  top: 50%;
  left: 0;
  transform: translateY(-50%);
  color: #fff;
  transition: .5s;
  pointer-events: none;
}

/* Kept input labels dynamic for bound inputs data state values */
.input-box input:focus ~ label, 
.input-box input:valid ~ label {
  top: -5px;
  color: #ff2770;
}

.input-box i { position: absolute; right: 0; top: 50%; transform: translateY(-50%); color: #fff; }

.btn {
  width: 100%;
  height: 45px;
  background: linear-gradient(90deg, #ff2770, #bc184d);
  border: none;
  border-radius: 40px;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
  margin-top: 30px;
  box-shadow: 0 0 10px #ff2770;
}

.info-content {
  width: 50%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  text-align: right;
  padding: 0 40px;
  z-index: 2;
  color: #fff;
}

.footer-link { text-align: center; margin-top: 20px; color: #fff; }
.footer-link a { color: #ff2770; text-decoration: none; font-weight: 600; }
.error-text { color: #ff2770; font-size: 12px; display: block; margin-top: 5px; }

/* Custom Additions matching your styling theme rules */
.forgot-link-wrapper {
    display: flex;
    justify-content: flex-end;
    margin-top: 10px;
}

.ghost-link-btn {
    background: none;
    border: none;
    color: #fff;
    font-size: 13px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    transition: color 0.2s;
}

.ghost-link-btn:hover {
    color: #ff2770;
    text-decoration: underline;
}

.inline-toggle-btn {
    background: none;
    border: none;
    color: #ff2770;
    font-weight: 600;
    cursor: pointer;
    font-size: inherit;
    font-family: 'Poppins', sans-serif;
    padding: 0;
}

.inline-toggle-btn:hover {
    text-decoration: underline;
}

.status-success-alert {
    background: rgba(46, 204, 113, 0.1);
    border: 1px solid #2ecc71;
    color: #2ecc71;
    padding: 10px;
    border-radius: 8px;
    font-size: 13px;
    text-align: center;
    margin-bottom: 10px;
}

.input-error {
    border-color: #ff2770 !important;
}
</style>