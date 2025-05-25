# FindLink - Lost and Found Application

This web application helps reconnect people with their lost belongings.

## User Role System

The application uses a simple role-based system:

- **Users**: Regular users who can post lost or found items (Default role)
- **Admin**: Administrative account with special privileges

## Setting Up Admin Account

After removing the role selection from the registration form, all new registrations are automatically assigned the "user" role.

To create an admin account:

1. Access `admin/admin_setup.php` from your local machine (This page is only accessible locally for security)
2. Set up the admin account with the fixed email `admin@gmail.com` and a password of your choice

**Note**: Only one admin account can exist in the system.

## Security Features

- Role selection has been removed from the registration form
- Admin account can only be created through a secure setup page that's only accessible locally
- All regular registrations are assigned the "user" role automatically
