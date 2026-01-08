# Book Now - User Guide & Help

Complete user documentation for the Book Now WordPress plugin.

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Dashboard Overview](#dashboard-overview)
3. [Managing Bookings](#managing-bookings)
4. [Consultation Types](#consultation-types)
5. [Categories](#categories)
6. [Availability Management](#availability-management)
7. [Payment Management](#payment-management)
8. [Calendar Integrations](#calendar-integrations)
9. [Email Notifications](#email-notifications)
10. [Shortcodes Reference](#shortcodes-reference)
11. [Settings Reference](#settings-reference)
12. [FAQ](#faq)
13. [Keyboard Shortcuts](#keyboard-shortcuts)

---

## Getting Started

### Quick Start (5 Minutes)

1. **Create a Consultation Type**
   - Go to Book Now > Consultation Types > Add New
   - Enter name, duration, and price
   - Click Publish

2. **Set Your Availability**
   - Go to Book Now > Availability
   - Set your working hours for each day
   - Click Save

3. **Add Booking Form to a Page**
   - Edit any page
   - Add shortcode: `[book_now_form]`
   - Publish/Update page

4. **Configure Payments**
   - Go to Book Now > Settings > Payments
   - Enter Stripe API keys
   - Test connection

That's it! You can now accept bookings.

---

## Dashboard Overview

The dashboard (Book Now > Dashboard) shows:

### Today's Bookings
- List of appointments scheduled for today
- Quick access to booking details
- Status indicators

### Upcoming Bookings
- Next 7 days of appointments
- Calendar preview
- Quick stats

### Statistics
| Metric | Description |
|--------|-------------|
| Total Bookings | All-time booking count |
| This Month | Current month bookings |
| Revenue | Payment totals |
| Pending | Awaiting confirmation |

### Integration Status
Quick view of connected services:
- Stripe (Payment)
- Google Calendar
- Microsoft Calendar

---

## Managing Bookings

### Viewing Bookings

**Navigate to:** Book Now > Bookings

The bookings list shows:
| Column | Description |
|--------|-------------|
| Reference | Unique booking ID |
| Customer | Name and email |
| Type | Consultation type |
| Date/Time | Scheduled appointment |
| Status | Current booking status |
| Payment | Payment status |

### Filtering Bookings

Use the filter bar to find bookings:

- **Status:** All, Confirmed, Pending, Cancelled, Completed
- **Date Range:** Select start and end dates
- **Type:** Filter by consultation type
- **Search:** Customer name, email, or reference

### Booking Statuses

| Status | Meaning | Color |
|--------|---------|-------|
| Pending | Awaiting payment | Yellow |
| Confirmed | Payment received | Green |
| Cancelled | Cancelled by customer/admin | Red |
| Completed | Appointment done | Blue |
| No-Show | Customer didn't attend | Gray |
| Refunded | Payment refunded | Purple |

### Viewing Booking Details

Click any booking to see:

**Customer Information:**
- Full name
- Email address
- Phone number
- Customer notes

**Booking Details:**
- Consultation type
- Date and time
- Duration
- Timezone

**Payment Information:**
- Amount paid
- Payment method
- Stripe reference
- Transaction date

**Admin Notes:**
- Internal notes (not visible to customer)
- Add/edit notes anytime

### Editing a Booking

1. Click on the booking
2. Click **Edit**
3. Modify details:
   - Reschedule date/time
   - Update customer info
   - Change status
   - Add admin notes
4. Click **Save Changes**

### Cancelling a Booking

1. Open booking details
2. Click **Cancel Booking**
3. Choose refund option:
   - Full refund
   - Partial refund
   - No refund
4. Optionally add cancellation reason
5. Confirm cancellation

The customer receives a cancellation email automatically.

### Processing Refunds

1. Open booking details
2. Click **Refund**
3. Enter refund amount
4. Add refund reason (optional)
5. Click **Process Refund**

Refunds are processed through Stripe automatically.

### Exporting Bookings

1. Apply any filters needed
2. Click **Export**
3. Choose format (CSV)
4. Download file

Export includes all visible columns.

---

## Consultation Types

### Creating a Consultation Type

**Navigate to:** Book Now > Consultation Types > Add New

**Required Fields:**

| Field | Description | Example |
|-------|-------------|---------|
| Name | Display name | "Discovery Call" |
| Duration | Length in minutes | 30 |
| Price | Cost to customer | 75.00 |

**Optional Fields:**

| Field | Description |
|-------|-------------|
| Description | Detailed description (supports HTML) |
| Featured Image | Image shown on cards |
| Deposit Amount | Require deposit instead of full payment |
| Category | Group related types |
| Buffer Before | Block time before appointment |
| Buffer After | Block time after appointment |
| Max Advance Days | How far ahead bookings allowed |
| Min Lead Time | Minimum notice required (hours) |
| Confirmation Message | Custom message after booking |

### Editing Consultation Types

1. Go to Book Now > Consultation Types
2. Hover over type and click **Edit**
3. Make changes
4. Click **Update**

### Duplicating a Type

1. Hover over type
2. Click **Duplicate**
3. Edit the copy as needed
4. Save

### Deactivating a Type

To hide a type without deleting:

1. Edit the type
2. Change Status to **Inactive**
3. Save

Inactive types don't appear in booking forms but existing bookings remain.

### Deleting a Type

1. Hover over type
2. Click **Trash**

**Note:** Types with existing bookings are moved to trash but can be restored. Types can only be permanently deleted if they have no associated bookings.

---

## Categories

### What Are Categories?

Categories help organize consultation types into groups. Examples:
- "Coaching" (Life Coaching, Career Coaching)
- "Business" (Strategy Sessions, Consultations)
- "Technical" (Code Reviews, Architecture Sessions)

### Creating Categories

**Navigate to:** Book Now > Categories

1. Enter category name
2. Select parent category (optional, for hierarchy)
3. Add description (optional)
4. Add image (optional)
5. Click **Add Category**

### Using Categories

**In Consultation Types:**
- Edit a consultation type
- Select category from dropdown
- Save

**In Shortcodes:**
```
[book_now_types category="coaching"]
[book_now_form category="business"]
```

### Hierarchical Categories

Categories can have parent-child relationships:

```
Business Services
  ├── Strategy Sessions
  └── Consultations
Personal Development
  ├── Life Coaching
  └── Career Coaching
```

---

## Availability Management

### Weekly Schedule

**Navigate to:** Book Now > Availability

Set your recurring weekly availability:

**For each day:**
1. Toggle day on/off
2. Set opening time
3. Set closing time
4. Add breaks (optional)

**Example:**
| Day | Status | Hours |
|-----|--------|-------|
| Monday | Open | 9:00 AM - 5:00 PM |
| Tuesday | Open | 9:00 AM - 5:00 PM |
| Wednesday | Open | 9:00 AM - 12:00 PM |
| Thursday | Open | 9:00 AM - 5:00 PM |
| Friday | Open | 9:00 AM - 3:00 PM |
| Saturday | Closed | - |
| Sunday | Closed | - |

### Adding Breaks

To add lunch breaks or other gaps:

1. Click **Add Break** for the day
2. Set break start time
3. Set break end time
4. Save

**Example:** 12:00 PM - 1:00 PM lunch break

### Date Overrides

Block specific dates (vacations, holidays):

1. Go to **Date Overrides** tab
2. Click **Add Block**
3. Select date or date range
4. Add reason (optional)
5. Save

### Custom Hours

Set different hours for specific dates:

1. Click **Add Custom Hours**
2. Select the date
3. Enter custom start/end times
4. Save

**Example:** December 24th: 9:00 AM - 12:00 PM only

### Consultation-Specific Availability

Set availability for specific consultation types:

1. Edit the consultation type
2. Go to **Availability** tab
3. Override default schedule
4. Save

---

## Payment Management

### Payment Settings

**Navigate to:** Book Now > Settings > Payments

| Setting | Description |
|---------|-------------|
| API Mode | Test or Live |
| Publishable Key | Stripe public key |
| Secret Key | Stripe secret key |
| Webhook Secret | For webhook verification |
| Currency | Default currency |
| Payment Collection | Full or Deposit |

### Testing Connection

1. Enter API keys
2. Click **Save**
3. Click **Test Connection**

**Success:** Shows green checkmark and account info
**Failure:** Shows error message with details

### Payment Statuses

| Status | Meaning |
|--------|---------|
| Pending | Awaiting payment |
| Paid | Payment successful |
| Failed | Payment declined |
| Refunded | Fully refunded |
| Partial Refund | Partially refunded |

### Viewing Payments in Stripe

1. Open booking details
2. Click Stripe reference link
3. Opens Stripe Dashboard to that payment

### Handling Failed Payments

When a payment fails:
1. Booking marked as "Pending"
2. Customer notified
3. Admin alerted
4. Customer can retry payment

---

## Calendar Integrations

### Google Calendar

**Connect:**
1. Go to Settings > Integrations
2. Enter Client ID and Secret
3. Click **Connect Google Calendar**
4. Authorize access
5. Select calendar
6. Click **Test Connection**

**Sync Behavior:**
- New bookings create calendar events
- Cancelled bookings delete events
- Rescheduled bookings update events

**Disconnect:**
1. Click **Disconnect**
2. Tokens are removed
3. Existing calendar events remain

### Microsoft Calendar

**Connect:**
1. Go to Settings > Integrations
2. Enter Application ID and Secret
3. Click **Connect Microsoft Calendar**
4. Authorize access
5. Select calendar
6. Click **Test Connection**

### Sync Options

| Option | Description |
|--------|-------------|
| Sync Direction | One-way or bidirectional |
| Block Busy Times | Use calendar for availability |
| Event Details | What to include in events |

### Troubleshooting Sync

**Events not appearing:**
- Verify connection (Test Connection button)
- Check correct calendar selected
- View sync logs

**Wrong calendar:**
- Disconnect and reconnect
- Select different calendar

---

## Email Notifications

### Email Types

| Email | Recipient | Trigger |
|-------|-----------|---------|
| Booking Confirmation | Customer | After successful booking |
| Booking Reminder | Customer | Before appointment |
| Cancellation Notice | Customer | When cancelled |
| New Booking Alert | Admin | New booking received |
| Cancellation Alert | Admin | Booking cancelled |

### Email Settings

**Navigate to:** Book Now > Settings > Emails

| Setting | Description |
|---------|-------------|
| From Name | Sender name |
| From Email | Sender email |
| Reply-To | Where replies go |
| Admin Email | Where admin alerts go |

### Customizing Templates

1. Go to Settings > Emails
2. Select email type
3. Edit subject and body
4. Use template variables (see below)
5. Save

### Template Variables

Use these placeholders in emails:

| Variable | Replaced With |
|----------|---------------|
| `{customer_name}` | Customer's full name |
| `{customer_email}` | Customer's email |
| `{reference}` | Booking reference |
| `{type_name}` | Consultation type name |
| `{booking_date}` | Date of appointment |
| `{booking_time}` | Time of appointment |
| `{duration}` | Appointment duration |
| `{amount}` | Amount paid |
| `{business_name}` | Your business name |
| `{cancel_link}` | Cancellation URL |

### Testing Emails

1. Go to Settings > Emails
2. Click **Send Test Email**
3. Check your inbox

---

## Shortcodes Reference

### [book_now_form]

Complete booking wizard.

**Attributes:**
| Attribute | Default | Options |
|-----------|---------|---------|
| type | "" | Consultation type slug |
| category | "" | Category slug |
| show_types | "true" | "true", "false" |
| show_calendar | "true" | "true", "false" |
| theme | "default" | "default", "minimal" |

**Examples:**
```
[book_now_form]
[book_now_form type="strategy-session"]
[book_now_form category="coaching" show_calendar="false"]
```

### [book_now_calendar]

Calendar availability view.

**Attributes:**
| Attribute | Default | Options |
|-----------|---------|---------|
| type | "" | Filter by type slug |
| months | "1" | Number of months |
| show_legend | "true" | "true", "false" |

**Examples:**
```
[book_now_calendar]
[book_now_calendar months="2" type="discovery-call"]
```

### [book_now_list]

List view of available slots.

**Attributes:**
| Attribute | Default | Options |
|-----------|---------|---------|
| type | "" | Filter by type slug |
| days | "7" | Days to show |
| show_empty | "false" | Show empty days |

**Examples:**
```
[book_now_list]
[book_now_list days="14" type="consultation"]
```

### [book_now_types]

Consultation type cards display.

**Attributes:**
| Attribute | Default | Options |
|-----------|---------|---------|
| category | "" | Filter by category |
| columns | "3" | 1, 2, 3, 4 |
| show_price | "true" | "true", "false" |
| show_duration | "true" | "true", "false" |
| layout | "grid" | "grid", "list" |

**Examples:**
```
[book_now_types]
[book_now_types category="business" columns="2"]
[book_now_types layout="list" show_price="false"]
```

### [book_now_single]

Single consultation type with booking.

**Attributes:**
| Attribute | Default | Required |
|-----------|---------|----------|
| type | "" | Yes |
| show_details | "true" | No |

**Examples:**
```
[book_now_single type="discovery-call"]
[book_now_single type="strategy-session" show_details="false"]
```

---

## Settings Reference

### General Settings

| Setting | Purpose |
|---------|---------|
| Business Name | Used in emails and receipts |
| Timezone | Your business timezone |
| Date Format | Date display format |
| Time Format | Time display format |
| Currency | Default currency |
| Booking Page | Page with booking form |
| Terms URL | Terms and conditions page |
| Privacy URL | Privacy policy page |
| Delete on Uninstall | Remove all data when uninstalling |

### Payment Settings

| Setting | Purpose |
|---------|---------|
| API Mode | Test or Live |
| Publishable Key | Stripe public key |
| Secret Key | Stripe secret key |
| Webhook Secret | Webhook signature key |
| Payment Collection | Full payment or deposit |

### Integration Settings

**Google Calendar:**
| Setting | Purpose |
|---------|---------|
| Client ID | OAuth client ID |
| Client Secret | OAuth client secret |
| Calendar | Which calendar to sync |
| Sync Enabled | Toggle sync on/off |

**Microsoft Calendar:**
| Setting | Purpose |
|---------|---------|
| Application ID | Azure AD app ID |
| Client Secret | Azure AD secret |
| Calendar | Which calendar to sync |
| Sync Enabled | Toggle sync on/off |

### Email Settings

| Setting | Purpose |
|---------|---------|
| From Name | Email sender name |
| From Email | Email sender address |
| Reply-To | Reply address |
| Admin Email | Admin notifications |
| Reminder Hours | Hours before for reminder |

### Styling Settings

| Setting | Purpose |
|---------|---------|
| Primary Color | Main brand color |
| Secondary Color | Accent color |
| Button Style | Rounded, square, pill |
| Calendar Theme | Light, dark, auto |
| Custom CSS | Additional styling |

---

## FAQ

### General Questions

**Q: Can I have multiple consultation types?**
A: Yes, create as many as needed with different durations and prices.

**Q: Can customers book multiple appointments at once?**
A: Currently one booking at a time. They can book again after completing.

**Q: What happens if there's a scheduling conflict?**
A: The system prevents double-booking. Unavailable times aren't shown.

### Payments

**Q: What payment methods are supported?**
A: All major credit/debit cards via Stripe (Visa, Mastercard, Amex, etc.)

**Q: Can I offer free consultations?**
A: Yes, set price to 0 and payment step is skipped.

**Q: How do refunds work?**
A: Process through the booking details page. Refunded via Stripe.

**Q: Can I collect deposits instead of full payment?**
A: Yes, set a deposit amount on the consultation type.

### Calendar

**Q: Do I need Google or Microsoft calendar?**
A: No, they're optional. Bookings work without calendar sync.

**Q: Can I use both Google and Microsoft calendars?**
A: Yes, you can connect both simultaneously.

**Q: Will it sync existing calendar events?**
A: It reads busy times but only creates events for new bookings.

### Technical

**Q: Does it work with page builders?**
A: Yes, shortcodes work with Elementor, Divi, Beaver Builder, etc.

**Q: Is it GDPR compliant?**
A: The plugin doesn't set tracking cookies. Customer data is stored in your database. Add appropriate privacy policy.

**Q: Can I customize the styling?**
A: Yes, via Settings > Styling or custom CSS.

---

## Keyboard Shortcuts

### Admin Pages

| Shortcut | Action |
|----------|--------|
| `s` | Focus search field |
| `n` | New booking/type |
| `?` | Show help |

### Booking Form (Frontend)

| Shortcut | Action |
|----------|--------|
| `Tab` | Next field |
| `Shift+Tab` | Previous field |
| `Enter` | Submit/Next step |
| `Esc` | Close modals |

---

## Getting More Help

### Resources

- **Installation Guide:** [INSTALL.md](INSTALL.md)
- **API Documentation:** [API_GUIDE.md](API_GUIDE.md)
- **Technical Specs:** [TECH_STACK.md](TECH_STACK.md)

### Support

- GitHub Issues: [Report a bug](https://github.com/jcastillotx/book-now/issues)
- Documentation: Check docs folder

### Debug Information

When reporting issues, include:
1. WordPress version
2. PHP version
3. Plugin version
4. Error messages (from debug.log)
5. Steps to reproduce

---

**Document Version:** 1.0
**Last Updated:** 2026-01-08
