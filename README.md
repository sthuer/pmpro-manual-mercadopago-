# PMPro Manual MercadoPago

WordPress add-on plugin for **Paid Memberships Pro** that creates a manual MercadoPago flow for **new signups only**.

## Features

- Real PMPro gateway registration with slug `manual_mp` and label **Manual MercadoPago**.
- Manual payment method for selected PMPro levels.
- Checkout requests are saved as pending.
- Membership is not kept active until admin approval.
- Custom order meta:
  - `_pmmmp_status` (`pending`, `approved`, `rejected`)
  - `_pmmmp_payment_method` (`manual_mp`)
- Confirmation page instructions with:
  - Custom HTML block
  - MercadoPago payment button
  - WhatsApp button with templated message
- Emails:
  - Initial user instructions email
  - Admin notification with user/order details
  - Approval and rejection emails to user
- Admin queue page under **Memberships → Manual Payments** with Approve/Reject actions.

## Installation

1. Copy this plugin folder into `/wp-content/plugins/pmpro-manual-mercadopago`.
2. Activate **PMPro Manual MercadoPago** from **Plugins**.
3. Ensure **Paid Memberships Pro** is installed and active.

## Setup

1. Go to **Memberships → Manual MercadoPago**.
2. In **Memberships → Settings → Payments**, select **Manual MercadoPago** as the gateway.
3. Configure:
   - Enable method
   - HTML instructions
   - Payment link and button label
   - WhatsApp number and message template
   - Admin email
   - Membership levels enabled
4. Save settings.
5. New signups for selected levels will enter pending review.

## Approval Flow

1. Open **Memberships → Manual Payments**.
2. Review pending requests (newest first).
3. Click **Approve** to activate membership (start date now).
4. Click **Reject** to keep user account and mark request rejected.

## Security and data handling

- Admin actions use nonces.
- Admin pages/actions require `manage_options` capability.
- Inputs are sanitized (`wp_kses_post`, `esc_url_raw`, `sanitize_text_field`, `sanitize_email`, etc.).

## Notes

- This plugin is hook-based and does not modify PMPro core files.
- Intended for manual verification workflows.