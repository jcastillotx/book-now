# Book Now by Kre8iv Tech - Project Specification

## Executive Summary

**Project Name:** Book Now
**Company:** Kre8iv Tech
**Version:** 1.0.0
**Document Version:** 1.0
**Last Updated:** 2026-01-08

Book Now is a WordPress plugin that enables businesses to offer online consultation booking with integrated payment processing. Visitors can browse consultation types, view availability, and book appointments with automatic calendar synchronization.

---

## 1. Project Overview

### 1.1 Purpose

Provide businesses with a seamless, all-in-one consultation booking solution that:
- Displays available consultation types with pricing
- Shows real-time availability
- Collects payments via Stripe
- Syncs with Google Calendar and Microsoft 365/Outlook
- Sends automated email notifications

### 1.2 Target Users

**Primary Users (Site Visitors):**
- Clients seeking to book consultations
- Customers browsing available services
- Users comparing consultation options

**Secondary Users (Business Owners/Admins):**
- Consultants managing their booking schedules
- Business owners configuring consultation types and pricing
- Administrators managing bookings and payments

### 1.3 Business Goals

1. Reduce manual scheduling overhead
2. Collect payments upfront or as deposits
3. Eliminate double-booking through calendar integration
4. Provide professional booking experience for clients
5. Automate reminder and confirmation emails

---

## 2. Functional Requirements

### 2.1 Frontend Features

#### 2.1.1 Booking Form (`[book_now_form]`)

A multi-step wizard for completing bookings:

**Step 1: Select Consultation Type**
- Display available consultation types as cards
- Show name, description, duration, and price
- Filter by category (optional)
- Support for featured/highlighted types

**Step 2: Select Date & Time**
- Calendar date picker
- Display available time slots for selected date
- Show timezone information
- Prevent selection of past dates/times
- Respect booking lead time settings

**Step 3: Enter Details**
- Customer name (required)
- Email address (required)
- Phone number (optional/configurable)
- Notes/message field
- Custom fields (admin-configurable)

**Step 4: Payment**
- Display booking summary
- Show price breakdown
- Stripe Elements for card input
- Support for full payment or deposit
- Apply promo codes (future)

**Step 5: Confirmation**
- Display booking reference number
- Show booking details summary
- Calendar invite download option
- Email confirmation sent

#### 2.1.2 Calendar View (`[book_now_calendar]`)

A monthly calendar displaying availability:

- Month/week navigation
- Color-coded availability (available/limited/unavailable)
- Click date to see available slots
- Responsive for mobile devices
- Option to filter by consultation type

#### 2.1.3 List View (`[book_now_list]`)

A day-by-day list of available slots:

- Configurable number of days to display
- Show date, available slots count
- Expand to see specific times
- Filter by consultation type
- Direct booking links

#### 2.1.4 Consultation Type Cards (`[book_now_types]`)

Display consultation offerings:

- Card layout with image support
- Name, description, duration, price
- Category grouping
- "Book Now" button on each card
- Grid or list layout options

### 2.2 Admin Features

#### 2.2.1 Dashboard

Main overview screen showing:
- Today's bookings
- Upcoming bookings (next 7 days)
- Recent bookings
- Quick statistics (total bookings, revenue)
- Integration status indicators

#### 2.2.2 Booking Management

**Bookings List:**
- Sortable/filterable table
- Search by customer name, email, reference
- Filter by status, date range, consultation type
- Bulk actions (cancel, delete)
- Export to CSV

**Booking Detail/Edit:**
- View complete booking information
- Update booking status
- Reschedule booking
- Process refunds
- Add admin notes
- View payment details
- Resend confirmation email

**Booking Statuses:**
- Pending (awaiting payment)
- Confirmed (payment received)
- Cancelled (by customer or admin)
- Completed (consultation done)
- No-Show
- Refunded

#### 2.2.3 Consultation Types Management

