<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({
  onSubmit: { type: Function, required: true },
})

const form = reactive({ password: '', password_confirmation: '' })
const error = ref('')
const isSaving = ref(false)

async function submit() {
  error.value = ''
  isSaving.value = true
  try {
    await props.onSubmit({ ...form })
  } catch (requestError) {
    error.value = requestError.message
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <main class="login-page">
    <section class="login-panel">
      <div>
        <p class="eyebrow">Seguridad</p>
        <h1>Actualiza tu contrasena</h1>
        <p>Debes establecer una contrasena personal antes de continuar.</p>
      </div>
      <form class="login-form" @submit.prevent="submit">
        <label>Nueva contrasena <input v-model="form.password" type="password" minlength="10" required /></label>
        <label>Confirmar contrasena <input v-model="form.password_confirmation" type="password" minlength="10" required /></label>
        <p v-if="error" class="error-text">{{ error }}</p>
        <button type="submit" :disabled="isSaving">{{ isSaving ? 'Guardando...' : 'Actualizar contrasena' }}</button>
      </form>
    </section>
  </main>
</template>
