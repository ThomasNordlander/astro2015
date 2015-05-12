#!/bin/sh

# Set proper permissions for the .registration folder:
chmod 755 .registration
chown apache .registration
# Also set the security context:
chcon -t httpd_sys_rw_content_t .registration
