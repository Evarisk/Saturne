/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    js/modules/setupNav.js
 * \ingroup saturne
 * \brief   Setup pages of the modules: the sections of the tab listed on the side, the one in sight lit. Built from
 *          the titles of the page (load_fiche_titre), so that every setup page gets it without a line in the module.
 */

/**
 * Init setupNav JS
 *
 * @since   23.7.0
 * @version 23.7.0
 */
window.saturne.setupNav = {};

/**
 * Fewer sections than this: the list would only repeat the title of the page
 *
 * @type {number}
 */
window.saturne.setupNav.minSections = 2;

/**
 * Room left above a section reached from the list: the top bar of Dolibarr stays on the screen
 *
 * @type {number}
 */
window.saturne.setupNav.offset = 68;

/**
 * Narrower content than this beside the list: the list goes above it
 *
 * @type {number}
 */
window.saturne.setupNav.minContentWidth = 640;

/**
 * Section chosen in the list: it stays lit until the user scrolls himself
 *
 * @type {string}
 */
window.saturne.setupNav.chosen = '';

/**
 * SetupNav init
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @return {void}
 */
window.saturne.setupNav.init = function() {
  if (!window.saturne.setupNav.build()) {
    return;
  }
  window.saturne.setupNav.place();
  window.saturne.setupNav.event();
  window.saturne.setupNav.openHash();

  // Once the other scripts of the page ran: a select2 takes its width when built, after this one
  window.setTimeout(window.saturne.setupNav.place, 0);
};

/**
 * SetupNav event
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @return {void}
 */
window.saturne.setupNav.event = function() {
  $(document).on('click', '.saturne-setup-nav a', window.saturne.setupNav.goTo);
  $(document).on('keydown', window.saturne.setupNav.release);
  $(window).on('wheel touchmove', window.saturne.setupNav.release);
  $(window).on('scroll', window.saturne.setupNav.schedule);
  // The images of the page change its widths once loaded
  $(window).on('resize load', window.saturne.setupNav.replace);
};

/**
 * Put the list beside the content of the tab, on the setup pages that have enough sections
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @return {boolean} Whether the list was built
 */
window.saturne.setupNav.build = function() {
  // Setup pages only: the admin directory of a module, or the generic setup pages of Saturne
  if (window.location.pathname.indexOf('/admin/') === -1) {
    return false;
  }
  // The first one: a page may open its form before the tabs, the content is then not right under .fiche
  const $bar = $('.fiche .tabBar').first();
  if (!$bar.length || $bar.hasClass('saturne-setup-nav-bar')) {
    return false;
  }

  const sections = window.saturne.setupNav.getSections($bar);
  if (sections.length < window.saturne.setupNav.minSections) {
    return false;
  }

  $bar.addClass('saturne-setup-nav-bar').prepend(window.saturne.setupNav.render(sections));
  return true;
};

/**
 * The list beside the content when it has the room, above it otherwise. Dolibarr draws the page in a table cell: a
 * content that cannot get narrower (a long label kept on one line, a wide table) would make the whole page wider.
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @return {void}
 */
window.saturne.setupNav.place = function() {
  const $bar  = $('.saturne-setup-nav-bar');
  const root  = document.documentElement;
  const above = function() {
    $bar.removeClass('saturne-setup-nav-on').addClass('saturne-setup-nav-top');
  };

  const wasBeside = $bar.hasClass('saturne-setup-nav-on');
  above();
  const width = root.scrollWidth;
  $bar.removeClass('saturne-setup-nav-top').addClass('saturne-setup-nav-on');
  if (root.scrollWidth > Math.max(width, root.clientWidth) || $bar.width() < window.saturne.setupNav.minContentWidth) {
    above();
  }

  // The list moved: the chosen section moved with the content
  const $title = window.saturne.setupNav.getTitle(window.saturne.setupNav.chosen);
  if (wasBeside !== $bar.hasClass('saturne-setup-nav-on') && $title.length) {
    $(window).scrollTop($title.offset().top - window.saturne.setupNav.offset);
  }
  window.saturne.setupNav.spy();
};

/**
 * Place the list again once the window is resized
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @return {void}
 */
window.saturne.setupNav.replace = function() {
  window.clearTimeout(window.saturne.setupNav.resizeTimer);
  window.saturne.setupNav.resizeTimer = window.setTimeout(window.saturne.setupNav.place, 150);
};

