# Google Docs Import — Manual Test Plan

## Prerequisites
1. A Google Cloud project with OAuth 2.0 client configured:
   - Authorized redirect URI matching your network's callback URL
   - OAuth consent screen with `documents.readonly` and `drive.readonly` scopes
2. A Pressbooks network with Google Docs Import configured (Network Admin → Settings → Google Docs Import)
3. A test Google Doc with:
   - Multiple H1 headings (to test chapter split)
   - H2–H6 headings
   - Bold, italic, underline text
   - Links
   - Bulleted and numbered lists (including nested)
   - At least one inline image with alt text set
   - A simple table (2+ rows, 2+ columns)

## Test Steps

### 1. Network admin settings
- [ ] Navigate to Network Admin → Settings → Google Docs Import
- [ ] Verify Client ID / Client Secret fields are shown
- [ ] Verify the Redirect URI is displayed
- [ ] Enter valid credentials and save
- [ ] Verify credentials persist after page reload

### 2. User connection flow
- [ ] Navigate to any book → Tools → Import
- [ ] Select "Google Docs" from the Import Type dropdown
- [ ] Verify "Connect Google Account" button appears
- [ ] Click "Connect Google Account"
- [ ] Verify redirect to Google consent screen
- [ ] Grant access
- [ ] Verify redirect back to import screen with success notice
- [ ] Verify "Google account connected" message appears

### 3. Import flow
- [ ] With Google Docs selected, choose "Import from URL" radio
- [ ] Paste a valid Google Doc URL
- [ ] Click "Begin Import"
- [ ] Verify chapter selection screen appears with correct chapter titles (split on H1)
- [ ] Select all chapters
- [ ] Click Import
- [ ] Verify chapters are created as draft posts
- [ ] Verify headings H2–H6 are preserved
- [ ] Verify bold/italic/underline are preserved
- [ ] Verify links are preserved
- [ ] Verify bulleted and numbered lists are correct
- [ ] Verify images are present with correct alt text
- [ ] Verify tables render correctly

### 4. Error handling
- [ ] Try importing a Google Sheets URL → expect "That URL is not a Google Doc" error
- [ ] Try importing a URL you don't have access to → expect "couldn't be opened" error
- [ ] Try importing with invalid/gibberish URL → expect "valid Google Docs URL" error

### 5. Disconnect
- [ ] Click "Disconnect" on the import screen
- [ ] Verify disconnect notice
- [ ] Verify "Connect Google Account" button reappears

## Known Limitations
- See spec: `docs/superpowers/specs/2026-04-16-google-docs-import-design.md` section 9
