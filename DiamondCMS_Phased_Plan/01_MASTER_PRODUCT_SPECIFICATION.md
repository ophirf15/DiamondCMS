# DiamondCMS Master Product Specification

## 1. Product identity

**Product name:** DiamondCMS  
**Initial site:** ophiryahalom.com  
**Name origin:** “Yahalom” means “diamond” in Hebrew.

DiamondCMS is a self-hosted personal-site CMS and visual website builder. Its focus is not publishing or blogging. Its focus is enabling an individual to build a polished, unique personal website containing:

- Résumé
- Work history
- Education
- Skills
- Certifications
- Awards
- Portfolio projects
- Technical projects
- Creative work
- Hobbies
- Interests
- Testimonials
- Photo galleries
- Downloadable documents
- Contact forms
- Timeline content
- Custom landing pages

The initial user is Ophir Yahalom. The first production website should combine a résumé, portfolio, personal profile, hobbies, projects, and interests.

## 2. Explicit exclusions

Do not build these into the initial core product:

- Blogging
- Public registration
- Memberships
- Public user profiles
- Comments
- Forums
- Social networking
- E-commerce
- Shopping carts
- Complex editorial approval workflows
- Public API marketplace
- SaaS billing
- Multi-site management

## 3. Installation and hosting

DiamondCMS must install similarly to WordPress or Joomla:

1. User uploads and extracts a release ZIP.
2. Browser opens an installation wizard.
3. Wizard validates server requirements.
4. User enters MySQL host, port, database, username, and password.
5. Wizard tests the database connection.
6. Wizard writes environment configuration safely.
7. Wizard runs migrations and initial seed data.
8. User creates the initial administrator account.
9. User configures site name and base URL.
10. Installer locks itself after successful completion.

Production assumptions:

- PHP 8.2 or newer, subject to final framework compatibility
- MySQL 8 or supported MariaDB equivalent
- Apache or LiteSpeed
- `.htaccess` support
- Common shared hosting
- phpMyAdmin availability
- No production Node.js requirement
- No Docker requirement
- No Redis requirement
- No shell access requirement

Local development may use:

- Node.js
- Composer
- npm
- Vite
- Docker optionally
- Git
- PHPUnit or Pest
- Browser automation tools

## 4. Administrator model

Initial release:

- Multiple administrator accounts
- All administrators have equal access
- No public registration
- Administrators can invite or create other administrators
- Password reset by email
- Two-factor authentication
- Login throttling
- Session management
- Activity logging

Later:

- Owner
- Administrator
- Designer
- Content Editor
- Form Manager
- Custom role permissions

## 5. Visual builder

The page builder must be a hybrid structured/freeform system.

### Required editor capabilities

- Direct visual editing
- Drag-and-drop page sections
- Drag-and-drop blocks inside sections
- Left-side component library
- Right-side properties inspector
- Inline text editing
- Desktop preview
- Tablet preview
- Mobile preview
- Responsive visibility controls
- Responsive spacing and typography controls
- Undo
- Redo
- Autosave
- Drafts
- Published versions
- Revision history
- Preview links
- Scheduled publishing
- Duplicate page
- Save page as template
- Save section as reusable block
- Global sections
- Global header and footer builder
- Layer or structure navigator
- Keyboard shortcuts
- Copy and paste blocks
- Section locking
- Block naming
- Reorder with keyboard-accessible controls
- Builder crash recovery
- Unsaved-change protection

### Layout model

Prefer a structured page schema:

- Page
- Region
- Section
- Container
- Row
- Column
- Block
- Style configuration
- Responsive overrides
- Data bindings
- Interaction configuration

Allow controlled free-positioning only inside designated canvas sections.

Do not create arbitrary HTML strings as the primary persistence model. Store builder content as validated structured JSON, with versioned schemas and server-side sanitization.

## 6. Component library

Initial block types should include:

- Heading
- Paragraph
- Rich text
- Image
- Gallery
- Video
- Button
- Icon
- Divider
- Spacer
- Quote
- Testimonial
- Timeline
- Resume experience
- Education
- Skills
- Certifications
- Awards
- Project card
- Project grid
- Project details
- Hobby card
- Interest list
- Download button
- Contact form
- Social links
- Navigation menu
- Breadcrumbs
- Tabs
- Accordion
- Modal
- Statistics
- Logo cloud
- Before-and-after comparison
- Code sample
- Embed
- Map
- Custom HTML block
- Custom collection list
- Related content
- Call to action
- Hero
- Footer content
- Cookie banner

## 7. Structured content

Provide built-in structured content types for:

- Work experience
- Education
- Skills
- Certifications
- Awards
- Portfolio projects
- Hobbies
- Interests
- Testimonials
- Galleries
- Documents
- Timeline entries
- Contact forms

No custom content-type builder is required in the initial release.

