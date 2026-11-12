/**
 * Bouton d'éditeur « Liens liseuse » : transforme les « p. 12 » du texte en liens vers les pages
 * d'un livre de la liseuse, ou retire ces liens.
 *
 * Les liens sont écrits sous la forme non SEF index.php?option=com_flipbook&view=book&id=…&page=…,
 * que le plugin SEF de Joomla transforme en adresse lisible à l'affichage.
 *
 * @copyright  Copyright (C) 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
(() => {
  'use strict';

  const root = document.getElementById('flipbook-links');
  const opts = window.Joomla && Joomla.getOptions('com_flipbook.links');

  if (!root || !opts) {
    return;
  }

  const t = (key, ...args) => {
    let s = Joomla.Text._(key, key);
    args.forEach((a) => { s = s.replace(/%[sd]/, a); });
    return s;
  };
  const $ = (role) => root.querySelector(`[data-role="${role}"]`);

  const search = $('search');
  const select = $('book');
  const apply = $('apply');
  const result = $('result');

  if (!select) {
    return;
  }

  // ---- Éditeur de la fenêtre parente (API Joomla 5/6, sinon l'ancienne) ----
  const editor = () => {
    const p = window.parent;
    if (p.JoomlaEditor && typeof p.JoomlaEditor.get === 'function') {
      const e = p.JoomlaEditor.get(opts.editor);
      if (e) {
        return e;
      }
    }
    return p.Joomla && p.Joomla.editors && p.Joomla.editors.instances ? p.Joomla.editors.instances[opts.editor] : null;
  };

  // ---- Filtre de la liste des livres ----
  search.addEventListener('input', () => {
    const q = search.value.trim().toLowerCase();
    [...select.options].forEach((o) => { o.hidden = q !== '' && !o.dataset.search.includes(q); });
  });
  select.addEventListener('dblclick', () => apply.click());

  // ---- Transformation ----
  const LINK = /(^|[?&])option=com_flipbook(&|&amp;)view=book\b/;
  const isBookLink = (a) => LINK.test(a.getAttribute('href') || '');
  // « p. 12 », « p.12 », « page 12 », « pages 12 » (le numéro seul est lié)
  const PAGE_RE = /(\bp\.\s?|\bpages?\s+)(\d{1,4})\b/gi;
  const href = (id, page) => `index.php?option=com_flipbook&view=book&id=${id}&page=${page}`;

  const transform = (html, id, mode) => {
    const doc = new DOMParser().parseFromString(`<div id="flipbook-root">${html}</div>`, 'text/html');
    const wrapper = doc.getElementById('flipbook-root');
    const stats = { added: 0, updated: 0, removed: 0 };

    // Liens déjà posés vers la liseuse
    wrapper.querySelectorAll('a[href]').forEach((a) => {
      if (!isBookLink(a)) {
        return;
      }
      if (mode === 'remove') {
        if (a.classList.contains('bulletin-page')) {
          // Case de page du sommaire : elle redevient un simple texte mis en forme
          const span = doc.createElement('span');
          span.className = a.className;
          span.textContent = a.textContent;
          a.replaceWith(span);
        } else {
          a.replaceWith(...a.childNodes);
        }
        stats.removed++;
      } else {
        const page = (a.getAttribute('href').match(/[?&](?:amp;)?page=(\d+)/) || [])[1];
        const next = page ? href(id, page) : href(id, '').replace(/&page=$/, '');
        if (a.getAttribute('href') !== next) {
          a.setAttribute('href', next);
          stats.updated++;
        }
      }
    });

    if (mode === 'remove') {
      return { html: wrapper.innerHTML, stats };
    }

    // Cases « p. 12 » du sommaire des bulletins : la case entière devient le lien
    wrapper.querySelectorAll('span.bulletin-page').forEach((span) => {
      const m = span.textContent.trim().match(/^p\.?\s*(\d{1,4})$/i);
      if (!m) {
        return;
      }
      const a = doc.createElement('a');
      a.className = span.className;
      a.setAttribute('href', href(id, m[1]));
      a.textContent = span.textContent;
      span.replaceWith(a);
      stats.added++;
    });

    // Autres « p. 12 » dans le texte, hors liens
    const walker = doc.createTreeWalker(wrapper, NodeFilter.SHOW_TEXT, {
      acceptNode: (node) => (node.parentElement.closest('a, script, style, code, pre')
        ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT),
    });
    const nodes = [];
    while (walker.nextNode()) {
      PAGE_RE.lastIndex = 0;
      if (PAGE_RE.test(walker.currentNode.nodeValue)) {
        nodes.push(walker.currentNode);
      }
    }

    nodes.forEach((node) => {
      const text = node.nodeValue;
      const fragment = doc.createDocumentFragment();
      let last = 0;
      PAGE_RE.lastIndex = 0;
      let m;
      while ((m = PAGE_RE.exec(text)) !== null) {
        fragment.append(text.slice(last, m.index));
        const a = doc.createElement('a');
        a.setAttribute('href', href(id, m[2]));
        a.textContent = m[0];
        fragment.append(a);
        stats.added++;
        last = m.index + m[0].length;
      }
      fragment.append(text.slice(last));
      node.replaceWith(fragment);
    });

    return { html: wrapper.innerHTML, stats };
  };

  apply.addEventListener('click', () => {
    const ed = editor();
    const mode = root.querySelector('input[name="flipbook-links-mode"]:checked').value;
    const option = select.selectedOptions[0];

    if (!ed) {
      result.textContent = t('COM_FLIPBOOK_LINKS_NO_EDITOR');
      return;
    }
    if (mode === 'add' && !option) {
      result.textContent = t('COM_FLIPBOOK_LINKS_CHOOSE');
      select.focus();
      return;
    }

    const id = option ? option.value : 0;
    const { html, stats } = transform(ed.getValue(), id, mode);
    ed.setValue(html);

    let message;
    if (mode === 'remove') {
      message = stats.removed ? t('COM_FLIPBOOK_LINKS_REMOVED', stats.removed) : t('COM_FLIPBOOK_LINKS_NONE');
    } else if (stats.added || stats.updated) {
      message = t('COM_FLIPBOOK_LINKS_DONE', stats.added, option.textContent.trim().split(/\s+—\s+/)[0]);
      if (stats.updated) {
        message += ' ' + t('COM_FLIPBOOK_LINKS_UPDATED', stats.updated);
      }
    } else {
      message = t('COM_FLIPBOOK_LINKS_NONE');
    }

    // Message dans la page de l'éditeur, puis fermeture de la fenêtre
    const p = window.parent;
    if (p.Joomla && p.Joomla.renderMessages) {
      if (p.Joomla.removeMessages) {
        p.Joomla.removeMessages();
      }
      // Certains gabarits du site gardent les anciens messages : on vide le conteneur
      const box = p.document.getElementById('system-message-container');
      if (box) {
        box.replaceChildren();
      }
      p.Joomla.renderMessages({ message: [message] });
    }
    result.textContent = message;

    const modal = p.Joomla && p.Joomla.Modal && p.Joomla.Modal.getCurrent && p.Joomla.Modal.getCurrent();
    if (modal) {
      modal.close();
    }
  });
})();