/**
 * The sections of the tab: its visible titles, with their icon and their label. When some titles have an icon, those
 * without one are parts of the section above them (e.g. the numbering models of each object of the page).
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @param  {jQuery} $bar Content of the tab
 * @return {Array}       {id, label, $icon, child}
 */
window.saturne.setupNav.getSections = function($bar) {
  const sections = [];

  $bar.find('table.table-fiche-title').each(function() {
    const $table = $(this);
    const $titre = $table.find('.titre').first();
    if (!$titre.length || !$table.is(':visible')) {
      return;
    }

    // The picto of load_fiche_titre, or the icon written at the start of the title
    const $copy    = $titre.clone();
    const $leading = window.saturne.setupNav.getLeadingIcons($copy);
    let $icon      = $table.find('td.col-picto').children().first().clone();
    if (!$icon.length) {
      $icon = $leading.first().clone();
    }
    $leading.remove();
    $copy.find('.badge').remove();

    const label = $copy.text().replace(/\s+/g, ' ').trim();
    if (!label) {
      return;
    }

    if (!$table.attr('id')) {
      $table.attr('id', window.saturne.setupNav.getFreeId(label));
    }
    $table.addClass('saturne-setup-nav-target');

    sections.push({id: $table.attr('id'), label: label, $icon: $icon, child: false});
  });

  // Titles without an icon below one with an icon: parts of its section. Then each title without an icon gets the one
  // of its subject, only in the list
  let parent = false;
  sections.forEach(function(section) {
    if (section.$icon.length) {
      parent = true;
      return;
    }
    section.child = parent;
    section.$icon = $('<i></i>').addClass(window.saturne.setupNav.getIconClass(section.label));
  });

  return sections;
};

/**
 * Icons of the titles that have none, by the words of their label (French or English). The first match wins: the
 * precise subjects come before the general ones.
 *
 * @type {Array}
 */
window.saturne.setupNav.icons = [
  [/numerot|numbering/, 'fas fa-hashtag'],
  [/modeles? de documents?|document models?|document templates?/, 'far fa-file-alt'],
  [/\battributs?\b|extrafield|champs? supplementaire|complementary/, 'fas fa-th-list'],
  [/couleur|colou?r/, 'fas fa-palette'],
  [/rappel|remind|notif|alerte?s?\b/, 'far fa-bell'],
  [/e-?mail|courriel|\bmails?\b/, 'far fa-envelope'],
  [/qr ?code/, 'fas fa-qrcode'],
  [/interface publique|\bpublic/, 'fas fa-globe'],
  [/\bmedias?\b|photo|\bimages?\b|galerie|gallery/, 'far fa-image'],
  [/audio|vocal|voice/, 'fas fa-microphone'],
  [/categor|\btags?\b|etiquette/, 'fas fa-tags'],
  [/statisti|tableau de bord|dashboard/, 'fas fa-chart-bar'],
  [/kanban/, 'fas fa-columns'],
  [/historique|history/, 'fas fa-history'],
  [/rapide|quick/, 'fas fa-bolt'],
  [/document|fichier|\bfiles?\b|attachment/, 'far fa-file'],
  [/factur|invoic/, 'fas fa-file-invoice-dollar'],
  [/\bapi\b|webhook/, 'fas fa-plug'],
  [/reception|inbox|boite/, 'fas fa-inbox'],
  [/entrepot|warehouse|\bstocks?\b/, 'fas fa-warehouse'],
  [/signalement|probleme|incident|\bissues?\b/, 'fas fa-exclamation-triangle'],
  [/vehicule|vehicle|\bmarques?\b|\bbrands?\b/, 'fas fa-car'],
  [/groupes? d'utilisateurs?|user groups?|\bequipes?\b|\bteams?\b/, 'fas fa-users'],
  [/\bcomptes?\b|\baccounts?\b/, 'fas fa-user-circle'],
  [/utilisateur|\busers?\b|salarie|employee/, 'fas fa-user'],
  [/\blieux?\b|\blocations?\b|adresse|address|gp\/ut/, 'fas fa-map-marker-alt'],
  [/objectif|\bgoals?\b|\btargets?\b/, 'fas fa-bullseye'],
  [/projet|project/, 'fas fa-project-diagram'],
  [/tache|\btasks?\b/, 'fas fa-tasks'],
  [/\bthemes?\b|apparence|appearance/, 'fas fa-paint-brush'],
  [/archive/, 'fas fa-archive'],
  [/delai|duree|\btemps\b|\btimes?\b|deadline|horaire/, 'far fa-clock'],
  [/evenement|\bevents?\b|agenda|calend/, 'far fa-calendar-alt'],
  [/\bappels?\b|\bcalls?\b|telephone|\bphones?\b/, 'fas fa-phone'],
  [/application|\bpwa\b|mobile/, 'fas fa-mobile-alt'],
  [/produit|product|\bservices?\b/, 'fas fa-cube'],
  [/ticket/, 'fas fa-ticket-alt'],
  [/diffus|partage|\bshar|broadcast/, 'fas fa-share-alt'],
  [/signature/, 'fas fa-signature'],
  [/securite|security|\bacces\b|\baccess\b|\bdroits?\b|permission/, 'fas fa-shield-alt'],
  [/\bmenus?\b/, 'fas fa-bars'],
  [/affichage|display|visibilit/, 'fas fa-eye'],
  [/javascript|\bscripts?\b/, 'fas fa-code'],
  [/maintenance|\boutils?\b|\btools?\b/, 'fas fa-tools'],
  [/transfert|transfer/, 'fas fa-exchange-alt'],
  [/redirection|redirect/, 'fas fa-directions'],
  [/upload|\bimport/, 'fas fa-upload'],
  [/\bexport/, 'fas fa-download'],
  [/information|a propos|\babout\b/, 'fas fa-info-circle'],
  [/donnee|\bdata\b/, 'fas fa-database'],
  [/\blistes?\b|\blists?\b|listing/, 'fas fa-list'],
  [/configuration|parametre|reglage|setup|setting|option|general|fonctionnalite|feature/, 'fas fa-cog']
];

/**
 * Icon of a title that has none, by the words of its label
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @param  {string} label Label of the title
 * @return {string}       Classes of the icon
 */
window.saturne.setupNav.getIconClass = function(label) {
  const words = label.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[\u2019]/g, '\'').toLowerCase();
  const match = window.saturne.setupNav.icons.find(function(icon) {
    return icon[0].test(words);
  });
  return match ? match[1] : 'fas fa-sliders-h';
};

