# Generate translations

## Setup

- Install and set up WP-CLI: [Set up WP-CLI for MAMP](https://jessicalyschik.de/wp-cli-fuer-mamp-einrichten/)
- Then follow the description of WordPress to read the translations: [Internationalization | Block Editor Handbook](https://developer.wordpress.org/block-editor/how-to-guides/internationalization/)

## Generate translations

### Create the template file:

Go to the plugin folder and generate the translation template. You probably want to change the uri according to your setup.

```bash
cd /Applications/MAMP/htdocs/wordpress/wp-content/plugins/greyd-plugin
wp i18n make-pot ./ languages/greyd_hub.pot
```

### Create the `.po` file:

Then create an initial .po file using the terminal or PoEdit (important: identifier **de_DE**).

### Use PoEdit to update your translations:
1. Open the `.po` file with PoEdit.
2. Go to **'Translation > Update from POT File...'** and select the generated file.
3. The source text in your file is now updated.
4. Translate all parts of the theme.
5. Save your file and generate an `.mo` file.
6. Now take a look at your WordPress Backend (maybe change your user language).

### Now you need to generate the Javascript Translation Files

```bash
wp i18n make-json languages/greyd_hub-de_DE.po --no-purge
```

## All at once

Or you can create the translations for all greyd themes & plugins at once:

```bash
cd /Applications/MAMP/htdocs/wordpress/wp-content/plugins/greyd-plugin
wp i18n make-pot ./ languages/greyd_hub.pot
cd /Applications/MAMP/htdocs/wordpress/wp-content/plugins/greyd_tp_management
wp i18n make-pot ./ languages/greyd_hub.pot
cd /Applications/MAMP/htdocs/wordpress/wp-content/plugins/greyd_tp_forms
wp i18n make-pot ./ languages/greyd_forms.pot
cd /Applications/MAMP/htdocs/wordpress/wp-content/plugins/greyd_globalcontent
wp i18n make-pot ./ languages/gc.pot
cd /Applications/MAMP/htdocs/wordpress/wp-content/themes/greyd-theme
wp i18n make-pot ./ languages/greyd-theme.pot
```

...and then generate the JSON files after updating the .po & .mo files:

```bash
cd /Applications/MAMP/htdocs/wordpress/wp-content/plugins/greyd-plugin
wp i18n make-json languages/greyd_hub-de_DE.po --no-purge
cd /Applications/MAMP/htdocs/wordpress/wp-content/plugins/greyd_tp_management
wp i18n make-json languages/greyd_hub-de_DE.po --no-purge
cd /Applications/MAMP/htdocs/wordpress/wp-content/plugins/greyd_tp_forms
wp i18n make-json languages/greyd_forms-de_DE.po --no-purge
cd /Applications/MAMP/htdocs/wordpress/wp-content/themes/greyd-theme
wp i18n make-json languages/de_DE.po --no-purge

for file in languages/*.json; do
    filename=$(basename -- "$file" .json)
    if [[ "$filename" != "greyd-theme-"* ]]; then
        if [ -f "languages/greyd-theme-$filename.json" ]; then
            rm "languages/greyd-theme-$filename.json"
        fi
        mv "$file" "languages/greyd-theme-$filename.json"
    fi
done
```