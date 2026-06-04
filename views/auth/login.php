<div class="row justify-content-center min-vh-100 align-items-center py-5">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow border-0">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="fas fa-chart-line text-se2026" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="fw-bold mb-1"><?= APP_NAME ?></h5>
                    <p class="text-muted small mb-0">BPS Kabupaten Jember</p>
                </div>

                <?php if (!empty($flash)): ?>
                    <?php foreach (['error', 'success', 'warning', 'info'] as $type): ?>
                        <?php if (isset($flash[$type])): ?>
                        <div class="alert alert-<?= $type === 'error' ? 'danger' : ($type === 'warning' ? 'warning' : 'info') ?> alert-dismissible fade show py-2 small" role="alert">
                            <i class="fas fa-<?= $type === 'error' ? 'exclamation-circle' : 'info-circle' ?> me-1"></i>
                            <?= htmlspecialchars($flash[$type]) ?>
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <form method="POST" action="?page=login" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" id="username" name="username"
                                   class="form-control" placeholder="Masukkan username"
                                   required autofocus autocomplete="off">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" id="password" name="password"
                                   class="form-control" placeholder="Masukkan password"
                                   required autocomplete="off">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-se2026 w-100 fw-semibold py-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>Sistem Internal BPS Jember
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
