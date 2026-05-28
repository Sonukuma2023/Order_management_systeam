<template>
  <div class="auth-body">
    <div class="container">
      <div class="curved-shape"></div>
      
      <div class="form-box">
        <h2 class="title">New Password</h2>
        
        <form @submit.prevent="submit">
          <!-- Email Input -->
          <div class="input-box">
            <input v-model="form.email" type="email" >
            <label>Confirm Email</label>
            <i class='bx bxs-user'></i>
          </div>
          <span v-if="form.errors.email" class="error-text">{{ form.errors.email }}</span>

          <!-- Password Input -->
          <div class="input-box">
            <input v-model="form.password" type="password" >
            <label>New Password</label>
            <i class='bx bxs-lock-alt'></i>
          </div>
          <span v-if="form.errors.password" class="error-text">{{ form.errors.password }}</span>

          <!-- Password Confirmation Input -->
          <div class="input-box">
            <input v-model="form.password_confirmation" type="password" >
            <label>Confirm Password</label>
            <i class='bx bxs-lock-alt'></i>
          </div>
          <span v-if="form.errors.password_confirmation" class="error-text">{{ form.errors.password_confirmation }}</span>

          <button type="submit" class="btn" :disabled="form.processing">
            {{ form.processing ? 'Updating...' : 'Update Password' }}
          </button>
        </form>
      </div>

      <div class="info-content">
        <h2>SECURE RECORD</h2>
        <p>Please provide a strong password combination mix to protect your application balance workspace profiling assets details.</p>
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
/* Reuses your uniform style definitions */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

.auth-body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #25252b; font-family: 'Poppins', sans-serif; }
.container { position: relative; width: 850px; height: 500px; background: #25252b; border: 2px solid #ff2770; box-shadow: 0 0 25px #ff2770; overflow: hidden; display: flex; }
.curved-shape { position: absolute; right: 0; top: 0; height: 600px; width: 500px; background: linear-gradient(45deg, #25252b, #ff2770); transform: rotate(10deg) skewY(40deg); transform-origin: bottom right; z-index: 1; }
.form-box { width: 50%; padding: 0 60px; display: flex; flex-direction: column; justify-content: center; z-index: 2; }
.title { font-size: 32px; color: #fff; text-align: center; margin-bottom: 20px; }
.input-box { position: relative; width: 100%; height: 50px; margin-top: 25px; border-bottom: 2px solid #fff; }
.input-box input { width: 100%; height: 100%; background: transparent; border: none; outline: none; color: #fff; font-size: 16px; }
.input-box label { position: absolute; top: 50%; left: 0; transform: translateY(-50%); color: #fff; transition: .5s; pointer-events: none; }
.input-box input:focus ~ label, .input-box input:valid ~ label { top: -5px; color: #ff2770; }
.input-box i { position: absolute; right: 0; top: 50%; transform: translateY(-50%); color: #fff; }
.btn { width: 100%; height: 45px; background: linear-gradient(90deg, #ff2770, #bc184d); border: none; border-radius: 40px; color: #fff; font-weight: 600; cursor: pointer; margin-top: 30px; box-shadow: 0 0 10px #ff2770; }
.info-content { width: 50%; display: flex; flex-direction: column; justify-content: center; text-align: right; padding: 0 40px; z-index: 2; color: #fff; }
.error-text { color: #ff2770; font-size: 12px; display: block; margin-top: 5px; }
</style>