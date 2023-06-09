# bardic-cms
 
Bardic CMS (formerly OppidumCMS) is a mini content management system focused on API functionalities, and can serve as a backend for both website and webapp frontend.

By default, Bardic CMS  can display ANY content publicly in JSON format, but the basic parameters to allow a quick creation of custom content types, with a hierarchy between those types (i.e. pages containing paragraphs etc.) is available in the CMS.

Made by Lucius Barde (www.bardic.space)

## Requirements
- A web server, preferably a Linux - Apache - MariaDB - PHP stack.
- Software packages: **git**, **composer**, ***docker (optional)***.
- Dependencies installed by composer: slim PHP core, Twig templating engine with some extensions, and PHPMailer.
- Dependencies installed in code: Bootstrap, jQuery, Parsedown, and other modules in /external.

## Installation
 1. `git clone 'NEW URL OF BARDIC CMS - TODO'
2. `composer install` in the public folder, to generate the public/vendor folder.
3. If using docker: run `docker-compose up`
4. copy config.sample.php to config.php
5. copy/paste the SQL table structure from config.php to PHPMyAdmin or Adminer (if using Docker it's most likely running on localhost:5001)
6. setup your own configuration in config.php. Change the default admin login and password to your own. If using Docker activate the 3 possible URLs for MySQL.
7. if you need to use the uploading tool available in the admin, create an 'uploads' folder in /public and allow write permissions to your webserver.
8. Done ! You've just set up your Bardic CMS site. The API should return this: `['status'=>'default_home_page','statusText'=>'It works !']`.
9. Optionally activate the frontend and create a home page. C^heck the rendering mode in PageController, and activate the JSON or frontend rendering depending on your needs. There are two places to check: at the bottom of the subpage route, and at the bottom of the homepage route. Then log into the website at /admin URL, and create a new page with the same URL as the $site['params']['homelink'] value in your config file, by default this URL is "home".


## Single-table
- Bardic CMS uses a single table and polymorphic objects (called "blobs") which uses the same fields, be it a page, a paragraph, a media, a user, a custom object, etc. In Bardic CMS the term BLOB stands for Blobby Long Object, in other words, a polymorphic object where any of its types uses the same basic fields ("params" being the array of additional fields).

	- INT(11) id: unique identifier
	- VARCHAR(32) type: any object type (i.e. "page", "paragraph", "image", "littlepony" etc.)
	- VARCHAR(128) url: unique resource locator
	- VARCHAR name: human-readable name for the object
	- TEXT content: directly loaded content
	- INT parent: id of parent object
	- INT status: 1=online, 0=offline, -1=deleted
	- TIMESTAMP edited: date of last modification
	- VARCHAR lang: language identifier of the resource
	- INT translation_of: id of an object which the object is a translation of
	- TEXT params: all the custom parameters
	
## config.php

### array $config
- bool displayErrorDetails: SlimPHP related config.
- bool addContentLengthHeader: SlimPHP related config.
- array db: database info
	- string host: database host (e.g. "localhost")
	- string user: database user (e.g. "root")
	- string pass: database password
	- string dbname: database name
	- string tbl: opcmsdev
- array site: site parameters
	- string name: Default site title (if frontend is active)
	- string content: Default site meta description (if frontend is active)
	- array params:
		- string default_language: the default language code
		- string homelink: the url of the home page (must be in the same language as the default lang.)
		- array languages: contains an array of the active languages ['code':'Language name']
- array user: the default user (formerly the first "user" entry in OpCMS 2)
	- string login: The login or email to access the admin panel or to query objects with status = 0
	- string password: The password to access the admin panel or to query objects with status = 0
- string abspath: the site's URL with http(s)://
- string absdir: the site's root path on the server (e.g. /var/www/opcms)
- string template_dir: relative path to the activated template



## index.php

### MVC (model-view-controller)
Bardic CMS uses a model-view-controller architecture based on Slim PHP's routing system.  The process is:

- The called URL will be executed by the controller which handles its route (i.e. AdminController handles all /admin pages, BlobController handles blob creation/update/etc., PageController handles the routes of the public pages, etc.)
- The controller executes some code, retrieves some parameters, and pass those parameters either to the view renderer, or to the JSON renderer.
- The view renderer passes all the aforementioned parameters either to JSON, or to the root template **standard.html.twig** if the frontend is used, and executes it. The *action* parameter defines which page / partial template will be loaded.

If you create custom models and controllers, please load them at the bottom of index.php.

## Controllers
Bardic CMS is an optimized API motor with layers of frontend and backend views upon it.
Controllers are located in /public/controllers

### AdminController
Renders admin backend pages. All functions require SESSION.

- admin /admin: redirects to userLogin or adminDashboard. 
- adminCreate /admin/create: renders edit from with the action of object creation.
- adminDashboard /admin/dashboard/{page}: renders adminDashboard list with pagination.
- adminDashboardByType /admin/dashboard/type/{type}/{page}: renders adminDashboard list from a single object type with pagination.
- adminEdit /admin/{id}/edit: renders edit form with the action of object edition.
- adminLogin /admin/login: login form and login URL. Use credentials set in $config['user'] in config.php to log in.
- adminLogout /admin/logout: logout URL.
- adminRecycle: /admin/recycle: renders admin recycle bin list.
- adminSitemap: /admin/sitemap: renders admin site map view. Allows an optional GET parameter langFilter to filter by language.
- adminUploads: /admin/uploads/{subdir}: renders the uploads manager.


### BlobController
In former OppidumCMS it handled all /blob routes, but in Bardic CMS they have been replaced by /{type} routes, where {type} is the current blob's type.

- createBlob /{type}/create (POST): Creates a new blob from a formData, and returns JSON status or redirects to a callback URL if provided. Requires:
	- param $callback: the callback URL
	- string $name
	- url $url
	- string $content
	- int $parent
	- int $status (-1,0,1)
	- param $lang
	- int $translation_of
	- json $params
	- SESSION
- deleteBlob /{type}/{id}/delete: Deletes blob {id} if status = -1, or else sets blob status to -1. Returns JSON status. Will fail if the given {type} doesn't match with {id}'s type. Require SESSION.
- getBlob /{type}/{id}: Displays blob {id} in JSON format, if its {type} allows public display and if its status is 1 (Requires SESSION in order to display a blob with status = 0)
- updateBlob /{type}/{id}/update (POST): Updates an existing blob from a formData, and returns JSON status or redirects to a callback URL if provided. Will fail if the given {type} doesn't match with {id}'s type. Requires:
	- int $id
	- param $callback: the callback URL
	- string $name
	- url $url
	- string $content
	- int $parent
	- int $status (-1,0,1)
	- param $lang
	- int $translation_of
	- json $params
	- SESSION
- apiGetBlob /{type}/{id} : Dispays blob {id} as JSON. Please note that by default all content is accessible publicly through this function. If your API contains non-public content, you might want to disable it.


### MediaController
- mediaFileUpload /media/fileUpload (POST): Retrieves form data with uploaded files, moves them in the /uploads structure, and returns a JSON status. Requires SESSION.
- mediaDelete /media/delete/{src}: executes deleteMedia() to delete a file. Returns JSON status. Requires SESSION. 
- mkdirMedia /media/mkdir (POST): creates a folder. Returns redirection to callback. Requires:
    - SESSION
    - mkdir-name: the folder name
    - mkdir-parent: the callback folder in /uploads
- rmdirMedia /media/rmdir/{src}: removes an empty folder. Returns redirection to callback or JSON 500 error. Requires:
    - SESSION
    - src (via url): the folder name
    - callback (via GET): the callback folder in /uploads
- mediaMoveTo /media/moveTo (POST): Moves a media to a sub directory. Requires:
	- SESSION
	- from: The source file
	- to: The destination
	- subdir: The subdirectory
- mediaRefreshFolderView: /media/refreshFolderView (POST): Refreshes the current folder view, after an asynchronous operation, and generate thumbnails for new images. Requires:
	- dir: The folder
	- (Requirement of SESSION is not active for an unknown reason, maybe because we want to be able to access a folder content publicly at some point)

### PageController
Contains the routes for the website's public pages. You can configure on PageController's two routes whether you want a frontend or JSON rendering for pages. (Use JSON if you strictly want to access your pages through an external app, for example a Vue.js app)

- homePage / : Renders website's public home page.
- page-{id} /{url} : PageController contains the url rewriting system that generate a route for each public page.




## Classes (Models)
Classes contain methods called inside controllers. They are located in /public/classes.
In order to use a class Xyz, it must be constructed as:
$xyzModel = new Xyz($container->db); with the optional database parameter.

### Admin model
Operations for admin pages.

- canEdit: check if SESSION user is allowed to edit the blob $id. Requires:
	1. int $id: id of the blob

- canDelete: check if SESSION user is allowed to delete the blob $id. Requires:
	1. int $id: id of the blob
	
- getPageContent (PRIVATE): selects all elements, except pages, whose parent are given id. Used in sitemap generation. Requires:
	1. int $parentID: id of the page (or parent blob)
	2. array $args:
		 - bool $admin: add to fetched data an "admin" fields containing the "canEdit" and "canDelete" statuses for elements.
		 
- getPageUrl: returns the complete URL for page $id, including parents if $id is a subpage. It can also return the URIs for non-page elements.
		 
- getSitemap: generates the root of the sitemap and begins the getSitemapTree loop. Requires:
	1. array $args:
		- bool $admin: add to fetched data an "admin" fields containing the "canEdit" and "canDelete" statuses for elements.
		
- getSitemapTree (PRIVATE) (Tree): executes one loop of the tree generation of the sitemap.  Returns a fetched array with "elements" (getPageContent) and children (getSitemapTree's next loop). Requires:
	1. array $siteRoot: the SITE's blob generated by Blob->getSiteProperties()
	2. array $args:
		- bool $admin: add to fetched data an "admin" fields containing the "canEdit" and "canDelete" statuses for elements.
		- param $langFilter: allows a WHERE clause to filter by lang.
		
- getTranslations: get the complete list of translated sentences for a specific language $lang. The function returns an array containing the default Bardic CMS sentences and the sentences for each custom module.

- login: Logs into the website. Uses the $config['user'] login data from config.php

- getAllRoutes: Gets a complete list of existing SlimPHP routes of the app. Should be used for debugging purposes in case of fatal error (like duplicate route).


### Blob Model
Operations related to any object, used mostly by the admin CRUD editor.

- addBlob: adds a new blob of any type. Requires:
	1. array $post: data corresponding to database fields.

- cleanOrderValues: useful legacy function which resets all order values to even numbers. The old OppidumCMS used a system to manage the order of elements in pages, where every element has a $blob['params']['order'] which is an even number, and any new element that is added before or after an element with order "x" should get the odd number "x - 1" or "x + 1", and then everything is re-numerated to even numbers.

- deleteBlob: deletes a blob. Requires:
	1. int $id: the ID of the blob

- getAllBlobs: get a filtered list of blobs, by type, by status, with or without pagination, etc. Requires:
    1. array $args:
    - mixed $type: get blobs from this specific type, or from this specific array of types.
	- int $paged: number of elements by page
	- int $page: get the data of a specific page
	- array of int $status: filter by blobs having the given statuses
	- param $orderby: order by specific field
	- param $order: ASC or DESC
	- int $parent: get blobs having this specific parent
	- param $searchTerm:^ get blobs having this term in ID or name or content or params. Used in admin dashboard.
	- bool $onlyCount: only get the amount of blobs selected, not the content. Used for pagination, for example.
	- int $limit: limit the result to X blobs.
	

- getBlob: get a specific blob by ID. Requires:
    1. int $id: the blob's ID.
    2. array $args:
	- bool $rawParams: if false (by default): decode JSON params as an array. If true, get the raw JSON data instead.

- getBlobParent: returns the parent of blob as an object. Requires:
     1. int $id: the blob's ID.
	
- getBlobStatus: get a blob's status. Requires:
     1. int $id: the blob's ID.

- getBlobType: get a blob's type. Requires:
     1. int $id: the blob's ID.

- getDefaultParams: get the list of default params for a specific blob's type. This data is stored in /modules/{{type}}.json. Requires:
     1. param $type: the requested blob type.

- getSiteProperties: gets $config['site'] from config.php.
    
- setBlobStatus: changes the status of a blob. Requires:
    1. int $id: the blob's ID.
    2. int $status. the blob's new status. By default Bardic CMS uses status 1 = online, 0 = offline (draft), -1 = deleted.

- updateBlob: updates a blob's entry. All fields are rewritten, so it requires everything. Params must be given as an array, and are encoded as JSON in this function. Requires:
    1. int $id: the blob's ID.
    2. array $post: data corresponding to database fields.


	
### Media model
Media operations.

- generateThumbnail: creates, from a given absdir of image, a thumbnail image with uploaded image's name plus "-WxH". Returns the thumbnail's image. No error handling, assuming that the uploads dir is writeable. Requires:
	1. string $img: image path in the absdir (absolute directory) format.
	2. int $twidth: thumbnail width in pixels, default 300
	3. int $theight: thumbnail height in pixels, default 300
	4. int $quality: for jpeg images, thumbnail quality (0-100), default 90

- getMedia: get the media list for a directory inside /uploads. Can be used synchronously or in AJAX. Requires:
- 1. string $subdir: the requested directory relative to the /uploads folder, should start with '/')

- moveMedia: moves a media to a subfolder or to the parent folder. Requires:
	1. current path of the file relative to /uploads $from
	2. destination path of the file relative to /uploads $to

- moveUploadedFile: moves a file, which was just received through a POSTed form, into the uploads structure. Requires:
	1. string $directory: directory where the file must be put in, in the absdir format. 
	2. object $uploadedFile: a SlimPHP object containing a single file from the file list generated by Request.getUploadedFiles() in the controller.

- deleteMedia: deletes file $src with unlink(). Requires:
	1. string $src: relative path of the file to delete, which comes after ABSDIR/uploads/{...}
	
### Page model
Page operations

- isHome: check if page $id is homepage, returns true or false. Requires:
	1. int $id: a page's id.
	
- getHomePage: get the homepage ID, with or without its content. Requires:
	1. bool $withContent: returns fetched row if true, or only homepageID if false.
	
- getPageElements: get page elements for sitemap or frontend display. Returns a fetched array with added "listing" array if the blob is a reference list, and sorted by the blob.params.order parameter which defines the order of elements on the page. Requires:
	1. int $id: a page's id.
	2. string $limit: the X,Y part of the LIMIT sql query (i.e. "0,10")
	3. string $type: only fetches page elements of this type (i.e. "paragraph"). Default Null. If Null, fetches anything except sub"page"s. 
	
- getTranslations: returns the blobs which have a translation_of field equal to the given blob id. Used in language menus. Requires:
	1. int $tr: the page's id equal to the translation group id.

### Validator model
Input validation operations

- asDateTime: validates string as date/time with SQL format.  Requires:
	1. string $s: a date or timestamp
	
- showDate, showDateTime, showHumanDateTime: outputs a date. This will probably be removed and a twig extension be used instead. Require:
	1. string $d: a date
	2. string $lang: a language for the format (only 'fr' is implemented yet).


- showResponsiveImage: triggers generateThumbnail() and displays an image from an image source relative to /uploads. Can be further developed into a srcset responsive system. Requires:
	1. blob $blob: an image blob having the existing params:
		- string $blob.params.src: source to the actual file
		- string $blob.params.link: the <a> link that will wrap the <img />
		- string $blob.params.link_target: the link target (i.e. _blank)
		- bool $blob.params.link_self: if true, the <a> link will open the image itself in browser.
		- string $class: the class parameter of the <img />
	2. array $args:
		- int $width: thumbnail width, default 150
		- int $height: thumbnail height, default 150,
		- int $quality: if jpeg, thumbnail quality (0-100), default 80
		

- asEmail: validates $s as email, using a regexp. Requires:
	1. string $s: a string that should be a valid email.
	
- asLink: validates $s as a hypertext link, using a regexp. Requires:
	1. string $s: a string that should be a valid link.
	
- asJson: returns json_decode if $s is valid JSON, false if invalid. Avoid saving invalid params in database. Requires:
	1. string $s: a string that should be valid JSON.

- asParam: applies trim, strip_tags, htmlspecialchars, htmlentities, and tricks on quotes, on string $s. Requires:
	1. string $s: a string that should be used as a site parameter.

- asString: applies trim and htmlentities on string $s. Requires:
	1. string $s: any text.
	
- asStatus: check if number $n is either -1, 0, or 1. Returns $n if valid, false if not. Requires:
	1. int $n: a number that should be either -1, 0, or 1.

- asURL: check if string $s is 1) not empty, 2) not existent in the getAllRoutes() list, 3) doesn't contain tags, specialchars, and quotes, and 4) equal to itself filtered with rawurlencode(). Returns false if any of these tests fail, returns $s if all succeed. Requires:
	1. string $s: a string that should be used as a blob url.
	
- toFileName: for basic file handling with PHP: replaces all spaces with underscores and removes all accents.
	1. string $string: any text made of latin characters.
	
- replaceAccents (PRIVATE): replaces accented latin characters with non-accented counterparts. Used by Validator::toFileName.
	1. string $s: any text made of latin characters.

- parseForm: useful legacy function for custom forms: replaces (with a regex) the following tags to their HTML counterparts. For example in your form twig template, you can show the fields with {{ validate.parseForm(element.content) }} if element.content is not empty, and display default fields otherwise. This function doesn't render the submit button, default hidden fields, nor any other form tags, those should still be added manually.
{input name "Label"} => input type text
{email name "Label"} => input type email
{text(area) name "Label"} => textarea
1. string $html = the pseudo code

- parseMd: uses Parsedown and a few custom methods to transform Markdown text into html. Requires:
	1. string $html: Markdown text that will be parsed to HTML on-the-fly.
	2. bool $paragraphs: Parsedown generates paragraphs if true, lines if false. Default true.

- removeMd: uses Parsedown to convert to tags and then strip tags, thus removing the markdown and converting to plain text. Useful, for example, if you want to show blog excerpts without the markdown contained in the blog articles.	

- asInt: validate as an integer number. Executes (int)$s on $s, returns the value if succeeded, false if failed. Requires:
	1. string $s: user-input text that should be an integer.

- asUnsignedInt: validate as a non-negative integer number. executes (int)$s and abs(). Returns the value if succeeded, false if failed. Requires:
	1. string $s: user-input text that should be a non-negative integer.
	
- asPriceFormat: formats a number with 2 decimals with number_format() and returns it as a string. Requires:
	1. int $price: any number.
	2. string $lang: not used, but may be used for different language formatting.
This might be removed and replaced with a Twig extension.

- validateArray (Tree): executes a filter on all elements of an array. Requires:
	1. array $array: any array of data of any depth.
	2. functionName $asTextType: an existing function of Validator to be executed on all elements (e.g. "asString", "asParam").
	
- showBlobLink: generates a link to an internal element. Is used in the custom markdown for paragraphs, allowing to generate links to internal pages based on their ID.
	1.  int $id : the element's ID.
	2.  string $linkText: the text of the link
	3.  param $params.target: optional target parameter i.e. "_blank"

- showExcerpt: show the first X characters of a text, with an ellipsis (...)
	1. string $content: the text
	2. int $limit: the number of characters. By default: 50

## Modules

Modules have default params which are used in create and edit forms, they are indicated in /modules/{module].json.

The list of parameters is documented in each one of the  /modules/{module].json files.

Here are of the default Bardic CMS modules:

- **page:** Page blobs are used for displaying pages, and their URL field is automatically used for URL rewriting. Bardic CMS can handle a maximum sub-page depth of 3 levels.
	- unsigned_int order: the numeric order in which pages come in the sitemap.
	- string metaDescription: the <meta name="description" /> tag for search engines.
	- the parent field should be the site blob's ID if a 1st level page, or another page's ID if a subpage.
	- the content field of page blobs should remain empty.

- **paragraph:** Paragraph blobs are simple blocks of text, they are generally children of page blobs.
	- unsigned_int order: the numeric order in which the page content is shown.
	- bool hideTitle: if "0" or false, the paragraph's title won't be displayed.
	
In former OppidumCMS there used to be many more content modules like contact form, image, gallery, etc., but since Bardic CMS is more turned towards API and custom content, I keep the default modules to the bare minimum. Please create your own modules.

	
## Base template "pico"
Bardic CMS's base template is a minimalist display of all the parameters, and is mostly suitable for development or debugging purposes. You should consider creating your own template, or use a javascript framework to display data through API requests. Pico template uses Pico.css.

Former OppidumCMS themes used similar templates as what is described here.

### Base view: standard.html.twig ###
This file is the base template, from <html> to </html>, called on every page. The dynamic content is provided by the various Controllers who give these parameters to the Twig template engine. The main parameters are:
- action: a string that requires which partial templates will be loaded. It is used for admin and user login pages. If no action is provided, then the basic frontend template will likely be loaded.
- object session: contains the $_SESSION data.
- object site: contains the site blob's data.
- object blob: if a single element is shown (i.e. a page, an article, etc.) then the 
- object elements: if several elements are shown
- string status: if a status message is shown, contains the status class (i.e. "success", "warning", "error", etc.)
- string statusText: if a status message is shown, contains the details.
- object sitemap: contains the whole site hierarchy, can be used to show the main navigation menu.
- bool isHome: true if is home page

### Admin views  ###
All the admin-{...}.html.twig, and signin-form.html.twig, are called by standard.html.twig instead of the frontend template, if the requested action is an admin one. 

### Module views ###
Bardic CMS uses templates named after the modules that are "pageElement"s. Currently the only core module that is a page element is paragraph.html.twig.

The standard.html.twig calls the different module templates when necessary, but if you create new modules you must add manually your new blob type in the page content's included list, otherwise the default template plainly displays the blob.name and the blob.content for unknown blob types.

## Separate admin template (not by default)
Bardic CMS can use a separate admin theme, or the same theme as frontend. By default no admin theme is provided and admin pages are on the same theme as the frontend, but activating $container->viewAdmin instead of $container->view can activate the admin theme renderer.

## Others
### /public/external and /public/css folders:
Contains external libraries used globally, independently from the theme. This should be cleaned and put inside the theme folder in the future.