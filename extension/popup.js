import { RADAR_URL } from './config.js';

const MAX_CATEGORIES = 3;

const state = { key: null, categories: [], selected: new Set(), duplicate: null };

const byTest = (id) => document.querySelector(`[data-test="${id}"]`);

class InvalidKeyError extends Error {}

async function api(method, path, body = null) {
    let response;
    try {
        response = await fetch(`${RADAR_URL}/api/extension${path}`, {
            method,
            headers: {
                Accept: 'application/ld+json',
                Authorization: `Bearer ${state.key}`,
                ...(body ? { 'Content-Type': 'application/ld+json' } : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    } catch {
        throw new Error('Radar est injoignable : vérifiez votre connexion.');
    }

    if (401 === response.status) {
        throw new InvalidKeyError();
    }
    if (429 === response.status) {
        throw new Error('Trop de clés refusées depuis votre réseau : réessayez dans quelques minutes.');
    }

    const data = (response.headers.get('Content-Type') ?? '').includes('json') ? await response.json() : null;

    return { status: response.status, data };
}

function violations(data) {
    return (data?.violations ?? []).map((violation) => violation.message);
}

function show(screen) {
    document.querySelectorAll('[data-screen]').forEach((section) => {
        section.hidden = section.dataset.screen !== screen;
    });
    if ('key' === screen) {
        byTest('extension-key-input').focus();
    }
}

function message(text) {
    const element = byTest('extension-message');
    element.textContent = text ?? '';
    element.hidden = !text;
}

async function guard(action) {
    message(null);
    try {
        await action();
    } catch (error) {
        if (error instanceof InvalidKeyError) {
            show('invalid-key');

            return;
        }
        message(error.message);
    }
}

async function currentPage() {
    const params = new URLSearchParams(location.search);
    if (params.has('url')) {
        return params.get('url');
    }

    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

    return tab?.url ?? '';
}

function renderCategories() {
    const query = byTest('extension-category-query').value.trim().toLocaleLowerCase('fr');
    const full = state.selected.size >= MAX_CATEGORIES;
    const container = byTest('extension-categories');
    container.replaceChildren(
        ...state.categories.map((category) => {
            const pill = document.createElement('button');
            const selected = state.selected.has(category.id);
            pill.type = 'button';
            pill.className = 'pill';
            pill.textContent = category.name;
            pill.dataset.test = 'extension-category';
            pill.setAttribute('aria-pressed', String(selected));
            pill.disabled = full && !selected;
            pill.title = pill.disabled ? 'Trois catégories au plus' : '';
            pill.hidden = '' !== query && !category.name.toLocaleLowerCase('fr').includes(query);
            pill.addEventListener('click', () => {
                selected ? state.selected.delete(category.id) : state.selected.add(category.id);
                renderCategories();
                renderDuplicate();
            });

            return pill;
        }),
    );
}

function categoryError(text) {
    const element = byTest('extension-category-error');
    element.textContent = text ?? '';
    element.hidden = !text;
}

async function pickCategory(name) {
    categoryError(null);
    let category = state.categories.find((item) => item.name.toLocaleLowerCase('fr') === name.toLocaleLowerCase('fr'));
    if (!category) {
        const { status, data } = await api('POST', '/categories', { name });
        if (201 !== status) {
            categoryError(violations(data).join(' ') || 'Catégorie non créée.');

            return;
        }
        category = { id: data.id, name: data.name };
        state.categories = [...state.categories, category].sort((a, b) => a.name.localeCompare(b.name, 'fr'));
    }

    if (!state.selected.has(category.id)) {
        if (state.selected.size >= MAX_CATEGORIES) {
            categoryError(`« ${category.name} » est disponible : désélectionnez une catégorie pour la retenir.`);
        } else {
            state.selected.add(category.id);
        }
    }

    byTest('extension-category-query').value = '';
    renderCategories();
    renderDuplicate();
}

async function checkDuplicate(notice = null) {
    const url = byTest('extension-url').value.trim();
    state.duplicate = null;
    if ('' !== url) {
        const { status, data } = await api('GET', `/duplicate?url=${encodeURIComponent(url)}`);
        state.duplicate = 200 === status ? data : null;
    }
    byTest('extension-duplicate-notice').textContent = notice ?? '';
    byTest('extension-duplicate-notice').hidden = !notice;
    renderDuplicate();
}

function duplicateCandidates() {
    const present = new Set(state.duplicate.categories.map((category) => category.id));

    return state.categories.filter((category) => state.selected.has(category.id) && !present.has(category.id));
}

function renderDuplicate() {
    const duplicate = state.duplicate;
    byTest('extension-duplicate').hidden = !duplicate;
    byTest('extension-submit').disabled = Boolean(duplicate);
    if (!duplicate) {
        return;
    }

    byTest('extension-duplicate-link-title').textContent = duplicate.title ?? byTest('extension-url').value;
    byTest('extension-duplicate-show').href = duplicate.cardUrl;

    const candidates = duplicateCandidates();
    const completion = byTest('extension-duplicate-completion');
    completion.hidden = 0 === candidates.length || 0 === duplicate.freeSlots;
    const fits = candidates.length <= duplicate.freeSlots;
    const names = candidates.map((category) => category.name).join(', ');
    byTest('extension-duplicate-candidates').textContent = fits
        ? `Ajouter vos catégories à ce lien : ${names}.`
        : `Il reste ${duplicate.freeSlots} place${duplicate.freeSlots > 1 ? 's' : ''} sur ce lien pour vos ${candidates.length} catégories : décochez-en ${candidates.length - duplicate.freeSlots} pour les y ajouter.`;
    byTest('extension-duplicate-complete').hidden = !fits;
}

function done(title, cardUrl, announced) {
    document.querySelector('.done-title').textContent = title;
    document.querySelector('[data-screen="done"] .hint').hidden = !announced;
    byTest('extension-card-link').href = cardUrl;
    show('done');
}

function formErrors(messages) {
    const list = byTest('extension-errors');
    list.replaceChildren(
        ...messages.map((text) => {
            const item = document.createElement('li');
            item.textContent = text;

            return item;
        }),
    );
    list.hidden = 0 === messages.length;
}

function isNewer(available, current) {
    const [left, right] = [available, current].map((version) => version.split('.').map(Number));
    for (let index = 0; index < Math.max(left.length, right.length); index++) {
        const difference = (left[index] ?? 0) - (right[index] ?? 0);
        if (0 !== difference) {
            return difference > 0;
        }
    }

    return false;
}

async function checkVersion() {
    try {
        const { status, data } = await api('GET', '/version');
        byTest('extension-update').hidden = 200 !== status || !isNewer(data.version, chrome.runtime.getManifest().version);
    } catch {
        // L'alerte reste un confort : son échec ne doit jamais gêner l'ajout.
    }
}

async function openForm() {
    const { status, data } = await api('GET', '/categories');
    if (200 !== status) {
        throw new Error('Radar ne répond pas : réessayez dans un instant.');
    }
    checkVersion();
    state.categories = data.member.map((category) => ({ id: category.id, name: category.name }));
    renderCategories();
    byTest('extension-url').value = await currentPage();
    await checkDuplicate();
    show('form');
    byTest('extension-context').focus();
}

async function submitLink(event) {
    event.preventDefault();
    formErrors([]);
    const { status, data } = await api('POST', '/links', {
        url: byTest('extension-url').value.trim(),
        context: byTest('extension-context').value,
        categories: [...state.selected],
    });

    if (201 === status) {
        done('Lien ajouté.', data.cardUrl, true);
    } else if (409 === status) {
        await checkDuplicate();
    } else {
        formErrors(violations(data).length > 0 ? violations(data) : [data?.detail ?? 'Le lien n\'a pas pu être ajouté.']);
    }
}

async function completeDuplicate() {
    const ids = duplicateCandidates().map((category) => category.id);
    const { status, data } = await api('POST', `/duplicate/${state.duplicate.id}/categories`, { categories: ids });
    if (200 === status) {
        done('Catégories ajoutées.', data.cardUrl, false);

        return;
    }

    await checkDuplicate('Ce lien vient d\'être complété par quelqu\'un d\'autre : vérifiez ses catégories.');
}

async function saveKey(event) {
    event.preventDefault();
    const key = byTest('extension-key-input').value.trim();
    if ('' === key) {
        return;
    }

    state.key = key;
    await chrome.storage.local.set({ key });
    byTest('extension-key-input').value = '';
    await openForm();
}

function debounce(callback, delay) {
    let timer;

    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => callback(...args), delay);
    };
}

byTest('extension-server').textContent = new URL(RADAR_URL).host;
document.querySelectorAll('[data-radar-link]').forEach((link) => {
    link.href = RADAR_URL + link.dataset.radarLink;
});
document.querySelectorAll('[data-action="change-key"]').forEach((button) => {
    button.addEventListener('click', () => {
        message(null);
        show('key');
    });
});
byTest('extension-key-form').addEventListener('submit', (event) => guard(() => saveKey(event)));
byTest('extension-link-form').addEventListener('submit', (event) => guard(() => submitLink(event)));
byTest('extension-duplicate-complete').addEventListener('click', () => guard(completeDuplicate));
byTest('extension-category-query').addEventListener('input', renderCategories);
byTest('extension-category-query').addEventListener('keydown', (event) => {
    if ('Enter' !== event.key) {
        return;
    }
    event.preventDefault();
    const name = event.target.value.trim();
    if ('' !== name) {
        guard(() => pickCategory(name));
    }
});
byTest('extension-url').addEventListener('input', debounce(() => guard(() => checkDuplicate()), 400));

guard(async () => {
    state.key = (await chrome.storage.local.get('key')).key ?? null;
    if (null === state.key) {
        show('key');

        return;
    }

    await openForm();
});
