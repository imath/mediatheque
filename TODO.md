#Todo list

[] When deleting a User Media Post type, if it's not a folder, the corresponding file should be deleted.
[] When deleting a folder or moving files, make sure filesys is also edited.
[] Add a shortcode to load the UI anywhere.
[x] Make sure the create_item() method can handle folders.
[] Make sure to switch_to_blog() before saving the User Media metadata.
[x] The way the embed template is filter is not right. I should check the post type first.
[x] Create the 2 User Media Types on activation (File/Folder).
[x] Improve the way Views are added to the screen in admin.js
[x] Add a message about how to configure the private folder with nginx: [@see](http://nicknotfound.com/2009/01/12/iphone-website-with-nginx/)
[x] Use the wp-pointer to guide the user in finishing the plugin's setup (default links vs pretty links, where are the options, where is the main admin)
[] Make sure an admin can upload a file in another user's folder.
[x] Use a Backbone Model to store query vars (eg: user ID, parent directory ID etc..)
[x] Add a breadcrumb to navigate into parent directories when inside a directory.
[] Add a check to make sure the destination folder is within /wp-content/uploads.
[] Make sure to use copy and unlink because rename breaks streams.
