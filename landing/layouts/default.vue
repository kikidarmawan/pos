<template>
  <div class="min-h-screen flex flex-col">
    <LandingHeader />
    <main class="flex-1">
      <slot />
    </main>
    <LandingFooter />
  </div>
</template>
