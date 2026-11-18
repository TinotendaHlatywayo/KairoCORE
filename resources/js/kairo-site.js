(() => {
    const root = document.documentElement;
    const body = document.body;
    const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

    const syncTheme = () => {
        const dark = root.classList.contains('dark');
        body.classList.toggle('kairo-dark', dark);
        body.dataset.t = dark ? 'd' : 'l';
        body.dataset.theme = dark ? 'dark' : 'light';
        document.querySelectorAll('[data-kf-theme]').forEach((button) => {
            button.setAttribute('aria-pressed', dark ? 'true' : 'false');
            button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
            button.title = dark ? 'Switch to light mode' : 'Switch to dark mode';
            const moon = button.querySelector('.kf-moon');
            const sun = button.querySelector('.kf-sun');
            if (moon) moon.hidden = dark;
            if (sun) sun.hidden = !dark;
        });
    };

    if (body) syncTheme();

    const inlineIcons = {
        mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        send: '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
        wa: '<path d="M20.5 11.5a8.5 8.5 0 0 1-12.2 7.6L4 20l.9-4.3a8.5 8.5 0 1 1 15.6-4.2Z"/><path d="M8.7 8.3c.3-.5.6-.5.9-.5h.5c.2 0 .4.1.5.4l.7 1.7c.1.3.1.5-.1.7l-.6.7c-.2.2-.2.4 0 .7.7 1.1 1.6 1.9 2.8 2.5.3.1.5.1.7-.1l.8-.9c.2-.2.4-.3.7-.2l1.6.8c.3.1.4.3.4.5 0 .4-.2 1.2-.7 1.6-.5.5-1.2.7-1.8.6-1-.1-2.4-.7-3.9-2-1.3-1.1-2.2-2.5-2.5-3.5-.3-.8-.1-1.8.2-2.3Z"/>',
        clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        pin: '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        chev: '<path d="m9 18 6-6-6-6"/>',
    };
    document.querySelectorAll('[data-i]').forEach((node) => {
        if (!node.childElementCount && inlineIcons[node.dataset.i]) {
            const size = Number(node.dataset.s) || 22;
            node.innerHTML = `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${inlineIcons[node.dataset.i]}</svg>`;
        }
    });

    document.querySelectorAll('[data-kf-theme]').forEach((button) => {
        button.addEventListener('click', () => {
            const dark = !root.classList.contains('dark');
            root.classList.toggle('dark', dark);
            localStorage.setItem('theme', dark ? 'dark' : 'light');
            localStorage.setItem('theme_manual', 'true');
            syncTheme();
            window.dispatchEvent(new CustomEvent('theme-toggle-sync', { detail: dark ? 'dark' : 'light' }));
        });
    });

    const nav = document.getElementById('kfNav');
    const mobile = document.getElementById('kfMobile');
    const burger = document.getElementById('kfBurger');

    const onScroll = () => {
        if (nav) nav.classList.toggle('is-scrolled', window.scrollY > 8);
        const progress = document.getElementById('pg') || document.querySelector('.kf-scroll-progress');
        if (progress) {
            const max = document.documentElement.scrollHeight - window.innerHeight;
            progress.style.width = `${max > 0 ? (window.scrollY / max) * 100 : 0}%`;
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    const scrim = document.getElementById('kfMobileScrim');
    const mobileOpen = () => Boolean(mobile?.classList.contains('is-open'));

    const setMobileMenu = (open, restoreFocus = false) => {
        if (!mobile || !burger) return;
        mobile.classList.toggle('is-open', open);
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        body.classList.toggle('kf-mobile-open', open);
        if (scrim) scrim.hidden = !open;
        if (open) {
            const first = mobile.querySelector('a');
            window.setTimeout(() => first?.focus({ preventScroll: true }), 80);
        } else if (restoreFocus) {
            burger.focus({ preventScroll: true });
        }
    };

    burger?.addEventListener('click', () => setMobileMenu(!mobileOpen()));
    scrim?.addEventListener('click', () => setMobileMenu(false, true));

    mobile?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setMobileMenu(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileOpen()) setMobileMenu(false, true);
    });

    window.matchMedia('(min-width: 981px)').addEventListener?.('change', (event) => {
        if (event.matches) setMobileMenu(false);
    });

    document.querySelectorAll('.kf-logo, .logo').forEach((logo) => {
        const image = logo.querySelector('img');
        if (!image) return;
        logo.addEventListener('mousemove', (event) => {
            if (reduceMotion) return;
            const rect = logo.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width - 0.5;
            const y = (event.clientY - rect.top) / rect.height - 0.5;
            image.style.transform = `rotateY(${x * 30}deg) rotateX(${-y * 30}deg) scale(1.1)`;
        });
        logo.addEventListener('mouseleave', () => {
            image.style.transform = '';
        });
    });

    const resolveFeatureId = (id) => (id === 'comms' ? 'communications' : id);
    const scrollToFeature = (id) => {
        const target = document.getElementById(resolveFeatureId(id));
        if (!target) return false;
        target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
        if (target.classList.contains('mc')) {
            target.classList.add('hl');
            window.setTimeout(() => target.classList.remove('hl'), 1900);
        }
        return true;
    };

    const featureTrigger = document.getElementById('ft');
    const featureBackdrop = document.getElementById('mb2');
    const featureModal = document.getElementById('mm');
    if (featureTrigger && featureBackdrop && featureModal) {
        const toggleFeatures = (open, restoreFocus = false) => {
            const next = typeof open === 'boolean' ? open : !featureModal.classList.contains('open');
            featureModal.classList.toggle('open', next);
            featureBackdrop.classList.toggle('open', next);
            featureModal.setAttribute('aria-hidden', next ? 'false' : 'true');
            featureTrigger.setAttribute('aria-expanded', next ? 'true' : 'false');
            if (!next && restoreFocus) featureTrigger.focus();
        };
        featureTrigger.addEventListener('click', (event) => {
            event.preventDefault();
            toggleFeatures();
        });
        featureTrigger.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleFeatures();
            }
        });
        featureBackdrop.addEventListener('click', () => toggleFeatures(false, true));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && featureModal.classList.contains('open')) toggleFeatures(false, true);
        });
        featureModal.addEventListener('click', (event) => {
            const link = event.target.closest('[data-g]');
            if (!link) return;
            if (!scrollToFeature(link.dataset.g)) return;
            event.preventDefault();
            toggleFeatures(false);
        });
        document.addEventListener('click', (event) => {
            if (featureModal.classList.contains('open') && !featureModal.contains(event.target) && !featureTrigger.contains(event.target)) toggleFeatures(false);
        });

        const initialHash = decodeURIComponent(window.location.hash.replace('#', ''));
        if (initialHash) {
            window.setTimeout(() => scrollToFeature(initialHash), 700);
        }
    }

    const fallingIcons = document.getElementById('fi');
    const iconCards = [...document.querySelectorAll('[data-feature-card] .mi2 svg path')];
    if (fallingIcons && iconCards.length && !reduceMotion) {
        const dropIcon = () => {
            const source = iconCards[Math.floor(Math.random() * iconCards.length)];
            const card = source.closest('[data-feature-card]');
            const icon = document.createElement('span');
            icon.className = 'fi';
            icon.style.cssText = `--c:${getComputedStyle(card).getPropertyValue('--c')};--t:${5 + Math.random() * 2}s;--d:${220 + Math.random() * 240}px;--r:${Math.floor(Math.random() * 100 - 50)}deg;left:${5 + Math.random() * 90}%;top:-72px`;
            icon.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="${source.getAttribute('d')}"></path></svg>`;
            icon.addEventListener('animationend', () => icon.remove(), { once: true });
            fallingIcons.append(icon);
        };
        for (let i = 0; i < 7; i++) window.setTimeout(dropIcon, i * 180);
        window.setInterval(dropIcon, 950);
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.more');
        if (!button) return;
        const card = button.closest('.xps');
        if (!card) return;
        const open = card.classList.toggle('open');
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        const label = button.querySelector('span');
        if (label) label.textContent = open ? button.dataset.lessLabel : button.dataset.moreLabel;
    });

    const featureCards = [...document.querySelectorAll('[data-feature-card]')];
    const featureSearch = document.getElementById('featureSearch');
    const emptyFeatures = document.getElementById('em');
    const featureCount = document.getElementById('cnt');
    const featureFilters = [...document.querySelectorAll('[data-feature-filter]')];
    const featureIndicator = document.querySelector('.fch .ind');
    let featureCategory = 'all';

    const positionFeatureIndicator = () => {
        if (!featureIndicator) return;
        const active = featureFilters.find((button) => button.classList.contains('on'));
        if (!active) return;
        featureIndicator.style.width = `${active.offsetWidth}px`;
        featureIndicator.style.transform = `translateX(${active.offsetLeft}px)`;
    };

    const filterFeatures = () => {
        const query = (featureSearch?.value || '').trim().toLocaleLowerCase();
        let visible = 0;
        featureCards.forEach((card) => {
            const show = (featureCategory === 'all' || card.dataset.category === featureCategory)
                && (!query || card.dataset.search.includes(query));
            card.hidden = !show;
            if (show) visible++;
        });
        if (emptyFeatures) emptyFeatures.hidden = visible > 0;
        if (featureCount) {
            const allLabel = featureCount.dataset.allLabel || 'Showing all :count modules';
            const filteredLabel = featureCount.dataset.filteredLabel || 'Showing :shown of :total modules';
            featureCount.textContent = visible === featureCards.length
                ? allLabel.replace(':count', visible)
                : filteredLabel.replace(':shown', visible).replace(':total', featureCards.length);
        }
        positionFeatureIndicator();
    };

    featureFilters.forEach((button) => {
        button.addEventListener('click', () => {
            featureCategory = button.dataset.featureFilter;
            featureFilters.forEach((item) => item.classList.toggle('on', item === button));
            filterFeatures();
        });
    });
    featureSearch?.addEventListener('input', filterFeatures);

    const resetFeatures = () => {
        featureCategory = 'all';
        if (featureSearch) featureSearch.value = '';
        featureFilters.forEach((button) => button.classList.toggle('on', button.dataset.featureFilter === 'all'));
        filterFeatures();
    };
    document.querySelectorAll('[data-feature-reset]').forEach((button) => button.addEventListener('click', resetFeatures));
    window.addEventListener('resize', positionFeatureIndicator);
    positionFeatureIndicator();
    document.fonts?.ready.then(positionFeatureIndicator);

    const blogSearch = document.getElementById('blogSearch');
    const blogCards = [...document.querySelectorAll('[data-blog-card]')];
    const blogNoResults = document.getElementById('blogNoResults');
    blogSearch?.addEventListener('input', () => {
        const query = blogSearch.value.trim().toLocaleLowerCase();
        let visible = 0;
        blogCards.forEach((card) => {
            const show = card.dataset.search.includes(query);
            card.hidden = !show;
            if (show) visible++;
        });
        if (blogNoResults) blogNoResults.hidden = visible > 0;
    });

    const featureDialog = document.getElementById('featureModal');
    let featureOpener = null;
    const closeFeatureModal = () => {
        if (!featureDialog) return;
        featureDialog.classList.remove('open');
        featureDialog.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        featureOpener?.focus();
    };
    document.querySelectorAll('[data-open-feature]').forEach((button) => {
        button.addEventListener('click', () => {
            const card = button.closest('[data-feature-card]');
            if (!card || !featureDialog) return;
            featureOpener = button;
            featureDialog.querySelector('#featureModalTitle').textContent = card.dataset.name;
            featureDialog.querySelector('#featureModalDetail').textContent = card.dataset.detail || card.querySelector('.mb > p')?.textContent || '';
            const points = featureDialog.querySelector('#featureModalPoints');
            points.replaceChildren(...['sub1', 'sub2', 'sub3'].map((key) => card.dataset[key]).filter(Boolean).map((text) => {
                const li = document.createElement('li');
                li.textContent = text;
                return li;
            }));
            featureDialog.classList.add('open');
            featureDialog.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            featureDialog.querySelector('[data-close-feature]')?.focus();
        });
    });
    featureDialog?.querySelectorAll('[data-close-feature]').forEach((button) => button.addEventListener('click', closeFeatureModal));
    featureDialog?.addEventListener('click', (event) => {
        if (event.target === featureDialog) closeFeatureModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && featureDialog?.classList.contains('open')) closeFeatureModal();
    });

    document.querySelectorAll('.kf-menu').forEach((menu) => {
        menu.addEventListener('click', (event) => {
            if (event.target.closest('.kf-menu-panel')) return;
            if (event.target.closest('summary') === menu.querySelector('summary')) {
                document.querySelectorAll('.kf-menu[open]').forEach((other) => {
                    if (other !== menu) other.removeAttribute('open');
                });
            }
        });
    });

    const closeUtilityMenus = () => document.querySelectorAll('.kf-menu[open]').forEach((menu) => menu.removeAttribute('open'));
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.kf-menu')) closeUtilityMenus();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeUtilityMenus();
    });

    const dockSocials = [...document.querySelectorAll('.kf-dock a')].filter((link) => link.getAttribute('aria-label') !== 'Email');
    if (!reduceMotion && dockSocials.length) {
        let previous = null;
        const teaseNextSocial = () => {
            if (!document.hidden) {
                const choices = dockSocials.filter((link) => link !== previous);
                const next = choices[Math.floor(Math.random() * choices.length)] || dockSocials[0];
                dockSocials.forEach((link) => link.classList.remove('is-crying'));
                void next.offsetWidth;
                next.classList.add('is-crying');
                next.addEventListener('animationend', () => next.classList.remove('is-crying'), { once: true });
                previous = next;
            }
            window.setTimeout(teaseNextSocial, 1100 + Math.random() * 1700);
        };
        window.setTimeout(teaseNextSocial, 900);
    }

    const clock = document.getElementById('clk');
    if (clock) {
        const tick = () => {
            clock.textContent = new Intl.DateTimeFormat(undefined, {
                hour: '2-digit',
                minute: '2-digit',
            }).format(new Date());
        };
        tick();
        setInterval(tick, 30000);
    }

    if (!reduceMotion) {
        const reveal = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('in');
                reveal.unobserve(entry.target);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px' });

        document.querySelectorAll('.rv, .reveal').forEach((element, index) => {
            if (!element.style.transitionDelay) {
                element.style.transitionDelay = `${Math.min(index % 6, 5) * 0.06}s`;
            }
            reveal.observe(element);
        });

        const count = (element) => {
            const target = parseFloat(element.dataset.n || element.dataset.countup || '0');
            const suffix = element.dataset.s || element.dataset.suffix || '';
            const decimals = String(target).includes('.') ? 1 : 0;
            const start = performance.now();
            const duration = 1400;
            const frame = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const value = target * eased;
                element.textContent = value.toLocaleString(undefined, {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals,
                }) + suffix;
                if (progress < 1) requestAnimationFrame(frame);
            };
            requestAnimationFrame(frame);
        };

        const counters = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                count(entry.target);
                counters.unobserve(entry.target);
            });
        }, { threshold: 0.4 });

        document.querySelectorAll('[data-n], [data-countup]').forEach((element) => counters.observe(element));

        document.querySelectorAll('.bc').forEach((card) => {
            card.addEventListener('pointermove', (event) => {
                const rect = card.getBoundingClientRect();
                card.style.setProperty('--x', `${event.clientX - rect.left}px`);
                card.style.setProperty('--y', `${event.clientY - rect.top}px`);
            });
        });
    } else {
        document.querySelectorAll('.rv, .reveal').forEach((element) => element.classList.add('in'));
    }

    document.addEventListener('pointerdown', (event) => {
        const target = event.target.closest('.btn, .ib, .up, .kf-btn, .kf-icon-btn');
        if (!target) return;
        const rect = target.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height) * 2;
        const ripple = document.createElement('span');
        ripple.className = 'rp';
        ripple.style.cssText = `width:${size}px;height:${size}px;left:${event.clientX - rect.left - size / 2}px;top:${event.clientY - rect.top - size / 2}px`;
        target.appendChild(ripple);
        setTimeout(() => ripple.remove(), 650);
    });

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';

    document.querySelectorAll('[data-like]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (button.dataset.busy === '1') return;
            button.dataset.busy = '1';
            try {
                const response = await fetch(button.dataset.like, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({}),
                });
                if (!response.ok) throw new Error('Like failed');
                const data = await response.json();
                document.querySelectorAll('[data-like]').forEach((likeButton) => {
                    if (likeButton.dataset.like !== button.dataset.like) return;
                    likeButton.classList.toggle('on', Boolean(data.liked));
                    likeButton.setAttribute('aria-pressed', data.liked ? 'true' : 'false');
                    const label = likeButton.querySelector('.lt');
                    if (label) label.textContent = data.liked ? likeButton.dataset.likedLabel : likeButton.dataset.likeLabel;
                    const count = likeButton.querySelector('[data-like-count]');
                    if (count) count.textContent = data.count;
                });
            } catch (error) {
                console.error(error);
            } finally {
                button.dataset.busy = '';
            }
        });
    });

    document.querySelectorAll('[data-share]').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            const url = button.dataset.shareUrl || window.location.href;
            const title = button.dataset.shareTitle || document.title;
            const record = button.dataset.shareRecord;

            const recordShare = async (target) => {
                if (!record) return;
                const response = await fetch(record, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ target }),
                });
                if (!response.ok) throw new Error('Share could not be recorded');
                const data = await response.json();
                document.querySelectorAll('[data-share-count-for]').forEach((count) => {
                    if (count.dataset.shareCountFor === record) count.textContent = data.count;
                });
            };

            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                    await recordShare('native');
                } catch (error) {
                    if (error.name !== 'AbortError') console.error(error);
                }
                return;
            }
            let menu = document.getElementById('kfShareMenu');
            if (!menu) {
                menu = document.createElement('div');
                menu.id = 'kfShareMenu';
                menu.className = 'sp';
                menu.setAttribute('role', 'menu');
                document.body.append(menu);
            }
            const encodedUrl = encodeURIComponent(url);
            const encodedTitle = encodeURIComponent(title);
            menu.replaceChildren();
            [
                ['whatsapp', button.dataset.whatsappLabel],
                ['facebook', button.dataset.facebookLabel],
                ['x', button.dataset.xLabel],
                ['copy', button.dataset.copyLabel],
            ].forEach(([target, label]) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.dataset.shareTarget = target;
                option.setAttribute('role', 'menuitem');
                option.textContent = label || target;
                menu.append(option);
            });
            const rect = button.getBoundingClientRect();
            menu.style.left = `${Math.max(12, Math.min(window.innerWidth - 248, rect.left + rect.width / 2 - 118))}px`;
            menu.style.top = `${Math.min(rect.bottom + 8, window.innerHeight - 220)}px`;
            menu.classList.add('show');
            menu.onclick = async (choiceEvent) => {
                const choice = choiceEvent.target.closest('[data-share-target]')?.dataset.shareTarget;
                if (!choice) return;
                try {
                    if (choice === 'copy') {
                        await navigator.clipboard.writeText(url);
                        const toast = document.getElementById('toast');
                        if (toast) {
                            toast.textContent = button.dataset.copiedLabel || 'Link copied';
                            toast.classList.add('show');
                            setTimeout(() => toast.classList.remove('show'), 1800);
                        }
                    } else {
                        const shareUrl = choice === 'whatsapp'
                            ? `https://wa.me/?text=${encodeURIComponent(`${title} ${url}`)}`
                            : choice === 'facebook'
                                ? `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`
                                : `https://x.com/intent/tweet?text=${encodedTitle}&url=${encodedUrl}`;
                        window.open(shareUrl, '_blank', 'noopener,width=680,height=560');
                    }
                    await recordShare(choice);
                } catch (error) {
                    console.error(error);
                } finally {
                    menu.classList.remove('show');
                }
            };
        });
    });

    document.addEventListener('click', (event) => {
        const menu = document.getElementById('kfShareMenu');
        if (menu && !menu.contains(event.target) && !event.target.closest('[data-share]')) menu.classList.remove('show');
    });

    document.querySelectorAll('[data-contact-form]').forEach((form) => {
        [['#name', '#contactNameCount', 100], ['#message', '#contactMessageCount', 2000]].forEach(([inputSelector, countSelector, limit]) => {
            const input = form.querySelector(inputSelector);
            const counter = form.querySelector(countSelector);
            if (!input || !counter) return;
            const updateCount = () => { counter.textContent = `${input.value.length} / ${limit}`; };
            input.addEventListener('input', updateCount);
            updateCount();
        });
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = '1';
            const button = form.querySelector('[type="submit"]');
            if (button) {
                button.disabled = true;
                button.dataset.label = button.innerHTML;
                button.innerHTML = 'Sending…';
            }
        });
    });
})();
