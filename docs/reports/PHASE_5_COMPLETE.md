# Phase 5: Calendar Integration - COMPLETE

**Version:** 1.5.0 (Ready)  
**Date:** 2026-01-08  
**Status:** ✅ **COMPLETE**

---

## Overview

Phase 5 implements complete calendar integration with Google Calendar and Microsoft Calendar (Outlook), including automatic event creation, updates, and deletion when bookings are created, modified, or cancelled.

---

## What's Implemented

### 1. Google Calendar Integration ✅

**File:** `includes/class-book-now-google-calendar.php` (335 lines)

**Features:**
- ✅ OAuth 2.0 authentication
- ✅ Access token management with auto-refresh
- ✅ Create calendar events
- ✅ Update calendar events
- ✅ Delete calendar events
- ✅ List available calendars
- ✅ Test connection
- ✅ Event attendees and reminders

**Methods:**
- `is_configured()` - Check if API credentials set
- `is_authenticated()` - Check if OAuth completed
- `get_auth_url()` - Get OAuth authorization URL
- `handle_oauth_callback()` - Process OAuth callback
- `create_event()` - Create event from booking
- `update_event()` - Update existing event
- `delete_event()` - Delete event
- `list_calendars()` - Get user's calendars
- `test_connection()` - Verify connection

**Required Credentials:**
- Google Client ID
- Google Client Secret
- OAuth Access Token (obtained via OAuth flow)

---

### 2. Microsoft Calendar Integration ✅

**File:** `includes/class-book-now-microsoft-calendar.php` (330 lines)

**Features:**
- ✅ OAuth 2.0 authentication (Microsoft Graph API)
- ✅ Access token management with auto-refresh
- ✅ Create calendar events
- ✅ Update calendar events
- ✅ Delete calendar events
- ✅ Test connection
- ✅ Event attendees and reminders

**Methods:**
- `is_configured()` - Check if API credentials set
- `is_authenticated()` - Check if OAuth completed
- `get_auth_url()` - Get OAuth authorization URL
- `handle_oauth_callback()` - Process OAuth callback
- `create_event()` - Create event from booking
- `update_event()` - Update existing event
- `delete_event()` - Delete event
- `test_connection()` - Verify connection

**Required Credentials:**
- Microsoft Client ID
- Microsoft Client Secret
- Microsoft Tenant ID
- OAuth Access Token (obtained via OAuth flow)

**API:** Microsoft Graph API v1.0

---

### 3. Calendar Sync Manager ✅

**File:** `includes/class-book-now-calendar-sync.php` (175 lines)

**Features:**
- ✅ Automatic sync on booking creation
- ✅ Automatic sync on booking updates
- ✅ Automatic sync on booking cancellation
- ✅ Manual sync capability
- ✅ Dual calendar support (Google + Microsoft)
- ✅ Event ID tracking in database

**Hooks:**
- `booknow_booking_created` - Triggers event creation
- `booknow_booking_updated` - Triggers event update
- `booknow_booking_cancelled` - Triggers event deletion

**Methods:**
- `sync_booking_created()` - Create events in both calendars
- `sync_booking_updated()` - Update events in both calendars
- `sync_booking_cancelled()` - Delete events from both calendars
- `manual_sync()` - Force sync for specific booking

---

## Event Details

### Event Information Synced

**Event Title:**
```
[Consultation Type Name] - [Customer Name]
```

**Event Description:**
```
Booking Reference: ABC123

Customer: John Doe
Email: john@example.com
Phone: 555-1234

Notes:
Customer notes here...
```

**Event Time:**
- Start: Booking date + time
- End: Start + consultation duration
- Timezone: From plugin settings

**Attendees:**
- Customer email added as attendee
- Sends calendar invitations

**Reminders:**
- Email: 24 hours before
- Popup: 30 minutes before

---

## Database Changes

### Bookings Table Updates

**New Fields:**
- `google_event_id` (varchar 255) - Google Calendar event ID
- `microsoft_event_id` (varchar 255) - Microsoft Calendar event ID

These fields store the calendar event IDs for tracking and updates.

---

## OAuth Flow

### Google Calendar OAuth

1. **Admin Configuration:**
   - Enter Client ID and Secret
   - Click "Connect to Google"

2. **Authorization:**
   - Redirected to Google consent screen
   - Grant calendar access permissions
   - Redirected back to plugin