**Types List:**
- All consultation types with key info
- Quick edit inline
- Reorder/sort
- Duplicate type
- Activate/deactivate

**Type Edit:**
- Name and slug
- Description (rich text)
- Featured image
- Duration (minutes)
- Price (connected to Stripe)
- Deposit amount (optional)
- Category assignment
- Buffer time before/after
- Maximum advance booking days
- Minimum lead time
- Available days of week
- Custom confirmation message
- Status (active/inactive)

#### 2.2.4 Categories Management

- Create/edit/delete categories
- Hierarchical categories (parent/child)
- Category description
- Category image
- Display order

#### 2.2.5 Availability Settings

**Weekly Schedule:**
- Set available hours per day
- Different hours for different days
- Multiple time blocks per day (e.g., 9-12, 2-5)

**Specific Date Overrides:**
- Mark specific dates as unavailable
- Set custom hours for specific dates
- Recurring unavailability (e.g., every Monday)

**Holiday/Block Dates:**
- Add date ranges to block
- Import holidays

#### 2.2.6 Settings Pages

**General Settings:**
- Business name
- Business timezone
- Date/time formats
- Default currency
- Booking page URL
- Terms and conditions URL
- Privacy policy URL

**Payment Settings (Stripe):**
- Stripe API mode (Test/Live)
- Publishable key
- Secret key
- Webhook secret
- **Test Connection Button**
- Payment collection (full/deposit)
- Currency settings
- Refund policy settings

**Integration Settings:**

*Google Calendar:*
- Enable/disable sync
- OAuth connect button
- Select calendar for sync
- **Test Connection Button**
- Sync direction (one-way/bidirectional)
- Event details template

*Microsoft 365/Outlook:*
- Enable/disable sync
- OAuth connect button
- Select calendar for sync
- **Test Connection Button**
- Sync direction
- Event details template

**Email Settings:**
- From name
- From email address
- Reply-to address
- Email templates:
  - Booking confirmation (customer)
  - Booking reminder (customer)
  - Cancellation notification (customer)
  - New booking alert (admin)
  - Cancellation alert (admin)
- Email template variables reference
- Test email button

**Styling Settings:**
- Primary color
- Secondary color
- Button style
- Calendar theme
- Custom CSS

### 2.3 API Integrations

#### 2.3.1 Stripe Payment Integration

**Requirements:**
- PCI-compliant card collection via Stripe Elements
- Payment Intents API for SCA compliance
- Support for major card types
- Webhook handling for payment events
- Automatic receipt emails via Stripe
- Refund processing capability

**Webhook Events to Handle:**
- `payment_intent.succeeded` - Confirm booking
- `payment_intent.payment_failed` - Mark booking failed
- `charge.refunded` - Update booking status
- `charge.dispute.created` - Alert admin

**Admin Features:**
- Test/Live mode toggle
- API key configuration with masked display
- **Test Connection Button** - Validates API keys work
- Webhook URL display for configuration
- Connection status indicator

#### 2.3.2 Google Calendar Integration

**Requirements:**
- OAuth 2.0 authentication flow
- Create events when booking confirmed
- Update events when booking modified
- Delete events when booking cancelled
- Read busy times for availability calculation
- Support for multiple calendars

**Admin Features:**
- Connect/Disconnect button
- Calendar selection dropdown
- **Test Connection Button** - Creates test event
- Sync status indicator
- Manual sync trigger
- Last sync timestamp

#### 2.3.3 Microsoft 365/Outlook Integration

**Requirements:**
- OAuth 2.0 via Azure AD
- Create/update/delete calendar events
- Read free/busy information
- Support for personal and work accounts

**Admin Features:**
- Connect/Disconnect button
- Calendar selection
- **Test Connection Button** - Creates test event
- Sync status indicator
- Account info display

---

## 3. Non-Functional Requirements

### 3.1 Performance

