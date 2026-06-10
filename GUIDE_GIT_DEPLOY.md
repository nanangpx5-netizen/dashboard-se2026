# Panduan Push & Deploy

## 1. Push dari Lokal

```powershell
cd C:\laragon\www\dashboard-se2026
git add .
git commit -m "pesan perubahan"
git push origin master
```

## 2. Deploy ke Hosting (SSH)

```bash
ssh bpsjembe@bpsjember.my.id
cd /home/bpsjembe/repositories/dashboard-se2026
git pull origin master
./scripts/deploy_dashboard_se2026.sh --with-data
```

## 3. Jika deploy error (biasanya .env dan vendor)

```bash
# Di SSH hosting (sekali saja)
cd /home/bpsjembe/repositories/dashboard-se2026
cp /home/bpsjembe/dashboard-se2026.bpsjember.my.id/.env .
cp -a /home/bpsjembe/dashboard-se2026.bpsjember.my.id/vendor .
composer dump-autoload 2>/dev/null

# Lalu deploy ulang
./scripts/deploy_dashboard_se2026.sh --with-data
```

## Catatan

- File `.env` dan `vendor/` hanya ada di web dir, tidak di git
- Semua patch SQL idempotent — aman dijalankan berulang
- Setelah deploy, akses: `https://dashboard-se2026.bpsjember.my.id`
