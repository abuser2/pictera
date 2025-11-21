<template>
  <label class="drop">
    <input type="file" accept="image/*" class="hidden" @change="onPick"/>
    <div>Upload Photo</div>
  </label>
</template>

<script setup>
const emit = defineEmits(['selected'])
function onPick(e){
  const f = e.target.files?.[0]
  if (f) emit('selected', f)
}
</script>
