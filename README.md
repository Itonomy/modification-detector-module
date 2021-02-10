# Modification Detector for Magento 2 projects

Hey there lonely wanderer. A module for Magento 2 that may spark your interest. And its open-source.
This module will give an overview of all preferences and plugins in your codebase.

And off-course it comes with some nifty commands

## Summary
This module lists all plugin/preferences in your codebase, and adds some options to filter them to your liking.
## Installation

Just the regular stuff

```bash
composer require itonomy/modification-detector
```

##Commands

List all preferences and plugins
```bash
bin/magento dev:modification:list
```

### --type [-t] 
Filter by type: plugin | preference
```bash
bin/magento dev:modification:list -t plugin
```
### --filter [-f] keyword
Filter/Search all plugins containing keyword
```bash
bin/magento dev:modification:list -f MyModule
```
### --no-native
Filter all prefs/plugs by Magento itself
```bash
bin/magento dev:modification:list --no-native
```
### --summary
Gives a count/overview of all plugins/preferences

```bash
bin/magento dev:modification:list --summary
```
### --detect-conflict
- Overview of all classes having multiple plugins/preferences on the same class
- Checks if in a "around" plugins callable is called

```bash
bin/magento dev:modification:list --detect-conflict
```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)