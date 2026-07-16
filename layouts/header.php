<style>
    /* HEADER UTAMA: FLOATING CARD MODERN */
    .top-header {
        display: flex !important; 
        justify-content: space-between !important; 
        align-items: center !important;
        padding: 12px 24px !important;
        min-height: 70px !important;
        background-color: #ffffff !important; 
        margin-bottom: 25px !important; 
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06) !important;
    }

    .header-left, .header-right {
        display: flex !important;
        align-items: center !important;
        gap: 15px !important; 
        height: 100% !important;
    }

    /* TOMBOL KEMBALI MINIMALIS */
    .btn-back-minimal {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #f8fafc;
        color: #475569;
        text-decoration: none;
        font-size: 22px;
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0;
    }

    .btn-back-minimal:hover {
        background: #eff6ff;
        color: #3b82f6;
        border-color: #bfdbfe;
        transform: translateX(-3px);
    }

    .header-divider {
        width: 1px;
        height: 30px;
        background-color: #e2e8f0;
        margin: 0 5px;
    }

    /* BAGIAN KIRI (LOGO & JUDUL) */
    .header-logo img {
        max-height: 38px !important;
        width: auto;
    }

    .header-title {
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
    }

    .header-title h1 {
        font-size: 18px !important;
        font-weight: 700 !important; 
        margin: 0 0 2px 0 !important;
        line-height: 1.2 !important;
        letter-spacing: -0.3px !important;
        color: #0f172a !important; 
    }

    .header-title p {
        font-size: 11px !important;
        font-weight: 500 !important;
        color: #64748b !important;
        margin: 0 !important;
    }

    /* BAGIAN KANAN (PROFIL & NOTIFIKASI) */
    .header-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
        background: transparent;
        border: none;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* KOTAK PROFIL BISA DI KLIK */
    .profile-box {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        margin: 0 !important;
        padding: 6px 12px !important;
        border-radius: 30px !important;
        transition: 0.2s ease !important;
        text-decoration: none !important;
        cursor: pointer;
    }

    .profile-box:hover {
        background: #f8fafc !important;
    }

    .profile-info {
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        align-items: flex-end !important; 
        margin: 0 !important;
        padding: 0 !important;
    }

    .profile-name {
        font-size: 13px !important;
        font-weight: 700 !important;
        color: #0f172a !important;
        line-height: 1.2 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .profile-role {
        font-size: 11px !important;
        font-weight: 500 !important;
        color: #64748b !important;
        margin: 2px 0 0 0 !important;
        padding: 0 !important;
    }

    .profile-avatar {
        width: 38px !important;
        height: 38px !important;
        font-size: 13px !important;
        background-color: #1B2D46 !important; 
        color: #ffffff !important; 
        border-radius: 50% !important; 
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-weight: 600 !important;
        margin: 0 !important;
    }
</style>

<div class="top-header">

    <div class="header-left">
        
        <?php if (isset($btn_kembali) && !empty($btn_kembali)): ?>
            <a href="<?= htmlspecialchars($btn_kembali) ?>" class="btn-back-minimal" title="Kembali ke halaman sebelumnya">
                <i class="bi bi-arrow-left-short"></i>
            </a>
            <div class="header-divider"></div>
        <?php endif; ?>

        <div class="header-logo">
            <img src="../assets/img/logo-museum.png" alt="Museum Geologi">
        </div>

        <div class="header-title">
            <h1><?= $page_title ?? 'Museum Geologi' ?></h1>
            <p><?= $page_subtitle ?? 'Penilaian Kompetensi SDM' ?></p>
        </div>

    </div>

    <div class="header-right">

        <button class="header-icon">
            <i class="bi bi-bell"></i>
        </button>

        <?php 
            $link_profil = ($_SESSION['role'] == 'pimpinan') ? 'profil.php' : 'profil.php'; 
        ?>

        <!-- INI SUDAH DIUBAH JADI TAG <A> AGAR BISA DIKLIK -->
        <a href="<?= $link_profil ?>" class="profile-box" title="Lihat Profil Saya">

            <div class="profile-info">
                <span class="profile-name">
                    <?= $_SESSION['nama_pegawai'] ?? 'User'; ?>
                </span>
                <span class="profile-role">
                    <?= ucfirst($_SESSION['role'] ?? 'Guest'); ?>
                </span>
            </div>

            <?php
                // Logika Inisial Nama
                $nama_session = $_SESSION['nama_pegawai'] ?? 'User';
                $nama = explode(' ', trim($nama_session));

                if (count($nama) >= 2) {
                    $inisial = strtoupper(substr($nama[0], 0, 1) . substr($nama[1], 0, 1));
                } else {
                    $inisial = strtoupper(substr($nama[0], 0, 2));
                }
            ?>

            <div class="profile-avatar">
                <?= $inisial; ?>
            </div>

        </a>

    </div>

</div>