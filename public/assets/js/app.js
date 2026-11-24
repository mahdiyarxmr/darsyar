(function () {
    'use strict';

    const root = document.documentElement;
    const isFa = root.getAttribute('lang') === 'fa';
    const themeToggle = document.getElementById('themeToggleBtn');
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const menuToggle = document.getElementById('menuToggleBtn');

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function localizeDigits(val) {
        const str = String(val);
        if (!isFa) return str;
        const map = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str.replace(/\d/g, d => map[Number(d)]);
    }

    function showToast(message, type) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = 'toast';
        if (type === 'error') toast.style.borderInlineStartColor = 'var(--danger)';
        if (type === 'success') toast.style.borderInlineStartColor = 'var(--success)';
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => { toast.remove(); }, 3600);
    }

    // Sidebar Mobile Drawer
    if (menuToggle && sidebar && backdrop) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('open');
        });
        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('open');
            backdrop.classList.remove('open');
        });
    }

    // Collapsible Action Drawers (Clean Progressive Disclosure)
    document.querySelectorAll('[data-drawer-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-drawer-toggle');
            const drawer = document.getElementById(targetId);
            if (!drawer) return;
            const isOpen = drawer.classList.toggle('open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });

    // Reusable UI Tabs ([data-tab-target])
    document.querySelectorAll('[data-tab-target]').forEach(function (tabBtn) {
        tabBtn.addEventListener('click', function () {
            const group = tabBtn.getAttribute('data-tab-group') || 'default';
            const targetId = tabBtn.getAttribute('data-tab-target');
            document.querySelectorAll('[data-tab-target][data-tab-group="' + group + '"]').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('[data-tab-panel][data-tab-group="' + group + '"]').forEach(p => p.classList.remove('active'));
            tabBtn.classList.add('active');
            const panel = document.getElementById(targetId);
            if (panel) panel.classList.add('active');
        });
    });

    // Interactive Animated Auth Switcher (Login <-> Register)
    const authSwitcher = document.getElementById('authSwitcher');
    if (authSwitcher) {
        const authBtns = authSwitcher.querySelectorAll('[data-auth-tab]');
        const authPanels = document.querySelectorAll('[data-auth-panel]');

        function activateAuthMode(mode, syncUrl) {
            authSwitcher.setAttribute('data-active', mode);
            authBtns.forEach(b => b.classList.toggle('active', b.getAttribute('data-auth-tab') === mode));
            authPanels.forEach(p => p.classList.toggle('active', p.getAttribute('data-auth-panel') === mode));
            if (syncUrl && window.history && window.history.replaceState) {
                const baseUrl = authSwitcher.getAttribute('data-url-' + mode);
                if (baseUrl) {
                    window.history.replaceState(null, '', baseUrl);
                }
            }
        }

        authBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activateAuthMode(btn.getAttribute('data-auth-tab'), true);
            });
        });

        // Student vs Parent role toggle on Register form
        const classFieldWrap = document.getElementById('registerClassWrap');
        document.querySelectorAll('input[name="role"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (classFieldWrap) {
                    classFieldWrap.style.display = radio.value === 'student' ? 'block' : 'none';
                }
            });
        });

        // One-click Dev Account Auto-fill (local dev only)
        document.querySelectorAll('[data-demo-user]').forEach(function (chip) {
            chip.addEventListener('click', function () {
                activateAuthMode('login', true);
                const idInput = document.getElementById('identifier');
                const pwInput = document.getElementById('password');
                if (idInput) idInput.value = chip.getAttribute('data-demo-user') || '';
                if (pwInput) pwInput.value = chip.getAttribute('data-demo-pass') || '';
                if (idInput) idInput.focus();
            });
        });
    }

    // Password Visibility Eye Toggles
    document.querySelectorAll('[data-toggle-pw]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-toggle-pw');
            const input = document.getElementById(targetId);
            if (!input) return;
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            const eyeShow = btn.querySelector('[data-eye-show]');
            const eyeHide = btn.querySelector('[data-eye-hide]');
            if (eyeShow && eyeHide) {
                eyeShow.style.display = showing ? 'inline-flex' : 'none';
                eyeHide.style.display = showing ? 'none' : 'inline-flex';
            }
        });
    });

    // Accessible Dropdowns
    document.querySelectorAll('[data-dropdown-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const wrap = btn.closest('.dropdown');
            const isOpen = wrap.classList.contains('open');
            document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
            if (!isOpen) wrap.classList.add('open');
        });
    });
    document.addEventListener('click', function () {
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
    });

    // Modals & Confirmation Dialogs
    function closeAllModals() {
        document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
    }

    document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = document.getElementById(btn.getAttribute('data-modal-open'));
            if (target) target.classList.add('open');
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', closeAllModals);
    });

    document.querySelectorAll('.modal-backdrop').forEach(function (bd) {
        bd.addEventListener('click', function (e) {
            if (e.target === bd) closeAllModals();
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllModals();
            if (sidebar && backdrop) {
                sidebar.classList.remove('open');
                backdrop.classList.remove('open');
            }
            document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
        }
    });

    // Confirmation Dialog before destructive forms
    const confirmModal = document.getElementById('confirmModal');
    const confirmMsgEl = document.getElementById('confirmModalMessage');
    const confirmAcceptBtn = document.getElementById('confirmModalAccept');
    let pendingConfirmForm = null;

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmed === '1') return;
            if (confirmModal && confirmMsgEl && confirmAcceptBtn) {
                e.preventDefault();
                pendingConfirmForm = form;
                confirmMsgEl.textContent = form.getAttribute('data-confirm') || '';
                confirmModal.classList.add('open');
            }
        });
    });

    if (confirmAcceptBtn) {
        confirmAcceptBtn.addEventListener('click', function () {
            if (pendingConfirmForm) {
                pendingConfirmForm.dataset.confirmed = '1';
                closeAllModals();
                pendingConfirmForm.submit();
                pendingConfirmForm = null;
            }
        });
    }

    // Theme Persistence (Light / Dark)
    if (themeToggle) {
        themeToggle.addEventListener('click', async function () {
            const current = root.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem('darsyar_theme', next);
            const url = themeToggle.getAttribute('data-endpoint');
            if (url) {
                try {
                    await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ theme: next })
                    });
                    const lightLabel = themeToggle.getAttribute('data-label-light') || '';
                    const darkLabel = themeToggle.getAttribute('data-label-dark') || '';
                    const labelEl = themeToggle.querySelector('[data-theme-label]');
                    if (labelEl) {
                        labelEl.textContent = next === 'dark' ? lightLabel : darkLabel;
                    }
                } catch (_) {}
            }
        });
    }

    // Jalali live preview for date inputs when interface is Persian
    if (isFa && typeof Intl !== 'undefined') {
        const faFmt = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        document.querySelectorAll('input[type="date"]').forEach(function (input) {
            const updateHint = function () {
                if (!input.value) return;
                const d = new Date(input.value + 'T12:00:00');
                if (isNaN(d.getTime())) return;
                let hint = input.parentElement.querySelector('.date-jalali-hint');
                if (!hint) {
                    hint = document.createElement('div');
                    hint.className = 'date-jalali-hint';
                    input.parentElement.appendChild(hint);
                }
                hint.textContent = faFmt.format(d);
            };
            updateHint();
            input.addEventListener('change', updateHint);
        });
    }

    // Study Plan Task Toggle via AJAX
    document.querySelectorAll('[data-toggle-task]').forEach(function (checkbox) {
        checkbox.addEventListener('change', async function () {
            const url = checkbox.getAttribute('data-toggle-task');
            const completed = checkbox.checked ? 1 : 0;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ is_completed: completed })
                });
                const data = await res.json();
                if (res.ok && data.stats) {
                    const card = checkbox.closest('.card') || document;
                    const pctEl = card.querySelector('[data-plan-pct]') || document.getElementById('planCompletionPct');
                    const barEl = card.querySelector('[data-plan-bar]') || document.getElementById('planCompletionBar');
                    const countsEl = card.querySelector('[data-plan-counts]') || document.getElementById('planCountsLabel');
                    if (pctEl) pctEl.textContent = localizeDigits(data.stats.percentage) + (isFa ? '٪' : '%');
                    if (barEl) barEl.style.width = data.stats.percentage + '%';
                    if (countsEl) countsEl.textContent = localizeDigits(data.stats.completed) + ' / ' + localizeDigits(data.stats.total);
                    showToast(localizeDigits(data.stats.percentage) + (isFa ? '٪ تکمیل شد' : '% completed'), 'success');
                }
            } catch (_) {
                showToast('Error updating task', 'error');
            }
        });
    });

    // Notification Mark-As-Read via AJAX
    document.querySelectorAll('[data-mark-read]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const url = btn.getAttribute('data-mark-read');
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({})
                });
                if (res.ok) {
                    const row = btn.closest('[data-notif-row]');
                    if (row) {
                        row.style.background = 'var(--surface-2)';
                        row.classList.remove('unread');
                    }
                    btn.remove();
                }
            } catch (_) {}
        });
    });

    // Exam Countdown Timer (Server-authoritative deadline)
    document.querySelectorAll('[data-exam-expires]').forEach(function (timerEl) {
        const expiresMs = parseInt(timerEl.getAttribute('data-exam-expires'), 10) * 1000;
        const form = document.getElementById('examTakeForm');
        function tick() {
            const diff = Math.max(0, Math.floor((expiresMs - Date.now()) / 1000));
            const mins = String(Math.floor(diff / 60)).padStart(2, '0');
            const secs = String(diff % 60).padStart(2, '0');
            timerEl.textContent = localizeDigits(mins + ':' + secs);
            if (diff <= 0) {
                clearInterval(interval);
                if (form) form.submit();
            }
        }
        tick();
        const interval = setInterval(tick, 1000);
    });

    // Loading state on form submit buttons
    document.querySelectorAll('form:not([data-confirm])').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('button[type="submit"]:not([data-no-loading])');
            if (btn && !btn.classList.contains('is-loading')) {
                btn.classList.add('is-loading');
            }
        });
    });

    // Lightweight SVG Bar Chart Renderer (>= 12px legible text)
    document.querySelectorAll('[data-bar-chart]').forEach(function (container) {
        let items = [];
        try {
            items = JSON.parse(container.getAttribute('data-bar-chart') || '[]');
        } catch (_) {}
        if (!items.length) return;

        const w = 560;
        const h = 215;
        const pad = 28;
        const maxVal = Math.max(100, ...items.map(i => Number(i.value || 0)));
        const barW = Math.max(20, Math.min(44, (w - pad * 2) / items.length - 12));
        const step = (w - pad * 2) / items.length;

        let svg = `<svg viewBox="0 0 ${w} ${h}" style="width:100%;height:auto;display:block;" role="img" aria-label="Chart">`;
        svg += `<line x1="${pad}" y1="${h - 32}" x2="${w - pad}" y2="${h - 32}" stroke="var(--border)" stroke-width="1.5"/>`;
        items.forEach((item, idx) => {
            const val = Number(item.value || 0);
            const barH = Math.max(6, (val / maxVal) * (h - 72));
            const x = pad + idx * step + (step - barW) / 2;
            const y = h - 32 - barH;
            const label = String(item.label || '').slice(0, 14);
            svg += `<rect x="${x}" y="${y}" width="${barW}" height="${barH}" rx="6" fill="var(--primary)" stroke="var(--border)" stroke-width="1.5"></rect>`;
            svg += `<text x="${x + barW / 2}" y="${y - 8}" text-anchor="middle" font-size="12" font-weight="700" fill="var(--text)">${localizeDigits(val)}</text>`;
            svg += `<text x="${x + barW / 2}" y="${h - 12}" text-anchor="middle" font-size="12" fill="var(--text-muted)">${label}</text>`;
        });
        svg += `</svg>`;
        container.innerHTML = svg;
    });

    // Lightweight SVG Donut Chart Renderer
    document.querySelectorAll('[data-donut-chart]').forEach(function (container) {
        let items = [];
        try {
            items = JSON.parse(container.getAttribute('data-donut-chart') || '[]');
        } catch (_) {}
        const total = items.reduce((acc, it) => acc + Number(it.value || 0), 0);
        if (!items.length || total <= 0) return;

        const palette = ['var(--primary)', 'var(--accent)', 'var(--success)', 'var(--warm)', 'var(--danger)', 'var(--secondary)'];
        const size = 164;
        const cx = size / 2;
        const cy = size / 2;
        const r = 56;
        const circ = 2 * Math.PI * r;
        let offset = 0;

        let circles = '';
        let legend = '';
        items.forEach((it, i) => {
            const val = Number(it.value || 0);
            const pct = val / total;
            const dash = pct * circ;
            const color = it.color || palette[i % palette.length];
            circles += `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${color}" stroke-width="18" stroke-dasharray="${dash} ${circ - dash}" stroke-dashoffset="${-offset}" transform="rotate(-90 ${cx} ${cy})"/>`;
            offset += dash;
            legend += `<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:0.85rem;">
                <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:11px;height:11px;border-radius:3px;border:1px solid var(--border);background:${color};display:inline-block;"></span>${it.label}</span>
                <strong>${localizeDigits(val)}</strong>
            </div>`;
        });

        container.innerHTML = `<div style="display:flex;flex-wrap:wrap;align-items:center;gap:20px;">
            <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" role="img" aria-label="Distribution">
                ${circles}
                <text x="${cx}" y="${cy + 6}" text-anchor="middle" font-size="18" font-weight="800" fill="var(--text)">${localizeDigits(total)}</text>
            </svg>
            <div style="flex:1;min-width:160px;display:grid;gap:7px;">${legend}</div>
        </div>`;
    });
})();
