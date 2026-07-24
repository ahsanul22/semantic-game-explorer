<?php
/**
 * Lapisan indikator memuat — disisipkan sekali sebelum </body> di
 * setiap halaman:
 *
 *   <?php require __DIR__ . '/includes/memuat.php'; ?>        (halaman akar)
 *   <?php require __DIR__ . '/../includes/memuat.php'; ?>     (page-model)
 *
 * Semua halaman dirender penuh di server; selama menunggu jawaban
 * DBpedia/Fuseki/Wikipedia, browser tampak diam. Lapisan ini menyala
 * otomatis saat: form dikirim (pencarian / kartu game) dan tautan
 * berpindah halaman diklik. Warna memakai token halaman bila ada,
 * dengan nilai cadangan yang sama untuk semua halaman.
 */
?>
<style>
  .memuat {
    /* z-index tinggi agar tetap tampak di atas modal Bootstrap
       (backdrop ~1040, dialog ~1055) — tautan genre diklik dari
       dalam modal, jadi indikator harus menutupinya. */
    position: fixed; inset: 0; z-index: 2000;
    display: none; align-items: center; justify-content: center;
    background: rgba(10, 4, 24, .74);
    backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
  }
  .memuat.aktif { display: flex; }
  .memuat__bar {
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    overflow: hidden; background: rgba(139, 99, 232, .18);
  }
  .memuat__bar::before {
    content: ""; position: absolute; top: 0; bottom: 0;
    left: -40%; width: 40%; border-radius: 999px;
    background: linear-gradient(90deg, transparent,
      var(--lime, #4af219), var(--violet-soft, #8b63e8));
    animation: memuat-sapu 1.1s ease-in-out infinite;
  }
  @keyframes memuat-sapu { to { left: 100%; } }
  .memuat__kotak {
    padding: 20px 42px;
    border: 1px solid var(--line, rgba(139, 99, 232, .28));
    border-radius: 16px;
    background: linear-gradient(160deg, rgba(23, 12, 44, .92), rgba(10, 4, 24, .92));
    box-shadow: 0 24px 70px rgba(0, 0, 0, .6), 0 0 26px rgba(74, 242, 25, .12);
  }
  .memuat__teks {
    font-family: var(--display, 'Bungee', 'Noto Sans JP', sans-serif);
    font-size: 13px; letter-spacing: 3px;
    color: var(--ink, #f2fff0);
    text-shadow: 0 0 16px rgba(74, 242, 25, .45);
  }
  .memuat__teks::after { content: ""; animation: memuat-titik 1.4s steps(4, end) infinite; }
  @keyframes memuat-titik {
    0% { content: ""; } 25% { content: "."; }
    50% { content: ".."; } 75% { content: "..."; }
  }
  @media (prefers-reduced-motion: reduce) {
    .memuat__bar::before { animation-duration: 2.4s; }
    .memuat__teks::after { animation: none; content: "..."; }
  }
</style>

<div class="memuat" id="memuat" aria-hidden="true">
  <div class="memuat__bar"></div>
  <div class="memuat__kotak">
    <span class="memuat__teks" id="memuatTeks">MEMUAT</span>
  </div>
</div>

<script>
  (function () {
    var lapisan = document.getElementById('memuat');
    var teks    = document.getElementById('memuatTeks');
    function tampil(pesan) {
      teks.textContent = pesan;
      lapisan.classList.add('aktif');
      lapisan.setAttribute('aria-hidden', 'false');
    }
    // Semua form berarti pindah halaman: form pencarian (kelas "seek")
    // atau kartu game (POST ke halaman detail).
    document.addEventListener('submit', function (e) {
      tampil(e.target.classList.contains('seek') ? 'MENCARI' : 'MEMBUKA');
    }, true);
    // Tautan yang berpindah halaman di tab yang sama. Dilewati bila:
    // tab baru, unduhan, pemicu modal Bootstrap, tautan #jangkar,
    // atau klik dengan tombol pengubah (buka di tab baru).
    document.addEventListener('click', function (e) {
      var a = e.target && e.target.closest ? e.target.closest('a') : null;
      if (!a) return;
      if (a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-bs-toggle')) return;
      var href = a.getAttribute('href') || '';
      if (href === '' || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
      if (e.defaultPrevented || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
      tampil('MEMUAT');
    }, true);
    // Kembali lewat tombol Back bisa menyajikan halaman dari cache
    // browser dengan lapisan masih menyala — padamkan lagi.
    window.addEventListener('pageshow', function (e) {
      if (e.persisted) {
        lapisan.classList.remove('aktif');
        lapisan.setAttribute('aria-hidden', 'true');
      }
    });
  })();
</script>
