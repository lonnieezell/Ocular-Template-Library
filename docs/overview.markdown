# What is Ocular? 

Ocular is a total system for the display your web pages in CodeIgniter, and includes parent/child themes, controller-based layout overrides, organized views, and asset management. It's primary goals are to provide the only templating/theming system you will need, and do so in a flexible, high-performance way.

# Theming Overview

All sites use themes of some sort. For many basic CI apps, there is nothing special to the system. However, many projects will benefit, and often require, the ability to have multiple themes available, whether for mobile development or simply providing options to the end-user.

Ocular strives to separate the different layers of your application as much as possible (structure, content, and skins). 

STRUCTURE is handled by the use of layouts the provide the framework of the pages themselves. 

CONTENT is provided by the applications views, themselves. Typically, these will change very little from one theme to the next, if the HTML and CSS is employed properly.

PRESENTATION is taken care of through the themes, and are especially simple to extend through the use of 'child themes', where a simple re-skinning of an app can be taken care of with nothing more than a single CSS file. 

## How Themes Work

### Layouts

When a page is rendered from within a controller, Ocular attempts to find the layout in several locations, which allows for the parent/child theme relationship.

- If a layout has been passed to the render method, we look for that layout in the active theme's folder.
- Next, Ocular will attempt to find a file matching the controller name within the active theme's folder. 
- It then looks for the default layout (typically 'application') within the active theme's folder.
- If it still hasn't been found, Ocular repeats the three steps within the default theme's folder.

While this can result in a lot of file reads (which are one of slowest actions that you can perform), in Production environments, some behind the scenes sleight-of-hand is done that will reduce the entire search time to one file search. 

### Views 

Views are handled similar to layouts in the search pattern, but a couple of small differences exist: 

- We first check if a view exists within the active theme's folder, under a sub-folder named after the controller, and a view with a name matching the method. This allows themes to override controller and module views, when needed.
- Finally, look for the theme in the typicall location (views/controller/method.php);

With all of the options available, you should be able to provide any modifications of theming that you need. Oh, and themes can be (and should be) stored in a folder seperate from the views. This keeps end-users out of the dangerous areas and doesn't mix things up with un-related content. 