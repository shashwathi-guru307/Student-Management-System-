<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
$is_admin = in_array($_SESSION['role'], ['admin', 'teacher']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials Library</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; }
        .media-card { background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden; position: relative; }
        .media-preview { height: 150px; background: #eee; display: flex; justify-content: center; align-items: center; color: var(--text-muted); font-size: 3rem; }
        .media-preview img, .media-preview video { width: 100%; height: 100%; object-fit: cover; }
        .media-info { padding: 1rem; }
        .media-title { font-weight: 600; margin-bottom: 0.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .media-meta { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem; }
        .media-actions { display: flex; gap: 0.5rem; }
        .media-actions a, .media-actions button { flex: 1; padding: 0.5rem; text-align: center; text-decoration: none; border-radius: var(--radius-md); font-size: 0.875rem; cursor: pointer; border: none; }
        .btn-dl { background: var(--primary-color); color: white; }
        .btn-del { background: #fee2e2; color: #b91c1c; }
        
        .upload-zone { border: 2px dashed var(--border-color); border-radius: var(--radius-lg); padding: 3rem 2rem; text-align: center; cursor: pointer; transition: background 0.2s, border-color 0.2s; background: #f8fafc; margin-bottom: 2rem; }
        .upload-zone:hover { background: #f1f5f9; border-color: var(--primary-color); }
    </style>
</head>
<body style="background: #F7FAFC;">
    <div style="background: white; padding: 1rem 2rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center;">
        <h1 style="font-size: 1.25rem; color: var(--primary-color);">📚 Course Materials Library</h1>
        <a href="<?php echo $is_admin ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>" class="btn btn-outline" id="back-btn">Back to Dashboard</a>
    </div>

    <div style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">
        
        <?php if ($is_admin): ?>
        <div class="upload-zone" onclick="document.getElementById('file-input').click()">
            <i class='bx bx-cloud-upload' style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
            <h3>Upload New Material</h3>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.5rem;">Click to select file. Supports PDF, Images, Word, MP4 (Max 50MB)</p>
            <input type="file" id="file-input" class="hidden" onchange="handleFileSelect(event)">
        </div>

        <div id="upload-form-container" class="hidden" style="background: white; padding: 1.5rem; border-radius: var(--radius-md); box-shadow: var(--shadow-md); margin-bottom: 2rem;">
            <h3>Confirm Upload</h3>
            <div id="upload-alert" class="alert mt-4"></div>
            <form id="upload-form" class="mt-4">
                <div class="form-group">
                    <label>Selected File</label>
                    <input type="text" id="selected-filename" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label>Material Title</label>
                    <input type="text" id="file-title" class="form-control" placeholder="E.g., Week 1 Physics Slides" required>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" onclick="cancelUpload()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="upload-btn">Upload to Library</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <h2 style="margin-bottom: 1.5rem;">Library Contents</h2>
        <div class="media-grid" id="media-grid">
            <!-- Media populated via JS -->
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted);">
                <i class='bx bx-loader bx-spin' style="font-size: 2rem;"></i> Loading library...
            </div>
        </div>
    </div>

    <script>
        let selectedFile = null;
        const isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024, dm = decimals < 0 ? 0 : decimals, sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        function getPreviewIcon(type, path) {
            if (type.startsWith('image/')) return `<img src="${path}" alt="Preview">`;
            if (type.startsWith('video/')) return `<video src="${path}" controls></video>`;
            if (type.includes('pdf')) return `<i class='bx bxs-file-pdf' style="color: #e3242b;"></i>`;
            if (type.includes('word')) return `<i class='bx bxs-file-doc' style="color: #2b579a;"></i>`;
            return `<i class='bx bxs-file'></i>`;
        }

        async function loadMedia() {
            try {
                const res = await fetch('api/media.php?action=list');
                const data = await res.json();
                
                const grid = document.getElementById('media-grid');
                grid.innerHTML = '';
                
                if (data.success) {
                    if(data.data.length === 0){
                        grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted);">No materials found in the library.</div>`;
                        return;
                    }

                    data.data.forEach(m => {
                        const date = new Date(m.uploaded_at).toLocaleDateString();
                        const size = formatBytes(m.file_size);
                        let delBtn = isAdmin ? `<button class="btn-del" onclick="deleteMedia(${m.id})"><i class='bx bx-trash'></i> Delete</button>` : '';

                        const html = `
                            <div class="media-card">
                                <div class="media-preview">
                                    ${getPreviewIcon(m.file_type, m.file_path)}
                                </div>
                                <div class="media-info">
                                    <div class="media-title" title="${m.title}">${m.title}</div>
                                    <div class="media-meta">
                                        Uploaded ${date} • ${size}<br>
                                        By: ${m.uploader_name}
                                    </div>
                                    <div class="media-actions">
                                        <a href="${m.file_path}" target="_blank" download class="btn-dl"><i class='bx bx-download'></i> Download</a>
                                        ${delBtn}
                                    </div>
                                </div>
                            </div>
                        `;
                        grid.insertAdjacentHTML('beforeend', html);
                    });
                }
            } catch (err) { console.error('Error loading media', err); }
        }

        <?php if ($is_admin): ?>
        function handleFileSelect(e) {
            if(e.target.files.length > 0) {
                selectedFile = e.target.files[0];
                document.getElementById('selected-filename').value = selectedFile.name;
                document.getElementById('upload-form-container').classList.remove('hidden');
                document.querySelector('.upload-zone').classList.add('hidden');
                
                // Auto fill title without ext
                const nameNoExt = selectedFile.name.replace(/\.[^/.]+$/, "");
                document.getElementById('file-title').value = nameNoExt;
            }
        }

        function cancelUpload() {
            selectedFile = null;
            document.getElementById('file-input').value = '';
            document.getElementById('upload-form-container').classList.add('hidden');
            document.querySelector('.upload-zone').classList.remove('hidden');
            document.getElementById('upload-alert').style.display = 'none';
        }

        document.getElementById('upload-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            if(!selectedFile) return;

            const btn = document.getElementById('upload-btn');
            btn.disabled = true;
            btn.textContent = "Uploading...";

            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('title', document.getElementById('file-title').value);

            try {
                const alertBox = document.getElementById('upload-alert');
                
                const res = await fetch('api/media.php?action=upload', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    alertBox.textContent = "Uploaded successfully!";
                    alertBox.className = "alert alert-success";
                    alertBox.style.display = 'block';
                    
                    setTimeout(() => {
                        cancelUpload();
                        loadMedia();
                    }, 1500);
                } else {
                    alertBox.textContent = data.message || "Upload failed.";
                    alertBox.className = "alert alert-error";
                    alertBox.style.display = 'block';
                }
            } catch (err) {
                console.error(err);
                alert("An error occurred during upload.");
            } finally {
                btn.disabled = false;
                btn.textContent = "Upload to Library";
            }
        });

        async function deleteMedia(id) {
            if(confirm("Are you sure you want to delete this file? It cannot be undone.")) {
                try {
                    const res = await fetch('api/media.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });
                    const data = await res.json();
                    if (data.success) {
                        loadMedia();
                    } else {
                        alert(data.message || "Failed to delete");
                    }
                } catch (err) { alert("Error deleting file"); }
            }
        }
        <?php endif; ?>

        document.addEventListener('DOMContentLoaded', loadMedia);
    </script>
</body>
</html>
