# Alignment & Styling Fix Plan

## Information Gathered:
After analyzing the codebase, I found several alignment and styling issues:

### Issues Identified:
1. **Inconsistent Styling Between Pages**
   - `admin/dashboard.php` has extensive inline CSS in `<style>` tag
   - `hr/dashboard.php` has its own inline CSS that duplicates styles but lacks responsive enhancements
   - Both pages include `responsive.css` but have conflicting inline styles

2. **Bootstrap Grid Conflicts in admin/dashboard.php**
   - Uses both Bootstrap's `.row`/`.col-*` classes AND custom `.cards-grid`/`.charts-grid`
   - The 3-column layout mixes Bootstrap grid with custom CSS grid causing misalignment

3. **Layout Alignment Problems**
   - 3-column layout in admin/dashboard.php doesn't collapse properly on smaller screens
   - Inconsistent padding/margins between columns

4. **CSS Conflicts**
   - Inline styles override `responsive.css` styles
   - `responsive.css` uses `!important` extensively which causes unexpected behavior
   - Duplicate CSS definitions between inline styles and responsive.css

5. **Missing Functionality**
   - `hr/dashboard.php` lacks fullscreen toggle button
   - Dynamic sidebar toggle conflicts with static button in hr/dashboard

## Plan:
1. **Create Unified Base Styles** - Create a common stylesheet that all pages should use
2. **Fix Bootstrap Grid in admin/dashboard.php** - Remove conflicting custom grids and use proper Bootstrap classes
3. **Standardize hr/dashboard.php** - Add consistent styling and missing components
4. **Fix 3-Column Layout** - Ensure proper alignment across all screen sizes
5. **Remove Duplicate CSS** - Clean up inline styles that conflict with responsive.css

## Dependent Files to be Edited:
1. `admin/dashboard.php` - Fix grid layout and inline CSS conflicts
2. `hr/dashboard.php` - Add consistent styling and missing components
3. `assets/responsive.css` - Clean up conflicting !important declarations

## Follow-up Steps:
- Test the layouts on different screen sizes
- Verify all buttons and interactive elements work properly
- Check that the sidebar toggles correctly on mobile

