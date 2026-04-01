# Easy Auth Login - Test Cases & QA Guide

Complete test suite for all features in Easy Auth Login plugin. Run these tests to verify functionality before deployment.

---

## Table of Contents

1. [Setup & Activation Tests](#setup--activation-tests)
2. [Admin Dashboard Tests](#admin-dashboard-tests)
3. [General Settings Tests](#general-settings-tests)
4. [Redirection Tests](#redirection-tests)
5. [Social Login Tests](#social-login-tests)
6. [Styling Tests](#styling-tests)
7. [Security Tests](#security-tests)
8. [Frontend Form Tests](#frontend-form-tests)
9. [Magic Link Tests](#magic-link-tests)
10. [Avatar Sync Tests](#avatar-sync-tests)
11. [Force Login Tests](#force-login-tests)
12. [Error Handling Tests](#error-handling-tests)

---

## Setup & Activation Tests

### Test 1.1: Plugin Activation
**Steps:**
1. Download and upload `easy-auth-login` to `/wp-content/plugins/`
2. Go to **Plugins** → **Installed Plugins**
3. Find "Easy Auth Login" and click **Activate**

**Expected Result:**
- ✅ Plugin activates without errors
- ✅ Menu item "Easy Auth Login" appears in sidebar
- ✅ `wp_easy_auth_logs` table created in database
- ✅ Default settings saved to `wp_options`

**Actual Result:** ___

---

### Test 1.2: Database Table Creation
**Steps:**
1. Check database for `wp_easy_auth_logs` table
2. Verify columns: `id`, `ip_address`, `username_attempted`, `time`, `lockout_expiry`

**Expected Result:**
- ✅ Table exists with correct schema
- ✅ Primary key on `id`
- ✅ Indexes on: `ip_address`, `username_attempted`, `lockout_expiry`

**Actual Result:** ___

---

### Test 1.3: Plugin Deactivation & Re-activation
**Steps:**
1. Deactivate plugin
2. Re-activate plugin
3. Check settings are preserved

**Expected Result:**
- ✅ Plugin deactivates cleanly
- ✅ Settings remain after re-activation
- ✅ No database errors

**Actual Result:** ___

---

## Admin Dashboard Tests

### Test 2.1: Admin Menu Access
**Steps:**
1. Log in as admin
2. Look for "Easy Auth Login" in WordPress sidebar

**Expected Result:**
- ✅ Menu item visible and clickable
- ✅ Icon shows lock emoji (dashicons-lock)
- ✅ Positioned correctly in menu

**Actual Result:** ___

---

### Test 2.2: Tab Navigation
**Steps:**
1. Click on "Easy Auth Login" menu
2. Verify all 5 tabs are visible: General, Redirection, Social Login, Styling, Security

**Expected Result:**
- ✅ All tabs visible
- ✅ Active tab highlighted
- ✅ Clicking each tab loads correct fields
- ✅ URL updates with `&tab=` parameter

**Actual Result:** ___

---

### Test 2.3: Settings Persistence
**Steps:**
1. Change a setting (e.g., button color)
2. Click **Save Settings**
3. Refresh page
4. Navigate away and back

**Expected Result:**
- ✅ Setting saved and persists
- ✅ No errors on save
- ✅ Post-redirect occurs without issues
- ✅ Success message displays

**Actual Result:** ___

---

## General Settings Tests

### Test 3.1: Enable/Disable AJAX Login
**Steps:**
1. Go to General tab
2. Toggle "Enable AJAX Login" on/off
3. Save
4. Go to login page with shortcode
5. Submit login form

**Expected Result (when ON):**
- ✅ Form submits without page reload
- ✅ Response message appears in real-time
- ✅ Redirect happens smoothly

**Expected Result (when OFF):**
- ✅ Form submission still works (fallback to standard POST)
- ✅ Page reloads on submission

**Actual Result:** ___

---

### Test 3.2: Enable/Disable AJAX Registration
**Steps:**
1. Toggle "Enable AJAX Registration" on/off
2. Save
3. Go to login page, click Register tab
4. Submit registration form with new user

**Expected Result (when ON):**
- ✅ No page reload
- ✅ Real-time feedback
- ✅ User created and logged in

**Expected Result (when OFF):**
- ✅ Form still works with page reload

**Actual Result:** ___

---

### Test 3.3: Default Login View - Inline Form
**Steps:**
1. Set "Default Login View" to "Inline Form"
2. Save
3. Visit login page with shortcode

**Expected Result:**
- ✅ Form displays as full-width or constrained container
- ✅ All tabs visible: Login, Register, Forgot, Magic Link
- ✅ Form appears immediately

**Actual Result:** ___

---

### Test 3.4: Default Login View - Modal
**Steps:**
1. Set "Default Login View" to "Modal Popup"
2. Save
3. Visit login page with shortcode

**Expected Result:**
- ✅ Form displays in a modal/popup box
- ✅ Centered on screen
- ✅ Background is slightly dimmed
- ✅ Modal has proper styling

**Actual Result:** ___

---

### Test 3.5: Already Logged In View (Enabled)
**Steps:**
1. Enable "Already Logged In View"
2. Save
3. Log in as a user
4. Visit page with login shortcode

**Expected Result:**
- ✅ Form hidden
- ✅ "Welcome back, [Username]" message displayed
- ✅ Logout button visible
- ✅ Clicking logout logs user out and redirects

**Actual Result:** ___

---

### Test 3.6: Already Logged In View (Disabled)
**Steps:**
1. Disable "Already Logged In View"
2. Save
3. Log in as a user
4. Visit page with login shortcode

**Expected Result:**
- ✅ Form still visible to logged-in users
- ✅ No "Welcome back" message

**Actual Result:** ___

---

### Test 3.7: Password Visibility Toggle
**Steps:**
1. Enable "Password Visibility Toggle"
2. Save
3. Visit login page
4. Focus on password field
5. Click eye icon

**Expected Result:**
- ✅ Eye icon visible in password field
- ✅ Clicking toggles between `type="password"` and `type="text"`
- ✅ Button text changes: "Show" ↔ "Hide"
- ✅ Works on both login and registration

**Actual Result:** ___

---

## Redirection Tests

### Test 4.1: AJAX Page Picker - Search Functionality
**Steps:**
1. Go to Redirection tab
2. Find "After Login Redirect Page"
3. Click search field
4. Type a page name (e.g., "Dashboard")

**Expected Result:**
- ✅ Dropdown appears with matching pages
- ✅ Shows page title and (ID)
- ✅ Results update as you type
- ✅ Less than 1 second delay (debounced)

**Actual Result:** ___

---

### Test 4.2: AJAX Page Picker - Select Page
**Steps:**
1. Search for a page
2. Click on a result

**Expected Result:**
- ✅ Page selected with title displayed
- ✅ Hidden input filled with page ID
- ✅ Search results closed
- ✅ Page title persists after save/refresh

**Actual Result:** ___

---

### Test 4.3: AJAX Page Picker - Clear Selection
**Steps:**
1. Select a page in redirect picker
2. Click the **Clear** button

**Expected Result:**
- ✅ Selection removed
- ✅ Search field empty
- ✅ Hidden input set to 0
- ✅ Falls back to default redirect

**Actual Result:** ___

---

### Test 4.4: Redirect After Login
**Steps:**
1. Set "After Login Redirect Page" to a specific page (e.g., Dashboard)
2. Save
3. Log out
4. Log in with valid credentials

**Expected Result:**
- ✅ After login, redirected to selected page
- ✅ URL matches the page slug
- ✅ Page content displays correctly

**Actual Result:** ___

---

### Test 4.5: Redirect After Registration
**Steps:**
1. Set "After Registration Redirect Page" to a welcome page
2. Save
3. Go to registration form
4. Register new user

**Expected Result:**
- ✅ After registration, redirected to selected page
- ✅ User logged in automatically
- ✅ URL matches the welcome page

**Actual Result:** ___

---

### Test 4.6: Redirect After Logout
**Steps:**
1. Set "After Logout Redirect Page" to homepage
2. Save
3. Log in
4. Visit login page with shortcode
5. Click Logout button

**Expected Result:**
- ✅ After logout, redirected to selected page
- ✅ User logged out (cookie cleared)
- ✅ Cannot access protected content

**Actual Result:** ___

---

### Test 4.7: Default Redirect (No Custom Page)
**Steps:**
1. Clear all redirect page selections (leave at 0)
2. Log in, register, logout
3. Observe where users are sent

**Expected Result:**
- ✅ Login → Dashboard (if admin) or Home
- ✅ Register → Home
- ✅ Logout → Home
- ✅ Sensible defaults applied

**Actual Result:** ___

---

## Social Login Tests

### Test 5.1: Google OAuth - Credentials Saved
**Steps:**
1. Go to Social Login tab
2. Enter Google Client ID and Secret
3. Click Save
4. Reload page

**Expected Result:**
- ✅ Credentials saved without error
- ✅ Values persist after refresh
- ✅ "Enable Google Login" can be toggled

**Actual Result:** ___

---

### Test 5.2: Google OAuth - Authorization Flow
**Prerequisites:** Google OAuth credentials configured
**Steps:**
1. Enable "Enable Google Login"
2. Save
3. Visit login page
4. Click "Continue with Google"

**Expected Result:**
- ✅ Redirected to Google consent screen
- ✅ Shows requested scopes: email, profile, openid
- ✅ User can approve or deny

**Actual Result:** ___

---

### Test 5.3: Google OAuth - Successful Login (New User)
**Steps:**
1. Approve Google OAuth
2. Complete authorization flow
3. Check if logged in

**Expected Result:**
- ✅ User logged in automatically
- ✅ WordPress user created with email from Google
- ✅ Username generated uniquely (no conflicts)
- ✅ Redirected to login destination page
- ✅ Avatar downloaded from Google profile

**Actual Result:** ___

---

### Test 5.4: Google OAuth - Successful Login (Existing User)
**Steps:**
1. Create WordPress user with email: `test@example.com`
2. Try to log in with Google using same email
3. Verify account linked

**Expected Result:**
- ✅ Existing user logged in
- ✅ Google account linked to WordPress user
- ✅ No duplicate user created
- ✅ Avatar updated from Google

**Actual Result:** ___

---

### Test 5.5: Google OAuth - Account Linking
**Steps:**
1. Create WordPress user
2. Log in to WordPress as that user
3. In separate browser, try to log in with Google using same email
4. Check usermeta

**Expected Result:**
- ✅ Google account linked to existing user
- ✅ `user_meta` shows `easy_auth_google_id`
- ✅ User can now use either password or Google login

**Actual Result:** ___

---

### Test 5.6: GitHub OAuth - Credentials Saved
**Steps:**
1. Go to Social Login tab
2. Enter GitHub Client ID and Secret
3. Click Save
4. Reload page

**Expected Result:**
- ✅ Credentials saved
- ✅ Values persist
- ✅ "Enable GitHub Login" toggles correctly

**Actual Result:** ___

---

### Test 5.7: GitHub OAuth - Authorization Flow
**Prerequisites:** GitHub OAuth credentials configured
**Steps:**
1. Enable "Enable GitHub Login"
2. Save
3. Visit login page
4. Click "Continue with GitHub"

**Expected Result:**
- ✅ Redirected to GitHub authorize page
- ✅ Shows app name and requested permissions
- ✅ User can authorize or cancel

**Actual Result:** ___

---

### Test 5.8: GitHub OAuth - Successful Login
**Steps:**
1. Authorize GitHub OAuth
2. Complete flow
3. Check if logged in

**Expected Result:**
- ✅ User logged in
- ✅ WordPress user created with GitHub email
- ✅ Username generated (no conflicts)
- ✅ Avatar downloaded from GitHub
- ✅ `easy_auth_github_id` stored in usermeta

**Actual Result:** ___

---

### Test 5.9: Linking Errors - Provider Not Configured
**Steps:**
1. Disable Google OAuth credentials
2. Try to click "Continue with Google"

**Expected Result:**
- ✅ Error message: "Provider is not configured"
- ✅ No redirect to Google
- ✅ Graceful error handling

**Actual Result:** ___

---

### Test 5.10: Multiple Provider Logins
**Steps:**
1. Log in with Google
2. Log out
3. Log in with GitHub using different email
4. Create second user

**Expected Result:**
- ✅ Two separate WordPress users created
- ✅ Both have their respective provider IDs
- ✅ Can log in with either provider

**Actual Result:** ___

---

## Styling Tests

### Test 6.1: Form Style - Light Theme
**Steps:**
1. Set "Form Style" to "Light"
2. Save
3. Visit login page

**Expected Result:**
- ✅ Form has light background
- ✅ Text is dark
- ✅ Buttons have defined colors
- ✅ Readable and accessible

**Actual Result:** ___

---

### Test 6.2: Form Style - Dark Theme
**Steps:**
1. Set "Form Style" to "Dark"
2. Save
3. Visit login page

**Expected Result:**
- ✅ Form has dark background
- ✅ Text is light
- ✅ High contrast for readability
- ✅ Buttons visible

**Actual Result:** ___

---

### Test 6.3: Form Style - Inherit from Theme
**Steps:**
1. Set "Form Style" to "Inherit from Theme"
2. Save
3. Visit login page

**Expected Result:**
- ✅ Form colors match site theme
- ✅ Text color determined by theme
- ✅ Buttons match theme style

**Actual Result:** ___

---

### Test 6.4: Custom Button Color
**Steps:**
1. Click color picker for "Primary Button Color"
2. Select a custom color (e.g., #ff0000 red)
3. Save
4. Visit login page

**Expected Result:**
- ✅ Submit button uses custom color
- ✅ Color applies to all buttons (Login, Register, Reset, Logout)
- ✅ Color persists after refresh

**Actual Result:** ___

---

### Test 6.5: Custom Button Text Color
**Steps:**
1. Click color picker for "Primary Button Text Color"
2. Select white text on dark background
3. Save
4. Visit login page

**Expected Result:**
- ✅ Button text uses custom color
- ✅ Text readable on button background
- ✅ Multiple colors don't clash

**Actual Result:** ___

---

### Test 6.6: Custom CSS
**Steps:**
1. Add custom CSS rule:
   ```css
   .easy-auth-btn { border-radius: 0 !important; }
   ```
2. Save
3. Visit login page

**Expected Result:**
- ✅ Custom CSS applied
- ✅ Buttons have square corners (no border-radius)
- ✅ CSS doesn't break form layout

**Actual Result:** ___

---

### Test 6.7: Custom CSS - Multiple Rules
**Steps:**
1. Add multiple rules:
   ```css
   .easy-auth-form input { font-size: 18px; }
   .easy-auth-tabs { gap: 0; }
   ```
2. Save
3. Visit login page

**Expected Result:**
- ✅ All CSS rules applied
- ✅ Form still functions correctly
- ✅ No conflicts or errors

**Actual Result:** ___

---

### Test 6.8: Mobile Responsiveness
**Steps:**
1. Visit login page on mobile device (or use DevTools)
2. Test at: 320px, 480px, 768px widths
3. Try all tabs and social buttons

**Expected Result:**
- ✅ Form stacks correctly on small screens
- ✅ Buttons full-width and tappable (min 44px)
- ✅ Text readable without zooming
- ✅ All features work on mobile

**Actual Result:** ___

---

## Security Tests

### Test 7.1: Honeypot Field Detection
**Steps:**
1. Enable "Anti-Spam Honeypot"
2. Save
3. Use browser DevTools to find hidden `company_name` field
4. Try to submit registration with that field filled

**Expected Result:**
- ✅ Hidden field exists in registration form
- ✅ When filled, submission rejected
- ✅ Error message: "Spam detected"
- ✅ User not created

**Actual Result:** ___

---

### Test 7.2: Rate Limiting - Failed Attempts
**Steps:**
1. Enable "Enable Login Lockout"
2. Set "Max Retries" to 2
3. Set "Lockout Duration" to 1 minute
4. Save
5. Try to log in with wrong password 3 times

**Expected Result:**
- ✅ First 2 attempts fail with "Invalid credentials"
- ✅ 3rd attempt blocked with "Too many failed attempts"
- ✅ IP address locked for 1 minute
- ✅ After 1 minute, can try again

**Actual Result:** ___

---

### Test 7.3: Rate Limiting - Different Users, Same IP
**Steps:**
1. From same IP, try wrong password for 5 different user accounts
2. Verify IP is locked, not individual users

**Expected Result:**
- ✅ IP locked (not individual users)
- ✅ All users from that IP blocked
- ✅ Other IPs unaffected

**Actual Result:** ___

---

### Test 7.4: Rate Limiting - Lockout Expiry
**Steps:**
1. Trigger lockout by failed attempts
2. Wait for lockout duration to expire
3. Try to log in again

**Expected Result:**
- ✅ After expiry, login attempts allowed again
- ✅ No manual intervention needed
- ✅ Transient lockout entry removed from logs

**Actual Result:** ___

---

### Test 7.5: Cloudflare Turnstile - Credentials Saved
**Steps:**
1. Go to Security tab
2. Enable "Enable Cloudflare Turnstile"
3. Enter Site Key and Secret Key
4. Save

**Expected Result:**
- ✅ Credentials saved
- ✅ Values persist
- ✅ Can toggle on/off independently

**Actual Result:** ___

---

### Test 7.6: Turnstile Widget Display (if configured)
**Steps:**
1. Configure Turnstile with valid keys
2. Save
3. Visit registration page

**Expected Result (when enabled):**
- ✅ Turnstile widget appears in form
- ✅ User can complete challenge
- ✅ Form submits after verification

**Actual Result:** ___

---

### Test 7.7: Nonce Verification
**Steps:**
1. Open browser DevTools Console
2. Try to call login endpoint without nonce:
   ```javascript
   fetch('/wp-json/easy-auth/v1/login', {
     method: 'POST',
     body: JSON.stringify({username: 'admin', password: 'test'})
   })
   ```

**Expected Result:**
- ✅ Request rejected with 403 error
- ✅ Error message: "Invalid security token"
- ✅ No user logged in
- ✅ Nonce validation works

**Actual Result:** ___

---

### Test 7.8: CSRF Protection - State Parameter
**Steps:**
1. Initiate Google OAuth
2. Capture the state parameter
3. Try to reuse old state parameter in callback

**Expected Result:**
- ✅ Old state rejected
- ✅ Error message displayed
- ✅ User not logged in
- ✅ Transient state cleared after use

**Actual Result:** ___

---

## Frontend Form Tests

### Test 8.1: Login Tab - Valid Credentials
**Steps:**
1. Go to login page with shortcode
2. Enter correct username and password
3. Click Login button

**Expected Result:**
- ✅ No page reload (AJAX)
- ✅ Success message: "Login successful"
- ✅ Redirected to dashboard/custom page
- ✅ User is logged in

**Actual Result:** ___

---

### Test 8.2: Login Tab - Invalid Credentials
**Steps:**
1. Enter invalid username or password
2. Click Login button

**Expected Result:**
- ✅ Error message: "Invalid credentials"
- ✅ Form not cleared (user sees what they entered)
- ✅ Not logged in
- ✅ Can retry immediately

**Actual Result:** ___

---

### Test 8.3: Login Tab - Empty Fields
**Steps:**
1. Leave username and password empty
2. Click Login button

**Expected Result:**
- ✅ HTML5 validation: "required" error
- ✅ Error message: "Username and password are required"
- ✅ Form not submitted

**Actual Result:** ___

---

### Test 8.4: Register Tab - Valid Input
**Steps:**
1. Click Register tab
2. Enter unique username, email, password
3. Click Register button

**Expected Result:**
- ✅ No page reload
- ✅ Success message: "Registration successful"
- ✅ User logged in automatically
- ✅ Redirected to welcome page
- ✅ User exists in WordPress with correct email

**Actual Result:** ___

---

### Test 8.5: Register Tab - Existing Email
**Steps:**
1. Enter email of existing WordPress user
2. Click Register button

**Expected Result:**
- ✅ Error message: "User already exists"
- ✅ Form not submitted
- ✅ No duplicate user created

**Actual Result:** ___

---

### Test 8.6: Register Tab - Existing Username
**Steps:**
1. Enter username of existing WordPress user
2. Click Register button

**Expected Result:**
- ✅ Error message: "User already exists"
- ✅ Form not submitted

**Actual Result:** ___

---

### Test 8.7: Register Tab - Weak Password
**Steps:**
1. Enter weak password (e.g., "123")
2. Observe strength meter

**Expected Result:**
- ✅ Strength meter shows red color
- ✅ User can still submit (no hard requirement)
- ✅ Account created with weak password (allowed)
- ✅ Meter updates as user types

**Actual Result:** ___

---

### Test 8.8: Register Tab - Strong Password
**Steps:**
1. Enter strong password (8+ chars, uppercase, number, symbol)
2. Observe strength meter

**Expected Result:**
- ✅ Strength meter shows green color
- ✅ Visual feedback is positive
- ✅ Encourages user to create secure password

**Actual Result:** ___

---

### Test 8.9: Tab Switching
**Steps:**
1. Click on Login, Register, Forgot, Magic Link tabs
2. Switch between tabs multiple times
3. Verify correct content in each tab

**Expected Result:**
- ✅ Smooth tab switching
- ✅ No form data loss when switching
- ✅ Active tab highlighted
- ✅ Message area cleared when switching

**Actual Result:** ___

---

### Test 8.10: Display on Mobile
**Steps:**
1. View login page on iPhone 12 simulator
2. Test all interactive elements with touch
3. Verify readability

**Expected Result:**
- ✅ Form readable without zoom
- ✅ Inputs easily tappable
- ✅ No horizontal scroll
- ✅ Tabs stacked or reorganized for mobile
- ✅ All buttons accessible

**Actual Result:** ___

---

## Magic Link Tests

### Test 9.1: Magic Link Request - Valid Email
**Steps:**
1. Click Magic Link tab
2. Enter email of existing WordPress user
3. Click "Email Magic Link"

**Expected Result:**
- ✅ No page reload
- ✅ Message: "If that account exists, a magic link has been sent"
- ✅ Email sent with magic link (check mail logs/SMTP)

**Actual Result:** ___

---

### Test 9.2: Magic Link Request - Non-existent Email
**Steps:**
1. Enter email that doesn't exist in WordPress
2. Click "Email Magic Link"

**Expected Result:**
- ✅ Same message as valid email (no user enumeration)
- ✅ No email sent
- ✅ Logs empty for non-existent user

**Actual Result:** ___

---

### Test 9.3: Magic Link Email Content
**Steps:**
1. Request magic link
2. Check email that arrives
3. Verify link format and content

**Expected Result:**
- ✅ Email has clear subject: "Your Magic Login Link"
- ✅ Email contains clickable URL
- ✅ URL format: `https://site.com/?easy_auth_magic=TOKEN&easy_auth_user=ID`
- ✅ Link is valid and accessible

**Actual Result:** ___

---

### Test 9.4: Magic Link Click - Successful Login
**Steps:**
1. Request magic link via email
2. Click the link in email
3. Verify logged in

**Expected Result:**
- ✅ User logged in automatically
- ✅ No password required
- ✅ Redirected to login destination page
- ✅ Session active

**Actual Result:** ___

---

### Test 9.5: Magic Link Expiry
**Steps:**
1. Request magic link
2. Wait 16+ minutes
3. Try to click the link

**Expected Result:**
- ✅ Link expires after 15 minutes
- ✅ Error message: "Magic link is invalid or expired"
- ✅ User not logged in
- ✅ User directed to request new link

**Actual Result:** ___

---

### Test 9.6: Magic Link Reuse
**Steps:**
1. Request magic link
2. Click it and log in
3. Log out
4. Try to click same link again

**Expected Result:**
- ✅ Link only works once
- ✅ Second click shows error: "Magic link is invalid or expired"
- ✅ User not logged in again

**Actual Result:** ___

---

### Test 9.7: Magic Link with Custom Redirect
**Steps:**
1. Request magic link on protected page (with `?redirect_to=` param)
2. Click magic link
3. Complete login

**Expected Result:**
- ✅ After login, redirected to originally requested page
- ✅ URL matches protected page

**Actual Result:** ___

---

## Avatar Sync Tests

### Test 10.1: Google Avatar Download
**Step:**
1. Log in with Google account that has profile picture
2. Check WordPress user page

**Expected Result:**
- ✅ Avatar downloaded and stored
- ✅ User meta shows `easy_auth_social_avatar_url`
- ✅ Avatar appears in WordPress user profile
- ✅ Can be viewed on site if theme supports

**Actual Result:** ___

---

### Test 10.2: GitHub Avatar Download
**Steps:**
1. Log in with GitHub account
2. Check if avatar synced

**Expected Result:**
- ✅ Avatar downloaded from GitHub
- ✅ Stored in WordPress
- ✅ Appears in user profile
- ✅ Update user meta entry

**Actual Result:** ___

---

### Test 10.3: Avatar Update on Re-login
**Steps:**
1. Log in with Google
2. Avatar synced
3. Log out
4. Log in again with same Google account
5. Change profile pic on Google
6. Log out and back in

**Expected Result:**
- ✅ Avatar updated on each login
- ✅ Latest image from provider shown
- ✅ Old avatar overwritten

**Actual Result:** ___

---

### Test 10.4: Avatar for User Without Picture
**Steps:**
1. Log in with a provider account with no profile picture
2. Check WordPress user profile

**Expected Result:**
- ✅ No error displayed
- ✅ Graceful fallback
- ✅ Default WordPress avatar shown
- ✅ User meta empty for avatar

**Actual Result:** ___

---

### Test 10.5: Avatar Image Formats
**Steps:**
1. Test login with accounts using different image formats:
   - JPEG
   - PNG
   - WebP (if provider supports)
2. Verify each syncs correctly

**Expected Result:**
- ✅ All formats handled
- ✅ Images stored as attachments
- ✅ Correct MIME types
- ✅ No client-side errors

**Actual Result:** ___

---

## Force Login Tests

### Test 11.1: Setup Force Login
**Steps:**
1. Go to Easy Auth Login → Redirection
2. Enable "Enable Redirect To Next Page"
3. Set "Force Login Redirect Page" to login page
4. Add 1-2 pages to "Protected Pages"
5. Save

**Expected Result:**
- ✅ Settings saved without error
- ✅ Protected pages added to list
- ✅ Can add multiple pages
- ✅ Can remove pages by clicking chip

**Actual Result:** ___

---

### Test 11.2: Redirect When Not Logged In
**Steps:**
1. Log out
2. Try to visit a protected page

**Expected Result:**
- ✅ Redirected to login page
- ✅ URL shows: `login-page/?redirect_to=protected-page-url`
- ✅ Login page loads without loop

**Actual Result:** ___

---

### Test 11.3: Return After Login
**Steps:**
1. From protected page redirect, log in
2. Observe redirect

**Expected Result:**
- ✅ After login, redirected back to original page
- ✅ Page content loads correctly
- ✅ User is logged in on that page
- ✅ No 404 or redirect loops

**Actual Result:** ___

---

### Test 11.4: Accessing Protected Page While Logged In
**Steps:**
1. Log in
2. Visit protected page directly

**Expected Result:**
- ✅ Page loads normally
- ✅ No redirect to login page
- ✅ Full access granted

**Actual Result:** ___

---

### Test 11.5: Accessing Non-Protected Page
**Steps:**
1. Log out
2. Visit unprotected page

**Expected Result:**
- ✅ Page loads normally
- ✅ No redirect
- ✅ Public content accessible

**Actual Result:** ___

---

### Test 11.6: Multiple Protected Pages
**Steps:**
1. Add 3 different pages to protected list
2. Log out
3. Try to access each one

**Expected Result:**
- ✅ Each page redirects to login
- ✅ Each with correct `redirect_to` parameter
- ✅ After login from each, returned to that page

**Actual Result:** ___

---

### Test 11.7: Disable Force Login
**Steps:**
1. Disable "Enable Redirect To Next Page"
2. Save
3. Log out
4. Try to access formerly protected page

**Expected Result:**
- ✅ Protected page accessible without login
- ✅ No redirect occurs
- ✅ Feature disabled cleanly

**Actual Result:** ___

---

## Error Handling Tests

### Test 12.1: Network Error During Login
**Steps:**
1. Go to login form
2. Disconnect internet or use DevTools to throttle connection
3. Try to log in

**Expected Result:**
- ✅ Error message: "Request failed. Please try again"
- ✅ Form not submitted
- ✅ Can retry after internet restored

**Actual Result:** ___

---

### Test 12.2: Invalid JSON Response
**Steps:**
1. Mock API to return invalid JSON
2. Try to submit form

**Expected Result:**
- ✅ Graceful error handling
- ✅ User-friendly message shown
- ✅ No console errors
- ✅ Form still usable

**Actual Result:** ___

---

### Test 12.3: 404 Response
**Steps:**
1. Mock API to return 404
2. Try to submit form

**Expected Result:**
- ✅ Error message displayed
- ✅ User knows request failed
- ✅ Can retry

**Actual Result:** ___

---

### Test 12.4: 500 Server Error
**Steps:**
1. Mock API to return 500
2. Try to submit form

**Expected Result:**
- ✅ Error message: "Request failed"
- ✅ Doesn't crash form
- ✅ User can retry

**Actual Result:** ___

---

### Test 12.5: CORS Error (if applicable)
**Steps:**
1. Try to make request from different domain
2. Verify CORS headers

**Expected Result:**
- ✅ REST API properly handles CORS
- ✅ Requests blocked if origin not allowed
- ✅ Or allowed if same-origin

**Actual Result:** ___

---

### Test 12.6: SQL Injection Attempt
**Steps:**
1. Try to submit username: `' OR '1'='1`
2. Try to submit password with SQL: `<script>alert(1)</script>`

**Expected Result:**
- ✅ All input sanitized
- ✅ Treated as literal string
- ✅ No database damage
- ✅ No XSS execution

**Actual Result:** ___

---

### Test 12.7: XSS Attempt in Form Fields
**Steps:**
1. Try to register with username: `<img src=x onerror=alert("xss")>`
2. Try to set email with script tags

**Expected Result:**
- ✅ All input sanitized
- ✅ Tags escaped in output
- ✅ No script execution
- ✅ Stored safely in database

**Actual Result:** ___

---

### Test 12.8: Missing Required Fields
**Steps:**
1. Manually bypass HTML validation using DevTools
2. Submit login with empty username

**Expected Result:**
- ✅ Server-side validation catches it
- ✅ Error: "Username and password are required"
- ✅ User not logged in

**Actual Result:** ___

---

## Regression Tests (Run Before Each Release)

### Test 13.1: Core Flow - Guest to Login to Logout
**Steps:**
1. Start as logged-out user
2. Visit login page
3. Log in
4. Verify logged in
5. Log out
6. Verify logged out

**Expected Result:**
- ✅ All steps work without errors
- ✅ User state correct at each step
- ✅ Redirects sensible

**Actual Result:** ___

---

### Test 13.2: Core Flow - Registration to Logout
**Steps:**
1. Start as guest
2. Register new account
3. Verify logged in
4. Log out
5. Try to log in with new credentials

**Expected Result:**
- ✅ New user created
- ✅ Password works
- ✅ All flows functional

**Actual Result:** ___

---

### Test 13.3: Social Login Flow - End-to-End
**Steps:**
1. Log in with Google
2. Log out
3. Log in with same Google account
4. Verify avatar still synced

**Expected Result:**
- ✅ All steps work
- ✅ Account linking works
- ✅ Avatar persists

**Actual Result:** ___

---

### Test 13.4: Admin Settings - All Tabs
**Steps:**
1. Go through each tab
2. Change settings
3. Save each
4. Reload page
5. Verify persistence

**Expected Result:**
- ✅ All settings persist
- ✅ No errors
- ✅ Frontend reflects changes

**Actual Result:** ___

---

### Test 13.5: Mobile Test - All Features
**Steps:**
1. Test on iPhone 12 simulator
2. Go through login, register, magic link, social login
3. Test redirects

**Expected Result:**
- ✅ All features work on mobile
- ✅ Forms readable
- ✅ No layout issues

**Actual Result:** ___

---

### Test 13.6: Performance - Page Load
**Steps:**
1. Disable caching
2. Load login page
3. Measure Time to First Byte (TTFB)
4. Measure size of assets

**Expected Result:**
- ✅ Page loads in < 2 seconds
- ✅ CSS file < 15KB
- ✅ JS file < 50KB
- ✅ No render-blocking resources

**Actual Result:** ___

---

### Test 13.7: Accessibility - Form Labels
**Steps:**
1. Use screen reader (NVDA, JAWS, VoiceOver)
2. Navigate form
3. Verify labels announced

**Expected Result:**
- ✅ All form fields labeled
- ✅ Labels announced by screen reader
- ✅ Keyboard navigation works
- ✅ Tab order logical

**Actual Result:** ___

---

## Summary Checklist

Use this to track overall test coverage:

- [ ] Setup & Activation (3 tests)
- [ ] Admin Dashboard (3 tests)
- [ ] General Settings (7 tests)
- [ ] Redirection (7 tests)
- [ ] Social Login (10 tests)
- [ ] Styling (8 tests)
- [ ] Security (8 tests)
- [ ] Frontend Forms (10 tests)
- [ ] Magic Links (7 tests)
- [ ] Avatar Sync (5 tests)
- [ ] Force Login (7 tests)
- [ ] Error Handling (8 tests)
- [ ] Regression (7 tests)

**Total: 101 Test Cases**

---

## Browser Compatibility

Test on:
- [ ] Chrome 90+
- [ ] Firefox 88+
- [ ] Safari 14+
- [ ] Edge 90+
- [ ] Mobile Chrome (Android)
- [ ] Mobile Safari (iOS)

---

## Device Compatibility

Test on:
- [ ] Desktop (1920x1080)
- [ ] Tablet (768x1024)
- [ ] Mobile (375x812)

---

## Notes for Testers

- Always test as both **logged-in** and **logged-out** users
- Always test with **JavaScript enabled** and **disabled**
- Check **browser console** for errors
- Check **network tab** for failed requests
- Test with **privacy mode** to ensure clean sessions
- Clear **browser cache** between major test sections
- Document any **unexpected behavior** with screenshots
- Note **browser version** when reporting issues

---

## Sign-Off

| Role | Name | Date | Result |
|------|------|------|--------|
| QA Tester | __________ | __________ | ✅ PASS / ❌ FAIL |
| Developer | __________ | __________ | __________ |
| Product Owner | __________ | __________ | __________ |