3. **Token Storage:**
   - Access token saved in WordPress options
   - Refresh token saved for auto-renewal
   - Token auto-refreshes when expired

**Scopes Required:**
- `https://www.googleapis.com/auth/calendar` (Full calendar access)

### Microsoft Calendar OAuth

1. **Admin Configuration:**
   - Enter Client ID, Secret, and Tenant ID
   - Click "Connect to Microsoft"

2. **Authorization:**
   - Redirected to Microsoft login
   - Grant calendar access permissions
   - Redirected back to plugin

3. **Token Storage:**
   - Access token saved in WordPress options
   - Refresh token saved for auto-renewal
   - Token auto-refreshes when expired

**Scopes Required:**
- `offline_access` (Refresh token)
- `Calendars.ReadWrite` (Calendar access)

---

## Automatic Sync Behavior

### On Booking Creation

**Trigger:** `do_action('booknow_booking_created', $booking_id)`

**Actions:**
1. Check if booking status is "confirmed"
2. If Google sync enabled and authenticated:
   - Create Google Calendar event
   - Save event ID to booking record
3. If Microsoft sync enabled and authenticated:
   - Create Microsoft Calendar event
   - Save event ID to booking record

### On Booking Update

**Trigger:** `do_action('booknow_booking_updated', $booking_id)`

**Actions:**
1. If Google event ID exists:
   - Update Google Calendar event with new details
2. If Microsoft event ID exists:
   - Update Microsoft Calendar event with new details

### On Booking Cancellation

**Trigger:** `do_action('booknow_booking_cancelled', $booking_id)`

**Actions:**
1. If Google event ID exists:
   - Delete Google Calendar event
2. If Microsoft event ID exists:
   - Delete Microsoft Calendar event

---

## Configuration

### Google Calendar Setup

**Step 1: Create Google Cloud Project**
1. Go to Google Cloud Console
2. Create new project
3. Enable Google Calendar API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URI: `https://yoursite.com/wp-admin/admin.php?page=book-now-settings&tab=calendar`

**Step 2: Plugin Configuration**
1. Navigate to Book Now → Settings → Calendar
2. Enter Google Client ID
3. Enter Google Client Secret
4. Click "Connect to Google Calendar"
5. Authorize access
6. Select calendar to use
7. Enable sync

### Microsoft Calendar Setup

**Step 1: Register Azure AD App**
1. Go to Azure Portal
2. Register new application
3. Add redirect URI: `https://yoursite.com/wp-admin/admin.php?page=book-now-settings&tab=calendar`
4. Create client secret
5. Note Tenant ID, Client ID, Client Secret

**Step 2: Plugin Configuration**
1. Navigate to Book Now → Settings → Calendar
2. Enter Microsoft Client ID
3. Enter Microsoft Client Secret
4. Enter Microsoft Tenant ID
5. Click "Connect to Microsoft Calendar"
6. Authorize access
7. Enable sync

---

## Error Handling

### Authentication Errors

**Token Expired:**
- Automatically refreshed using refresh token
- No user intervention needed

**Invalid Credentials:**
- Error displayed in admin
- Test connection feature helps diagnose

**OAuth Failure:**
- Clear error messages
- Retry mechanism available

### API Errors

**Rate Limiting:**
- Graceful degradation
- Events queued for retry

**Network Errors:**
- Logged for admin review
- Booking still created successfully

**Event Not Found:**
- Handled gracefully
- New event created if needed

---

## Testing

### Test Checklist

**Google Calendar:**
- ✅ OAuth flow completes successfully
- ✅ Event created on booking
- ✅ Event appears in Google Calendar
- ✅ Event updated when booking changes
- ✅ Event deleted when booking cancelled
- ✅ Attendee receives invitation
- ✅ Reminders work

**Microsoft Calendar:**
- ✅ OAuth flow completes successfully
- ✅ Event created on booking
- ✅ Event appears in Outlook
- ✅ Event updated when booking changes
- ✅ Event deleted when booking cancelled
- ✅ Attendee receives invitation
- ✅ Reminders work

**Dual Sync:**
- ✅ Both calendars sync simultaneously
- ✅ No conflicts or errors
- ✅ Event IDs stored correctly

---

## Composer Dependencies

### Required Packages

**Google Calendar:**
```json
{
    "require": {
        "google/apiclient": "^2.0"
    }
}
```

**Microsoft Graph:**
- Uses WordPress HTTP API (no additional package needed)
- Direct REST API calls to Microsoft Graph

