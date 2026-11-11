import os
import zipfile

root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
zip_path = os.path.join(root, 'deploy_cpanel_mts_alhuda.zip')

if os.path.exists(zip_path):
    os.remove(zip_path)

exclude_dirs = {'.git', '.vscode', 'backup', 'node_modules', 'storage', 'stitch_sistem_akreditasi_web_terpadu'}
exclude_files = {'deploy_cpanel_mts_alhuda.zip', 'stitch_sistem_akreditasi_web_terpadu.zip'}

with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for dirpath, dirnames, filenames in os.walk(root):
        # Filter dirnames
        rel_dir = os.path.relpath(dirpath, root)
        parts = rel_dir.split(os.sep) if rel_dir != '.' else []
        
        if any(p in exclude_dirs for p in parts):
            continue
            
        for f in filenames:
            if f in exclude_files:
                continue
            if rel_dir == 'config' and (f == 'database.local.php' or f == 'ftp.local.php'):
                continue
            full_path = os.path.join(dirpath, f)
            arcname = os.path.relpath(full_path, root).replace('\\', '/')
            zipf.write(full_path, arcname)

print(f"Clean zip created: {zip_path} ({os.path.getsize(zip_path)} bytes)")