- Page load time < 3 seconds
- AJAX operations < 1 second response
- Support for 100+ consultation types
- Handle 1000+ bookings without degradation
- Efficient database queries with indexing

### 3.2 Security

- All API keys encrypted at rest
- HTTPS required for payment pages
- CSRF protection on all forms
- SQL injection prevention
- XSS prevention
- Rate limiting on booking endpoints
- Webhook signature verification
- Capability-based access control

### 3.3 Compatibility

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile responsive design
- Screen reader accessible (WCAG 2.1 AA)

### 3.4 Internationalization

- All strings translatable
- POT file generation
- Date/time format localization
- Currency format localization
- RTL language support

### 3.5 Scalability

- Efficient database schema
- Caching where appropriate
- Lazy loading of assets
- API rate limit handling

---

## 4. User Stories

### 4.1 Site Visitor Stories

1. **As a visitor**, I want to see available consultation types so I can choose the right service.

2. **As a visitor**, I want to view a calendar of available dates so I can find a convenient time.

3. **As a visitor**, I want to see available time slots for a specific date so I can pick an exact time.

4. **As a visitor**, I want to book and pay for a consultation in one flow so I don't have to wait for confirmation.

5. **As a visitor**, I want to receive a confirmation email so I have a record of my booking.

6. **As a visitor**, I want to cancel my booking if needed and understand the refund policy.

### 4.2 Business Owner Stories

1. **As a business owner**, I want to define consultation types with custom pricing and duration so I can offer different services.

2. **As a business owner**, I want to set my availability schedule so visitors only see times I'm available.

3. **As a business owner**, I want bookings to sync with my Google/Outlook calendar so I don't double-book.

4. **As a business owner**, I want to collect payment at booking time so I reduce no-shows.

5. **As a business owner**, I want to receive notifications of new bookings so I can prepare.

6. **As a business owner**, I want to view all bookings in one place so I can manage my schedule.

7. **As a business owner**, I want to test my API connections before going live so I know everything works.

8. **As a business owner**, I want to process refunds when needed directly from the plugin.

---

## 5. Database Schema

### 5.1 Tables

#### `{prefix}booknow_consultation_types`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT(20) UNSIGNED | Primary key |
| name | VARCHAR(255) | Display name |
| slug | VARCHAR(255) | URL-friendly identifier |
| description | TEXT | Full description |
| duration | INT(11) | Duration in minutes |
| price | DECIMAL(10,2) | Price amount |
| deposit_amount | DECIMAL(10,2) | Optional deposit |
| currency | VARCHAR(3) | Currency code |
| stripe_product_id | VARCHAR(255) | Stripe product reference |
| stripe_price_id | VARCHAR(255) | Stripe price reference |
| category_id | BIGINT(20) UNSIGNED | Category foreign key |
| buffer_before | INT(11) | Minutes buffer before |
| buffer_after | INT(11) | Minutes buffer after |
| max_advance_days | INT(11) | Max days in advance to book |
| min_lead_time | INT(11) | Minimum hours notice |
| featured_image | VARCHAR(255) | Image URL |
| confirmation_message | TEXT | Custom confirmation text |
| status | VARCHAR(20) | active/inactive |
| display_order | INT(11) | Sort order |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

#### `{prefix}booknow_categories`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT(20) UNSIGNED | Primary key |
| name | VARCHAR(255) | Category name |
| slug | VARCHAR(255) | URL-friendly identifier |
| description | TEXT | Category description |
| parent_id | BIGINT(20) UNSIGNED | Parent category (nullable) |
| image | VARCHAR(255) | Category image URL |
| display_order | INT(11) | Sort order |
| created_at | DATETIME | Creation timestamp |

