# WP Forms Memberships
Allow successful WP Forms submissions to add users to WooCommerce Memberships.

![Memberships settings field](assets/images/field.png "")

## How to use

### User Registration Form
1. Create a User Registration form (requires WP Forms full version).
1. Add one or more WooCommerce Membership Plans in the form settings.
1. Configure the form and settings accordingly, to successfully create a user.
1. Configure notifications and form redirect as needed, depending on whether user approval is required.
1. If user approve is not required it may be nice to redirect the user right to the content.

### All Forms (requires user to be logged in)
1. Create any type of form besides a User Registration form.
1. Add one or more WooCommerce Membership Plans in the form settings.
1. You don't need to add fields to the form since the logged in user will be added to the membership plan.
1. If you only want a button, add a Hidden Field and set the default value to the current Post/Page URL.
1. Configure notifications and form redirect as needed.
