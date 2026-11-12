/* Yamaha Audio theme — no dependencies, ~2 KB gzipped. */
(function () {
	'use strict';

	var doc = document;
	var $ = function (sel, ctx) { return (ctx || doc).querySelector(sel); };
	var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); };

	/* ---------- Off-canvas mobile menu ---------- */
	var mnav = $('#hx-mnav');
	var scrim = $('.hx-scrim');
	var burger = $('.hx-burger');

	function openPanel(panel) {
		if (!panel) { return; }
		panel.hidden = false;
		scrim.hidden = false;
		requestAnimationFrame(function () {
			panel.classList.add('is-open');
			scrim.classList.add('is-open');
		});
		doc.body.classList.add('hx-locked');
		if (burger && panel === mnav) { burger.setAttribute('aria-expanded', 'true'); }
		var first = panel.querySelector('a, button, input');
		if (first) { first.focus({ preventScroll: true }); }
	}

	function closePanels() {
		$$('.hx-mnav.is-open, .hx-rail.is-open').forEach(function (p) {
			p.classList.remove('is-open');
			setTimeout(function () { if (!p.classList.contains('is-open')) { p.hidden = p.classList.contains('hx-mnav') ? true : p.hidden; } }, 250);
		});
		if (scrim) {
			scrim.classList.remove('is-open');
			setTimeout(function () { if (!scrim.classList.contains('is-open')) { scrim.hidden = true; } }, 250);
		}
		doc.body.classList.remove('hx-locked');
		if (burger) { burger.setAttribute('aria-expanded', 'false'); }
	}

	if (burger && mnav) {
		burger.addEventListener('click', function () { mnav.hidden ? openPanel(mnav) : closePanels(); });
	}
	$$('[data-hx-close], [data-hx-rail-close]').forEach(function (el) { el.addEventListener('click', closePanels); });
	doc.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closePanels(); } });

	// Accordion for nested items in the mobile menu.
	if (mnav) {
		$$('.menu-item-has-children > a', mnav).forEach(function (link) {
			var li = link.parentNode;
			var btn = doc.createElement('button');
			btn.type = 'button';
			btn.className = 'hx-mnav__toggle';
			btn.setAttribute('aria-expanded', 'false');
			btn.setAttribute('aria-label', 'Deschide ' + link.textContent.trim());
			btn.innerHTML = '<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>';
			btn.addEventListener('click', function () {
				var open = li.classList.toggle('is-open');
				btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			});
			link.insertAdjacentElement('afterend', btn);
		});
	}

	/* ---------- Facets: "show all" + in-facet search ---------- */
	$$('[data-hx-facet-more]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var box = btn.closest('.hx-filter');
			var open = btn.getAttribute('aria-expanded') === 'true';
			$$('.hx-filter__extra', box).forEach(function (li) { li.hidden = open; });
			btn.setAttribute('aria-expanded', open ? 'false' : 'true');
			btn.textContent = open ? btn.getAttribute('data-label-more') || btn.textContent : 'Mai puține −';
			if (!open && !btn.getAttribute('data-label-more')) { btn.setAttribute('data-label-more', 'Toate +'); }
		});
	});
	$$('[data-hx-facet-search]').forEach(function (input) {
		var box = input.closest('.hx-filter');
		input.addEventListener('input', function () {
			var q = input.value.trim().toLowerCase();
			$$('.hx-filter__list > li', box).forEach(function (li) {
				var name = (li.querySelector('.hx-filter__name') || li).textContent.toLowerCase();
				if (q) { li.hidden = name.indexOf(q) === -1; }
				else { li.hidden = li.classList.contains('hx-filter__extra'); }
			});
		});
	});

	/* ---------- Filter rail on mobile ---------- */
	var rail = $('#hx-rail');
	$$('[data-hx-rail-open]').forEach(function (btn) {
		btn.addEventListener('click', function () { if (rail) { rail.hidden = false; openPanel(rail); } });
	});

	/* ---------- Header nav: when the bar is tight, squeeze the link padding/size one step ---------- */
	var navEl = $('.hx-nav');
	if (navEl) {
		var navNeed = function () {
			// width the two groups need (scrollWidth would count the hidden mega panels)
			var n = 0, kids = navEl.children;
			for (var i = 0; i < kids.length; i++) { n += kids[i].offsetWidth; }
			return n + 12 * Math.max(0, kids.length - 1);
		};
		var fitNav = function () {
			navEl.classList.remove('is-tight', 'is-tighter');
			if (navNeed() <= navEl.clientWidth) { return; }
			navEl.classList.add('is-tight');
			if (navNeed() > navEl.clientWidth) { navEl.classList.add('is-tighter'); }
		};
		fitNav();
		var navTimer;
		window.addEventListener('resize', function () { clearTimeout(navTimer); navTimer = setTimeout(fitNav, 120); });
		if (doc.fonts && doc.fonts.ready) { doc.fonts.ready.then(fitNav); }
	}

	/* ---------- Mega menu: open on hover/focus, keyboard friendly ---------- */
	var items = $$('.hx-nav__item.has-mega');
	var closeTimer;
	function closeAll(except) {
		items.forEach(function (li) {
			if (li !== except) {
				li.classList.remove('is-open');
				var a = li.querySelector('.hx-nav__link');
				var mega = li.querySelector('.hx-mega');
				if (a) { a.setAttribute('aria-expanded', 'false'); }
				if (mega) { mega.setAttribute('aria-hidden', 'true'); }
			}
		});
	}
	items.forEach(function (li) {
		var link = li.querySelector('.hx-nav__link');
		var mega = li.querySelector('.hx-mega');
		function open() {
			clearTimeout(closeTimer);
			closeAll(li);
			li.classList.add('is-open');
			link.setAttribute('aria-expanded', 'true');
			mega.setAttribute('aria-hidden', 'false');
		}
		function scheduleClose() { closeTimer = setTimeout(function () { closeAll(); }, 120); }
		li.addEventListener('mouseenter', open);
		li.addEventListener('mouseleave', scheduleClose);
		li.addEventListener('focusin', open);
		li.addEventListener('focusout', function (e) { if (!li.contains(e.relatedTarget)) { closeAll(); } });
		// On touch, first tap opens the panel; second tap follows the link.
		link.addEventListener('click', function (e) {
			if (window.matchMedia('(hover: none)').matches && !li.classList.contains('is-open')) {
				e.preventDefault();
				open();
			}
		});
	});
	doc.addEventListener('click', function (e) { if (!e.target.closest('.hx-nav')) { closeAll(); } });

	/* ---------- Open / closed status (Europe/Bucharest), kept fresh on cached pages ---------- */
	var openEl = $('.hx-open[data-hx-hours]');
	if (openEl) {
		var hoursTable = null;
		try { hoursTable = JSON.parse(openEl.getAttribute('data-hx-hours')); } catch (e) { hoursTable = null; }
		var fmtMin = function (m) { return ('0' + Math.floor(m / 60)).slice(-2) + ':' + ('0' + (m % 60)).slice(-2); };
		var dayNames = { 1: 'luni', 2: 'marți', 3: 'miercuri', 4: 'joi', 5: 'vineri', 6: 'sâmbătă', 7: 'duminică' };
		var updateOpen = function () {
			if (!hoursTable) { return; }
			var parts;
			try {
				parts = new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Bucharest', weekday: 'short', hour: 'numeric', minute: 'numeric', hour12: false }).formatToParts(new Date());
			} catch (e) { return; }
			var get = function (t) { var p = parts.filter(function (x) { return x.type === t; })[0]; return p ? p.value : ''; };
			var dow = { Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6, Sun: 7 }[get('weekday')];
			var mins = (parseInt(get('hour'), 10) % 24) * 60 + parseInt(get('minute'), 10);
			var today = hoursTable[dow];
			var open = false, label = '';
			if (today && mins >= today[0] && mins < today[1]) { open = true; label = 'Deschis acum · până la ' + fmtMin(today[1]); }
			else if (today && mins < today[0]) { label = 'Deschidem azi la ' + fmtMin(today[0]); }
			else {
				for (var i = 1; i <= 7; i++) {
					var d = (dow + i - 1) % 7 + 1;
					if (hoursTable[d]) { label = 'Ne vedem ' + (i === 1 ? 'mâine' : dayNames[d]) + ' de la ' + fmtMin(hoursTable[d][0]); break; }
				}
			}
			if (!label) { return; }
			var lbl = openEl.querySelector('.hx-open__label');
			if (lbl.textContent !== label) {
				lbl.textContent = label;
				openEl.classList.remove('is-sweep'); void openEl.offsetWidth; openEl.classList.add('is-sweep');
			}
			openEl.classList.toggle('is-open', open);
			openEl.classList.toggle('is-closed', !open);
		};
		updateOpen();
		setInterval(updateOpen, 60000);
		requestAnimationFrame(function () { openEl.classList.add('is-sweep'); });
	}

	/* ---------- Top bar ticker on phones: one item at a time, rotating ---------- */
	var ticker = $('[data-hx-ticker]');
	if (ticker) {
		var tickerItems = $$('.hx-topbar__item', ticker);
		var mq = window.matchMedia('(max-width: 900px)');
		var tickTimer = null, tickIdx = 0, paused = false;
		var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var show = function (i) {
			tickerItems.forEach(function (el, n) { el.classList.toggle('is-shown', n === i); });
		};
		var start = function () {
			if (tickTimer || tickerItems.length < 2) { return; }
			ticker.classList.add('is-ticker');
			tickIdx = 0; show(0);
			if (reduce) { return; }
			tickTimer = setInterval(function () {
				if (paused) { return; }
				tickIdx = (tickIdx + 1) % tickerItems.length;
				show(tickIdx);
			}, 3500);
		};
		var stop = function () {
			ticker.classList.remove('is-ticker');
			tickerItems.forEach(function (el) { el.classList.remove('is-shown'); });
			if (tickTimer) { clearInterval(tickTimer); tickTimer = null; }
		};
		var apply = function () { mq.matches ? start() : stop(); };
		apply();
		(mq.addEventListener ? mq.addEventListener('change', apply) : mq.addListener(apply));
		ticker.addEventListener('touchstart', function () { paused = true; }, { passive: true });
		ticker.addEventListener('touchend', function () { paused = false; }, { passive: true });
		ticker.addEventListener('click', function (e) {
			// tapping the bar advances to the next message (unless a link was tapped)
			if (!e.target.closest('a') && tickTimer) { tickIdx = (tickIdx + 1) % tickerItems.length; show(tickIdx); }
		});
	}

	/* ---------- Scroll reveal (fade only) ---------- */
	if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		var revealTargets = $$('.hx-section__head, .hx-cat, .hx-card, .hx-bundle, .hx-trust__item, .hx-band__grid, .hx-postcard, .hx-ribbon, .hx-why__item');
		revealTargets.forEach(function (el, i) {
			// only elements below the first viewport animate; above-the-fold content paints at once
			if (el.getBoundingClientRect().top > window.innerHeight * 0.9) { el.classList.add('hx-reveal'); }
		});
		var ro = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					var el = en.target;
					// stagger siblings slightly
					var sib = Array.prototype.indexOf.call(el.parentNode.children, el);
					el.style.transitionDelay = Math.min(sib, 7) * 60 + 'ms';
					el.classList.add('is-in');
					ro.unobserve(el);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
		$$('.hx-reveal').forEach(function (el) { ro.observe(el); });
		// Belt and braces: anything in view after a scroll is shown even if the observer is late,
		// and nothing stays hidden for more than a few seconds.
		var revealAll = function () { $$('.hx-reveal:not(.is-in)').forEach(function (el) { el.classList.add('is-in'); ro.unobserve(el); }); };
		var revealVisible = function () {
			$$('.hx-reveal:not(.is-in)').forEach(function (el) {
				var r = el.getBoundingClientRect();
				if (r.top < window.innerHeight && r.bottom > 0) { el.classList.add('is-in'); ro.unobserve(el); }
			});
		};
		var revTimer;
		window.addEventListener('scroll', function () { clearTimeout(revTimer); revTimer = setTimeout(revealVisible, 150); }, { passive: true });
		setTimeout(revealAll, 4000);
	}

	/* ---------- Header shadow after scroll ---------- */
	var header = $('#hx-header');
	if (header) {
		var ticking = false;
		window.addEventListener('scroll', function () {
			if (!ticking) {
				requestAnimationFrame(function () {
					header.classList.toggle('is-scrolled', window.scrollY > 8);
					ticking = false;
				});
				ticking = true;
			}
		}, { passive: true });
	}

	/* ---------- Sticky buy bar on product pages ---------- */
	var buybar = $('#hx-buybar');
	var summary = $('#hx-summary');
	if (buybar && summary && 'IntersectionObserver' in window) {
		var target = summary.querySelector('.single_add_to_cart_button') || summary;
		var io = new IntersectionObserver(function (entries) {
			var visible = entries[0].isIntersecting || entries[0].boundingClientRect.top > 0;
			buybar.hidden = visible;
			buybar.classList.toggle('is-visible', !visible);
		}, { rootMargin: '-130px 0px 0px 0px' });
		io.observe(target);

		var buyBtn = buybar.querySelector('[data-hx-buy]');
		if (buyBtn) {
			buyBtn.addEventListener('click', function () {
				var real = summary.querySelector('.single_add_to_cart_button');
				var form = summary.querySelector('form.cart');
				if (real && !real.disabled && form && !form.classList.contains('variations_form')) {
					real.click();
				} else {
					(form || summary).scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
			});
		}
	}

	/* ---------- Gallery: show at most 5 thumbnails, the last one badged "+N" ---------- */
	var limitThumbs = function () {
		var thumbs = $('.woocommerce-product-gallery .flex-control-thumbs');
		if (!thumbs || thumbs.getAttribute('data-hx-limited')) { return; }
		var items = $$('li', thumbs);
		if (items.length <= 5) { thumbs.setAttribute('data-hx-limited', '1'); return; }
		items.forEach(function (li, i) { if (i >= 5) { li.hidden = true; } });
		var last = items[4];
		var badge = doc.createElement('span');
		badge.className = 'hx-thumb-more';
		badge.textContent = '+' + (items.length - 5);
		last.classList.add('has-more');
		last.appendChild(badge);
		// Jump to the 6th image when the badged thumb is clicked a second time (first click selects it).
		var armed = false;
		last.addEventListener('click', function () {
			if (armed) { items[5].querySelector('img') && items[5].querySelector('img').click(); }
			armed = true;
			setTimeout(function () { armed = false; }, 1500);
		});
		thumbs.setAttribute('data-hx-limited', '1');
	};
	/* Tiny gallery images (feature badges from supplier feeds, e.g. 90×90 logos) must not be
	   stretched to fill the slide: show them at natural size on the white stage instead. */
	var tagSmallImages = function () {
		$$('.woocommerce-product-gallery__image img').forEach(function (img) {
			var mark = function () {
				if (img.naturalWidth && img.naturalWidth < 300 && img.naturalHeight < 300) {
					img.classList.add('is-small');
				}
			};
			if (img.complete) { mark(); } else { img.addEventListener('load', mark, { once: true }); }
		});
	};
	var galleryRefresh = function () { limitThumbs(); tagSmallImages(); };
	galleryRefresh();
	if (window.jQuery) {
		window.jQuery('.woocommerce-product-gallery').on('wc-product-gallery-after-init', function () { setTimeout(galleryRefresh, 50); });
		// After a variation swaps the main image, WooCommerce re-inits the slider: re-apply.
		window.jQuery('form.variations_form').on('found_variation reset_data', function () {
			$$('.woocommerce-product-gallery .flex-control-thumbs').forEach(function (t) { t.removeAttribute('data-hx-limited'); $$('.hx-thumb-more', t).forEach(function (b) { b.remove(); }); $$('li', t).forEach(function (li) { li.hidden = false; li.classList.remove('has-more'); }); });
			setTimeout(galleryRefresh, 300);
			setTimeout(galleryRefresh, 1200);
		});
	}
	setTimeout(galleryRefresh, 800);
	setTimeout(galleryRefresh, 2500);

	/* ---------- Price box follows the chosen variation (no second price on the page) ---------- */
	var priceBox = $('.hx-single__pricing');
	if (priceBox && window.jQuery) {
		var lei = function (n) { n = Number(n) || 0; var cents = Math.abs(n - Math.round(n)) > 0.004; return n.toLocaleString('ro-RO', { minimumFractionDigits: cents ? 2 : 0, maximumFractionDigits: cents ? 2 : 0 }) + ' lei'; };
		var initialPrice = priceBox.innerHTML;
		var bar = $('.hx-buybar__price'); var initialBar = bar ? bar.innerHTML : '';
		var rates = window.hxData && hxData.rates ? hxData.rates : null;
		window.jQuery('form.variations_form').on('found_variation', function (e, v) {
			if (!v || !(v.display_price > 0)) { return; }
			var now = priceBox.querySelector('.hx-price__now'); if (!now) { return; }
			now.innerHTML = lei(v.display_price);
			var was = priceBox.querySelector('.hx-price__was'), save = priceBox.querySelector('.hx-price__save');
			var onSale = v.display_regular_price > v.display_price;
			if (was) { was.hidden = !onSale; if (onSale) { was.textContent = lei(v.display_regular_price); } }
			if (save) { save.hidden = !onSale; if (onSale) { save.textContent = save.textContent.replace(/[\d.,]+\s*lei/, lei(v.display_regular_price - v.display_price)); } }
			var camp = priceBox.querySelector('.hx-price__camp b');
			if (camp) { camp.textContent = onSale ? '−' + Math.round(100 - v.display_price / v.display_regular_price * 100) + '%' : ''; }
			var rate = priceBox.querySelector('.hx-single__rate strong');
			if (rate && rates && v.display_price >= rates.min) { rate.textContent = rates.count + ' × ' + lei(Math.ceil(v.display_price / rates.count)); }
			if (bar) { var sp = bar.querySelector('span'); if (sp) { sp.textContent = lei(v.display_price); } var sm = bar.querySelector('small'); if (sm && rates && v.display_price >= rates.min) { sm.textContent = rates.count + ' × ' + lei(Math.ceil(v.display_price / rates.count)); } }
		}).on('reset_data', function () {
			priceBox.innerHTML = initialPrice; if (bar) { bar.innerHTML = initialBar; }
		});
		// Extra Product Options keeps a (hidden) "final total"; mirror it into the one visible price.
		var parseLei = function (t) { t = String(t || '').replace(/[^\d.,]/g, ''); if (!t) { return 0; } t = t.replace(/\./g, '').replace(',', '.'); return parseFloat(t) || 0; };
		var setPrice = function (n) {
			var now = priceBox.querySelector('.hx-price__now'); if (!now || !(n > 0)) { return; }
			now.innerHTML = lei(n);
			var rate = priceBox.querySelector('.hx-single__rate strong');
			if (rate && rates && n >= rates.min) { rate.textContent = rates.count + ' × ' + lei(Math.ceil(n / rates.count)); }
			if (bar) { var sp = bar.querySelector('span'); if (sp) { sp.textContent = lei(n); } }
		};
		var totalsNode = function () { return $('form.cart .tm-final-totals .amount, form.cart .tc-final-totals .amount, form.cart [class*="final-totals"] .amount, form.cart .tm-final-totals'); };
		var syncTotals = function () { var n = totalsNode(); if (!n) { return; } var v = parseLei(n.textContent); if (v > 0) { setPrice(v); } };
		var form = $('form.cart');
		if (form && window.MutationObserver) {
			new MutationObserver(function () { clearTimeout(form._hxT); form._hxT = setTimeout(syncTotals, 80); }).observe(form, { childList: true, subtree: true, characterData: true });
		}
		if (window.jQuery) { window.jQuery(doc.body).on('tm-epo-after-update tc-epo-after-update tm-epo-update tc-epo-update tm-epo-totals-updated', function () { setTimeout(syncTotals, 50); }); }
		setTimeout(syncTotals, 1200);
	}

	/* ---------- Availability box follows the chosen variation ---------- */
	var promise = $('[data-hx-promise]');
	if (promise && window.jQuery) {
		var initial = { kind: promise.getAttribute('data-kind'), html: promise.innerHTML };
		var setAvail = function (a) {
			if (!a) { return; }
			var stock = promise.querySelector('.hx-stock');
			stock.className = 'hx-stock hx-stock--' + a['class'] + ' hx-promise__stock';
			promise.querySelector('[data-hx-stock-label]').textContent = a.label;
			promise.querySelector('[data-hx-stock-qty]').textContent = a.qty_text || '';
			var show = a.kind === 'low' ? 'instock' : a.kind;
			$$('[data-hx-line]', promise).forEach(function (l) { l.hidden = l.getAttribute('data-hx-line') !== show; });
			promise.setAttribute('data-kind', a.kind);
		};
		window.jQuery('form.variations_form').on('found_variation', function (e, variation) {
			if (variation && variation.hx_availability) { setAvail(variation.hx_availability); }
		}).on('reset_data', function () {
			promise.innerHTML = initial.html; promise.setAttribute('data-kind', initial.kind);
		});
	}

	/* ---------- Long descriptions: clamp + "Citește tot" ---------- */
	var prose = $('.hx-single__desc .hx-prose');
	if (prose && prose.scrollHeight > 620) {
		prose.classList.add('is-clamped');
		var more = doc.createElement('button');
		more.type = 'button';
		more.className = 'hx-btn hx-btn--ghost hx-btn--sm hx-readmore';
		more.setAttribute('aria-expanded', 'false');
		more.textContent = 'Citește tot';
		more.addEventListener('click', function () {
			var open = prose.classList.toggle('is-open');
			more.setAttribute('aria-expanded', open ? 'true' : 'false');
			more.textContent = open ? 'Arată mai puțin' : 'Citește tot';
			if (!open) { prose.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
		});
		prose.insertAdjacentElement('afterend', more);
	}

	/* ---------- Finish picker for products without styled swatches ----------
	   Extra Product Options draws image tiles only where a product has them configured; everything
	   else falls back to a bare dropdown. Build the same tiles from the variation data instead. */
	$$('form.variations_form').forEach(function (form) {
		var jq = window.jQuery;
		var variations = [];
		try { variations = JSON.parse(form.getAttribute('data-product_variations') || '[]') || []; } catch (e) { variations = []; }
		var epoLists = $$('.tm-extra-product-options ul.tm-extra-product-options-variations', form.parentNode || doc);
		var built = 0;
		$$('table.variations select', form).forEach(function (native) {
			var attr = native.getAttribute('data-attribute_name') || native.name;
			var values = $$('option', native).map(function (o) { return o.value; }).filter(Boolean);
			if (!values.length) { return; }
			// Is the plugin already drawing this attribute? Tiles: leave it. Its own dropdown: replace it.
			var driver = native, epoHide = null, skip = false;
			epoLists.forEach(function (ul) {
				var sel = ul.querySelector('select.tm-epo-variation-element');
				var radios = $$('input.tm-epo-variation-element', ul).map(function (i) { return i.value; });
				if (sel) {
					var v = $$('option', sel).map(function (o) { return o.value; }).filter(Boolean);
					if (v.length && v.every(function (x) { return values.indexOf(x) > -1; })) { driver = sel; epoHide = ul.closest('.tc-element-container') || ul; }
				} else if (radios.length && radios.every(function (x) { return values.indexOf(x) > -1; })) { skip = true; }
			});
			if (skip) { return; }
			if (driver === native && getComputedStyle(native.closest('table')).display === 'none') { return; }

			var imageFor = function (value) {
				for (var i = 0; i < variations.length; i++) {
					var v = variations[i];
					if (v.attributes && v.attributes[attr] === value && v.image && (v.image.gallery_thumbnail_src || v.image.thumb_src)) {
						return v.image.thumb_src || v.image.gallery_thumbnail_src;
					}
				}
				return '';
			};
			var isColour = /culoare|color|finisaj/.test(attr);
			var withImages = isColour && values.every(function (v) { return !!imageFor(v); });
			var labelEl = native.closest('tr') ? native.closest('tr').querySelector('label') : null;
			var wrap = doc.createElement('div');
			wrap.className = 'hx-finish';
			var title = doc.createElement('div');
			title.className = 'hx-finish__title';
			title.textContent = isColour ? 'Culori disponibile' : (labelEl ? labelEl.textContent.trim() : '');
			var value = doc.createElement('span');
			value.className = 'hx-finish__value';
			title.appendChild(value);
			var list = doc.createElement('div');
			list.className = 'hx-finish__list' + (withImages ? '' : ' hx-finish__list--pills');
			list.setAttribute('role', 'radiogroup');
			list.setAttribute('aria-label', title.firstChild.textContent);
			var buttons = values.map(function (val) {
				var opt = native.querySelector('option[value="' + val.replace(/"/g, '\\"') + '"]');
				var b = doc.createElement('button');
				b.type = 'button';
				b.className = withImages ? 'hx-swatch' : 'hx-pill';
				b.setAttribute('role', 'radio');
				b.dataset.value = val;
				var text = opt ? opt.textContent.trim() : val;
				if (withImages) {
					var frame = doc.createElement('span');
					frame.className = 'hx-swatch__frame';
					var img = doc.createElement('img');
					img.src = imageFor(val); img.alt = ''; img.loading = 'lazy'; img.width = 104; img.height = 104;
					frame.appendChild(img);
					b.appendChild(frame);
				}
				var name = doc.createElement('span');
				name.className = withImages ? 'hx-swatch__name' : 'hx-pill__name';
				name.textContent = text;
				b.appendChild(name);
				b.addEventListener('click', function () {
					if (b.disabled || driver.value === val) { return; }
					driver.value = val;
					if (jq) { jq(driver).trigger('change'); } else { driver.dispatchEvent(new Event('change', { bubbles: true })); }
					sync();
				});
				list.appendChild(b);
				return b;
			});
			function sync() {
				var cur = native.value || driver.value;
				var curText = '';
				buttons.forEach(function (b) {
					var opt = native.querySelector('option[value="' + b.dataset.value.replace(/"/g, '\\"') + '"]');
					var on = cur === b.dataset.value;
					b.classList.toggle('is-on', on);
					b.setAttribute('aria-checked', on ? 'true' : 'false');
					b.disabled = !opt || opt.disabled;
					if (on) { curText = b.lastChild.textContent; }
				});
				value.textContent = curText;
				value.hidden = !curText;
			}
			wrap.appendChild(title);
			wrap.appendChild(list);
			if (epoHide) {
				epoHide.classList.add('hx-finish-replaced');
				epoHide.insertAdjacentElement('beforebegin', wrap);
			} else {
				native.closest('table').insertAdjacentElement('beforebegin', wrap);
				native.closest('tr').classList.add('hx-finish-replaced');
				built++;
			}
			sync();
			if (jq) { jq(form).on('woocommerce_update_variation_values found_variation reset_data change', function () { setTimeout(sync, 30); }); }
			else { form.addEventListener('change', function () { setTimeout(sync, 30); }); }
		});
		if (built) { form.classList.add('hx-has-finish'); }
	});

	/* ---------- Finish picker: show the chosen finish beside the section title ---------- */
	$$('.tm-extra-product-options ul.tm-extra-product-options-variations').forEach(function (list) {
		var section = list.closest('.tc-section-inner-wrap, .cpf-section, .tc-styled-variations') || list.closest('.tm-extra-product-options');
		if (!section) { return; }
		var title = section.querySelector('.tm-section-description, .tc-element-title, .tm-section-label, .tm-epo-section-label');
		if (!title) { return; }
		var value = doc.createElement('span');
		value.className = 'hx-finish__value';
		title.appendChild(value);
		var update = function () {
			var active = section.querySelector('.tmhexcolorimage-li.tc-active .tc-label-text, .tmcp-field-wrap.tc-active .tc-label-text');
			var checked = section.querySelector('.tm-epo-variation-element:checked');
			var text = active ? active.textContent.trim() : '';
			if (!text && checked) { var lab = checked.closest('label'); text = lab ? lab.textContent.trim() : ''; }
			value.textContent = text;
		};
		update();
		section.addEventListener('change', function () { setTimeout(update, 30); });
		section.addEventListener('click', function () { setTimeout(update, 60); });
		if (window.jQuery) { window.jQuery(doc.body).on('found_variation reset_data tc-variation-changed', function () { setTimeout(update, 60); }); }
	});

	/* ---------- Notifications: close buttons, auto-dismiss, and a toast for AJAX add-to-cart ---------- */
	var noticeBox = $('.hx-notices');
	function dressNotice(el) {
		if (el.dataset.hxDressed) { return; }
		el.dataset.hxDressed = '1';
		var close = doc.createElement('button');
		close.type = 'button';
		close.className = 'hx-notice__close';
		close.setAttribute('aria-label', 'Închide');
		close.innerHTML = '&times;';
		close.addEventListener('click', function () { dismissNotice(el); });
		el.appendChild(close);
		if (!el.classList.contains('woocommerce-error')) {
			var t = setTimeout(function () { dismissNotice(el); }, 7000);
			el.addEventListener('mouseenter', function () { clearTimeout(t); });
		}
	}
	function dismissNotice(el) {
		if (!el || el.classList.contains('is-leaving')) { return; }
		el.classList.add('is-leaving');
		setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 320);
	}
	function toast(title, text, actionLabel, actionUrl, kind) {
		if (!noticeBox) {
			noticeBox = doc.createElement('div');
			noticeBox.className = 'hx-notices';
			doc.body.appendChild(noticeBox);
		}
		var el = doc.createElement('div');
		el.className = 'hx-toast ' + (kind === 'error' ? 'woocommerce-error' : kind === 'info' ? 'woocommerce-info' : 'woocommerce-message');
		el.setAttribute('role', 'status');
		el.innerHTML = '<span><span class="hx-toast__title"></span><span class="hx-toast__text"></span></span>';
		el.querySelector('.hx-toast__title').textContent = title;
		el.querySelector('.hx-toast__text').textContent = text || '';
		if (actionLabel && actionUrl) {
			var a = doc.createElement('a');
			a.className = 'hx-btn hx-btn--sm';
			a.href = actionUrl;
			a.textContent = actionLabel;
			el.appendChild(a);
		}
		noticeBox.appendChild(el);
		dressNotice(el);
		return el;
	}
	if (noticeBox) { $$(':scope > *', noticeBox).forEach(dressNotice); }
	window.hxToast = toast;

	/* ---------- Light / dark switch ---------- */
	$$('[data-hx-theme]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var next = doc.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
			doc.documentElement.setAttribute('data-theme', next);
			try { localStorage.setItem('hx-theme', next); } catch (e) {}
			var meta = $('meta[name="theme-color"]');
			if (meta) { meta.setAttribute('content', meta.getAttribute(next === 'light' ? 'data-hx-light' : 'data-hx-dark') || ''); }
		});
	});

	/* ---------- Campaign countdown (Black Edition, Winter Sales…) ---------- */
	var timers = $$('[data-hx-countdown]');
	if (timers.length) {
		var pad = function (n) { return (n < 10 ? '0' : '') + n; };
		var tick = function () {
			var now = Math.floor(Date.now() / 1000);
			timers.forEach(function (el) {
				var left = parseInt(el.getAttribute('data-hx-countdown'), 10) - now;
				if (isNaN(left)) { return; }
				if (left <= 0) {
					el.innerHTML = '<span class="hx-cd__label">s‑a încheiat</span>';
					return;
				}
				var d = Math.floor(left / 86400), h = Math.floor(left % 86400 / 3600), m = Math.floor(left % 3600 / 60), s = left % 60;
				var html = '<span class="hx-cd__label">se încheie în</span>';
				if (d > 0) { html += '<b class="hx-cd__seg">' + d + '<i>' + (d === 1 ? 'zi' : 'zile') + '</i></b>'; }
				html += '<b class="hx-cd__seg">' + pad(h) + '<i>h</i></b><b class="hx-cd__seg">' + pad(m) + '<i>m</i></b>';
				if (d === 0) { html += '<b class="hx-cd__seg">' + pad(s) + '<i>s</i></b>'; }
				el.innerHTML = html;
				el.classList.toggle('is-urgent', d === 0);
			});
		};
		tick();
		setInterval(tick, 1000);
	}

	/* ---------- Added-to-cart feedback on the header icon ---------- */
	if (window.jQuery) {
		window.jQuery(doc.body).on('added_to_cart', function (e, fragments, hash, button) {
			var cart = $('.hx-cart');
			if (cart) {
				cart.classList.add('is-bump');
				setTimeout(function () { cart.classList.remove('is-bump'); }, 600);
			}
			// a toast naming the product, with a way into the cart
			var btn = button && button[0] ? button[0] : null;
			var card = btn ? btn.closest('.hx-card') : null;
			var name = card ? (card.querySelector('.hx-card__title') || {}).textContent : '';
			toast('Adăugat în coș', (name || '').trim(), 'Vezi coșul', (window.hxData && hxData.cartUrl) || '/cart/', 'success');
			if (btn && btn.classList.contains('hx-btn')) {
				if (!btn.dataset.hxLabel) { btn.dataset.hxLabel = btn.textContent; }
				btn.textContent = 'Adăugat ✓';
				btn.classList.add('is-added');
				setTimeout(function () {
					btn.textContent = 'Vezi coșul';
					btn.classList.remove('add_to_cart_button', 'ajax_add_to_cart');
					if (window.hxData && hxData.cartUrl) { btn.setAttribute('href', hxData.cartUrl); }
				}, 1600);
			}
		});
	}
})();
