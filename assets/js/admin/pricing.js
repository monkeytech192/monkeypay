/**
 * MonkeyPay Admin — Pricing Page
 *
 * Fetch plans, render pricing cards, period toggle.
 *
 * @package MonkeyPay
 * @since   3.0.0
 */

(function ($) {
    'use strict';

    const MP = window.MonkeyPay;

    let plans = [];
    let period = 'monthly';

    // Plan metadata
    const planMeta = {
        free: {
            desc: 'Dùng thử cho cá nhân, shop nhỏ',
            features: ['50 request/tháng', '1 cổng thanh toán', '1 tài khoản/cổng', 'Hỗ trợ qua Email'],
            cta: 'Gói hiện tại',
            color: 'var(--mp-text-muted)',
        },
        basic: {
            desc: 'Phù hợp cho cửa hàng vừa và nhỏ',
            badge: 'Phổ biến nhất',
            features: ['500 request/tháng', '2 cổng thanh toán', '1 tài khoản/cổng', 'Đối soát tự động', 'Hỗ trợ ưu tiên Zalo'],
            cta: 'Chọn gói này',
            recommended: true,
            color: 'var(--mp-primary)',
        },
        advanced: {
            desc: 'Dành cho doanh nghiệp đa chi nhánh',
            features: ['2.000 request/tháng', '5 cổng thanh toán', '3 tài khoản/cổng', 'Đối soát tự động', 'Đa chi nhánh', 'Hỗ trợ 24/7'],
            cta: 'Chọn gói này',
            color: '#8b5cf6',
        },
        pro: {
            desc: 'Không giới hạn, toàn quyền',
            features: ['Không giới hạn request', 'Không giới hạn cổng TT', 'Không giới hạn tài khoản', 'Đối soát + đa chi nhánh', 'White-label', 'Hỗ trợ VIP chuyên biệt'],
            cta: 'Liên hệ',
            color: '#f59e0b',
        },
    };

    // ─── Fetch Plans ────────────────────────────────

    async function fetchPlans(grid) {
        try {
            const res = await fetch(`${MP.restUrl}plans`, {
                headers: { 'X-WP-Nonce': MP.nonce },
            });
            const data = await res.json();
            if (data.success && data.data && data.data.plans) {
                plans = data.data.plans;
            }
        } catch (err) {
            console.error('Failed to load plans', err);
        }
        renderPlans(grid);
    }

    // ─── Format Price ───────────────────────────────

    function formatPrice(price) {
        if (price === 0) return 'Miễn phí';
        return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    }

    // ─── Render Plans ───────────────────────────────

    function renderPlans(grid) {
        if (!plans.length) {
            grid.innerHTML = '<p style="text-align:center;color:var(--mp-text-muted);padding:40px;">Không tải được dữ liệu gói. Vui lòng tải lại trang.</p>';
            return;
        }

        // Get current plan
        const planBadge = document.getElementById('mp-account-plan');
        const currentPlanName = planBadge ? planBadge.textContent.trim().toLowerCase() : '';

        grid.innerHTML = plans.map(plan => {
            const meta = planMeta[plan.id] || {};
            const isRecommended = meta.recommended || false;
            const isCurrent = currentPlanName === plan.name.toLowerCase();

            // Price calculation
            let monthlyPrice = plan.price;
            let displayPrice = monthlyPrice;
            let suffix = '/tháng';
            let savedAmount = '';

            if (period === 'yearly' && monthlyPrice > 0) {
                const yearlyTotal = monthlyPrice * 10;
                displayPrice = Math.round(yearlyTotal / 12);
                suffix = '/tháng';
                savedAmount = formatPrice(monthlyPrice * 2);
            }

            const priceDisplay = displayPrice === 0
                ? '<span class="mp-plan-price-amount">Miễn phí</span>'
                : `<span class="mp-plan-price-amount">${formatPrice(displayPrice)}</span><span class="mp-plan-price-suffix">${suffix}</span>`;

            const yearlyNote = period === 'yearly' && monthlyPrice > 0
                ? `<div class="mp-plan-yearly-note">Thanh toán ${formatPrice(monthlyPrice * 10)}/năm <span class="mp-plan-save">Tiết kiệm ${savedAmount}</span></div>`
                : period === 'yearly' && monthlyPrice === 0
                    ? '<div class="mp-plan-yearly-note">Miễn phí mãi mãi</div>'
                    : '';

            const featuresHtml = (meta.features || []).map(f =>
                `<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>${f}</li>`
            ).join('');

            let ctaClass = 'mp-plan-cta';
            let ctaText = meta.cta || 'Chọn gói';
            let ctaDisabled = '';
            if (isCurrent) {
                ctaClass += ' mp-plan-cta--current';
                ctaText = 'Gói hiện tại';
                ctaDisabled = 'disabled';
            } else if (isRecommended) {
                ctaClass += ' mp-plan-cta--recommended';
            }

            return `
                <div class="mp-plan-card ${isRecommended ? 'mp-plan-card--recommended' : ''} ${isCurrent ? 'mp-plan-card--current' : ''}" data-plan-id="${plan.id}">
                    ${isRecommended ? '<div class="mp-plan-ribbon">Phổ biến nhất</div>' : ''}
                    <div class="mp-plan-header">
                        <h3 class="mp-plan-name" style="color:${meta.color || 'inherit'}">${plan.name}</h3>
                        <p class="mp-plan-desc">${meta.desc || ''}</p>
                    </div>
                    <div class="mp-plan-price">
                        ${priceDisplay}
                        ${yearlyNote}
                    </div>
                    <button type="button" class="${ctaClass}" ${ctaDisabled} data-plan="${plan.id}">
                        ${ctaText}
                    </button>
                    <ul class="mp-plan-features">
                        ${featuresHtml}
                    </ul>
                </div>
            `;
        }).join('');

        // CTA click handlers
        grid.querySelectorAll('.mp-plan-cta:not([disabled])').forEach(btn => {
            btn.addEventListener('click', () => {
                const planId = btn.dataset.plan;
                if (planId === 'pro') {
                    MP.showToast('Vui lòng liên hệ Zalo "Monkey Tech 192" để nâng cấp gói Pro', 'info');
                } else {
                    MP.showToast('Tính năng nâng cấp sẽ sớm ra mắt! Liên hệ Zalo "Monkey Tech 192" để được hỗ trợ.', 'info');
                }
            });
        });
    }

    // ─── Init ───────────────────────────────────────

    function initPricing() {
        const grid = document.getElementById('mp-pricing-grid');
        if (!grid) return;

        fetchPlans(grid);

        // Period toggle
        const toggle = document.getElementById('mp-pricing-period-toggle');
        if (toggle) {
            toggle.addEventListener('click', () => {
                period = period === 'monthly' ? 'yearly' : 'monthly';
                toggle.classList.toggle('mp-pricing-toggle-switch--active', period === 'yearly');

                document.querySelectorAll('.mp-pricing-toggle-label').forEach(lbl => {
                    lbl.classList.toggle('mp-pricing-toggle-label--active', lbl.dataset.period === period);
                });

                renderPlans(grid);
            });
        }
    }

    // ─── Boot ───────────────────────────────────────

    $(document).ready(function () {
        initPricing();
    });

})(jQuery);
