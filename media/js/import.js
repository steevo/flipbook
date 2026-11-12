/**
 * Import d'un PDF dans un livre de la liseuse : pdf.js rend chaque page dans le navigateur,
 * en trois tailles (l, m, t), envoyées une page à la fois au serveur.
 *
 * @copyright  Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
const root = document.getElementById('flipbook-import');
const opts = window.Joomla && Joomla.getOptions('com_flipbook.import');

if (root && opts) {
  const t = (key, ...args) => {
    let s = Joomla.Text._(key, key);
    args.forEach((a) => { s = s.replace(/%[sd]/, a); });
    return s;
  };
  const $ = (role) => root.querySelector(`[data-role="${role}"]`);

  const fileInput = $('file');
  const replace = $('replace');
  const go = $('go');
  const progress = $('progress');
  const bar = progress.querySelector('.progress-bar');
  const status = $('status');
  const errors = $('errors');
  const preview = $('preview');

  // Source du PDF : fichier de l'ordinateur ou fichier de Phoca Download (si proposé)
  const phoca = $('phoca');
  const sources = [...root.querySelectorAll('[data-role="source"]')];
  const source = () => (sources.find((r) => r.checked) || { value: 'local' }).value;
  const ready = () => (source() === 'phoca' ? !!(phoca && phoca.value) : fileInput.files.length > 0);

  let running = false;

  const refresh = () => {
    root.querySelectorAll('[data-source]').forEach((el) => { el.hidden = el.dataset.source !== source(); });
    go.disabled = running || !ready();
  };

  fileInput.addEventListener('change', refresh);
  if (phoca) {
    phoca.addEventListener('change', refresh);
  }
  sources.forEach((r) => r.addEventListener('change', refresh));
  refresh();

  // PDF de Phoca Download, transmis par le serveur (son dossier n'est pas lisible directement)
  const download = async (id) => {
    const body = new FormData();
    body.append(opts.token, '1');
    body.append('file', id);

    const response = await fetch(opts.source, { method: 'POST', body, credentials: 'same-origin' });

    if (!response.ok || !(response.headers.get('Content-Type') || '').includes('application/pdf')) {
      const json = await response.json().catch(() => null);
      throw new Error((json && json.message) || `HTTP ${response.status}`);
    }

    return response.arrayBuffer();
  };

  window.addEventListener('beforeunload', (e) => {
    if (running) {
      e.preventDefault();
      e.returnValue = t('COM_FLIPBOOK_IMPORT_LEAVE');
    }
  });

  const setProgress = (done, total) => {
    const pct = total ? Math.round((done / total) * 100) : 0;
    bar.style.width = `${pct}%`;
    bar.textContent = `${pct} %`;
    progress.setAttribute('aria-valuenow', String(pct));
  };

  const addError = (message) => {
    const li = document.createElement('li');
    li.textContent = message;
    errors.appendChild(li);
  };

  // Envoi au serveur, avec le jeton CSRF ; une nouvelle tentative en cas d'échec réseau
  const post = async (url, fields, tries = 2) => {
    const body = new FormData();
    body.append(opts.token, '1');
    Object.entries(fields).forEach(([k, v]) => {
      if (Array.isArray(v)) {
        body.append(k, v[0], v[1]);
      } else {
        body.append(k, v);
      }
    });

    let lastError;
    for (let i = 0; i < tries; i++) {
      try {
        const response = await fetch(url, { method: 'POST', body, credentials: 'same-origin' });
        const json = await response.json().catch(() => null);

        if (!response.ok || !json || !json.success) {
          throw new Error((json && json.message) || `HTTP ${response.status}`);
        }
        return json.data;
      } catch (e) {
        lastError = e;
      }
    }
    throw lastError;
  };

  // WebP si le navigateur sait l'encoder (pas Safari ancien), sinon JPEG
  const toBlob = (canvas, quality) => new Promise((resolve) => {
    canvas.toBlob((blob) => {
      if (blob && blob.type === 'image/webp') {
        resolve(blob);
      } else {
        canvas.toBlob(resolve, 'image/jpeg', quality);
      }
    }, 'image/webp', quality);
  });

  const scaled = (source, height) => {
    const ratio = height / source.height;
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(source.width * ratio));
    canvas.height = Math.max(1, Math.round(height));
    const ctx = canvas.getContext('2d');
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(source, 0, 0, canvas.width, canvas.height);
    return canvas;
  };

  const renderPage = async (doc, n) => {
    const page = await doc.getPage(n);
    const base = page.getViewport({ scale: 1 });
    const viewport = page.getViewport({ scale: opts.sizes.l / base.height });

    const large = document.createElement('canvas');
    large.width = Math.round(viewport.width);
    large.height = Math.round(viewport.height);
    const ctx = large.getContext('2d');
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, large.width, large.height);

    await page.render({ canvasContext: ctx, viewport }).promise;
    page.cleanup();

    const medium = scaled(large, opts.sizes.m);
    const thumb = scaled(medium, opts.sizes.t);

    const blobs = {
      l: await toBlob(large, opts.quality.l),
      m: await toBlob(medium, opts.quality.m),
      t: await toBlob(thumb, opts.quality.t),
    };

    // Aperçu de la page en cours
    preview.hidden = false;
    preview.width = thumb.width;
    preview.height = thumb.height;
    preview.getContext('2d').drawImage(thumb, 0, 0);

    large.width = large.height = medium.width = medium.height = 0;

    return blobs;
  };

  go.addEventListener('click', async () => {
    const fromPhoca = source() === 'phoca';
    const file = fileInput.files[0];

    if (running || !ready()) {
      return;
    }

    if (replace.checked && !window.confirm(t('COM_FLIPBOOK_IMPORT_CONFIRM_REPLACE'))) {
      return;
    }

    running = true;
    go.disabled = true;
    const controls = [fileInput, replace, phoca, ...sources].filter(Boolean);
    controls.forEach((c) => { c.disabled = true; });
    errors.innerHTML = '';
    progress.hidden = false;
    setProgress(0, 1);

    try {
      let data;
      if (fromPhoca) {
        status.textContent = t('COM_FLIPBOOK_IMPORT_DOWNLOADING');
        data = await download(phoca.value);
      } else {
        data = await file.arrayBuffer();
      }

      status.textContent = t('COM_FLIPBOOK_IMPORT_READING');
      const pdfjs = await import(opts.pdfjs);
      pdfjs.GlobalWorkerOptions.workerSrc = opts.worker;

      const doc = await pdfjs.getDocument({ data }).promise;
      const total = doc.numPages;

      await post(opts.start, { replace: replace.checked ? '1' : '0' });

      for (let n = 1; n <= total; n++) {
        status.textContent = t('COM_FLIPBOOK_IMPORT_PROGRESS', n, total);

        const blobs = await renderPage(doc, n);
        const name = String(n).padStart(3, '0');
        const ext = (b) => (b.type === 'image/webp' ? 'webp' : 'jpg');

        await post(opts.page, {
          n: String(n),
          'page[l]': [blobs.l, `${name}.${ext(blobs.l)}`],
          'page[m]': [blobs.m, `${name}.${ext(blobs.m)}`],
          'page[t]': [blobs.t, `${name}.${ext(blobs.t)}`],
        });

        setProgress(n, total);
      }

      await doc.destroy();
      const result = await post(opts.finish, {});
      status.textContent = t('COM_FLIPBOOK_IMPORT_DONE', result.pages);

      // Nombre de pages affiché dans l'onglet Livre
      const count = document.getElementById('jform_page_count');
      if (count) {
        count.value = result.pages;
      }
    } catch (e) {
      status.textContent = t('COM_FLIPBOOK_IMPORT_FAILED');
      addError(e.message || String(e));
    } finally {
      running = false;
      controls.forEach((c) => { c.disabled = false; });
      refresh();
    }
  });
}
