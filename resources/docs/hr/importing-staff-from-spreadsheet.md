---
title: Import staff from a spreadsheet
summary: Download the employee template, fill it in, and upload it — re-uploading with the same national IDs updates existing staff instead of duplicating them.
category: hr
slug: importing-staff-from-spreadsheet
order: 2
published: true
updated: 2026-10-27
---

# Import staff from a spreadsheet

The importer creates staff records in bulk, with the same fields as the **New Employee** wizard. It is also safe to run more than once: staff are matched by **National ID**, so a re-upload updates existing employees instead of creating duplicates.

## 1. Download the template

1. Open **HR & Payroll → Staff Directory & HR → Employees**.
2. Click **Import Employees (Excel/CSV)**.
3. In the wizard, click **Download Excel Template**.

The template arrives with the correct column headers and five example rows showing how varied data should look. Delete the examples before entering your real staff, or keep them and fix the values.

## 2. Fill it in

Only these six columns must be present with values:

| Required column | Notes |
| --- | --- |
| First Name | |
| Last Name | |
| Email | Must be unique across staff and portal accounts |
| Phone Number | |
| National ID | The matching key — never change it for an existing person |
| Designation | Job title, e.g. "Mathematics Teacher" |

Everything else is optional and has a sensible default:

| Column | Default when blank |
| --- | --- |
| Gender | Female |
| Date of Birth | 1990-01-01 |
| Marital Status | Single |
| Physical Address | Not Provided |
| Emergency Contact Name / Phone | Not Provided |
| Department | Blank (see Departments below) |
| Role | Teaching Staff |
| Employment Type | Permanent |
| Date Joined | Today |
| Salary Grade | Blank |
| Status | Active |

**Contract End Date is required when Employment Type is Contract.** Dates should be Excel dates or `YYYY-MM-DD`.

### Lists and pairs

Some columns hold more than one value. Use a colon between name and value, and a semicolon between entries:

- `Individual Allowances` → `Teaching Allowance: 150; Transport: 80`
- `Spouse Details` → `Spouse: Rudo Maphosa; Occupation: Nurse`
- `Dependents` → `Child 1: Tadiwa (8); Child 2: Kuda (5)`
- `Next of Kin` → `Name: Rudo Maphosa; Phone: 0771234567; Relationship: Spouse`
- `Custom Fields` → `TSC Number: 123456; Pension Number: PN-88`

## 3. Upload it

1. Click **Import Employees (Excel/CSV)** and choose the completed file. Columns are matched automatically from the header names — there is no mapping screen.
2. Review the summary and confirm the import.

## 4. What happens on re-upload

This is the important part for updating staff in bulk:

- **Same National ID, changed details** → the existing employee is updated. No new record, no duplicate portal account, and no second activation email.
- **Same National ID with a new email** → the email on the employee record and their linked login account are both updated.
- **New National ID** → a new employee is created and a portal account provisioned.
- **Email belongs to somebody else** → the row is rejected with a clear message; fix the email and re-upload.

So to refresh a staff list, download the template (or your last export), change only the emails or other details you need, and upload the same file again.

## Departments and salary grades

- **Department** matches an existing department by name. If it doesn't exist, the importer asks whether to create it or skip that value — creating it also generates a department code.
- **Salary Grade** matches an existing grade by name. Leave it blank if the person has no grade yet.

## When rows fail

The import never half-saves: rows with problems are listed in the results with the exact reason (for example a missing Contract End Date, an invalid gender, an unknown role, or a malformed date). Download the rejected rows as a CSV, fix them, and import just that file. Valid rows in the same upload are still imported.

> Keep the National ID stable for existing staff — that single column is what makes a re-upload an update rather than a duplicate.
