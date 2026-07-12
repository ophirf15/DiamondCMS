---
title: Theme Tabbed Preview - Plan
date: 2026-07-12
artifact_contract: ce-unified-plan/v1
artifact_readiness: requirements-only
product_contract_source: ce-brainstorm
execution: code
status: draft
---

# Theme Tabbed Preview - Plan

## Goal Capsule

- **Objective:** Make Theme admin findable and honest — tabbed by page type, with a preview that always matches the active tab (including Portfolio and a first cut of Resume layout tokens).
- **Product authority:** Site owner editing design tokens in Admin → Theme; public site continues to consume saved tokens.
- **Open blockers:** None. Portfolio index-vs-detail preview toggle and Resume first-cut token scope are decided below.

---

## Product Contract

### Summary

Replace the single long Theme scroll with **page-type tabs** (Overall site, Portfolio, Resume, extensible). Each tab owns that surface’s controls. The **live preview follows the active tab** so UI kit, portfolio layout, and resume layout changes are visible without saving and visiting the public site.

### Problem Frame

Theme has grown into one dense panel. Portfolio and UI kit controls sit mid-scroll with no matching preview, so edits feel like no-ops. Site chrome is partially previewed; project pages and resume presentation are not. Finding the right control is slow, and trust in “Live preview” is low.

### Key Decisions

- **Tabs by page type, not by token category.** Top-level IA is Overall site / Portfolio / Resume (and future page types). Global look-and-feel lives under Overall, with light sub-groups so that tab does not recreate the infinite scroll.
- **Preview follows the tab.** Switching tabs switches the preview target. No independent preview switcher or pin in this pass.
- **Portfolio preview defaults to project detail**, with an in-tab toggle to the projects **index** mock so both portfolio surfaces are editable honestly.
- **Resume tab is a real first cut**, not a stub: a small set of resume layout tokens plus a resume-shaped preview that reacts to them.
- **Honest preview over live iframe.** In-panel mocks (or equivalent reactive previews) must reflect the controls on the active tab; embedding the full live site is out of scope.

### Requirements

**Shell and findability**

- R1. Theme admin presents top-level tabs for at least Overall site, Portfolio, and Resume.
- R2. Only the active tab’s controls are shown in the primary editing column (no single endless stack of every Theme setting).
- R3. Overall site groups controls into clear sub-sections (at minimum: Chrome, Look & type, UI kit) so the tab remains scannable.
- R4. Adding a future page-type tab does not require inventing a second navigation pattern; the shell is designed to extend.

**Preview behavior**

- R5. The preview always reflects the active tab’s page type.
- R6. Overall preview shows site chrome and applies UI kit signals the user can see (radius, surface, density, control style, and default social presentation as applicable).
- R7. Portfolio preview defaults to a project-detail mock driven by portfolio tokens (layout, logo placement/size/style, CTA, skills, gallery presentation).
- R8. Portfolio tab includes a control to switch the preview between project detail and projects index (index layout / card fit visible).
- R9. Resume preview shows a resume-shaped mock driven by the new resume layout tokens.
- R10. Preview updates as tokens change in the panel before save; save still persists tokens for the public site as today.

**Resume first cut**

- R11. Theme introduces a small resume layout token set (density / section rhythm / experience list presentation — exact keys deferred to planning) with defaults that preserve current public resume appearance until the owner changes them.
- R12. Resume tokens apply to existing resume presentation surfaces (builder resume blocks and/or print/resume chrome already in the product), not a new resume content product.

**Honesty and trust**

- R13. Controls that affect public appearance must either update the active preview or be labeled as not previewable; silent no-ops are not acceptable for UI kit or portfolio settings covered by R6–R8.
- R14. Public application of UI kit density/control (and related) must match what the Overall preview claims — preview and live site stay aligned for those signals.

**Scope Boundaries**

- R15. Out of scope: pinning preview to another page while editing; true iframe of the live public site; redesigning Portfolio content admin or Resume data modeling beyond presentation tokens; Live Edit as the Theme editor.

### Key Flows

- F1. Edit Overall look
  - **Trigger:** Owner opens Theme → Overall site.
  - **Steps:** Adjust chrome / colors / type / UI kit; preview updates site chrome immediately; save persists.
  - **Outcome:** Owner sees UI kit and chrome changes without leaving Theme.
- F2. Edit Portfolio presentation
  - **Trigger:** Owner opens Theme → Portfolio.
  - **Steps:** Adjust project layout / logos / gallery; preview shows project detail; optionally toggle to index preview; save persists.
  - **Outcome:** Logo size and layout choices are judged in context of the project page, not a header mock.
- F3. Edit Resume presentation
  - **Trigger:** Owner opens Theme → Resume.
  - **Steps:** Adjust first-cut resume layout tokens; resume mock updates; save persists to public resume surfaces.
  - **Outcome:** Resume density/list style is tunable from Theme with visible feedback.

### Visualizations

```text
┌──────────────── Theme ──────────────────────────────┐
│ [ Overall site ] [ Portfolio ] [ Resume ]            │
├──────────────────────────┬──────────────────────────┤
│ Tab controls             │ Preview (follows tab)    │
│  Overall: Chrome / Look  │  Overall → site chrome   │
│           / UI kit       │  Portfolio → detail|index│
│  Portfolio: layout…      │  Resume → resume mock    │
│  Resume: density…        │                          │
└──────────────────────────┴──────────────────────────┘
```

### Acceptance Examples

- A1. Covers R5–R6, R13
  - **Given:** Theme is on Overall and UI kit density is Compact.
  - **When:** Owner switches density and inspects preview.
  - **Then:** Preview visibly tightens chrome/controls; after save, public site matches that density signal.
- A2. Covers R7–R8
  - **Given:** Theme is on Portfolio with logo placement Beside title and size Large.
  - **When:** Owner views project-detail preview, then toggles to index.
  - **Then:** Detail preview shows logos beside the title at title-scale; index preview reflects index layout / card fit.
- A3. Covers R9, R11–R12
  - **Given:** Theme is on Resume with default tokens.
  - **When:** Owner changes resume density (or equivalent first-cut token).
  - **Then:** Resume mock updates immediately; saved tokens change the live resume presentation surfaces without altering resume content records.

### Success Criteria

- Owner can find Portfolio and Resume presentation controls without scrolling through unrelated chrome settings.
- Changing a Portfolio or UI kit control produces an obvious preview change on the matching tab.
- Resume first-cut tokens ship with a working preview and non-breaking defaults.
- Theme remains one Save action for the design token document (no fragmented save UX unless planning proves otherwise).

### Assumptions

- Sample/placeholder content in Portfolio and Resume previews is acceptable (does not require loading every real project or resume variant).
- Future page-type tabs (e.g. blog) follow the same shell when those tokens exist; they are not required in this pass beyond an extensible tab pattern.

### Outstanding Questions

None blocking planning. Planning may refine the exact resume token keys and whether Overall sub-groups use secondary tabs, accordion, or sticky sub-nav.