## 8. Résumé system

### Import

Support:

- PDF
- DOCX

Import workflow:

1. Upload file.
2. Extract text and layout metadata.
3. Send structured extraction request to configured AI provider, where enabled.
4. Parse work history, dates, education, skills, achievements, certifications, awards, and contact details.
5. Show a review screen.
6. User corrects and confirms each field.
7. Save confirmed data into structured résumé records.
8. Preserve the original file in the media library.

### Multiple versions

Support multiple résumé variants, such as:

- Property management
- Web development
- Technical projects
- General professional
- Creative portfolio

Each version may select different experience bullets, skills, summary text, and visual template.

### Export and sharing

Support:

- Print-ready PDF export
- Public résumé webpage
- Friendly share URL
- Downloadable source résumé file
- Public/private visibility
- Optional expiration for private share links
- Multiple visual résumé templates
- ATS-friendly PDF template
- Rich visual template similar in spirit to the supplied dark sidebar résumé screenshot

## 9. Portfolio and projects

Project records should support:

- Title
- Slug
- Short summary
- Full case study
- Cover image
- Image gallery
- Video
- Technology tags
- Skills demonstrated
- Project category
- Project type
- Status
- Start date
- Completion date
- Client or employer
- Role
- Collaborators
- Live demo URL
- Source repository URL
- Downloadable files
- Before-and-after images
- Challenges
- Process
- Outcome
- Metrics
- Lessons learned
- Related projects
- Visibility
- Featured status

Filtering should support:

- Category
- Skill
- Year
- Project type
- Status
- Featured

## 10. Templates and themes

Initial release requires approximately ten complete starter-site templates:

1. Dark technical résumé
2. Minimal professional résumé
3. Creative portfolio
4. Property-management professional
5. Developer and technical-project portfolio
6. Photography or visual-art portfolio
7. Personal biography and interests
8. Split-screen résumé
9. Editorial case-study portfolio
10. Modern one-page personal site

Also include:

- Page templates
- Section templates
- Header templates
- Footer templates
- Resume templates
- Project page templates
- Contact page templates
- 404 templates

Templates must remain editable through the visual builder.

## 11. Branding system

Global design controls must include:

- Primary, secondary, accent, and neutral colors
- Full color palette
- Gradient builder
- Typography
- Font pairing
- Font sizes
- Font weights
- Line heights
- Letter spacing
- Logo
- Alternate logo
- Favicon
- Button styles
- Border radii
- Borders
- Shadows
- Spacing scale
- Container widths
- Section spacing
- Light mode
- Dark mode
- Automatic mode
- Section backgrounds
- Background images
- Background video
- Animation presets
- Hover states
- Focus states
- Link styles
- Form styles
- Navigation styles
- Custom CSS
- Advanced custom JavaScript with explicit warnings
- Google Fonts
- Locally uploaded fonts

Custom code must be restricted to administrators and protected by warnings, sanitization where possible, Content Security Policy guidance, and rollback history.

## 12. Media library

Required capabilities:

- Folder-like organization
- Tags
- Search
- Filters
- Bulk upload
- Drag-and-drop upload
- Image previews
- File metadata
- Alt text
- Captions
- Credits
- Focal point
- Crop
- Resize
- Rotate
- Compression
- WebP generation
- AVIF generation when supported
- Responsive image variants
- Document uploads
- PDF preview
- Duplicate detection
- Usage tracking
- Replace file while preserving references
- Unused-media report
- Download
- Bulk delete with reference warnings
- Storage quota display
- Maximum file size configuration

## 13. Forms and email

Build a general-purpose form builder with:

- Text
- Email
- Phone
- Number
- Textarea
- Dropdown
- Multi-select
- Checkbox
- Radio
- Date
- Time
- File upload
- Consent checkbox
- Hidden field
- Heading
- Description
- Divider

Form features:

- Drag-and-drop ordering
- Required rules
- Validation rules
- Conditional fields
- Spam protection
- Honeypot
- Rate limiting
- Cloudflare Turnstile
- CAPTCHA adapter
- Email notifications
- Confirmation emails
- Database-stored submissions
- CSV export
- Custom success message
- Redirect after submission
- Visual styling in page builder
- File-upload controls
- Privacy and retention controls
- Submission status
- Notes
- Search
- Archive
- Delete
- Bulk export

SMTP is a global system feature, not a form-only feature.

Global email settings:

- SMTP host
- Port
- Encryption
- Username
- Password
- Sender name
- Sender address
- Reply-to
- Test email
- Queue or synchronous fallback
- Email templates
- Delivery logs without exposing passwords

Webhooks are not required initially.

## 14. AI system

Supported providers:

- OpenAI
- Anthropic Claude
- Google Gemini
- Optional OpenAI-compatible endpoint later

Provider configuration:

