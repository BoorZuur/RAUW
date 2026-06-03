# RAUW Web App

React/Vite web app for RAUW.

## Local commands

Run installs and scripts from this directory:

```bash
cd apps/web
npm install
npm run dev
npm run build
npm run lint
```

## HTTP requests (axios)

This app uses [axios](https://axios-http.com/) for HTTP requests.

```js
import axios from 'axios';

const { data } = await axios.get('/api/example');
```

For repeated use, create a configured instance:

```js
// src/lib/api.js
import axios from 'axios';

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
});
```

## Tailwind CSS

This app uses Tailwind CSS v4 through the `@tailwindcss/vite` plugin in `vite.config.js`:

```js
plugins: [react(), tailwindcss()]
```

Tailwind is loaded from `src/index.css` with:

```css
@import 'tailwindcss';
```

Tailwind preflight can affect browser defaults and element styling. Check `src/index.css` first when default spacing, typography, borders, or form behavior changes unexpectedly.

## Styling conventions

Use styles in this order:

1. **Tailwind utilities** for one-off layout, spacing, color, and state styles directly in JSX.
2. **Custom CSS classes** for reusable, complex, or semantic styles that should not be repeated as long utility lists.
3. **CSS variables and base defaults** in `src/index.css` for shared design tokens, root styles, element defaults, and global layout.

### CSS locations

- `src/index.css`: Tailwind import, shared CSS variables, global/base selectors, and app-wide defaults.
- `src/App.css`: app-level or component-specific classes used by the current `App` implementation.
- Future component CSS: place styles near the component when they are only used by that component.

### Naming and selectors

- Name custom classes with descriptive kebab-case, for example `.hero-card` or `.feature-list`.
- Use IDs only for stable page landmarks or anchors, not for routine styling hooks.
- Avoid custom class names that look like Tailwind utilities or accidentally override Tailwind-generated utilities.
- Prefer CSS variables for shared tokens such as colors, borders, shadows, fonts, and spacing values.

### Adding a custom class

1. Confirm the style is reusable, complex, or semantic enough to justify CSS instead of Tailwind utilities.
2. Add the class in the closest appropriate CSS file: `index.css` for global/base styles, `App.css` for current app-specific styles, or a component-local CSS file for future components.
3. Use CSS variables from `:root` when referencing shared tokens.
4. Verify responsive behavior and dark-mode behavior, especially when using media queries or color variables.
