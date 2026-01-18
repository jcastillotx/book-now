# Design System Rules

This document outlines the structure of the design system for the Book Now plugin. It provides guidelines for integrating Figma designs using the Model Context Protocol.

## 1. Token Definitions

There is no formal system for design tokens. All styling values are hardcoded in the CSS files. When implementing new designs, strive to use the existing values for consistency.

### Colors

The following colors are commonly used:

- `booknow-blue`: `#0073aa` (Primary color, WordPress default blue)
- `booknow-green`: `#5cb85c` / `#4caf50` (Success color)
- `booknow-white`: `#fff`
- `booknow-light-gray`: `#f8f9fa` / `#f3f3f3` / `#f0f8ff`
- `booknow-gray`: `#ddd` (Borders)
- `booknow-dark-gray`: `#666` (Text)
- `booknow-black`: `#333` (Headings/text)
- `booknow-red`: `#f44336` / `#c62828` (Error color)

**File Reference:**
- `public/css/book-now-public.css`
- `public/css/booking-wizard.css`

### Typography

- **Font Family**: Inherits from the active WordPress theme.
- **Font Sizes**: Common sizes are `14px`, `16px`, `18px`, `20px`, `24px`.
- **Font Weights**: `400` (normal), `500`, `600` (semibold), `bold`.

### Spacing & Sizing

- Spacing is managed with `margin` and `padding` using pixel values. Common values are `8px`, `10px`, `15px`, `20px`, `30px`.
- Layouts are often created using CSS Grid and Flexbox.

## 2. Component Library

The plugin does not use a component library in the modern sense (e.g., React or Vue components). Instead, UI is rendered through a combination of WordPress shortcodes and PHP partials.

- **Shortcodes**: Defined in `public/class-book-now-shortcodes.php`. They are the entry points for rendering UI on the frontend.
  - `[book_now_form]`
  - `[book_now_calendar]`
  - `[book_now_list]`
  - `[book_now_types]`
- **PHP Partials**: These files contain the HTML structure for the components. They are located in:
  - `public/partials/`
  - `admin/partials/`

When creating new UI, you should create a new PHP partial and, if necessary, a new shortcode to render it.

## 3. Frameworks & Libraries

- **UI Frameworks**: None. The frontend uses plain JavaScript and relies on jQuery, which is bundled with WordPress.
- **Styling Libraries**: None. Plain CSS is used.
- **Build System**: Webpack is used to bundle CSS and JavaScript assets. Configuration can be inferred from `package.json`.

## 4. Asset Management

The plugin does not currently use any image assets (`.jpg`, `.png`, `.svg`). The UI is built entirely with CSS and an icon font.

If new images are required, they should be placed in a new `public/images` directory and referenced from the CSS or PHP files.

## 5. Icon System

The plugin uses **WordPress Dashicons**, which is the icon font included with WordPress.

- **Implementation**: Icons are added with a `<span>` element.
- **Naming Convention**: Classes follow the format `dashicons dashicons-{icon-name}`.

**Example:**
```html
<span class="dashicons dashicons-calendar-alt"></span>
```

A list of available icons can be found in the [WordPress developer documentation](https://developer.wordpress.org/resource/dashicons/).

## 6. Styling Approach

- **Methodology**: The project uses a BEM-like naming convention for CSS classes (e.g., `booknow-form-wrapper`, `booknow-form-nav__button`). This is not strictly enforced but should be followed for new styles.
- **Global Styles**: There are no global style sheets for tokens. The CSS is modular, with styles loaded on pages where the plugin's shortcodes are present.
- **Responsive Design**: Media queries are used to implement responsive styles for smaller screens. The primary breakpoint is `600px`.

**Example:**
```css
@media screen and (max-width: 600px) {
    .booknow-form-wrapper {
        padding: 20px;
    }
}
```

## 7. Project Structure

The codebase is organized as a standard WordPress plugin:

- `admin/`: Contains the code for the WordPress admin dashboard area.
- `public/`: Contains the code for the public-facing parts of the plugin.
- `includes/`: Contains the core plugin logic, classes, and helper functions.
- `languages/`: Contains translation files.
- `vender/`: PHP dependencies managed by Composer.
- `node_modules/`: JavaScript dependencies managed by npm.
