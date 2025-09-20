# VRISTO UI Reference

**IMPORTANT**: VRISTO is used ONLY as visual/design reference. No VRISTO technology, Alpine.js, or legacy code should be used.

## Technology Stack
- **Primary**: Vue 3 + Inertia.js + TailwindCSS v4
- **UI Components**: Existing modern UI component library
- **Icons**: Lucide Vue Next
- **Navigation**: Inertia.js router and Link components

## VRISTO Design Elements to Reference

### Color Palette
```css
--color-primary: #4361ee;
--color-primary-light: #eaf1ff;
--color-secondary: #805dca;
--color-success: #00ab55;
--color-danger: #e7515a;
--color-warning: #e2a03f;
--color-info: #2196f3;
--color-dark: #3b3f5c;
--color-black: #0e1726;
```

### Typography
- Font: Nunito (already integrated)
- Font weights: 400, 500, 600, 700, 800

### Layout Patterns
1. **Sidebar Navigation**
   - Fixed sidebar with collapsible behavior
   - Organized sections with headers
   - Icon + text navigation items
   - Active state highlighting

2. **Header Design**
   - Logo placement
   - User menu dropdown
   - Search functionality
   - Theme toggle

3. **Content Areas**
   - Proper spacing and padding
   - Card-based layouts
   - Clean typography hierarchy

### Visual References Only
- Button styles and variations
- Form input designs
- Card component layouts
- Navigation patterns
- Color usage and theming
- Spacing and typography

## Implementation Approach
1. Use existing Vue 3 + Inertia.js components
2. Apply VRISTO-inspired styling with TailwindCSS v4
3. Maintain modern component architecture
4. Use CSS custom properties for VRISTO colors
5. Keep all navigation with Inertia.js Link components
6. No Alpine.js or VRISTO JavaScript dependencies