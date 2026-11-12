/**
 * Sommaire d'un livre : lit le texte de la page du sommaire dans le PDF (pdf.js) et le met au format
 * du champ « Sommaire » de la fiche :
 *
 *   # Rubrique
 *   13 | Titre de l'article | Auteur (facultatif)
 *
 * Reconnaît les sommaires du type « 13 Titre » (numéro de page puis titre), avec des rubriques en
 * majuscules et des titres sur plusieurs lignes. Le résultat reste modifiable à la main.
 *
 * @copyright  Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
const root = document.getElementById('flipbook-toc');
const opts = window.Joomla && Joomla.getOptions('com_flipbook.import');

/**
 * Lignes de texte d'une page du PDF → texte du sommaire
 */
export const parseToc = (lines) => {
  const clean = (s) => s
    .replace(/\s+/g, ' ')
    .replace(/(\d)\s+(e|es|er|ers|re|res|ème|èmes)\b/gi, '$1$2') // « 75 e » → « 75e »
    .trim();
  const letters = (s) => s.replace(/[^A-Za-zÀ-ÖØ-öø-ÿŒœ]/g, '');
  const isUpper = (s) => letters(s).length >= 3 && s === s.toLocaleUpperCase('fr');
  const sentence = (s) => {
    const lower = s.toLocaleLowerCase('fr');
    return lower.charAt(0).toLocaleUpperCase('fr') + lower.slice(1);
  };

  const out = [];
  let last = null;

  lines.map(clean).filter(Boolean).forEach((line) => {
    // En-têtes et renvois : « SOMMAIRE … », « P. 13 », numéro de page seul
    if (/sommaire|contents/i.test(line) || /^p\.?\s*\d+$/i.test(line) || /^\d+$/.test(line)) {
      return;
    }

    const entry = line.match(/^(\d{1,3})\s+(.+)$/);

    if (entry) {
      last = { page: parseInt(entry[1], 10), title: entry[2] };
      out.push(last);
    } else if (isUpper(line)) {
      last = null;
      out.push({ section: sentence(line) });
    } else if (last) {
      // Suite du titre précédent (titre sur plusieurs lignes)
      last.title = last.title.endsWith('-') ? last.title.slice(0, -1) + line : `${last.title} ${line}`;
    }
  });

  return out.map((e) => (e.section ? `# ${e.section}` : `${e.page} | ${e.title}`)).join('\n');
};

if (root && opts) {
  const t = (key, ...args) => {
    let s = Joomla.Text._(key, key);
    args.forEach((a) => { s = s.replace(/%[sd]/, a); });
    return s;
  };
  const $ = (role) => root.querySelector(`[data-role="${role}"]`);

  const generate = $('generate');
  const status = $('status');
  const fileInput = $('file');
  const phoca = $('phoca');
  const sources = [...root.querySelectorAll('[data-role="source"]')];
  const source = () => (sources.find((r) => r.checked) || { value: 'local' }).value;
  const field = document.getElementById('jform_toc');
  const pageField = document.getElementById('jform_toc_page');
  const offsetField = document.getElementById('jform_page_offset');

  const refresh = () => {
    root.querySelectorAll('[data-source]').forEach((el) => { el.hidden = el.dataset.source !== source(); });
  };
  sources.forEach((r) => r.addEventListener('change', refresh));
  refresh();

  // PDF : fichier de l'ordinateur, ou fichier Phoca Download transmis par le serveur
  const pdfData = async () => {
    if (source() !== 'phoca') {
      if (!fileInput.files.length) {
        throw new Error(t('COM_FLIPBOOK_IMPORT_CHOOSE'));
      }
      return fileInput.files[0].arrayBuffer();
    }

    if (!phoca.value) {
      throw new Error(t('COM_FLIPBOOK_IMPORT_CHOOSE'));
    }

    const body = new FormData();
    body.append(opts.token, '1');
    body.append('file', phoca.value);
    const response = await fetch(opts.source, { method: 'POST', body, credentials: 'same-origin' });

    if (!response.ok || !(response.headers.get('Content-Type') || '').includes('application/pdf')) {
      const json = await response.json().catch(() => null);
      throw new Error((json && json.message) || `HTTP ${response.status}`);
    }

    return response.arrayBuffer();
  };

  // Lignes de texte d'une page : pdf.js signale les fins de ligne (hasEOL)
  const pageLines = async (doc, n) => {
    const content = await (await doc.getPage(n)).getTextContent();
    const lines = [''];
    content.items.forEach((item) => {
      lines[lines.length - 1] += item.str;
      if (item.hasEOL) {
        lines.push('');
      } else if (item.str && !item.str.endsWith(' ')) {
        lines[lines.length - 1] += ' ';
      }
    });
    return lines;
  };

  generate.addEventListener('click', async () => {
    const printed = parseInt(pageField.value, 10);

    if (!printed) {
      status.textContent = t('COM_FLIPBOOK_TOC_NO_PAGE');
      pageField.focus();
      return;
    }

    generate.disabled = true;
    status.className = 'mb-0';

    try {
      status.textContent = source() === 'phoca' ? t('COM_FLIPBOOK_IMPORT_DOWNLOADING') : t('COM_FLIPBOOK_IMPORT_READING');
      const data = await pdfData();

      status.textContent = t('COM_FLIPBOOK_IMPORT_READING');
      const pdfjs = await import(opts.pdfjs);
      pdfjs.GlobalWorkerOptions.workerSrc = opts.worker;
      const doc = await pdfjs.getDocument({ data }).promise;

      // Numéro imprimé → rang de la page dans le PDF
      const index = printed - (parseInt(offsetField && offsetField.value, 10) || 0);

      if (index < 1 || index > doc.numPages) {
        await doc.destroy();
        throw new Error(t('COM_FLIPBOOK_TOC_PAGE_OUT', printed, doc.numPages));
      }

      const text = parseToc(await pageLines(doc, index));
      await doc.destroy();

      if (!text) {
        throw new Error(t('COM_FLIPBOOK_TOC_NO_TEXT'));
      }

      if (field.value.trim() !== '' && !window.confirm(t('COM_FLIPBOOK_TOC_CONFIRM_REPLACE'))) {
        status.textContent = '';
        return;
      }

      field.value = text;
      field.dispatchEvent(new Event('change', { bubbles: true }));
      status.textContent = t('COM_FLIPBOOK_TOC_DONE', text.split('\n').filter((l) => !l.startsWith('#')).length);
    } catch (e) {
      status.className = 'mb-0 text-danger';
      status.textContent = e.message || String(e);
    } finally {
      generate.disabled = false;
    }
  });
}