#### `{prefix}booknow_bookings`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT(20) UNSIGNED | Primary key |
| reference_number | VARCHAR(20) | Unique booking reference |
| consultation_type_id | BIGINT(20) UNSIGNED | Foreign key |
| customer_name | VARCHAR(255) | Customer full name |
| customer_email | VARCHAR(255) | Customer email |
| customer_phone | VARCHAR(50) | Customer phone |
| booking_date | DATE | Date of booking |
| start_time | TIME | Start time |
| end_time | TIME | End time |
| timezone | VARCHAR(100) | Customer timezone |
| status | VARCHAR(20) | Booking status |
| payment_status | VARCHAR(20) | Payment status |
| amount_paid | DECIMAL(10,2) | Amount paid |
| currency | VARCHAR(3) | Currency code |
| stripe_payment_intent_id | VARCHAR(255) | Stripe reference |
| stripe_charge_id | VARCHAR(255) | Stripe charge reference |
| google_event_id | VARCHAR(255) | Google Calendar event ID |
| microsoft_event_id | VARCHAR(255) | Microsoft event ID |
| customer_notes | TEXT | Notes from customer |
| admin_notes | TEXT | Internal admin notes |
| ip_address | VARCHAR(45) | Customer IP |
| user_agent | VARCHAR(255) | Browser info |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |
| cancelled_at | DATETIME | Cancellation timestamp |
| refunded_at | DATETIME | Refund timestamp |

#### `{prefix}booknow_availability`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT(20) UNSIGNED | Primary key |
| rule_type | VARCHAR(20) | weekly/specific/block |
| day_of_week | TINYINT | 0-6 (Sunday-Saturday) |
| specific_date | DATE | For specific date rules |
| start_time | TIME | Available from |
| end_time | TIME | Available until |
| is_available | TINYINT(1) | 1=available, 0=blocked |
| consultation_type_id | BIGINT(20) UNSIGNED | Type-specific (nullable) |
| created_at | DATETIME | Creation timestamp |

#### `{prefix}booknow_email_log`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT(20) UNSIGNED | Primary key |
| booking_id | BIGINT(20) UNSIGNED | Foreign key |
| email_type | VARCHAR(50) | Type of email |
| recipient | VARCHAR(255) | Email address |
| subject | VARCHAR(255) | Email subject |
| status | VARCHAR(20) | sent/failed |
| sent_at | DATETIME | Send timestamp |
| error_message | TEXT | Error if failed |

---

## 6. Shortcode Reference

### 6.1 `[book_now_form]`

Complete booking wizard form.

**Attributes:**
| Attribute | Default | Description |
|-----------|---------|-------------|
| type | "" | Specific consultation type slug |
| category | "" | Filter by category slug |
| show_types | "true" | Show type selection step |
| show_calendar | "true" | Show calendar in step 2 |
| theme | "default" | Visual theme |

**Examples:**
```
[book_now_form]
[book_now_form type="strategy-session"]
[book_now_form category="coaching"]
[book_now_form show_types="false" type="discovery-call"]
```

### 6.2 `[book_now_calendar]`

Calendar view showing availability.

**Attributes:**
| Attribute | Default | Description |
|-----------|---------|-------------|
| type | "" | Filter by consultation type |
| months | "1" | Number of months to show |
| show_legend | "true" | Show color legend |

**Examples:**
```
[book_now_calendar]
[book_now_calendar type="consultation" months="2"]
```

### 6.3 `[book_now_list]`

List view of available slots.

**Attributes:**
| Attribute | Default | Description |
|-----------|---------|-------------|
| type | "" | Filter by consultation type |
| days | "7" | Number of days to display |
| show_empty | "false" | Show days with no slots |

**Examples:**
```
[book_now_list]
[book_now_list days="14" type="coaching-session"]
```

### 6.4 `[book_now_types]`

Display consultation type cards.

**Attributes:**
| Attribute | Default | Description |
|-----------|---------|-------------|
| category | "" | Filter by category |
| columns | "3" | Number of columns |
| show_price | "true" | Display prices |
| show_duration | "true" | Display duration |
| layout | "grid" | grid or list |

