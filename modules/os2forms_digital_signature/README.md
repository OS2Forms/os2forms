# OS2Forms Digital Signature module

## Module purpose

This module provides functionality for adding digital signature to the webform PDF submissions.

To use this module, you must also have a signature server set up (<https://github.com/MitID-Digital-Signature>).
The signature server consists of two parts. A frontend module
(<https://github.com/MitID-Digital-Signature/os2forms_dig_sig_server/>) and a backend component
(<https://github.com/MitID-Digital-Signature/Signing-Server/>).

## How does it work

### Activating Digital Signature

1. Add the OS2forms attachment element to the form.
2. Indicate that the OS2Forms attachment requires a digital signature.
3. Add the Digital Signature Handler to the webform.
4. If the form requires an email handler, ensure the trigger is set to **...when submission is locked** in the handler’s
*Additional settings*.

### Flow Explained

1. Upon form submission, a PDF is generated, saved in the private directory, and sent to the signature service via URL.
2. The user is redirected to the signature service to provide their signature.
3. After signing, the user is redirected back to the webform solution.
4. The signed PDF is downloaded and stored in Drupal’s private directory.
5. When a submission PDF is requested (e.g., via download link or email), the signed PDF is served instead of generating
a new one on the fly.

## Settings page

URL: `admin/os2forms_digital_signature/settings`

- **Signature server URL**

  The URL of the service providing digital signature. This is the example of a known service [https://signering.bellcom.dk/sign.php?](https://signering.bellcom.dk/sign.php?)

- **Hash Salt used for signature**

  Must match hash salt on the signature server

- **List IPs which can download unsigned PDF submissions**

  Only requests from this IP will be able to download PDF which are to be signed.
