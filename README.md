# Easy Auth Login

A lightweight, modern, AJAX-powered WordPress authentication plugin with social login, magic links, and force-login redirects. Built for speed, security, and frictionless user experience.

**Version:** 0.1.0  
**Requires:** WordPress 6.4+, PHP 7.4+  
**License:** GPL v2

---

## Table of Contents

1. [Key Features](#key-features)
2. [Installation Guide](#installation-guide)
3. [Configuration](#configuration)
4. [User Manual](#user-manual)
5. [Shortcode & Block Reference](#shortcode--block-reference)
6. [Social Login Setup](#social-login-setup)
7. [Force Login & Redirect](#force-login--redirect)
8. [Developer Hooks](#developer-hooks)
9. [Troubleshooting](#troubleshooting)

---

## Key Features

### ⚡ Core Authentication
- **AJAX Login/Register/Forgot Password** - Zero-page-reload authentication
- **Magic Link Login** - Secure, one-time passwordless login via email
- **Custom Login Page** - Replace default wp-login.php with branded shortcode/block
- **Password Visibility Toggle** - Show/hide password with eye icon
- **Live Password Strength Meter** - Real-time feedback during registration (Red/Yellow/Green)
- **Already Logged-In State** - Smart detection and display when user is authenticated
- **Inline Validation Errors** - Immediate user feedback on form submission

### 🌐 Social Login
- **Google OAuth 2.0** - 1-click login with automatic account creation
- **GitHub OAuth 2.0** - 1-click login with automatic account creation
- **Profile Picture Sync** - Automatically pull and store user avatars
- **Smart Account Linking** - Link social accounts to existing users by email
- **Unique Username Generation** - Auto-create conflict-free WordPress usernames

### 🔐 Security
- **Rate Limiting** - Configurable login attempt lockout (brute-force protection)
- **Honeypot Field** - Lightweight spam prevention
- **Cloudflare Turnstile** - Optional advanced bot protection (frictionless)
- **Nonce Verification** - CSRF protection on all REST endpoints
- **State Parameter Validation** - CSRF protection for OAuth flows
- **Secure Password Generation** - Cryptographically strong passwords for social users
- **URL Validation** - Safe redirect handling with WordPress validation

### 🎯 Redirection & Flow Logic
- **Global Redirects** - Set different redirect URLs for login, registration, logout
- **AJAX Page Picker** - Admin-friendly page selection UI (no manual page IDs)
- **Force Login** - Redirect logged-out users to login page when accessing protected pages
- **Redirect to Next Page** - Return users to the page they originally requested after auth
- **Smart Defaults** - Falls back to sensible defaults when no custom redirect set

### 🎨 Customization
- **Form Style Themes** - Light, Dark, or Inherit from Theme
- **Dynamic Button Colors** - Button and text color pickers in admin
- **Custom CSS Support** - Add custom styles via admin textarea
- **Shortcode & Block** - Native Gutenberg block + WordPress shortcode
- **Responsive Design** - Mobile-optimized forms

### ⚙️ Admin Dashboard
- **Tabbed Settings** - Organized into 5 clear tabs:
  - General (AJAX, login view, password toggle)
  - Redirection (post-auth redirect targets)
  - Social Login (OAuth credentials)
  - Styling (colors, themes, custom CSS)
  - Security (rate limiting, Turnstile, honeypot)
- **Live Page Search** - AJAX-powered page selection for redirects
- **Multi-Page Protected List** - Easy selection of pages requiring authentication

---

## Installation Guide

### 1. Manual Installation

1. Download the `easy-auth-login` plugin folder
2. Upload to `/wp-content/plugins/` via SFTP or file manager
3. Activate the plugin from **Plugins** → **Installed Plugins**

### 2. Verify Activation

After activation, you should see:
- New menu item: **Easy Auth Login** in WordPress sidebar
- 5 tabs under settings: General, Redirection, Social Login, Styling, Security

### 3. Initial Setup

#### Minimum Setup (5 minutes)
1. Go to **Easy Auth Login** → **General**
2. Enable "Enable AJAX Login" (default: on)
3. Enable "Enable AJAX Registration" (default: on)
4. Click **Save Settings**

#### Social Login Setup (15 minutes per provider)
See [Social Login Setup](#social-login-setup) below for detailed OAuth setup.

#### Force Login Setup (10 minutes)
See [Force Login & Redirect](#force-login--redirect) below.

---

## Configuration

### General Tab

| Setting | Default | Description |
|---------|---------|-------------|
| Enable AJAX Login | ✓ On | Use AJAX for login (zero reload) |
| Enable AJAX Registration | ✓ On | Use AJAX for registration (zero reload) |
| Default Login View | Inline Form | Show login form inline or as modal |
| Already Logged In View | ✓ On | Show "Welcome back" message to authenticated users |
| Password Visibility Toggle | ✓ On | Show eye icon to toggle password visibility |

### Redirection Tab

| Setting | Description |
|---------|-------------|
| After Login Redirect Page | Page users see after successful login (e.g., Dashboard) |
| After Registration Redirect Page | Page users see after registration (e.g., Welcome) |
| After Logout Redirect Page | Page users see after logout (e.g., Homepage) |
| Enable Redirect To Next Page | Capture and return users to protected page after auth |
| Force Login Redirect Page | **Required if enabling "Redirect To Next Page"** - Page containing login shortcode |
| Protected Pages | List pages that require login (users redirected if not logged in) |

### Social Login Tab

| Setting | Required | Source |
|---------|----------|--------|
| Google Client ID | If using Google | [Google Cloud Console](https://console.cloud.google.com) |
| Google Client Secret | If using Google | [Google Cloud Console](https://console.cloud.google.com) |
| GitHub Client ID | If using GitHub | [GitHub Settings](https://github.com/settings/apps) |
| GitHub Client Secret | If using GitHub | [GitHub Settings](https://github.com/settings/apps) |

### Styling Tab

| Setting | Default | Description |
|---------|---------|-------------|
| Form Style | Inherit from Theme | Light, Dark, or match site theme |
| Primary Button Color | #146ef5 (Blue) | Color picker for submit button |
| Primary Button Text Color | #ffffff (White) | Color picker for button text |
| Custom CSS | (empty) | Add custom CSS rules for styling |

### Security Tab

| Setting | Default | Description |
|---------|---------|-------------|
| Anti-Spam Honeypot | ✓ On | Hidden form field to catch bots |
| Enable Cloudflare Turnstile | Off | Advanced bot protection (requires API keys) |
| Turnstile Site Key | (empty) | From Cloudflare dashboard |
| Turnstile Secret Key | (empty) | From Cloudflare dashboard |
| Enable Login Lockout | ✓ On | Lock out after failed attempts |
| Max Retries | 5 | Failed login attempts before lockout |
| Lockout Duration | 15 (minutes) | How long to lock out IP address |

---

## User Manual

### For End Users

#### Login
1. Navigate to the page with the `[easy_auth_login]` shortcode
2. Enter username (or email) and password
3. Click **Login**
4. You're redirected to your dashboard (or custom redirect page)

#### Registration
1. Click **Register** tab on login form
2. Enter username, email, password
3. Password strength meter shows requirements (Red → Yellow → Green)
4. Click **Register**
5. Account created, automatically logged in
6. Redirected to welcome page (or custom redirect)

#### Forgot Password
1. Click **Forgot** tab on login form
2. Enter username or email
3. Click **Send Reset Link**
4. Check email for reset link
5. Click link to reset password

#### Magic Link (Passwordless Login)
1. Click **Magic Link** tab on login form
2. Enter email address
3. Click **Email Magic Link**
4. Check email for magic link
5. Click link to log in (no password needed)
6. Automatically logged in and redirected

#### Social Login
1. Click **Continue with Google** or **Continue with GitHub**
2. Approve on provider's site
3. Automatically logged in
4. Profile pic automatically added to account

#### Enhanced UX Features
- **Password Show/Hide** - Click eye icon to toggle visibility
- **Already Logged In** - If authenticated, form hidden and "Welcome back" shown with Logout button
- **Password Strength** - Visual progress bar during registration
- **Force Login** - If you visit a protected page while logged out, automatically redirected to login
- **Return After Auth** - After logging in from a protected page, returned to that exact page

### For Administrators

#### Managing Users
- Social login creates WordPress users automatically
- Users can log in via password or social provider
- Avatar automatically synced from social profile (visible in wp-admin)

#### Viewing Login Attempts
- All failed login attempts logged in `wp_easy_auth_logs` table
- IP lockouts tracked with expiry times
- Manual clear via database if needed

#### Customizing Redirects
1. Go to **Easy Auth Login** → **Redirection**
2. For each redirect type (Login, Register, Logout):
   - Click search field
   - Type page name
   - Click to select
   - Click **Save Settings**

#### Setting Up Protected Pages
1. Go to **Easy Auth Login** → **Redirection**
2. Enable **Enable Redirect To Next Page**
3. Set **Force Login Redirect Page** (your login page)
4. In **Protected Pages**:
   - Search for a page
   - Click to add it
   - Click the chip again to remove
5. Click **Save Settings**

Now any logged-out user visiting protected pages is redirected to your login page, then back to the original page after auth.

---

## Shortcode & Block Reference

### Shortcode: `[easy_auth_login]`

Display the authentication form anywhere.

#### Basic Usage
```
[easy_auth_login]
```

#### Where to Use
- Pages (recommended)
- Posts
- Any page/post using shortcode

#### Output
- **For logged-out users**: Tabbed form with Login, Register, Forgot, Magic Link tabs + Social buttons
- **For logged-in users**: "Welcome back, [Name]" message + Logout button

#### Attributes
Currently no attributes. Future versions may support custom styling via shortcode attributes.

### Gutenberg Block: Easy Auth Login

Display the authentication form in the block editor.

#### How to Insert
1. Edit a Page
2. Click **+** to add block
3. Search for **Easy Auth Login**
4. Click to insert
5. Block displays on frontend with same output as shortcode

#### Advantages over Shortcode
- Native block editor integration
- Searchable in block inserter
- No need to memorize shortcode syntax
- Better theme integration

### Combining with Other Plugins
- **Works with:** Elementor (use post/page with embedded shortcode)
- **Works with:** WooCommerce (place on custom page)
- **Works with:** Multisite (per-site credentials)

---

## Social Login Setup

### Google OAuth 2.0 Setup

#### Prerequisites
- Google account with developer access
- Site running on HTTPS (required by Google)

#### Step-by-Step

1. **Create Google Cloud Project**
   - Go to [Google Cloud Console](https://console.cloud.google.com)
   - Click **Create Project**
   - Name it "Easy Auth Login" (or your site name)
   - Click **Create**

2. **Enable Google+ API**
   - Search for "Google+ API"
   - Click **Google+ API**
   - Click **Enable**

3. **Create OAuth 2.0 Credentials**
   - Go to **Credentials** (left sidebar)
   - Click **+ Create Credentials**
   - Choose **OAuth Client ID**
   - If prompted, set up OAuth consent screen first:
     - Choose **External** user type
     - Fill required fields (app name, user email, etc.)
     - Add scopes: `email`, `profile`, `openid`
     - Add test users (optional)
     - Save and continue

4. **Configure OAuth Client**
   - Application type: **Web application**
   - Name: "Easy Auth Login"
   - Authorized redirect URIs: 
     - `https://yoursite.com/` (replace with your domain)
     - Add if running locally: `http://localhost/`
   - Click **Create**
   - Copy **Client ID** and **Client Secret**

5. **Save in WordPress**
   - Go to **Easy Auth Login** → **Social Login**
   - Enable **Enable Google Login**
   - Paste **Client ID**
   - Paste **Client Secret**
   - Click **Save Settings**

6. **Test**
   - Go to your login page
   - Click **Continue with Google**
   - Should redirect to Google consent screen

### GitHub OAuth Setup

#### Prerequisites
- GitHub account
- Site accessible from GitHub (localhost won't work, use ngrok or similar for testing)

#### Step-by-Step

1. **Create OAuth App**
   - Go to [GitHub Settings → Developer Settings → OAuth Apps](https://github.com/settings/developers)
   - Click **New OAuth App**

2. **Fill Application Details**
   - Application name: "Easy Auth Login"
   - Homepage URL: `https://yoursite.com`
   - Authorization callback URL: `https://yoursite.com/` (note the trailing slash)
   - Description: "Social login for my site" (optional)
   - Check **Request user authorization (OAuth) during installation** (optional)
   - Click **Register application**

3. **Get Credentials**
   - You'll see **Client ID** immediately
   - Click **Generate a new client secret**
   - Copy the secret (it won't display again)

4. **Save in WordPress**
   - Go to **Easy Auth Login** → **Social Login**
   - Enable **Enable GitHub Login**
   - Paste **Client ID**
   - Paste **Client Secret**
   - Click **Save Settings**

5. **Test**
   - Go to your login page
   - Click **Continue with GitHub**
   - Should redirect to GitHub authorize page

### Troubleshooting Social Login

**"Provider is not configured"**
- Ensure Client ID and Secret are saved in admin
- Check they're not empty in settings

**"Failed to exchange code for token"**
- Verify callback URL matches EXACTLY in provider settings
- For Google: must be HTTPS in production
- For GitHub: must be publicly accessible

**"Failed to retrieve user information"**
- Check API credentials are correct
- Verify scopes are correct (Google: email, profile, openid)

---

## Force Login & Redirect

### What It Does
Automatically redirects logged-out users to your login page when they try to access protected content, then returns them to the original page after authentication.

**Scenario:** You have a booking page at `/book-ticket`. A visitor clicks the link but isn't logged in. They're redirected to `/login` with `?redirect_to=/book-ticket`. After logging in, they're sent back to `/book-ticket`.

### Setup

1. **Create or Select Login Page**
   - Go to Pages
   - Create a new page called "Login" (or use existing)
   - Add shortcode: `[easy_auth_login]`
   - Publish

2. **Configure in Admin**
   - Go to **Easy Auth Login** → **Redirection**
   - Enable **Enable Redirect To Next Page**
   - Set **Force Login Redirect Page** to your login page
   - Save

3. **Mark Protected Pages**
   - In same tab, scroll to **Protected Pages (Force Login)**
   - Search for page name (e.g., "Book Ticket")
   - Click to add to list
   - Repeat for other protected pages
   - Save Settings

4. **Test**
   - Log out
   - Try to visit a protected page
   - Should redirect to login with `?redirect_to=original-page-url`
   - After login, should return to original page

### Advanced: Multiple Protected Page Types

**Scenario:** Only allow paid members to access certain pages.

Currently, the force-login feature applies equally to all protected pages. For role-based protection, use:
- WordPress [Members Plugin](https://wordpress.org/plugins/members/)
- Or [WooCommerce Memberships](https://woocommerce.com/products/woocommerce-memberships/)
- Integration with Easy Auth Login for seamless redirect

---

## Developer Hooks

### REST API Endpoints

#### 1. Login
```
POST /wp-json/easy-auth/v1/login
```
**Parameters:**
- `username` (string, required)
- `password` (string, required)
- `redirect_to` (string, optional) - URL to redirect after login

**Response:**
```json
{
  "success": true,
  "message": "Login successful.",
  "redirect_url": "https://site.com/dashboard"
}
```

#### 2. Register
```
POST /wp-json/easy-auth/v1/register
```
**Parameters:**
- `username` (string, required)
- `email` (string, required)
- `password` (string, required)
- `company_name` (string, honeypot - leave empty)
- `redirect_to` (string, optional)

**Response:**
```json
{
  "success": true,
  "message": "Registration successful.",
  "redirect_url": "https://site.com/welcome"
}
```

#### 3. Forgot Password
```
POST /wp-json/easy-auth/v1/forgot-password
```
**Parameters:**
- `user_login` (string, required) - Username or email

**Response:**
```json
{
  "success": true,
  "message": "Password reset email sent."
}
```

#### 4. Logout
```
POST /wp-json/easy-auth/v1/logout
```
**Parameters:** None

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully.",
  "redirect_url": "https://site.com/"
}
```

#### 5. Magic Link Request
```
POST /wp-json/easy-auth/v1/magic-link/request
```
**Parameters:**
- `email` (string, required)
- `redirect_to` (string, optional)

**Response:**
```json
{
  "success": true,
  "message": "If that account exists, a magic link has been sent."
}
```

#### 6. OAuth Authorize
```
POST /wp-json/easy-auth/v1/oauth/authorize
```
**Parameters:**
- `provider` (string, required) - "google" or "github"

**Response:**
```json
{
  "success": true,
  "auth_url": "https://accounts.google.com/o/oauth2/v2/auth?..."
}
```

### WordPress Hooks (Filters)

#### `easy_auth_redirect_url`
Modify redirect URL before redirecting.

```php
add_filter('easy_auth_redirect_url', function($redirect_url, $flow, $user_id) {
    if($flow === 'login' && user_has_role($user_id, 'editor')) {
        return admin_url();
    }
    return $redirect_url;
}, 10, 3);
```

### Data Storage

**User Meta Keys** (for custom queries):
- `easy_auth_google_id` - Google account ID
- `easy_auth_github_id` - GitHub account ID
- `easy_auth_social_avatar_url` - Profile picture URL
- `easy_auth_avatar_attachment_id` - Local avatar attachment ID

**Database Table** (login attempts):
- `wp_easy_auth_logs` - Columns: id, ip_address, username_attempted, time, lockout_expiry

---

## Troubleshooting

### "Not Found - The requested URL was not found on this server"

**Cause:** Redirect URL is malformed.

**Solutions:**
1. Confirm the page you're redirecting to exists
2. Check in **Redirection** tab that correct page is selected
3. Verify page is published and not in draft
4. Check page slug doesn't have special characters
5. Clear browser cache and try again

### AJAX Login Returns Error

**"Request failed. Please try again"**
1. Check browser console (F12 → Console tab)
2. Verify nonce is being generated (check `EasyAuthLoginData.nonce`)
3. Ensure REST API is enabled (usually default)
4. Check WordPress permalinks are enabled

**"Too many failed attempts. Please try later"**
1. User/IP hit login attempt limit
2. Check **Security** tab for lockout duration (default 15 min)
3. Wait for timeout or clear manually in database

### Social Login Not Working

**Google step missing after clicking button:**
1. Check Client ID and Secret are saved
2. Verify redirect URI exactly matches Google Cloud settings
3. Check site is HTTPS (Google requires it)

**GitHub redirect loop:**
1. Verify callback URL in GitHub settings includes trailing slash
2. Check site is publicly accessible for GitHub to call back
3. Clear browser cache and cookies

### Password Reset Not Sent

1. Check email configuration in WordPress
2. Verify SMTP plugin installed (optional but recommended)
3. Check spam folder
4. Verify user email is correct in WordPress

### Magic Link Expired

**"Magic link is invalid or expired"**
1. Magic links valid for 15 minutes
2. User must click link before expiry
3. Resend link if expired

---

## Support & Contributing

For issues, feature requests, or contributions:
- Check [TEST.md](TEST.md) for test cases to verify functionality
- Review code in `/includes` folder for implementation details
- All endpoints follow WordPress REST API standards
- All functions use proper escaping and sanitization

---

## Security Notes

- ✅ All REST endpoints require nonce verification
- ✅ Passwords never stored in logs
- ✅ OAuth state parameter prevents CSRF attacks
- ✅ Rate limiting prevents brute force
- ✅ Honeypot field catches bots
- ✅ URLs validated with `wp_validate_redirect()`
- ⚠️ Always use HTTPS in production for social login
- ⚠️ Keep OAuth credentials in environment variables if possible

---

## Changelog

### Version 0.1.0 (Initial Release)
- Core AJAX authentication (login, register, forgot password)
- Magic link passwordless login
- Google and GitHub OAuth 2.0 with account linking
- Avatar sync from social profiles
- Force login with redirect-to-next
- Rate limiting and spam protection
- Shortcode and Gutenberg block
- Tabbed admin dashboard
- AJAX page picker for redirects
