<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Template Dokumen - Museum Geologi</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Nunito', sans-serif; background: #f4f7fe; color: #334155; margin: 0; padding: 0; }
        .header { background: #3e54a0; padding: 40px 20px; text-align: center; color: white; }
        .header h1 { margin: 0 0 10px 0; font-weight: 800; font-size: 28px; }
        .header p { margin: 0; color: #bfdbfe; font-size: 15px; }
        .container { max-width: 900px; margin: -30px auto 40px; padding: 0 20px; }
        .back-btn { display: inline-flex; align-items: center; gap: 6px; color: white; text-decoration: none; font-weight: 700; margin-bottom: 20px; font-size: 14px;}
        .back-btn:hover { color: #bfdbfe; text-decoration: none; }
        
        .grid-templates { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .template-card { background: white; border-radius: 20px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); display: flex; align-items: flex-start; gap: 16px; transition: 0.3s; border: 1px solid #e2e8f0;}
        .template-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(62, 84, 160, 0.1); border-color: #cbd5e1;}
        .icon-box { width: 50px; height: 50px; border-radius: 14px; background: #f0fdf4; color: #10b981; display: flex; justify-content: center; align-items: center; font-size: 24px; flex-shrink: 0;}
        .info { flex: 1; }
        .info h3 { margin: 0 0 6px 0; font-size: 15px; font-weight: 800; color: #1e293b; }
        .info p { margin: 0 0 15px 0; font-size: 12px; color: #64748b; line-height: 1.5; }
        .btn-download { display: inline-block; background: #f1f5f9; color: #3e54a0; font-weight: 700; font-size: 12px; padding: 8px 16px; border-radius: 50px; text-decoration: none; transition: 0.2s;}
        .btn-download:hover { background: #3e54a0; color: white; }
    </style>
</head>
<body>

    <div class="header">
        <div class="container" style="margin: 0 auto; text-align: left;">
            <a href="{{ route('login') }}" class="back-btn"><i class="bi bi-arrow-left-circle-fill"></i> Kembali ke Login</a>
        </div>
        <h1>Pusat Template Dokumen</h1>
        <p>Unduh format standar laporan dan bukti kerja Museum Geologi di sini.</p>
    </div>

    <div class="container">
        <div class="grid-templates">
            
            <!-- KARTU TEMPLATE 1 -->
            <div class="template-card">
                <div class="icon-box" style="background:#eff6ff; color:#3b82f6;"><i class="bi bi-file-earmark-word-fill"></i></div>
                <div class="info">
                    <h3>Template Laporan Pengadaan</h3>
                    <p>Format resmi Berita Acara dan laporan penerimaan koleksi baru.</p>
                    <!-- Buat folder 'templates' di dalam folder 'public' project Anda dan masukkan filenya -->
                    <a href="{{ asset('templates/Format_Laporan_Pengadaan.docx') }}" download class="btn-download"><i class="bi bi-cloud-arrow-down-fill"></i> Unduh .DOCX</a>
                </div>
            </div>

            <!-- KARTU TEMPLATE 2 -->
            <div class="template-card">
                <div class="icon-box" style="background:#fefce8; color:#f59e0b;"><i class="bi bi-file-earmark-word-fill"></i></div>
                <div class="info">
                    <h3>Formulir Inventarisasi Koleksi</h3>
                    <p>Borang pencatatan identifikasi dan kondisional spesimen koleksi.</p>
                    <a href="{{ asset('templates/Form_Inventarisasi.docx') }}" download class="btn-download"><i class="bi bi-cloud-arrow-down-fill"></i> Unduh .DOCX</a>
                </div>
            </div>

            <!-- KARTU TEMPLATE 3 -->
            <div class="template-card">
                <div class="icon-box" style="background:#fef2f2; color:#ef4444;"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                <div class="info">
                    <h3>SOP Kurator Museum</h3>
                    <p>Dokumen acuan standar operasional prosedur kuratorial.</p>
                    <a href="{{ asset('templates/SOP_Kurator.pdf') }}" download class="btn-download"><i class="bi bi-cloud-arrow-down-fill"></i> Unduh .PDF</a>
                </div>
            </div>

        </div>
    </div>

</body>
</html>