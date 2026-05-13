# Deeplink Setup Guide

## Overview
The deeplink system allows you to create short, shareable URLs that redirect to specific admin panel pages. The system uses a JSON-based configuration for easy management and extensibility.

**Configuration File:** `config/deeplinks.json`

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
// Initialize the router
DeepLinkRouter::init();

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

// Get all deeplinks
$all = DeepLinkRouter::getAll();

// Get organized deeplinks by category (with metadata)
$organized = DeepLinkRouter::getOrganized();
```

## API Endpoints

### List All Deeplinks (Flat)
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

### Get Deeplinks by Category (Default)
```
GET /deeplink_api.php?action=organized
```

**Response:**
```json
{
  "status": "success",
  "total": 45,
  "deeplinks": {
    "user_management": {
      "label": "User Management",
      "links": {
        "users": {
          "path": "dashboard/all_users.php",
          "label": "All Users",
          "icon": "fa-users"
        },
        ...
      }
    },
    ...
  }
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

## Configuration

### Modifying Deeplinks

The deeplink configuration is stored in `config/deeplinks.json`. Each deeplink is organized by category.

**Structure:**
```json
{
  "deeplinks": {
    "category_name": {
      "label": "Category Label",
      "links": {
        "deeplink_name": {
          "path": "path/to/page.php",
          "label": "Display Label",
          "icon": "fa-icon-name"
        }
      }
    }
  }
}
```

### Adding a New Deeplink

1. Open `config/deeplinks.json`
2. Find or create the appropriate category
3. Add a new link entry:
   ```json
   "my_new_page": {
     "path": "dashboard/my_new_page.php",
     "label": "My New Page",
     "icon": "fa-star"
   }
   ```
4. Save the file
5. Use it: `index.php?deeplink=my_new_page`

## Security Considerations

1. **Authentication Required**: Users must be logged in to access deeplinked pages. If not logged in, they're redirected to the login page.
2. **Role-based Access**: The admin panel's existing permission system applies to all deeplinks.
3. **File Validation**: The system checks if the target file exists before redirecting.
4. **Error Handling**: Invalid deeplinks are logged and redirect to the main dashboard.
5. **JSON Validation**: Configuration is validated on load; invalid configs gracefully fall back to empty array.

## Files Involved

- **config/deeplinks.json** - Central configuration file
- **DeepLinkRouter.php** - Core deeplink routing class
- **deeplink_api.php** - API endpoint for managing deeplinks
- **index.php** - Modified to handle deeplink parameters

## Testing

You can test deeplinks in multiple ways:

### Command Line
```bash
curl "https://yourdomain.com/deeplink_api.php?action=list"
curl "https://yourdomain.com/deeplink_api.php?action=organized"
curl "https://yourdomain.com/deeplink_api.php?action=url&deeplink=users"
```

### Browser
Visit: `https://yourdomain.com/deeplink_api.php?action=organized`

### Direct Link
Visit: `https://yourdomain.com/index.php?deeplink=users`

## Troubleshooting

### Deeplink not working?
- Verify the deeplink name exists in `config/deeplinks.json`
- Check that the target file path exists
- Ensure you're logged in
- Check error logs for details

### Getting "Unknown deeplink" error?
- Double-check the spelling (deeplinks are case-insensitive)
- Verify the deeplink exists using the API: `deeplink_api.php?action=check&deeplink=<name>`
- Review the configuration: `deeplink_api.php?action=organized`

### Configuration not loading?
- Verify `config/deeplinks.json` is readable
- Check JSON syntax validity using an online JSON validator
- Check PHP error logs

---

**Need to add or modify deeplinks?** Simply update `config/deeplinks.json`!