**Examples:**
```
[book_now_types]
[book_now_types category="business" columns="2"]
[book_now_types layout="list" show_price="false"]
```

### 6.5 `[book_now_single]`

Single consultation type with booking form.

**Attributes:**
| Attribute | Default | Description |
|-----------|---------|-------------|
| type | "" | Consultation type slug (required) |
| show_details | "true" | Show type details |

**Examples:**
```
[book_now_single type="discovery-call"]
```

---

## 7. REST API Endpoints

### 7.1 Public Endpoints

All public endpoints: `/wp-json/book-now/v1/`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/consultation-types` | List active types |
| GET | `/consultation-types/{slug}` | Get single type |
| GET | `/categories` | List categories |
| GET | `/availability` | Get available slots |
| POST | `/bookings` | Create new booking |
| GET | `/bookings/{ref}` | Get booking by reference |
| POST | `/bookings/{ref}/cancel` | Cancel booking |
| POST | `/payment/create-intent` | Create Stripe Payment Intent |
| POST | `/payment/webhook` | Stripe webhook handler |

### 7.2 Admin Endpoints

Requires authentication with `manage_options` capability.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/bookings` | List all bookings |
| GET | `/admin/bookings/{id}` | Get booking details |
| PUT | `/admin/bookings/{id}` | Update booking |
| DELETE | `/admin/bookings/{id}` | Delete booking |
| POST | `/admin/bookings/{id}/refund` | Process refund |
| GET | `/admin/stats` | Dashboard statistics |
| POST | `/admin/test-stripe` | Test Stripe connection |
| POST | `/admin/test-google` | Test Google Calendar |
| POST | `/admin/test-microsoft` | Test Microsoft Calendar |

---

## 8. Questions & Clarifications

Before development begins, please clarify:

1. **Deposit vs Full Payment:** Should deposits be a percentage or fixed amount? Can both options be offered per consultation type?

2. **Cancellation Policy:** What's the cancellation/refund policy? Time-based restrictions (e.g., 24h notice)?

3. **Recurring Bookings:** Is support for recurring appointments needed in v1.0?

4. **Multiple Staff:** Will there ever be multiple consultants with separate calendars, or is this single-provider?

5. **Timezone Handling:** Should availability be shown in business timezone or visitor's timezone?

6. **Custom Fields:** What customer fields are needed beyond name/email/phone?

7. **Confirmation Workflow:** Auto-confirm on payment, or manual approval option?

8. **Buffer Time:** Default buffer between appointments? Configurable per type?

9. **Booking Modifications:** Can customers reschedule, or only cancel?

10. **Stripe Account:** Will you use Stripe Connect for marketplace features, or direct integration?

---

## 9. Acceptance Criteria

### 9.1 Minimum Viable Product (MVP)

The following must be complete for initial release:

- [ ] Plugin activates without errors
- [ ] Admin can create consultation types with pricing
- [ ] Admin can set weekly availability schedule
- [ ] Visitors can complete booking form
- [ ] Stripe payment processing works (test and live)
- [ ] Booking confirmation emails sent
- [ ] Google Calendar sync creates events
- [ ] Bookings list shows all bookings
- [ ] Admin can cancel bookings and process refunds
- [ ] All shortcodes render correctly
- [ ] Test connection buttons work for all APIs

### 9.2 Quality Requirements

- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors
- [ ] Mobile responsive on all views
- [ ] WPCS code standards compliance
- [ ] Security review passed
- [ ] 70%+ test coverage

---

## 10. Appendices

### Appendix A: Wireframes

*To be created during design phase*

### Appendix B: Email Templates

*Template designs and variable reference*

### Appendix C: Stripe Setup Guide

*Step-by-step Stripe configuration*

### Appendix D: Google Calendar Setup

*OAuth app setup instructions*

### Appendix E: Microsoft Calendar Setup

*Azure AD app registration guide*

---

**Document History:**

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-01-08 | Claude | Initial specification |
