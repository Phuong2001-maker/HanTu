<script lang="ts">
  import { goto } from '$app/navigation';
  import { api } from '$lib/api/client';
  import { session } from '$lib/stores/session.svelte';
  import { heartbeat } from '$lib/stores/heartbeat';

  // Tạm thời ở Giai đoạn 0: Giai đoạn 1 thay bằng màn Bàn học đầy đủ (07 §4.1).
  heartbeat.setActivity({ part: 'lt', screen: 'home' });

  async function logout() {
    await api.post('/auth/logout').catch(() => {});
    session.clear();
    await goto('/login');
  }
</script>

<svelte:head><title>Bàn học · Zìkǎ</title></svelte:head>

<main class="page stack">
  <h1 style="font-size: 34px">Hôm nay học cấp nào, {session.user?.displayName}?</h1>
  <p class="muted">Bàn học đang được dựng (Giai đoạn 1).</p>
  <div class="row">
    <button type="button" class="btn" onclick={logout}>Đăng xuất</button>
  </div>
</main>
