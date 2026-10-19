---
title: Troubleshooting common issues
summary: Fixes for the problems schools hit most — login trouble, missing students, blank report cards and email delivery.
category: troubleshooting
slug: common-issues
order: 1
published: true
updated: 2026-10-19
---

# Troubleshooting common issues

Quick checks for the problems that come up most often. If something here doesn't resolve it, contact support through the platform website.

## I can't sign in

- Confirm you're on your school's workspace URL (for example `your-school.kairo.co.zw/workspace`), not the platform address.
- Use **Forgot password** on the login page to reset.
- If you never received the activation email, check spam, then ask an administrator to resend it from **System Administration → User Accounts**.

## A student doesn't appear in lists

- Check the table **filters** — a leftover form or year filter hides otherwise-matching students.
- Search by admission number or student ID number instead of name (spelling differs more than you'd think).
- If the student was admitted through Applications, confirm the application actually reached admitted status.

## The auto-billing engine created nothing

- Verify the students have an **enrolment** (academic year + form) — billing targets enrolments, not bare student rows.
- Check that **fee structures** exist for that form and academic year.
- If invoices already exist for that scope and are still unpaid, the engine skips those students on purpose to prevent double billing.

## Report cards print with empty subject marks

- Confirm the **marks were captured** against the assessment for that term and subject.
- Confirm the reports were **generated after** the marks were entered — regenerate if marks arrived later.
- Check you're printing the right **term**.

## Announcement emails didn't arrive

- Check the delivery setting was **Email** or **Email only**, not in-app only.
- Confirm the audience actually includes the recipients (role-based targeting matches real role storage).
- Check the school's spam folder; ask IT to allow the platform's sending domain.

## A payment is on the wrong invoice

- Record payments only through **Record Payment** on the invoice row — that keeps balances and receipts consistent.
- If a payment was captured against the wrong invoice, contact support rather than editing the database — payment history feeds receipts and statements.