/**
 * Elements written before the text of a title: its icon (<i>, <img>, the coloured label of an object...)
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @param  {jQuery} $titre Copy of the title
 * @return {jQuery}        Elements
 */
window.saturne.setupNav.getLeadingIcons = function($titre) {
  // Down to the element holding the text: load_fiche_titre wraps it in a span
  let $holder = $titre;
  while ($holder.contents().length === 1 && $holder.children().length === 1) {
    $holder = $holder.children().first();
  }

  let icons = $();
  let done  = false;
  $holder.contents().each(function() {
    if (done) {
      return;
    }
    if (this.nodeType === 3) {
      done = $.trim(this.nodeValue) !== '';
    } else if (this.nodeType === 1) {
      const $node = $(this);
      if ($node.is('i, img, [class*="fa-"], .ref, [class*="picto"]')) {
        icons = icons.add($node);
      } else {
        done = true;
      }
    }
  });

  return icons;
};

/**
 * An id for a title, from its label, free in the page
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @param  {string} label Label of the title
 * @return {string}       Id
 */
window.saturne.setupNav.getFreeId = function(label) {
  const slug = 'section-' + (label.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'title');
  let id     = slug;
  for (let n = 2; $('#' + id).length; n++) {
    id = slug + '-' + n;
  }
  return id;
};

/**
 * The list on the side
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @param  {Array}  sections Sections
 * @return {jQuery}          Nav
 */
window.saturne.setupNav.render = function(sections) {
  const $list = $('<div class="saturne-setup-nav__list"></div>');

  sections.forEach(function(section) {
    const $link = $('<a class="saturne-setup-nav__item"></a>').attr({href: '#' + section.id, 'data-section': section.id, title: section.label});
    $link.toggleClass('saturne-setup-nav__item--child', section.child);

    // The classes of a title size and space the picto for it: the list gives it its own size
    const $icon = $('<span class="saturne-setup-nav__icon"></span>');
    section.$icon.removeAttr('id').removeClass(function(index, classes) {
      return classes.split(/\s+/).filter(function(name) {
        return /^(valignmiddle|pictotitle|widthpictotitle|pictofixedwidth|padding|margin)/.test(name);
      }).join(' ');
    });
    $icon.append(section.$icon);

    $list.append($link.append($icon, $('<span class="saturne-setup-nav__label"></span>').text(section.label)));
  });

  return $('<nav class="saturne-setup-nav"></nav>').append($list);
};

