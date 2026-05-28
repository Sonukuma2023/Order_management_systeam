<template>
  <nav class="navbar">
    <div class="logo">MySite</div>
    <div class="nav-links">
      <template v-if="!$page.props.auth.user">
        <Link href="/login" class="nav-item">Login</Link>
        <Link href="/register" class="nav-item">Register</Link>
      </template>

      <template v-else>
        <span class="user-name">Hi, {{ $page.props.auth.user.name }}</span>
        <Link 
            :href="$page.props.auth.user.role === 'vendor' 
                ? '/vendor/dashboard' 
                : '/dashboard'"
            class="nav-item"
        >
            Dashboard
        </Link>
        <Link href="/logout" method="post" as="button" class="logout-btn">Logout</Link>
      </template>
    </div>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

</script>

<style scoped>
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 50px;
  background: white;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  position: fixed;
  top: 0;
  width: 100%;
  box-sizing: border-box;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 20px;
}

.nav-item {
  text-decoration: none;
  color: #4f46e5;
  font-weight: 600;
}

.logout-btn {
  background: #ef4444;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
}

.user-name {
  color: #555;
  font-size: 0.9rem;
}

.welcome-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  font-family: sans-serif;
}
</style>