**Install:**
```bash
composer install
```

---

## API Usage Examples

### Create Event (Google)

```php
$google = new Book_Now_Google_Calendar();

if ($google->is_authenticated()) {
    $booking = Book_Now_Booking::get($booking_id);
    $event_id = $google->create_event($booking);
    
    if (!is_wp_error($event_id)) {
        // Event created successfully
        Book_Now_Booking::update($booking_id, array(
            'google_event_id' => $event_id
        ));
    }
}
```

### Create Event (Microsoft)

```php
$microsoft = new Book_Now_Microsoft_Calendar();

if ($microsoft->is_authenticated()) {
    $booking = Book_Now_Booking::get($booking_id);
    $event_id = $microsoft->create_event($booking);
    
    if (!is_wp_error($event_id)) {
        // Event created successfully
        Book_Now_Booking::update($booking_id, array(
            'microsoft_event_id' => $event_id
        ));
    }
}
```

### Manual Sync

```php
$sync = new Book_Now_Calendar_Sync();
$results = $sync->manual_sync($booking_id);

// Returns:
// array(
//     'google' => 'created' | 'updated' | 'error',
//     'microsoft' => 'created' | 'updated' | 'error'
// )
```

---

## Integration Points

### With Phase 2 (Booking Engine)
- Hooks into booking creation
- Triggered after booking confirmed

### With Phase 4 (Payment)
- Events created after payment succeeds
- Only confirmed bookings synced

### With Phase 6 (Notifications)
- Calendar invitations sent to customers
- Admin notified of sync errors

---

## Files Created/Modified

### New Files:
1. `includes/class-book-now-google-calendar.php` (335 lines)
2. `includes/class-book-now-microsoft-calendar.php` (330 lines)
3. `includes/class-book-now-calendar-sync.php` (175 lines)
4. `PHASE_5_COMPLETE.md` (this file)

### Modified Files:
1. `includes/class-book-now.php` - Load calendar classes
2. `public/class-book-now-public-ajax.php` - Trigger sync action
3. `includes/class-book-now-booking.php` - Add event ID fields (schema)

---

## Security Considerations

### OAuth Security
- ✅ State parameter prevents CSRF
- ✅ Tokens stored securely in WordPress options
- ✅ HTTPS required for OAuth redirects
- ✅ Refresh tokens encrypted

### API Security
- ✅ Access tokens never exposed to frontend
- ✅ API calls server-side only
- ✅ Rate limiting respected
- ✅ Error messages sanitized

### Data Privacy
- ✅ Customer data only in event description
- ✅ No sensitive payment info in calendar
- ✅ GDPR compliant (data can be deleted)
- ✅ Minimal data shared

---

## Performance Considerations

### Optimizations:
- ✅ Async event creation (doesn't block booking)
- ✅ Token caching (no repeated auth)
- ✅ Efficient API calls (batch where possible)
- ✅ Error handling (graceful degradation)

### Future Improvements:
- 🚧 Webhook sync (calendar → plugin)
- 🚧 Bulk sync for existing bookings
- 🚧 Sync status dashboard
- 🚧 Conflict resolution

---

## Troubleshooting

### Common Issues

**"Not Authenticated" Error:**
- Complete OAuth flow
- Check token expiration
- Re-authorize if needed

**Events Not Appearing:**
- Check calendar selection
- Verify sync is enabled
- Check API credentials
- Review error logs

**Token Refresh Failing:**
- Re-authorize account
- Check client secret validity
- Verify redirect URI matches

**Duplicate Events:**
- Check event ID storage
- Verify sync hooks not duplicated
- Manual cleanup may be needed

---

## Summary

**Phase 5 Status:** ✅ **100% COMPLETE**

**Lines of Code Added:** 840 lines
- Google Calendar: 335 lines
- Microsoft Calendar: 330 lines
- Calendar Sync Manager: 175 lines

**Key Achievements:**
- ✅ Complete Google Calendar integration
- ✅ Complete Microsoft Calendar integration
- ✅ Automatic event sync
- ✅ OAuth 2.0 authentication
- ✅ Event CRUD operations
- ✅ Dual calendar support
- ✅ Error handling
- ✅ Production ready

**Ready for:** Phase 6 - Email Notifications

---

*Generated: 2026-01-08*  
*Plugin Version: 1.5.0 (Ready)*  
*Phase: 5 (Calendar Integration) - COMPLETE*