/**
 * Go to a section chosen in the list
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @param  {Event} event Click
 * @return {void}
 */
window.saturne.setupNav.goTo = function(event) {
  const id     = $(this).attr('data-section');
  const $title = window.saturne.setupNav.getTitle(id);
  if (!$title.length) {
    return;
  }
  event.preventDefault();

  // A section chosen in the list stays lit: a short one at the bottom of the page cannot go up to the top of the
  // window, the spy alone would light the last one
  window.saturne.setupNav.chosen = id;
  window.saturne.setupNav.light(id);
  $('html, body').stop().animate({scrollTop: $title.offset().top - window.saturne.setupNav.offset}, 300);
  if (window.history.replaceState) {
    window.history.replaceState(null, '', '#' + id);
  }
};

/**
 * An address with a section: the ids did not exist yet when the browser looked for it
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @return {void}
 */
window.saturne.setupNav.openHash = function() {
  const id     = window.location.hash.substring(1);
  const $title = window.saturne.setupNav.getTitle(id);
  if (id && $title.length) {
    window.saturne.setupNav.chosen = id;
    window.saturne.setupNav.light(id);
    $(window).scrollTop($title.offset().top - window.saturne.setupNav.offset);
  } else {
    window.saturne.setupNav.spy();
  }
};

/**
 * Title of a section listed on the side, compared as text: an id from the address is never read as a selector
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @param  {string} id Id of the title
 * @return {jQuery}    Title, empty when the list has no such section
 */
window.saturne.setupNav.getTitle = function(id) {
  return $('.saturne-setup-nav-target').filter(function() {
    return $(this).attr('id') === id;
  });
};

/**
 * The user scrolling himself: the spy takes over again
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @param  {Event} event Wheel, touch or key
 * @return {void}
 */
window.saturne.setupNav.release = function(event) {
  if (event.type === 'keydown' && ['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '].indexOf(event.key) === -1) {
    return;
  }
  if (window.saturne.setupNav.chosen) {
    window.saturne.setupNav.chosen = '';
    $('html, body').stop();
  }
};

/**
 * Follow the scroll once per frame
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @return {void}
 */
window.saturne.setupNav.schedule = function() {
  if (window.saturne.setupNav.pending) {
    return;
  }
  window.saturne.setupNav.pending = true;
  window.requestAnimationFrame(function() {
    window.saturne.setupNav.pending = false;
    window.saturne.setupNav.spy();
  });
};

/**
 * Light a section in the list
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @param  {string} id Id of its title
 * @return {void}
 */
window.saturne.setupNav.light = function(id) {
  const $list   = $('.saturne-setup-nav__list');
  const $links  = $list.find('a').removeClass('is-active');
  const $active = $links.filter(function() {
    return $(this).attr('data-section') === id;
  }).addClass('is-active');

  // A list longer than the window scrolls by itself: the lit section stays in it
  if ($active.length && $list[0].scrollHeight > $list[0].clientHeight) {
    const top = $active.position().top;
    if (top < 0 || top + $active.outerHeight() > $list.innerHeight()) {
      $list.scrollTop($list.scrollTop() + top - $list.innerHeight() / 2);
    }
  }
};

/**
 * The section in sight: the last one whose title went above the first third of the window
 *
 * @since   23.7.0
 * @version 23.7.0
 *
 * @return {void}
 */
window.saturne.setupNav.spy = function() {
  if (window.saturne.setupNav.chosen) {
    return;
  }
  const $titles = $('.saturne-setup-nav-target');
  const top     = $(window).scrollTop();
  const limit   = top + $(window).height() / 3;
  let current   = $titles.first().attr('id');
  $titles.each(function() {
    if ($(this).offset().top <= limit) {
      current = $(this).attr('id');
    }
  });
  // At the bottom of the page, the last section, too short to reach the third of the window
  if (top + $(window).height() >= $(document).height() - 4) {
    current = $titles.last().attr('id');
  }
  window.saturne.setupNav.light(current);
};
