---
name: Al-Huda Academic System
colors:
  surface: '#f9f9ff'
  surface-dim: '#d3daef'
  surface-bright: '#f9f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f1f3ff'
  surface-container: '#e9edff'
  surface-container-high: '#e1e8fd'
  surface-container-highest: '#dce2f7'
  on-surface: '#141b2b'
  on-surface-variant: '#3f493f'
  inverse-surface: '#293040'
  inverse-on-surface: '#edf0ff'
  outline: '#6f7a6e'
  outline-variant: '#becabc'
  surface-tint: '#006d30'
  primary: '#00652c'
  on-primary: '#ffffff'
  primary-container: '#15803d'
  on-primary-container: '#d3ffd5'
  inverse-primary: '#79db8d'
  secondary: '#396845'
  on-secondary: '#ffffff'
  secondary-container: '#b8ecbf'
  on-secondary-container: '#3d6d49'
  tertiary: '#006431'
  on-tertiary: '#ffffff'
  tertiary-container: '#008040'
  on-tertiary-container: '#cfffd5'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#95f8a7'
  primary-fixed-dim: '#79db8d'
  on-primary-fixed: '#00210a'
  on-primary-fixed-variant: '#005323'
  secondary-fixed: '#bbefc2'
  secondary-fixed-dim: '#9fd3a7'
  on-secondary-fixed: '#00210c'
  on-secondary-fixed-variant: '#21502f'
  tertiary-fixed: '#6dfe9c'
  tertiary-fixed-dim: '#4de082'
  on-tertiary-fixed: '#00210c'
  on-tertiary-fixed-variant: '#005227'
  background: '#f9f9ff'
  on-background: '#141b2b'
  surface-variant: '#dce2f7'
  deep-forest-green: '#0B3D2E'
  emerald-green: '#1E7145'
  accent-green: '#219653'
  pale-mint: '#E8F5EC'
  light-green: '#DCEFE2'
  mint: '#A7F3D0'
  pale-green: '#BBF7D0'
  gray: '#6B7280'
  slate: '#94A3B8'
  border: '#E1ECE5'
  soft: '#F9FAFB'
  amber: '#B45309'
  red: '#DC2626'
  blue: '#1D4ED8'
typography:
  hero-publik:
    fontFamily: Plus Jakarta Sans
    fontSize: 54px
    fontWeight: '800'
    lineHeight: '0.99'
    letterSpacing: -2.4px
  section-heading:
    fontFamily: Plus Jakarta Sans
    fontSize: 25px
    fontWeight: '800'
    lineHeight: '1.15'
    letterSpacing: -0.8px
  page-title:
    fontFamily: Plus Jakarta Sans
    fontSize: 21px
    fontWeight: '800'
    lineHeight: '1.25'
    letterSpacing: -0.2px
  stat-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 40px
    fontWeight: '800'
    lineHeight: '1.0'
    letterSpacing: -1.5px
  card-stat:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '800'
    lineHeight: '1.2'
    letterSpacing: -0.6px
  card-title:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '800'
    lineHeight: '1.4'
    letterSpacing: '0'
  body-text:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: '1.5'
    letterSpacing: '0'
  body-small:
    fontFamily: Plus Jakarta Sans
    fontSize: 13px
    fontWeight: '400'
    lineHeight: '1.55'
    letterSpacing: '0'
  form-label:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '700'
    lineHeight: '1.4'
    letterSpacing: '0'
  helper-text:
    fontFamily: Plus Jakarta Sans
    fontSize: 11.5px
    fontWeight: '400'
    lineHeight: '1.5'
    letterSpacing: '0'
  table-column-label:
    fontFamily: Plus Jakarta Sans
    fontSize: 10.5px
    fontWeight: '800'
    lineHeight: '1.4'
    letterSpacing: 0.07em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 16px
  margin: 24px
  space-xs: 4px
  space-sm: 8px
  space-md: 16px
  space-lg: 24px
  space-xl: 32px
---

## Brand & Style

This design system embodies an orderly, calm, and trustworthy aesthetic tailored for an educational institution. The emotional response is one of clarity, reliability, and grounded professionalism—avoiding loud gradients and cartoonish elements in favor of a "green, clean, and clear" visual narrative.

