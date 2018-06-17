# Change Log

## 1.2.1

_Requires WordPress 4.7_
_Tested up to WordPress 5.0_

### Bug fixes

- Make sure the file exists when trying to get its size.
- Make sure a directory still exists on the server before deleting it.
- Add a fallback to wpApiSettings in case Gutenberg is active.


---

## 1.2.0

_Requires WordPress 4.7_
_Tested up to WordPress 5.0_

### Bug fixes

- Refresh the MediaThèque block to adapt it to Gutenberg version 3.0.2.

### Features

+ Add a new display preference to improve the public PDF user media display.

---

## 1.1.2

_Requires WordPress 4.7_
_Tested up to WordPress 4.9_

### Bug fixes

- Make sure the MediaThèque icon is inline with the other Gutenberg's Block icons.
- Use the MediaThèque icon as a placeholder to the Add User Media button into the Gutenberg block.

---

## 1.1.1

_Requires WordPress 4.7_
_Tested up to WordPress 4.9_

### Bug fixes

- Adapt to Gutenberg 1.8 className changes.

---

## 1.1.0

_Requires WordPress 4.7_
_Tested up to WordPress 4.9_

### Features

+ Gutenberg (1.7+) Block.

---

## 1.0.1

_Requires WordPress 4.7_
_Tested up to WordPress 4.9_

### Bug fixes

- Improve the query performance when users delete all their user media.
- Improve the mime types setting output.
- Improve the MediaTheque UI when the supported user media statuses are filtered and the `publish` status is not available.

### Props

@TweetPressFr

---

## 1.0.0

_Requires WordPress 4.7_
_Tested up to WordPress 4.8_

This is the very first stable release for MediaThèque, an alternative & complementary media library for all WordPress users.

### Features

+ Any registered users can publish files.
+ Tidy these files the way users want thanks to directories.
+ Share files or directories on any WordPress powered sites.
+ Administrator will be able to monitor users files & directories
+ Contributors (& more powerful roles) can attach files to their posts.
+ Contributors (& more powerful roles) can define the display preferences of their media.
+ Vanished media are no longer leaving a trace in contents & Administrators are informed about any vanished media.
+ Administrator can set MediaThèque options (e.g.: role required to be able to use it, allowed file types, etc.).
+ Multisite Users can enjoy their personal MediaThèque from any site of the network.
