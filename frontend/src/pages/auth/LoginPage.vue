<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({
  onLogin: {
    type: Function,
    required: true,
  },
})

const form = reactive({
  email: '',
  password: '',
})

const error = ref('')
const isSubmitting = ref(false)

async function submit() {
  error.value = ''
  isSubmitting.value = true

  try {
    await props.onLogin({ ...form })
  } catch (requestError) {
    error.value = requestError.message
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <main class="login-page">
    <section class="login-panel">
      <div>
        <p class="eyebrow">Acceso seguro</p>
        <h1>SODASA</h1>
        <p>Ingresa con tu usuario autorizado para continuar.</p>
      </div>

      <form class="login-form" @submit.prevent="submit">
        <label>
          Correo
          <input v-model="form.email" type="email" autocomplete="username" required />
        </label>
        <label>
          Contrasena
          <input v-model="form.password" type="password" autocomplete="current-password" required />
        </label>
        <p v-if="error" class="error-text">{{ error }}</p>
        <button type="submit" :disabled="isSubmitting">
          {{ isSubmitting ? 'Validando...' : 'Iniciar sesion' }}
        </button>
      </form>
    </section>
  </main>
</template>
