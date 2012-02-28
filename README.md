# TranslationEditorBundle

The TranslationEditorBundle is a Symfony2 bundle that provides web base UI to manage Symfony2 translations.

The following command line tools are provided:

* Import translation files

	./app/console locale:editor:import [--dry-run] [filename]

Import translation files into MongoDB. If no files is specified, the command will search for files in translations directories in src/

* Export to translation files

	./app/console locale:editor:export [--dry-run] [filename]

Export translations to translation files from MongoDB. If no files is specified, the command will search for files in translations directories in src/


## Screenshot

<img src="http://farm8.staticflickr.com/7158/6668570353_1b852e0e7b_b_d.jpg" />

## Installation:

Download or clone the bundle. If you use deps file add it like this:

	[TranslationEditorBundle]
		git=git://github.com/servergrove/TranslationEditorBundle.git
		target=/bundles/ServerGrove/Bundle/TranslationEditorBundle

Then run ./bin/vendors install

Add ServerGrove namespace to app/autoload.php:

	$loader->registerNamespaces(array(
		...
		'ServerGrove' => __DIR__.'/../vendor/bundles',
		...
	));


Enable it in your app/AppKernel.php (we recommend that you do it only for the dev environments)

	public function registerBundles()
	{
		...

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
        	...
            $bundles[] = new ServerGrove\Bundle\TranslationEditorBundle\ServerGroveTranslationEditorBundle();
        }

		...
	}


## Configuration:

We recommend that you only enable this bundle for the development environments, so only add the configuration in config_dev.yml

The collection parameter allows you to define the collection that will contain the translations for the project, so you can have multiple Symfony2 projects in the same mongodb server.

A sample configuration (in your config_dev.yml):

    server_grove_translation_editor:
      storage:
        type: server_grove_translation_editor.storage.orm
        manager: doctrine.orm.entity_manager
      importer:
        type: server_grove_translation_editor.importer.yaml
      exporter:
        type: server_grove_translation_editor.exporter.yaml

Add the routing configuration to app/config/routing_dev.yml

	SGTranslationEditorBundle:
		resource: "@ServerGroveTranslationEditorBundle/Resources/config/routing.yml"
		prefix:   /

## Usage:

1. Import translation files into mongodb

	./app/console locale:editor:import

2. Load editor in browser, edit your translations

	http://your-project.url/translations/editor

3. Export changes to translation files

	./app/console locale:editor:export

## WARNING

**PLEASE** Backup your translation files before using the editor. **Use a source control system like git, even svn is ok**. We are not responsible for lost information.

## TODO

* Support for nested yml format
* Support for xliff files
* Import strings from twig files
* Ability to edit key
* Add Google Translate API interface
* Add Search capabilities

**Pull requests are welcome! We open sourced this bundle hoping people find it useful. Please contribute back any enhancements.**

**Notice:** This bundle has been developed with very short time availability so it does not contain tests, comments, etc, so don't look at it to see how things are done. Instead of complaining of the ugly code, please contribute pull requests with enhancements :)

## More information:

* [ServerGrove Website](http://www.servergrove.com/)
* [ServerGrove Blog](http://blog.servergrove.com/)
* [Follow ServerGrove @ Twitter](http://twitter.com/servergrove)
* [GitHub Downloads](http://github.com/servergrove)