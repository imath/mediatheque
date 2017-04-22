#Todo list

[x] When deleting a User Media Post type, if it's not a folder, the corresponding file should be deleted.
[x] When deleting a folder, make sure filesys is also edited.
[x] The Disk usage should be updated when a file is deleted.
[] Add a shortcode to load the UI anywhere.
[x] Make sure the create_item() method can handle folders.
[] Make sure to switch_to_blog() before saving the User Media metadata.
[x] The way the embed template is filter is not right. I should check the post type first.
[x] Create the 2 User Media Types on activation (File/Folder).
[x] Improve the way Views are added to the screen in admin.js
[x] Add a message about how to configure the private folder with nginx: [@see](http://nicknotfound.com/2009/01/12/iphone-website-with-nginx/)
[x] Use the wp-pointer to guide the user in finishing the plugin's setup (default links vs pretty links, where are the options, where is the main admin)
[x] Make sure an admin can upload a file in another user's folder.
[x] Use a Backbone Model to store query vars (eg: user ID, parent directory ID etc..)
[x] Add a breadcrumb to navigate into parent directories when inside a directory.
[] Add a check to make sure the destination folder is within /wp-content/uploads.
[x] Make sure to use copy and unlink because rename breaks streams.
[x] Fix the problem when coming back to the public view (moving User Media)
[x] When a User Media is moved inside a Directory the Modified Date of the Directory should be updated.
[x] Make sure User Media are listed by modified date DESC.
[] Work on capabilities making sure cap is publish for others when current user id is not author id
[x] Find way to avoid the wpUploader to listen to drag and drop inside the WP Media Editor.
[x] Find a way to deal with Media insterted into content when its parent directory has been changed 'embed?'
[x] Make sure the htaccess file is only added to the "private status" user dir and not in children
[x] Take care of folder display.
[x] Make sure to take care of dead media of moved media
[] Make sure private media cannot be attached but only embed.
[] Make sure avatar UI is only fetching public images
[x] Make sure the media inserted has a line feed before and after.
[] Add a tool to clear vanished media log
[x] Instead of using wp_attachment_is() create a new function to get the type of a user media @see MediaTheque_REST_Controller->prepare_item_for_response().
[x] For the file output in the editor add KB infos.
[] Implement the Download action
[] Add a view to edit title and content of user media
[x] Check the attachment navigation