- API keys stored encrypted server-side
- Keys never exposed to browser JavaScript
- Provider connection test
- Available-model discovery where supported
- Manual model entry fallback
- Default provider and model
- Per-task model selection
- Token and cost logging where data is available
- Monthly usage limits
- Disable AI globally
- Disable AI per administrator
- Audit log of AI actions

AI features:

- Rewrite selected text
- Change tone
- Shorten
- Expand
- Proofread
- Review résumé
- Strengthen résumé bullets
- Identify missing résumé sections
- Generate page copy
- Generate SEO metadata
- Generate complete draft pages
- Generate a complete draft site from a questionnaire
- Suggest layouts
- Select templates
- Suggest sections
- Configure existing blocks
- Generate new component configurations using the design system
- Generate custom HTML and CSS when existing components are insufficient
- Recommend colors
- Recommend typography
- Generate image prompts
- Analyze portfolio projects
- Site-wide consistency review
- Accessibility review
- Navigation review
- Content-gap review
- Mobile-layout review
- Call-to-action review

AI may use existing site content as context, subject to explicit administrator action.

### AI change safety

Permission levels:

1. Suggest
2. Draft
3. Apply after confirmation
4. Site-wide plan and apply after confirmation
5. Autonomous draft-site generation

Rules:

- Never silently alter published content.
- Create a revision before applying changes.
- Show a diff or meaningful preview.
- Site-wide changes require a generated plan.
- User must approve the plan.
- AI-created pages begin unpublished.
- Provide undo and rollback.
- Do not allow AI to generate or execute PHP application code from production admin.
- Do not allow AI to run arbitrary shell commands.
- Do not allow AI to alter server configuration.
- Do not send API keys, passwords, private form submissions, or protected content to providers without explicit handling rules.

## 15. Public-site features

Required:

- SEO title and description
- Canonical URL
- Open Graph metadata
- Social-sharing image
- XML sitemap
- robots.txt management
- Redirect manager
- Custom 404
- Password-protected pages
- Scheduled publishing
- Secure draft previews
- Header builder
- Footer builder
- Sticky navigation
- Transparent navigation
- Dropdown menus
- Mobile menus
- Cookie-consent banner
- Google Analytics integration
- Google Tag Manager integration
- Custom analytics scripts
- Accessibility auditing
- Performance auditing
- Image optimization
- Lazy loading
- Cache controls
- CDN-compatible asset URLs
- Multilingual-ready data model
- Initial UI may remain single-language, but the schema must not prevent future translations

## 16. Export, import, deployment, and updates

### Site package export/import

Provide a complete-site package feature for moving a local build to production.

Export package should contain:

- Database content in a portable format
- Media
- Theme settings
- Builder pages
- Templates
- Menus
- Forms
- Email templates without secrets
- AI configuration without API keys
- Redirects
- SEO settings
- Site settings
- Version manifest
- Checksums

Import must support:

- New installation
- Replace existing site
- Merge where safe
- Pre-import backup
- Compatibility validation
- Dry-run report
- Conflict report
- URL replacement
- Storage-path normalization
- Post-import health check
- Rollback

Secrets must never be exported by default.

### GitHub updater

The CMS should check the designated DiamondCMS GitHub repository for tagged releases.

Required updater workflow:

1. Check current installed version.
2. Retrieve latest compatible release metadata.
3. Display release notes.
4. Verify minimum PHP and database requirements.
5. Download production release package.
6. Verify checksum or signature.
7. Enter maintenance mode.
8. Back up database and application files.
9. Install files using a staged directory.
10. Run migrations.
11. Clear caches.
12. Run health checks.
13. Exit maintenance mode.
14. Roll back automatically on failure.
15. Keep update logs.

Initial updater needs only the single official DiamondCMS repository.

Later:

- Theme repositories
- Plugin repositories
- Private repository authentication
- Update channels
- Licensing

## 17. Security

Required:

- CSRF protection
- XSS prevention
- SQL injection prevention
- Strong password hashing
- Secure sessions
- Cookie security
- Login throttling
- Two-factor authentication
- Email-based password resets
- Server-side authorization
- File-upload validation
- MIME verification
- Image re-encoding
- SVG sanitization
- Path traversal prevention
- Secret encryption
- Audit logging
- Activity history
- Content revision history
- Backups
- Restore testing
- Content Security Policy support
- Secure headers
- Dependency vulnerability checks
- Update package verification
- Installer lock
- Debug mode disabled in production
- Error pages that do not expose secrets

## 18. Later productization

Final “nice to have” phase:

- White-label mode
- Replace DiamondCMS name and logo
- Custom admin colors
- Branded login page
- Role-based permissions
- Plugins
- Themes
- Third-party repositories
- License keys
- Installation IDs
- Optional telemetry
- Hosted-service preparation
- SaaS architecture evaluation
- Multi-site evaluation
