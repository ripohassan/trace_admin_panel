# Deeplink Setup Guide

## Overview
The deeplink system allows you to create short, shareable URLs that redirect to specific admin panel pages. This is useful for quickly navigating to important sections or sending direct links to team members.

## URL Format

```
https://yourdomain.com/index.php?deeplink=<section>
```

**Alternative syntax:**
```
https://yourdomain.com/index.php?link=<section>
```

## Available Deeplinks

### User Management
- `?deeplink=users` → All Users
- `?deeplink=admin_users` → Admin Users
- `?deeplink=edit_user` → Edit User

### Ads Management
- `?deeplink=ads` → All Ads
- `?deeplink=add_ad` → Add New Ad
- `?deeplink=edit_ad` → Edit Ad
- `?deeplink=ads_settings` → Ads Settings

### Payments & Withdrawals
- `?deeplink=payments` → Payments
- `?deeplink=payouts` → Payouts
- `?deeplink=pending_withdrawals` → Pending Withdrawals
- `?deeplink=withdrawals` → All Withdrawals
- `?deeplink=coin_requests` → Coin Requests
- `?deeplink=coin_traders` → Coin Traders

### Agency Management
- `?deeplink=agency` → Agency List
- `?deeplink=agency_applications` → Agency Applications
- `?deeplink=agency_members` → Agency Members

### Content Management
- `?deeplink=gifts` → Gifts
- `?deeplink=gift_categories` → Gift Categories
- `?deeplink=announcements` → Announcements
- `?deeplink=banners` → Banners
- `?deeplink=categories` → Categories

### Frames, Effects & Themes
- `?deeplink=avatar_frames` → Avatar Frames
- `?deeplink=entrance_effects` → Entrance Effects
- `?deeplink=party_themes` → Party Themes

### Analytics & Stats
- `?deeplink=comments` → Comments
- `?deeplink=clicks` → Clicks
- `?deeplink=calls` → Calls
- `?deeplink=follows` → Follows
- `?deeplink=favorites` → Favorites

### Coins & Converter
- `?deeplink=coin_plans` → Coin Plans
- `?deeplink=converter_packages` → Converter Packages

### Content Approvals
- `?deeplink=photo_approvals` → Photo Approvals
- `?deeplink=hangout_approvals` → Hangout Approvals

### Other
- `?deeplink=messages` → Messages
- `?deeplink=panel` → Main Dashboard
- `?deeplink=dashboard` → Dashboard (alias for panel)

## Usage Examples

### Direct Links
```
https://myapp.com/index.php?deeplink=users
https://myapp.com/index.php?deeplink=payments
https://myapp.com/index.php?deeplink=agencies
```

### In HTML/Email
```html
<a href="index.php?deeplink=users">View All Users</a>
<a href="index.php?deeplink=payments">Check Payments</a>
```

### PHP Code
```php
// Using the DeepLinkRouter class
$url = DeepLinkRouter::generateUrl('users');
// Output: https://yourdomain.com/index.php?deeplink=users

// Checking if a deeplink exists
if (DeepLinkRouter::exists('users')) {
    echo "Deeplink exists!";
}

// Getting the file path
$path = DeepLinkRouter::getPath('users');
// Output: dashboard/all_users.php
```

## API Endpoints

### List All Deeplinks
```
GET /deeplink_api.php?action=list
```

**Response:**
```json
{
  "status": "success",
  "total": 45,
  "deeplinks": ["users", "ads", "payments", ...],
  "format": "index.php?deeplink=<name>"
}
```

### Get URL for a Deeplink
```
GET /deeplink_api.php?action=url&deeplink=users
```

**Response:**
```json
{
  "status": "success",
  "deeplink": "users",
  "url": "https://yourdomain.com/index.php?deeplink=users"
}
```

### Check if Deeplink Exists
```
GET /deeplink_api.php?action=check&deeplink=users
```

**Response:**
```json
{
  "status": "success",
  "deeplink": "users",
  "exists": true
}
```

### Get Detailed Deeplinks
```
GET /deeplink_api.php?action=detailed
```

**Response:**
```json
{
  "status": "success",
  "total": 45,
  "deeplinks": [
    {
      "name": "users",
      "path": "dashboard/all_users.php",
      "url": "https://yourdomain.com/index.php?deeplink=users",
      "example": "index.php?deeplink=users"
    },
    ...
  ]
}
```

## Security Considerations

1. **Authentication Required**: Users must be logged in to access deeplinked pages. If not logged in, they're redirected to the login page.
2. **Role-based Access**: The admin panel's existing permission system applies to all deeplinks.
3. **File Validation**: The system checks if the target file exists before redirecting.
4. **Error Handling**: Invalid deeplinks are logged and redirect to the main dashboard.

## Adding New Deeplinks

To add a new deeplink:

1. Open `DeepLinkRouter.php`
2. Add a new entry to the `$deeplinks` array:
   ```php
   'my_new_page' => 'dashboard/my_new_page.php',
   ```
3. Use it: `index.php?deeplink=my_new_page`

## Testing

You can test deeplinks in multiple ways:

### Command Line
```bash
curl "https://yourdomain.com/deeplink_api.php?action=list"
curl "https://yourdomain.com/deeplink_api.php?action=url&deeplink=users"
```

### Browser
Visit: `https://yourdomain.com/deeplink_api.php?action=detailed`

### Direct Link
Visit: `https://yourdomain.com/index.php?deeplink=users`

## Troubleshooting

### Deeplink not working?
- Verify the deeplink name exists in `DeepLinkRouter::$deeplinks`
- Check that the target file path exists
- Ensure you're logged in
- Check error logs for details

### Getting "Unknown deeplink" error?
- Double-check the spelling (deeplinks are case-insensitive)
- Verify the deeplink exists using the API: `deeplink_api.php?action=check&deeplink=<name>`
- Review the file at [deeplink_api.php](deeplink_api.php?action=detailed) for all available deeplinks

## Files Involved

- **DeepLinkRouter.php** - Core deeplink routing class
- **deeplink_api.php** - API endpoint for managing deeplinks
- **index.php** - Modified to handle deeplink parameters

---

**Need to add more deeplinks?** Simply update the `$deeplinks` array in `DeepLinkRouter.php`!