We utilize a **Corporate / Modern** design framework mixed with high-utility structural patterns. The focus rests on crisp typography, high readability, and purposeful use of deep forest and emerald greens to communicate institutional trust, growth, and institutional stability.

## Colors

The color palette is built on authoritative deep greens and vibrant emerald action colors, supported by soft mints and clean neutrals. 

- **Primary (`#15803D`)**: Drives primary actions, links, focus rings, and active navigational elements.
- **Secondary (`#0B3D1E`)**: Anchors dark surfaces like admin sidebars and footers.
- **Neutrals (`#111827`, `#6B7280`, `#F9FAFB`)**: Ensure maximum readability across long-form reading contexts and data tables while maintaining high WCAG AAA compliance.
- **Status Colors**: Purpose-built amber (`#B45309`), red (`#DC2626`), and blue (`#1D4ED8`) communicate transactional states clearly.

## Typography

Typography relies exclusively on **Plus Jakarta Sans**, utilizing weights 400, 600, 700, and 800 to establish rigorous hierarchy without requiring secondary font families. Proportional tracking and tight line heights are calibrated for dense educational dashboards and crisp mobile displays.

For responsive scaling, oversized headings automatically clamp on viewports below tablet width to prevent awkward text wrapping, while maintaining a strict 12px minimum baseline for helper and form text.

## Layout & Spacing

This design system operates on a strict **4px spacing rhythm**, ensuring mathematical consistency across all component padding, gaps, and structural containers.

- **Grid Architecture**: Employs a fluid layout model with fixed containers (public max-width `1100px`, admin max-width `1320px`, fixed sidebar `262px`).
- **Spacing Scale**: Scales predictably from `4px` (`--sp-1`) up to `48px` (`--sp-12`), dictating predictable vertical rhythms between form groups (`26px`) and section dividers (`32px` to `40px`).

## Elevation & Depth

Visual hierarchy is communicated primarily through flat structural boundaries, low-opacity green-tinted shadows, and clean surface layering rather than heavy skeuomorphic depths.

- **Shadow Tokens**: 
  - `--shadow-sm` (`0 2px 8px rgba(15,61,30,.06)`) is applied universally to static cards.
  - `--shadow` (`0 12px 30px rgba(15,61,30,.10)`) elevates modals and floating panels.
  - Primary buttons utilize a dedicated emerald shadow (`0 4px 12px rgba(21,128,61,.22)`).
- **Surface Boundaries**: Tables, lists, and form inputs favor thin borders (`#E1ECE5`) over drop shadows to maintain a crisp, information-dense aesthetic.

## Shapes

The shape language is purposefully disciplined, utilizing a clear roundedness scale to distinguish structural containers from interactive controls:

- **Small Controls (`--r-sm` - 7px)**: Applied to pagination items and micro-interactions.
- **Standard Controls (`--r-md` - 10px)**: Used for buttons, text inputs, and square badges.
- **Cards & Panels (`--r-lg` - 16px)**: Applied to major content cards and modals.
- **Pills & Avatars (`--r-full` - 999px)**: Reserved exclusively for status pills, avatars, and toggle switches.

## Components

All UI components must adhere strictly to the tokenized specifications for color, typography, and shape:

- **Buttons**: Primary buttons feature the solid emerald fill (`#15803D`), 10px corner radius, and specialized emerald-tinted shadows. Limit usage to a maximum of one primary button per screen view.
- **Input Fields**: Built with a 10px radius, 1px solid `#E1ECE5` borders, and clear 12px bold labels above. Focus states trigger a 2px solid emerald border with a soft matching ring.
- **Cards**: Surface containers use a 16px border radius (`--r-lg`), white background, and subtle `--shadow-sm` elevation, padded consistently at 16px to 20px.
- **Badges & Pills**: Fully rounded (`999px`) status indicators pairing soft pastel backgrounds with high-contrast text tones (e.g., success, warning amber, and destructive red).
- **Lists & Tables**: Utilize alternating rows or clean border dividers with 10.5px uppercase bold column headers and 13px compact body descriptions.