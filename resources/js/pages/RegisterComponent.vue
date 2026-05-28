<template>
  <div class="auth-body">
    <div class="container">
      <div class="curved-shape"></div>
      <div class="curved-shape2"></div>

      <div class="info-content">
        <h2 class="animation">JOIN US!</h2>
        <p class="animation">Create an account to manage your orders, save your address, and get a faster checkout experience.</p>
      </div>

      <div class="form-box">
        <h2 class="animation">Register</h2>
        <form @submit.prevent="submit">
          
          <div class="input-box animation">
            <input v-model="form.name" type="text"  @blur="touched.name = true">
            <label>Full Name</label>
            <i class='bx bxs-user'></i>
            <span v-if="shouldShowError('name')" class="error-msg">{{ form.errors.name }}</span>
          </div>

          <div class="input-box animation">
            <input v-model="form.email" type="email"  @blur="touched.email = true">
            <label>Email</label>
            <i class='bx bxs-envelope'></i>
            <span v-if="shouldShowError('email')" class="error-msg">{{ form.errors.email }}</span>
          </div>

          <div class="input-box animation">
            <input v-model="form.phone" type="tel"  @blur="touched.phone = true">
            <label>Phone Number</label>
            <i class='bx bxs-phone'></i>
            <span v-if="shouldShowError('phone')" class="error-msg">{{ form.errors.phone }}</span>
          </div>

          <div class="input-box animation">
            <input v-model="form.password" type="password"  @blur="touched.password = true">
            <label>Password</label>
            <i class='bx bxs-lock-alt'></i>
            <span v-if="shouldShowError('password')" class="error-msg">{{ form.errors.password }}</span>
          </div>

          <div class="input-box animation">
            <input v-model="form.address" type="text" @blur="touched.address = true">
            <label>Address</label>
            <i class='bx bxs-map'></i>
            <span v-if="shouldShowError('address')" class="error-msg">{{ form.errors.address }}</span>
          </div>

          <button type="submit" class="btn animation" :disabled="form.processing">
            {{ form.processing ? 'Creating Account...' : 'Register Now' }}
          </button>

          <div class="regi-link animation">
            <p>Already have an account? <Link href="/login">Sign In</Link></p>
          </div>
        </form>
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
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

.auth-body {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: #25252b;
  font-family: 'Poppins', sans-serif;
  color: #fff;
  padding: 20px;
}

.container {
  position: relative;
  width: 900px; /* Slightly wider to accommodate labels */
  height: 650px; /* Taller to fit Name, Email, Password, Phone, Address */
  background: #25252b;
  border: 2px solid #ff2770;
  box-shadow: 0 0 25px #ff2770;
  overflow: hidden;
  display: flex;
}

/* Decorative Shapes */
.curved-shape {
  position: absolute;
  left: -200px;
  bottom: 0;
  height: 700px;
  width: 900px;
  background: linear-gradient(45deg, #ff2770, #25252b);
  transform: rotate(-10deg) skewY(-40deg);
  transform-origin: bottom left;
  z-index: 1;
  pointer-events: none;
}

.curved-shape2 {
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  width: 100%;
  background: #25252b;
  z-index: 0;
}

/* Info Section */
.info-content {
  position: relative;
  width: 45%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0 40px;
  z-index: 2;
}

.info-content h2 { font-size: 40px; margin-bottom: 10px; text-transform: uppercase; }
.info-content p { font-size: 14px; line-height: 1.6; }

/* Form Section */
.form-box {
  position: relative;
  width: 55%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0 60px;
  z-index: 2;
}

.form-box h2 { font-size: 32px; text-align: center; margin-bottom: 10px; }

.input-box {
  position: relative;
  width: 100%;
  height: 45px;
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
  font-size: 15px;
  padding-right: 30px;
}

.input-box label {
  position: absolute;
  top: 50%;
  left: 0;
  transform: translateY(-50%);
  transition: .4s;
  pointer-events: none;
  color: #efefef;
}

.input-box input:focus ~ label,
.input-box input:valid ~ label {
  top: -5px;
  color: #ff2770;
  font-size: 12px;
}

.input-box i {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  font-size: 18px;
}

.error-msg {
  color: #ff2770;
  font-size: 10px;
  position: absolute;
  bottom: -16px;
  left: 0;
}

/* Button */
.btn {
  width: 100%;
  height: 45px;
  background: #ff2770;
  border: none;
  border-radius: 40px;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
  margin-top: 35px;
  box-shadow: 0 0 10px #ff2770;
  transition: 0.3s ease;
}

.btn:hover {
  background: #bc184d;
  box-shadow: 0 0 20px #ff2770;
}

.regi-link { text-align: center; margin-top: 20px; font-size: 14px; }
.regi-link a { color: #ff2770; text-decoration: none; font-weight: 700; }
</style>