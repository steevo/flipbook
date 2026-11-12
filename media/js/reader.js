/**
 * Liseuse com_flipbook : feuilletage avec StPageFlip, chargement progressif des pages.
 *
 * @copyright  Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
(() => {
  'use strict';

  const init = () => {
    const root = document.getElementById('flipbook-reader');
    const opts = window.Joomla && Joomla.getOptions('com_flipbook.reader');

    if (!root || !opts || !window.St || !opts.visible) {
      return;
    }

    const t = (key, ...args) => {
      let s = Joomla.Text._(key, key);
      args.forEach((a) => { s = s.replace(/%[sd]/, a); });
      return s;
    };
    const $ = (role) => root.querySelector(`[data-role="${role}"]`);

    const stage = $('stage');
    const bookEl = $('book');
    const input = $('page-input');
    const thumbs = $('thumbs');
    const zoom = $('zoom');
    const zoomPages = $('zoom-pages');
    const toolbar = root.querySelector('.flipbook-toolbar');

    const visible = opts.visible;
    const restricted = visible < opts.pageCount;
    const ratio = opts.width / opts.height;
    const url = (n, size) => `${opts.imageUrl}&n=${n}&s=${size}`;

    // ---- Pages -------------------------------------------------------------
    const pages = [];

    for (let n = 1; n <= visible; n++) {
      const page = document.createElement('div');
      page.className = 'flipbook-page';
      page.dataset.n = String(n);

      const img = document.createElement('img');
      img.alt = t('COM_FLIPBOOK_PAGE_IMAGE', n + opts.offset);
      img.decoding = 'async';
      img.draggable = false;
      page.appendChild(img);
      pages.push(page);
    }

    // Pages réservées : une page d'avertissement à la place de la suite
    if (restricted) {
      const page = document.createElement('div');
      page.className = 'flipbook-page flipbook-page-restricted';
      page.innerHTML = `<div class="flipbook-restricted-inner"><span aria-hidden="true">🔒</span><p></p></div>`;
      page.querySelector('p').textContent = t('COM_FLIPBOOK_RESTRICTED_PAGE');
      pages.push(page);
    }

    // Couverture et dos rigides
    pages[0].dataset.density = 'hard';
    if (pages.length > 2) {
      pages[pages.length - 1].dataset.density = 'hard';
    }

    pages.forEach((p) => bookEl.appendChild(p));

    const loaded = new Set();
    const load = (index) => {
      if (index < 0 || index >= visible || loaded.has(index)) {
        return;
      }
      loaded.add(index);
      pages[index].querySelector('img').src = url(index + 1, 'm');
    };
    const ensure = (index) => {
      for (let i = index - opts.preload; i <= index + opts.preload + 1; i++) {
        load(i);
      }
    };

    // ---- Taille : le livre occupe la hauteur disponible de l'écran ------------
    const MIN_PAGE_WIDTH = 280;
    const fit = () => {
      const full = document.fullscreenElement === root;
      const avail = window.innerHeight - toolbar.offsetHeight - (thumbs.hidden ? 0 : thumbs.offsetHeight) - (full ? 24 : 40);
      // Une page (portrait) sous 2 × la largeur minimale, sinon une double page : pas de bande vide
      const width = stage.clientWidth;
      const pageWidth = width < 2 * MIN_PAGE_WIDTH ? width : width / 2;
      const height = Math.max(260, Math.min(avail, pageWidth / ratio));
      stage.style.height = `${Math.round(height)}px`;
    };
    window.addEventListener('resize', fit);
    fit();

    // ---- StPageFlip --------------------------------------------------------
    const flip = new St.PageFlip(bookEl, {
      width: opts.width,
      height: opts.height,
      size: 'stretch',
      minWidth: MIN_PAGE_WIDTH,
      maxWidth: opts.width * 2,
      minHeight: Math.round(MIN_PAGE_WIDTH / ratio),
      maxHeight: opts.height * 2,
      showCover: true,
      usePortrait: true,
      autoSize: false,
      drawShadow: true,
      maxShadowOpacity: 0.5,
      flippingTime: opts.flippingTime,
      mobileScrollSupport: false,
      showPageCorners: true,
      startPage: opts.start - 1,
    });

    ensure(opts.start - 1);
    flip.loadFromHTML(pages);

    // ---- Navigation --------------------------------------------------------
    const current = () => flip.getCurrentPageIndex();

    const update = (index) => {
      ensure(index);
      input.value = String(Math.min(index + 1, visible));

      const params = new URLSearchParams(window.location.search);
      params.set('page', String(Math.min(index + 1, visible) + opts.offset));
      window.history.replaceState(null, '', `${window.location.pathname}?${params}${window.location.hash}`);

      thumbs.querySelectorAll('[aria-current]').forEach((b) => b.removeAttribute('aria-current'));
      const thumb = thumbs.querySelector(`[data-index="${index}"]`);
      if (thumb) {
        thumb.setAttribute('aria-current', 'true');
      }
    };

    const goTo = (index) => {
      index = Math.max(0, Math.min(index, pages.length - 1));
      ensure(index);
      flip.flip(index);
    };

    flip.on('flip', (e) => update(e.data));
    flip.on('init', () => update(current()));
    update(opts.start - 1);

    const actions = {
      first: () => goTo(0),
      prev: () => flip.flipPrev(),
      next: () => flip.flipNext(),
      last: () => goTo(pages.length - 1),
      thumbs: (btn) => toggleThumbs(btn),
      zoom: () => openZoom(),
      'zoom-close': () => closeZoom(),
      fullscreen: () => toggleFullscreen(),
    };

    root.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-action]');
      if (btn && actions[btn.dataset.action]) {
        actions[btn.dataset.action](btn);
      }
    });

    input.addEventListener('change', () => goTo(parseInt(input.value, 10) - 1));

    document.addEventListener('keydown', (e) => {
      if (e.target.closest('input, textarea, select') || e.altKey || e.ctrlKey || e.metaKey) {
        return;
      }
      if (!zoom.hidden) {
        if (e.key === 'Escape') {
          closeZoom();
        }
        return;
      }
      if (e.key === 'ArrowLeft') {
        flip.flipPrev();
      } else if (e.key === 'ArrowRight') {
        flip.flipNext();
      } else {
        return;
      }
      e.preventDefault();
    });

    // ---- Vignettes ---------------------------------------------------------
    let thumbsBuilt = false;
    const toggleThumbs = (btn) => {
      if (!thumbsBuilt) {
        thumbsBuilt = true;
        for (let n = 1; n <= visible; n++) {
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'flipbook-thumb';
          b.dataset.index = String(n - 1);
          b.innerHTML = '<img loading="lazy" alt=""><span></span>';
          b.querySelector('img').src = url(n, 't');
          b.querySelector('span').textContent = String(n + opts.offset);
          b.title = t('COM_FLIPBOOK_PAGE_IMAGE', n + opts.offset);
          b.addEventListener('click', () => goTo(n - 1));
          thumbs.appendChild(b);
        }
        update(current());
      }
      thumbs.hidden = !thumbs.hidden;
      btn.setAttribute('aria-pressed', String(!thumbs.hidden));
      fit();
      if (!thumbs.hidden) {
        thumbs.querySelector('[aria-current]')?.scrollIntoView({ block: 'nearest', inline: 'center' });
      }
    };

    // ---- Zoom : page(s) affichée(s) en grande taille -------------------------
    const openZoom = () => {
      const index = current();
      const shown = [index];
      if (flip.getOrientation() === 'landscape' && index > 0 && index + 1 < visible) {
        shown.push(index + 1);
      }

      zoomPages.innerHTML = '';
      shown.filter((i) => i < visible).forEach((i) => {
        const img = document.createElement('img');
        img.src = url(i + 1, 'l');
        img.alt = t('COM_FLIPBOOK_PAGE_IMAGE', i + 1 + opts.offset);
        img.addEventListener('click', () => zoomPages.classList.toggle('is-large'));
        zoomPages.appendChild(img);
      });

      zoomPages.classList.remove('is-large');
      zoom.hidden = false;
      zoom.querySelector('[data-action="zoom-close"]').focus();
    };
    const closeZoom = () => {
      zoom.hidden = true;
      zoomPages.innerHTML = '';
      root.querySelector('[data-action="zoom"]').focus();
    };

    // ---- Plein écran -------------------------------------------------------
    const toggleFullscreen = () => {
      if (document.fullscreenElement) {
        document.exitFullscreen();
      } else if (root.requestFullscreen) {
        root.requestFullscreen();
      }
    };
    document.addEventListener('fullscreenchange', () => {
      root.classList.toggle('is-fullscreen', document.fullscreenElement === root);
      fit();
      // StPageFlip recalcule sa taille sur l'événement resize
      window.dispatchEvent(new Event('resize'));
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
